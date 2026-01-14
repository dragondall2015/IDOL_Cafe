<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
ob_start(); // 출력 버퍼링

require_once 'db.php'; // DB 연결 ($conn)

$sql = "SELECT * FROM birthday_events ORDER BY event_date DESC";
$result = $conn->query($sql);

$events = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['event_date'];
    if (!isset($events[$date])) $events[$date] = [];

    $start = $row['start_time'] ?? '';
    $end = $row['end_time'] ?? '';
    $timeRange = ($start && $end) ? "$start ~ $end" : '';

    $events[$date][] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'location' => $row['location'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'address' => $row['address'],
        'description' => $row['description'],
        'poster' => $row['poster_path']
    ];
}

$conn->close();

ob_clean(); // JSON 외 출력물 제거
echo json_encode(['success' => true, 'events' => $events]);
