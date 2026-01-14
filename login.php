<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';

    $stmt=$conn->prepare("SELECT id,pw_hash,role FROM users WHERE username=?");
    $stmt->bind_param('s',$u); $stmt->execute();
    $stmt->bind_result($id,$hash,$role);

    if($stmt->fetch() && password_verify($p,$hash)){
        $_SESSION['uid']=$id;
        $_SESSION['uname']=$u;
        $_SESSION['role']=$role;
        header("Location: index.php"); exit;
    }
    $error="로그인 실패";
}
?>
<!doctype html><html lang="ko"><head><meta charset="utf-8">
<title>로그인</title></head><body>
    <a href="register.php">회원가입</a>
<h2>🔐 로그인</h2>
<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
<form method="post">
    <input name="username" placeholder="아이디"><br>
    <input type="password" name="password" placeholder="비밀번호"><br>
    <button>로그인</button>
</form>
</body></html>
