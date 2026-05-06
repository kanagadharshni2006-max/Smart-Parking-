<?php
require_once '../config.php';

/**
 * This script should be run via a Cron Job or manually to release slots
 * whose booking duration has expired.
 */

// Logic: current_time > (created_at_date + arrival_time + duration)
$current_time = date('Y-m-d H:i:s');

$sql = "SELECT b.id, b.slot_id, b.created_at, b.arrival_time, b.duration 
        FROM bookings b 
        JOIN slots s ON b.slot_id = s.id 
        WHERE b.status = 'Confirmed' AND s.status = 'Booked'";

$result = $conn->query($sql);
$released_count = 0;

while ($row = $result->fetch_assoc()) {
    $booking_date = date('Y-m-d', strtotime($row['created_at']));
    $start_datetime = $booking_date . ' ' . $row['arrival_time'];
    $expiry_datetime = date('Y-m-d H:i:s', strtotime("$start_datetime + " . $row['duration'] . " hours"));

    if (strtotime($current_time) > strtotime($expiry_datetime)) {
        // Release slot
        $slot_id = $row['slot_id'];
        $booking_id = $row['id'];
        
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE slots SET status = 'Available' WHERE id = $slot_id");
            $conn->query("UPDATE bookings SET status = 'Completed' WHERE id = $booking_id");
            $conn->commit();
            $released_count++;
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Released $released_count expired slots.",
    'timestamp' => $current_time
]);
?>
