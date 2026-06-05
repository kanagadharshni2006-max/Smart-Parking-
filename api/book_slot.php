<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to book a slot.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['slot_id']) || !isset($data['vehicle_number'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$slot_id = $conn->real_escape_string($data['slot_id']);
$vehicle_number = $conn->real_escape_string($data['vehicle_number']);
$vehicle_model = $conn->real_escape_string($data['vehicle_model'] ?? '');
$arrival_time = $conn->real_escape_string($data['arrival_time']);
$duration = (int)$data['duration'];

// Fetch slot details
$slot_res = $conn->query("SELECT * FROM slots WHERE id = $slot_id");
$slot = $slot_res->fetch_assoc();
$base_price = (float)$slot['price_per_hour'];
$floor = $slot['floor'];

// Calculate temporal (time-of-day) multiplier
$time_hour = (int)date('H', strtotime($arrival_time));
$time_multiplier = 1.0;
if (($time_hour >= 9 && $time_hour < 12) || ($time_hour >= 17 && $time_hour < 20)) {
    $time_multiplier = 1.3;
} elseif ($time_hour >= 22 || $time_hour < 6) {
    $time_multiplier = 0.8;
}

// Calculate floor occupancy multiplier
$occ_res = $conn->query("SELECT COUNT(*) as occupied, (SELECT COUNT(*) FROM slots WHERE floor = '$floor') as total FROM slots WHERE floor = '$floor' AND status != 'Available'");
$occ_data = $occ_res->fetch_assoc();
$occupied = (int)$occ_data['occupied'];
$total_slots = (int)$occ_data['total'];
$occupancy_rate = $total_slots > 0 ? ($occupied / $total_slots) : 0;

$occupancy_surcharge = 0.0;
if ($occupancy_rate >= 0.75) {
    $occupancy_surcharge = 0.2;
} elseif ($occupancy_rate >= 0.50) {
    $occupancy_surcharge = 0.1;
}

$total_multiplier = $time_multiplier + $occupancy_surcharge;
$hourly_price = round($base_price * $total_multiplier, 2);
$subtotal = round($hourly_price * $duration, 2);
$service_fee = 5.00;
$total_price = round($subtotal + $service_fee, 2);

// Generate Unique Reference ID
$reference_id = "SP" . strtoupper(substr(uniqid(), -6));

// Start transaction
$conn->begin_transaction();

try {
    // 1. Update slot status to 'Booked'
    $update_sql = "UPDATE slots SET status = 'Booked' WHERE id = $slot_id AND status = 'Available'";
    $conn->query($update_sql);
    
    if ($conn->affected_rows === 0) {
        throw new Exception("Slot is no longer available.");
    }

    // 2. Insert into bookings
    $insert_sql = "INSERT INTO bookings (user_id, slot_id, reference_id, vehicle_number, vehicle_model, arrival_time, duration, total_price) 
                   VALUES ($user_id, $slot_id, '$reference_id', '$vehicle_number', '$vehicle_model', '$arrival_time', $duration, $total_price)";
    $conn->query($insert_sql);

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Booking confirmed!',
        'reference_id' => $reference_id,
        'total_price' => $total_price
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
