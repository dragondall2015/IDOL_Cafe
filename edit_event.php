<?php
require_once 'db.php';
$conn->set_charset("utf8mb4");
$id = $_GET['id'] ?? null; if(!$id) die('잘못된 접근');
/* ① POST 저장 처리 */
if($_SERVER['REQUEST_METHOD']==='POST'){
    $sql="UPDATE birthday_events SET name=?,location=?,event_date=?,start_time=?,end_time=?,address=?,description=?,tweet_url=? WHERE id=?";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param("ssssssssi",
        $_POST['name'],$_POST['location'],$_POST['event_date'],
        $_POST['start_time'],$_POST['end_time'],
        $_POST['address'],$_POST['description'],
        $_POST['tweet_url'],$id
    );
    if($stmt->execute()){
        header("Location: admin.php"); exit;
    }else{ $msg="저장 실패: ".$stmt->error; }
}
/* ② 기존 데이터 읽기 */
$stmt=$conn->prepare("SELECT * FROM birthday_events WHERE id=?");
$stmt->bind_param("i",$id); $stmt->execute();
$event=$stmt->get_result()->fetch_assoc() or die('행사를 찾을 수 없습니다.');
?>
<!doctype html><html lang="ko"><head>
<meta charset="utf-8"><title>이벤트 수정</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/modern-css-reset/dist/reset.min.css">
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f7fa;padding:30px}
form{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:24px}
label{display:block;margin-top:14px;font-weight:600}
input,textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-top:6px;font-size:14px}
button{margin-top:20px;padding:10px 20px;border:none;border-radius:8px;background:#6c5ce7;color:#fff;font-size:14px;cursor:pointer}
button:hover{background:#5944d6}
.msg{color:#e74c3c}
</style></head><body>
<h2 style="text-align:center;margin-bottom:20px">📝 이벤트 정보 수정</h2>
<?php if(isset($msg)) echo "<p class='msg'>$msg</p>"; ?>
<form method="post">
    <label>행사명<input name="name" value="<?=htmlspecialchars($event['name'])?>"></label>
    <label>장소<input name="location" value="<?=htmlspecialchars($event['location'])?>"></label>
    <label>날짜<input type="date" name="event_date" value="<?=$event['event_date']?>"></label>
    <label>시작 시간<input type="time" name="start_time" value="<?=$event['start_time']?>"></label>
    <label>종료 시간<input type="time" name="end_time" value="<?=$event['end_time']?>"></label>
    <label>주소<input name="address" value="<?=htmlspecialchars($event['address'])?>"></label>
    <label>설명<textarea rows="4" name="description"><?=htmlspecialchars($event['description'])?></textarea></label>
    <label>트윗 원본 URL<input name="tweet_url" placeholder="https://twitter.com/..." value="<?=htmlspecialchars($event['tweet_url'])?>"></label>
    <button type="submit">💾 저장</button>
    <a href="admin.php" style="margin-left:12px">취소</a>
</form>
</body></html>
<?php $conn->close(); ?>
