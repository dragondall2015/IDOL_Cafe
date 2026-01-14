<?php
require_once 'db.php';
$conn->set_charset("utf8mb4");

$id = $_GET['id'] ?? null;
if (!$id) {
    die("잘못된 접근입니다.");
}

$stmt = $conn->prepare("SELECT * FROM birthday_events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
if (!$event) die("해당 행사를 찾을 수 없습니다.");

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['name']) ?> - 생일카페</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            min-height: 100vh;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
            color: #6c5ce7;
        }

        .poster-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        p {
            font-size: 14px;
            margin-bottom: 12px;
        }

        strong {
            color: #555;
        }

        .btn {
            display: inline-block;
            background: #f0f0f0;
            color: #6c5ce7;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #e0e0e0;
        }

        .btn-secondary {
            background: #f1f1f1;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e2e2e2;
        }

        @media (max-width: 480px) {
            .container {
                padding: 16px;
            }

            h2 {
                font-size: 18px;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($event['name']) ?></h2>

        <?php if ($event['poster_path']): ?>
            <img src="<?= htmlspecialchars($event['poster_path']) ?>" class="poster-image" alt="포스터">
        <?php endif; ?>

        <p><strong>장소:</strong> <?= htmlspecialchars($event['location']) ?></p>
        <p><strong>날짜:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
        <p><strong>운영시간:</strong> <?= htmlspecialchars($event['start_time']) ?> ~ <?= htmlspecialchars($event['end_time']) ?></p>
        <p><strong>주소:</strong> <?= htmlspecialchars($event['address']) ?></p>
        <p><strong>설명:</strong><br><?= nl2br(htmlspecialchars($event['description'])) ?></p>
        
        <?php if ($event['tweet_url']): ?>
            <p><strong>원본 트윗:</strong>
                <a href="<?= htmlspecialchars($event['tweet_url']) ?>" target="_blank">
                        트윗 보러가기 ↗
                </a>
            </p>
        <?php endif; ?>

        <div style="text-align:center; margin-top:30px;">
            <a href="/" class="btn btn-secondary">← 돌아가기</a>
        </div>
    </div>
</body>
</html>
