<?php
// qa_page.php
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>AI 채팅</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { margin: 0; padding: 0; background: #f7f7f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    .container { max-width: 800px; margin: 0 auto; padding: 20px; }
    .header h1 { text-align: center; margin-bottom: 16px; }
    .chat-box { height: 60vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; padding: 10px; background: #fff; margin-bottom: 10px; display:flex; flex-direction:column; }
    .message { padding: 8px 12px; margin: 4px 0; border-radius: 8px; max-width: 75%; white-space: pre-wrap; word-break: break-word; }
    .message.user { background: #DCF8C6; align-self: flex-end; margin-left: auto; }
    .message.assistant { background: #F1F0F0; align-self: flex-start; margin-right: auto; }
    .input-area { display: flex; gap: 8px; }
    .textarea-wrapper { flex: 1; }
    textarea { width: 100%; padding: 10px; resize: none; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; }
    button { padding: 10px 16px; border: none; background: #111; color: #fff; border-radius: 8px; cursor: pointer; }
    button:disabled { opacity: .6; cursor: not-allowed; }
    button:hover { background: #333; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header"><h1>AI 채팅</h1></div>

    <div class="chat-box" id="chatArea"></div>

    <form id="chatForm" class="input-area">
      <div class="textarea-wrapper">
        <textarea id="question" rows="3" placeholder="질문을 입력하세요"></textarea>
      </div>
      <button id="sendBtn" type="submit">보내기</button>
    </form>
  </div>

  <script>
    // === 설정 한 줄 ===
    const API_URL = "https://ai.choeaecafe.com/ask"; // 필요 시 "/ai/ask"로 바꿔 동일 도메인 경로 프록시도 사용 가능

    const form = document.getElementById("chatForm");
    const chatArea = document.getElementById("chatArea");
    const questionInput = document.getElementById("question");
    const sendBtn = document.getElementById("sendBtn");

    let messages = [
      { role: "system", content: "아이돌 생일카페 정보를 모아 친절하게 답변해드립니다." }
    ];

    // Enter로 전송(Shift+Enter는 줄바꿈)
    questionInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        form.requestSubmit();
      }
    });

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const question = questionInput.value.trim();
      if (!question || sendBtn.disabled) return;

      // 사용자 메시지 출력
      appendBubble("user", question);
      questionInput.value = "";

      // AI 응답 자리
      const aiBubble = appendBubble("assistant", "생각 중...");

      // 중복 제출 방지
      sendBtn.disabled = true;

      try {
        // 타임아웃(예: 30초)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        const res = await fetch(API_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ query: question, messages }),
          signal: controller.signal,
          // credentials: "include", // 쿠키 필요 시 주석 해제하고 서버 CORS에 Allow-Credentials 설정 필요
        });

        clearTimeout(timeoutId);

        // HTTP 에러 처리
        if (!res.ok) {
          const text = await res.text().catch(() => "");
          aiBubble.textContent = `오류: HTTP ${res.status}${text ? " - " + text : ""}`;
          sendBtn.disabled = false;
          return;
        }

        // JSON 응답 처리
        const data = await res.json();
        if (data.answer) {
          aiBubble.textContent = data.answer;
        } else if (data.error) {
          aiBubble.textContent = "오류: " + data.error;
        } else {
          aiBubble.textContent = "응답을 불러오지 못했습니다.";
        }

        // 대화 히스토리 업데이트
        messages.push({ role: "user", content: question });
        messages.push({ role: "assistant", content: aiBubble.textContent });
      } catch (err) {
        aiBubble.textContent = "서버 호출 중 오류: " + (err.name === "AbortError" ? "요청 시간 초과" : err.message);
      } finally {
        sendBtn.disabled = false;
        chatArea.scrollTop = chatArea.scrollHeight;
      }
    });

    function appendBubble(role, text) {
      const bubble = document.createElement("div");
      bubble.className = `message ${role === "user" ? "user" : "assistant"}`;
      bubble.textContent = text;
      chatArea.appendChild(bubble);
      chatArea.scrollTop = chatArea.scrollHeight;
      return bubble;
    }
  </script>
</body>
</html>
