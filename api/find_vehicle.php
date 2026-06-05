<?php
header('Content-Type: application/json');
require_once '../config.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a vehicle number or booking reference ID']);
    exit;
}

// Clean up input query: remove spaces and hyphens for flexible matches
$clean_query = str_replace([' ', '-'], '', $query);

$sql = "SELECT b.id as booking_id, b.reference_id, b.vehicle_number, b.vehicle_model, 
               b.arrival_time, b.duration, b.total_price, b.status, b.created_at,
               s.slot_number, s.floor, s.vehicle_type
        FROM bookings b
        JOIN slots s ON b.slot_id = s.id
        WHERE (REPLACE(REPLACE(b.vehicle_number, ' ', ''), '-', '') = ? OR b.reference_id = ?)
        AND b.status IN ('Confirmed', 'Completed')
        ORDER BY b.created_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $clean_query, $query);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'booking' => [
            'reference_id' => $booking['reference_id'],
            'vehicle_number' => $booking['vehicle_number'],
            'vehicle_model' => $booking['vehicle_model'],
            'arrival_time' => $booking['arrival_time'],
            'duration' => $booking['duration'],
            'total_price' => $booking['total_price'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at'],
            'slot_number' => $booking['slot_number'],
            'floor' => $booking['floor'],
            'vehicle_class' => $booking['vehicle_type']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No active parking booking found for this vehicle number or reference ID.']);
}
?>
