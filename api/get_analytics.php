<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$analytics = [];

// 1. Total Revenue
$rev_res = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'Confirmed' OR status = 'Completed'");
$analytics['total_revenue'] = (float)$rev_res->fetch_assoc()['total'];

// 2. Occupancy Rate
$total_slots_res = $conn->query("SELECT COUNT(*) as count FROM slots");
$booked_slots_res = $conn->query("SELECT COUNT(*) as count FROM slots WHERE status = 'Booked'");
$total_slots = (int)$total_slots_res->fetch_assoc()['count'];
$booked_slots = (int)$booked_slots_res->fetch_assoc()['count'];
$analytics['occupancy_rate'] = $total_slots > 0 ? round(($booked_slots / $total_slots) * 100, 1) : 0;

// 3. Revenue by Date (last 7 days)
$date_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $res = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE DATE(created_at) = '$date'");
    $date_revenue[] = [
        'date' => date('M d', strtotime($date)),
        'total' => (float)($res->fetch_assoc()['total'] ?? 0)
    ];
}
$analytics['revenue_chart'] = $date_revenue;

// 4. Booking Status Distribution
$status_dist = [];
$res = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $status_dist[] = $row;
}
$analytics['status_distribution'] = $status_dist;

echo json_encode($analytics);
?>
