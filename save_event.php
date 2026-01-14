<?php
/**
 * save_event.php  (AUTO-MIGRATION 버전)
 * 1) birthday_events 테이블에 tweet_url 컬럼이 없으면 즉시 생성
 * 2) 업로드된 파일을 /uploads/ 에 저장
 * 3) name·location·…·poster_path·tweet_url 까지 INSERT
 *    ⇒ 성공 시 { success:true } JSON 반환
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';           // ✅ DB 연결용
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(['success'=>false,'message'=>'DB 연결 실패: '.$conn->connect_error]);
    exit;
}

/* -----------------------------------------------------------
   1. tweet_url 컬럼 존재 확인 → 없으면 즉시 추가 (한 번만 실행)
----------------------------------------------------------- */
$chkSql = "
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'birthday_events'
      AND COLUMN_NAME  = 'tweet_url'
    LIMIT 1";
$chkRes = $conn->query($chkSql);

if ($chkRes && $chkRes->num_rows === 0) {
    $alter = "ALTER TABLE birthday_events ADD COLUMN tweet_url VARCHAR(512) NULL AFTER poster_path";
    if (!$conn->query($alter)) {
        echo json_encode(['success'=>false,'message'=>'tweet_url 컬럼 자동 생성 실패: '.$conn->error]);
        exit;
    }
}

/* -----------------------------------------------------------
   2. 입력값 수집
----------------------------------------------------------- */
$name        = $_POST['name']        ?? '';
$location    = $_POST['location']    ?? '';
$eventDate   = $_POST['eventDate']   ?? '';
$startTime   = $_POST['start_time']  ?? '';
$endTime     = $_POST['end_time']    ?? '';
$address     = $_POST['address']     ?? '';
$description = $_POST['description'] ?? '';
$tweetUrl    = $_POST['tweet_url']   ?? '';
$posterPath  = '';                               // 기본값(없음)

/* -----------------------------------------------------------
   3. 포스터 파일 업로드 (선택)
----------------------------------------------------------- */
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename   = time() . '_' . basename($_FILES['poster']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
        $posterPath = $targetPath;
    }
}

/* -----------------------------------------------------------
   4. DB INSERT
----------------------------------------------------------- */
$sql = "INSERT INTO birthday_events
        (name, location, event_date, start_time, end_time, address, description, poster_path, tweet_url)
        VALUES (?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'쿼리 준비 실패: '.$conn->error]);
    exit;
}

$stmt->bind_param(
    "sssssssss",
    $name, $location, $eventDate, $startTime, $endTime,
    $address, $description, $posterPath, $tweetUrl
);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB 저장 실패: '.$stmt->error]);
}

$stmt->close();
$conn->close();
?>
