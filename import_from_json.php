<?php
/* -----------------------------------------------------------
   event-detail/import_from_json.php
   -----------------------------------------------------------
   1) 동일 폴더의 event.json 파일을 그대로 읽는다
   2) birthday_events 테이블에 INSERT (tweet_id 중복은 자동 무시)
   3) 끝나면 JSON 형태로 처리 결과 출력
----------------------------------------------------------- */

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';
$conn->set_charset('utf8mb4');

/* ① JSON 파일 경로
   - 기본: events.json
   - GitHub 공개 시에는 events.sample.json 또는 로컬 경로를 .env로 지정 가능
     예) IMPORT_JSON_PATH=events.sample.json
*/
$file = env('IMPORT_JSON_PATH', __DIR__ . '/events.json');
if (!is_file($file)) {
    exit(json_encode(['success' => false, 'message' => 'event.json 파일을 찾을 수 없습니다']));
}
$json = file_get_contents($file);
$data = json_decode($json, true);
if (json_last_error() || !is_array($data)) {
    exit(json_encode(['success' => false, 'message' => 'event.json 형식 오류']));
}

// 데이터 포맷 호환: {"documents": [...]} 또는 [...] 둘 다 지원
if (isset($data['documents']) && is_array($data['documents'])) {
    $data = $data['documents'];
}

/* ② INSERT IGNORE = tweet_id(UNIQUE) 중복이면 건너뜀 */
$sql = "INSERT IGNORE INTO birthday_events
        (tweet_id, tweet_url, name, event_date, description, poster_path)
        VALUES (?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit(json_encode(['success' => false, 'message' => '쿼리 준비 실패: '.$conn->error]));
}

$imported = 0;
foreach ($data as $twt) {
    // tweet_id 우선, 없으면 id 사용
    $tweetId  = $twt['tweet_id']  ?? ($twt['id'] ?? null);
    if (!$tweetId) continue;                 // id 없으면 스킵

    // tweet_url이 없으면 tweet_id로 구성 (X/Twitter)
    $tweetUrl = $twt['tweet_url'] ?? ($twt['url'] ?? ("https://x.com/i/web/status/" . $tweetId));

    // 표시 이름: cafe_name이 있으면 우선, 없으면 tweet_text/content의 첫 줄
    $rawText  = $twt['tweet_text'] ?? ($twt['content'] ?? '');
    $name     = trim($twt['cafe_name'] ?? trim(strtok($rawText, "\n\r")));

    // 날짜: start_date 우선, 없으면 tweet_time/timestamp 기반
    $dateSrc  = $twt['start_date'] ?? ($twt['tweet_time'] ?? ($twt['timestamp'] ?? 'now'));
    $date     = date('Y-m-d', strtotime($dateSrc));

    $desc     = $rawText;
    $poster   = $twt['images'][0] ?? '';

    $stmt->bind_param("ssssss", $tweetId, $tweetUrl, $name, $date, $desc, $poster);
    if ($stmt->execute()) $imported++;
}

echo json_encode(['success' => true, 'imported' => $imported]);
