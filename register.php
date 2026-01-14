<?php
session_start();
require 'db.php';

/* ---------- 1) POST 처리 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 입력값 */
    $u  = trim($_POST['username'] ?? '');
    $pw = $_POST['password']      ?? '';
    $pw2= $_POST['password2']     ?? '';

    /* 기본 검증 */
    if ($u === '' || $pw === '') {
        $msg = '아이디와 비밀번호를 모두 입력하세요.';
    } elseif ($pw !== $pw2) {
        $msg = '비밀번호가 일치하지 않습니다.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $u)) {
        $msg = '아이디는 영문/숫자/밑줄 4~20자이어야 합니다.';
    } else {
        /* 중복 확인 */
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE username=?");
        $stmt->bind_param('s', $u);
        $stmt->execute();
        if ($stmt->get_result()->num_rows) {
            $msg = '이미 존재하는 아이디입니다.';
        } else {
            /* INSERT */
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $ins  = $conn->prepare("
                INSERT INTO users(username, pw_hash, role)
                VALUES (?, ?, 'user')
            ");
            $ins->bind_param('ss', $u, $hash);

            if ($ins->execute()) {
                /* 자동 로그인 후 메인 페이지로 리다이렉트 */
                $_SESSION['uid']   = $ins->insert_id;
                $_SESSION['uname'] = $u;
                $_SESSION['role']  = 'user';
                header("Location: index.php");
                exit;
            } else {
                $msg = 'DB 오류: '.$ins->error;
            }
        }
    }
}
?>
<!doctype html><html lang="ko"><head>
<meta charset="utf-8">
<title>회원가입</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
     background:#f5f7fa;padding:40px}
form{max-width:320px;margin:0 auto;background:#fff;border-radius:12px;
     padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.05)}
input{width:100%;padding:10px;font-size:14px;margin:8px 0;
      border:1px solid #ccc;border-radius:6px}
button{width:100%;padding:10px;margin-top:12px;border:none;border-radius:8px;
       background:#6c5ce7;color:#fff;font-size:15px;cursor:pointer}
button:hover{background:#5944d6}
.msg{color:#e74c3c;margin:8px 0}
</style></head><body>
<h2 style="text-align:center;margin-bottom:20px">📝 회원가입</h2>

<?php if(isset($msg)): ?>
  <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <input name="username" placeholder="아이디(4~20자)" value="<?= htmlspecialchars($_POST['username']??'') ?>">
    <input type="password" name="password"  placeholder="비밀번호">
    <input type="password" name="password2" placeholder="비밀번호 확인">
    <button type="submit">가입하기</button>
    <p style="text-align:center;margin-top:10px">
      이미 계정이 있으신가요? <a href="login.php">로그인</a>
    </p>
</form>
</body></html>
