<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<div class="top-nav" style="text-align:right;margin-bottom:10px">
  <?php if (isset($_SESSION['uid'])): ?>
    <span><?= htmlspecialchars($_SESSION['uname']) ?>님</span> |
    <a href="logout.php">로그아웃</a>
    <?php if ($_SESSION['role']==='admin'): ?>
      | <a href="admin.php">관리자</a>
    <?php endif; ?>
  <?php else: ?>
    <a href="login.php">로그인</a>
  <?php endif; ?>
</div>
