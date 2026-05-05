<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT b.*, s.slot_number FROM bookings b 
        JOIN slots s ON b.slot_id = s.id 
        WHERE b.user_id = $user_id 
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);

$history = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

echo json_encode($history);
?>
