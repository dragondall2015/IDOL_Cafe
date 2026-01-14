<?php
/* admin.php : 이벤트 목록 / 간단 대시보드 */
require_once 'db.php';
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT id,name,event_date,location,tweet_url FROM birthday_events ORDER BY event_date DESC");
?>
<!doctype html><html lang="ko"><head>
<meta charset="utf-8"><title>관리자 – 생일카페</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/modern-css-reset/dist/reset.min.css">
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f7fa;padding:30px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}
th,td{padding:12px 16px;font-size:14px;border-bottom:1px solid #eee;text-align:left}
th{background:#6c5ce7;color:#fff}
a.btn{display:inline-block;padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;color:#fff;background:#6c5ce7}
a.btn:hover{background:#5944d6}
</style></head><body>
  <?php include 'header.php'; ?>
  <a href="register.php">회원가입</a>
<h2 style="margin-bottom:20px">🎛️ 생일카페 이벤트 관리</h2>

<table>
<thead><tr>
  <th>ID</th><th>행사명</th><th>날짜</th><th>장소</th><th>트윗 URL</th><th>편집</th>
</tr></thead><tbody>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['name']) ?></td>
  <td><?= $row['event_date'] ?></td>
  <td><?= htmlspecialchars($row['location']) ?></td>
  <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
      <?php if($row['tweet_url']): ?>
        <a href="<?= htmlspecialchars($row['tweet_url'])?>" target="_blank">원본보기↗</a>
      <?php endif; ?>
  </td>
  <td><a class="btn" href="edit_event.php?id=<?= $row['id'] ?>">수정</a></td>
</tr>
<?php endwhile; ?>
</tbody></table>

<p style="margin-top:20px">
  <a class="btn" href="index.php">← 캘린더로 돌아가기</a>
</p>
</body></html>
<?php $conn->close(); ?>
