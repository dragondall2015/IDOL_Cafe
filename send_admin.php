<?php
/**
 * send_admin.php
 * 관리자 계정 1회 생성 스크립트
 *
 * ✅ 기본 비밀번호 하드코딩을 제거했습니다.
 * .env 또는 환경변수로 ADMIN_USERNAME/ADMIN_PASSWORD를 설정하세요.
 *
 * 권장 실행(CLI):
 *   php send_admin.php
 */

// 웹에서 실행 방지 (보안)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI에서만 실행 가능합니다.');
}

require 'db.php'; // 같은 폴더의 db.php (config.php/.env 로드 포함)

$u = env('ADMIN_USERNAME', 'admin');
$plain = env('ADMIN_PASSWORD', '');
if ($plain === '') {
    exit("ADMIN_PASSWORD가 설정되지 않았습니다. .env에 ADMIN_PASSWORD를 설정하세요.\n");
}

$p = password_hash($plain, PASSWORD_BCRYPT);

// 이미 존재하면 중복 생성 방지
$stmt = $conn->prepare("SELECT 1 FROM users WHERE username=?");
$stmt->bind_param('s', $u);
$stmt->execute();
if ($stmt->get_result()->num_rows) {
    exit("이미 존재하는 관리자 계정입니다: {$u}\n");
}

$ins = $conn->prepare("INSERT INTO users(username,pw_hash,role) VALUES(?, ?, 'admin')");
$ins->bind_param('ss', $u, $p);

if ($ins->execute()) {
    echo "✅ admin 계정 생성 완료: {$u}\n";
} else {
    echo "❌ 오류: " . $ins->error . "\n";
}
