# /volume2/web/choeaecafe/app_copy.py
from flask import Flask, request, Response, jsonify
import json
import os
from pathlib import Path
from sentence_transformers import SentenceTransformer
import numpy as np
import faiss
from openai import OpenAI


def _load_env_file(path: Path) -> None:
    """간단한 .env 로더 (python-dotenv 없이 동작)
    - KEY=VALUE
    - # 주석/빈 줄 무시
    - 따옴표(" ")/' ' 지원
    """
    if not path.exists():
        return
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        k = k.strip()
        v = v.strip().strip('"').strip("'")
        # 이미 설정된 환경변수는 덮어쓰지 않음
        os.environ.setdefault(k, v)

# -----------------------------
# 설정
# -----------------------------
BASE_DIR = Path(__file__).resolve().parent

# 프로젝트 루트의 .env 로드 (있을 경우)
_load_env_file(BASE_DIR / ".env")

# 데이터 경로 (기본: events.json)
JSON_PATH = Path(os.getenv("RAG_EVENTS_JSON_PATH", str(BASE_DIR / "events.json")))

# ✅ GitHub 공개를 위해 API Key/엔드포인트를 코드에서 제거했습니다.
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
OPENAI_BASE_URL = os.getenv("OPENAI_BASE_URL", "https://api.deepseek.com")
OPENAI_MODEL = os.getenv("OPENAI_MODEL", "deepseek-chat")

# CORS 허용 오리진 (쉼표로 구분)
default_origins = {
    "https://test.choeaecafe.com",
    "https://ai.choeaecafe.com",
}
env_origins = {o.strip() for o in os.getenv("ALLOWED_ORIGINS", "").split(",") if o.strip()}
ALLOWED_ORIGINS = env_origins or default_origins

# -----------------------------
# Flask 앱
# -----------------------------
app = Flask(__name__)

def _origin_is_allowed(origin: str) -> bool:
    return origin in ALLOWED_ORIGINS

@app.after_request
def add_cors_headers(resp):
    """모든 응답에 CORS 헤더 자동 추가"""
    origin = request.headers.get("Origin")
    if origin and _origin_is_allowed(origin):
        resp.headers["Access-Control-Allow-Origin"] = origin
        resp.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"
        resp.headers["Access-Control-Allow-Headers"] = "Content-Type, Authorization"
        resp.headers["Access-Control-Max-Age"] = "86400"
        resp.headers["Vary"] = "Origin"
    return resp

# -----------------------------
# 데이터 로드 & 임베딩 인덱스
# -----------------------------
def load_text_chunks(json_path: Path, chunk_size_words: int = 300):
    if not json_path.exists():
        print(f"❌ JSON 파일을 찾을 수 없습니다: {json_path}")
        return []
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    texts = [item.get("tweet_text", "") for item in data.get("documents", []) if "tweet_text" in item]
    if not texts:
        print("❌ 문서에 tweet_text가 없습니다.")
        return []

    text = " ".join(texts)
    words = text.split()
    return [' '.join(words[i:i + chunk_size_words]) for i in range(0, len(words), chunk_size_words)]

model = SentenceTransformer("all-MiniLM-L6-v2")
chunks = load_text_chunks(JSON_PATH)
if chunks:
    embeddings = model.encode(chunks, convert_to_numpy=True).astype("float32")
    dim = embeddings.shape[1]
    index = faiss.IndexFlatL2(dim)
    index.add(embeddings)
else:
    embeddings = None
    index = None

# OpenAI 클라이언트는 API Key가 있을 때만 생성 (지연 초기화)
_client = None

# -----------------------------
# API 엔드포인트
# -----------------------------
@app.route("/ask", methods=["POST", "OPTIONS"])
def ask():
    # 프리플라이트 요청
    if request.method == "OPTIONS":
        origin = request.headers.get("Origin", "")
        if not _origin_is_allowed(origin):
            return Response("Origin not allowed", status=403)
        return Response(status=204)  # after_request가 CORS 헤더 붙임

    # 실제 요청
    data = request.json or {}
    user_query = data.get("query", "")
    messages = data.get("messages", [])

    if not user_query:
        return jsonify({"error": "질문이 비어 있어요…"}), 400
    if index is None:
        return jsonify({"error": "인덱스가 준비되지 않았습니다. events.json 확인 필요."}), 500
    if not OPENAI_API_KEY:
        return jsonify({
            "error": "OPENAI_API_KEY가 설정되지 않았습니다. .env 또는 환경변수로 OPENAI_API_KEY를 설정하세요."
        }), 500

    global _client
    if _client is None:
        _client = OpenAI(api_key=OPENAI_API_KEY, base_url=OPENAI_BASE_URL)

    # 유사 텍스트 검색
    query_emb = model.encode([user_query], convert_to_numpy=True).astype("float32")
    _, idx = index.search(query_emb, 5)
    retrieved_chunks = [chunks[i] for i in idx[0]]

    # 프롬프트 생성
    context = "\n\n".join(retrieved_chunks)
    prompt = f"""
당신은 아이돌 팬들을 위해 생일 카페 정보를 친절하고 정성스럽게 알려주는 AI입니다.
아래는 문서에서 추출한 생일 카페 관련 정보들이에요:

{context}

사용자의 질문은 다음과 같아요:
"{user_query}"

위 정보를 참고하여, 사용자에게 도움이 되는 생일 카페 정보나 추천을 따뜻한 말투로 안내해주세요.
가능하면 구체적인 날짜, 장소, 이벤트 특징도 알려주세요.
문서에 없는 내용은 지어내지 않고, 솔직하게 없다고 말하세요.
문서 내용 중 웹 링크 형식의 내용은 사용자가 직접 요구할 때만 말하세요.
답변:
"""

    # LLM 호출
    messages.append({"role": "user", "content": prompt})
    try:
        resp = _client.chat.completions.create(
            model=OPENAI_MODEL,
            messages=messages,
            stream=False
        )
        return jsonify({"answer": resp.choices[0].message.content})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

# -----------------------------
# 메인 실행
# -----------------------------
if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port)
