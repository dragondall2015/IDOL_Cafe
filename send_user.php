<?php
/**
 * send_user.php
 * 일반 사용자 계정 1회 생성 스크립트(테스트용)
 *
 * ✅ 기본 아이디/비밀번호 하드코딩을 제거했습니다.
 * .env 또는 환경변수로 SEED_USER_USERNAME/SEED_USER_PASSWORD를 설정하세요.
 *
 * 권장 실행(CLI):
 *   php send_user.php
 */

// 웹에서 실행 방지 (보안)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI에서만 실행 가능합니다.');
}

require 'db.php';

$u  = env('SEED_USER_USERNAME', 'testuser');
$pw = env('SEED_USER_PASSWORD', '');
if ($pw === '') {
    exit("SEED_USER_PASSWORD가 설정되지 않았습니다. .env에 SEED_USER_PASSWORD를 설정하세요.\n");
}

// 이미 존재하면 건너뛰도록 체크
$stmt = $conn->prepare("SELECT 1 FROM users WHERE username=?");
$stmt->bind_param('s', $u);
$stmt->execute();
if ($stmt->get_result()->num_rows) {
    exit("이미 존재하는 아이디입니다.\n");
}

$hash = password_hash($pw, PASSWORD_BCRYPT);
$sql  = "INSERT INTO users(username,pw_hash,role) VALUES(?,?, 'user')";
$ins  = $conn->prepare($sql);
$ins->bind_param('ss', $u, $hash);

if ($ins->execute()) {
    echo "✅ 일반 계정 '{$u}' 생성 완료\n";
} else {
    echo "❌ 오류: ".$ins->error."\n";
}
