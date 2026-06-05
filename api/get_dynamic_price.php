<?php
header('Content-Type: application/json');
require_once '../config.php';

$slot_id = isset($_GET['slot_id']) ? (int)$_GET['slot_id'] : 0;
$arrival_time = isset($_GET['arrival_time']) ? $_GET['arrival_time'] : date('H:i');
$duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 1;

if ($slot_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot ID']);
    exit;
}

// 1. Fetch slot details
$slot_stmt = $conn->prepare("SELECT * FROM slots WHERE id = ?");
$slot_stmt->bind_param("i", $slot_id);
$slot_stmt->execute();
$slot = $slot_stmt->get_result()->fetch_assoc();

if (!$slot) {
    echo json_encode(['success' => false, 'message' => 'Slot not found']);
    exit;
}

$base_price = (float)$slot['price_per_hour'];
$floor = $slot['floor'];

// 2. Calculate temporal (time-of-day) multiplier
$time_hour = (int)date('H', strtotime($arrival_time));
$time_multiplier = 1.0;
$time_category = "Standard Hours (1.0x)";
$is_peak = false;
$is_offpeak = false;

if (($time_hour >= 9 && $time_hour < 12) || ($time_hour >= 17 && $time_hour < 20)) {
    $time_multiplier = 1.3;
    $time_category = "Peak Hour Demand Surcharge (+30%)";
    $is_peak = true;
} elseif ($time_hour >= 22 || $time_hour < 6) {
    $time_multiplier = 0.8;
    $time_category = "Off-Peak Hour Discount (-20%)";
    $is_offpeak = true;
}

// 3. Calculate floor occupancy multiplier
$occ_stmt = $conn->prepare("SELECT COUNT(*) as occupied, (SELECT COUNT(*) FROM slots WHERE floor = ?) as total FROM slots WHERE floor = ? AND status != 'Available'");
$occ_stmt->bind_param("ss", $floor, $floor);
$occ_stmt->execute();
$occ_data = $occ_stmt->get_result()->fetch_assoc();

$occupied = (int)$occ_data['occupied'];
$total_slots = (int)$occ_data['total'];
$occupancy_rate = $total_slots > 0 ? ($occupied / $total_slots) : 0;

$occupancy_surcharge = 0.0;
$occupancy_category = "Normal Occupancy (No Surcharge)";

if ($occupancy_rate >= 0.75) {
    $occupancy_surcharge = 0.2;
    $occupancy_category = "Critical Occupancy Surcharge (+20%)";
} elseif ($occupancy_rate >= 0.50) {
    $occupancy_surcharge = 0.1;
    $occupancy_category = "High Occupancy Surcharge (+10%)";
}

// 4. Compute final rates
$total_multiplier = $time_multiplier + $occupancy_surcharge;
$hourly_price = round($base_price * $total_multiplier, 2);
$subtotal = round($hourly_price * $duration, 2);
$service_fee = 5.00;
$total_price = round($subtotal + $service_fee, 2);

echo json_encode([
    'success' => true,
    'base_price' => $base_price,
    'time_multiplier' => $time_multiplier,
    'occupancy_surcharge' => $occupancy_surcharge,
    'total_multiplier' => $total_multiplier,
    'hourly_price' => $hourly_price,
    'subtotal' => $subtotal,
    'service_fee' => $service_fee,
    'total_price' => $total_price,
    'breakdown' => [
        'time_category' => $time_category,
        'occupancy_category' => $occupancy_category,
        'occupancy_rate' => round($occupancy_rate * 100, 1),
        'is_peak' => $is_peak,
        'is_offpeak' => $is_offpeak
    ]
]);
?>
