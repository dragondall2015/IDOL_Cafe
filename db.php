<?php
/**
 * db.php
 * 모든 PHP 파일에서 공통으로 불러오는 MySQL 연결 스크립트
 *
 * ✅ GitHub 공개를 위해 DB 계정/비밀번호를 코드에서 제거했습니다.
 * 프로젝트 루트의 .env 파일 또는 환경변수에서 값을 읽습니다.
 */

require_once __DIR__ . '/config.php';

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$db   = env('DB_NAME', 'birthday_db');
$port = (int) (env('DB_PORT', '3306'));

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('DB 연결 실패: ' . $conn->connect_error);
}

// 한글/이모지 저장을 위해 utf8mb4 권장
$conn->set_charset('utf8mb4');
?>
