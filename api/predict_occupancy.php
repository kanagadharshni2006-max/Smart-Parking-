<?php
header('Content-Type: application/json');

$time = isset($_GET['time']) ? $_GET['time'] : date('H:i');
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$day_of_week = date('l', strtotime($date));
$hour = (int)date('H', strtotime($time));

// 1. Baseline occupancy based on day of week
$base_occupancy = 60; // default
switch ($day_of_week) {
    case 'Monday':
        $base_occupancy = 75;
        break;
    case 'Tuesday':
        $base_occupancy = 60;
        break;
    case 'Wednesday':
        $base_occupancy = 55;
        break;
    case 'Thursday':
        $base_occupancy = 60;
        break;
    case 'Friday':
        $base_occupancy = 80;
        break;
    case 'Saturday':
        $base_occupancy = 85;
        break;
    case 'Sunday':
        $base_occupancy = 70;
        break;
}

// 2. Adjust occupancy based on hour of the day
$adjustment = 0;
if ($hour >= 8 && $hour < 11) {
    $adjustment = 15; // Morning Rush
} elseif ($hour >= 11 && $hour < 14) {
    $adjustment = 5;
} elseif ($hour >= 14 && $hour < 17) {
    $adjustment = 10;
} elseif ($hour >= 17 && $hour < 20) {
    $adjustment = 20; // Evening Rush
} elseif ($hour >= 20 && $hour < 23) {
    $adjustment = -10;
} elseif ($hour >= 23 || $hour < 5) {
    $adjustment = -45; // Night
} elseif ($hour >= 5 && $hour < 8) {
    $adjustment = -25; // Early morning
}

$predicted_occupancy = $base_occupancy + $adjustment;
// Add a tiny random variation to make it feel organic (-2% to +2%)
$predicted_occupancy += rand(-2, 2);
// Clamp between 10% and 98%
$predicted_occupancy = max(10, min(98, $predicted_occupancy));

// Calculate estimated slots (assuming 36 total slots)
$total_slots = 36;
$occupied_slots = round(($predicted_occupancy / 100) * $total_slots);
$free_slots = $total_slots - $occupied_slots;

// Determine warning details
$status_class = "success";
$status_message = "Low traffic expected. Off-peak discount or standard rates will apply.";
$advice = "Plenty of parking slots available. You can book anytime!";

if ($predicted_occupancy >= 85) {
    $status_class = "danger";
    $status_message = "Critical occupancy expected! High demand surcharges will apply.";
    $advice = "Slots will be extremely limited. We recommend booking immediately to secure your spot!";
} elseif ($predicted_occupancy >= 60) {
    $status_class = "warning";
    $status_message = "Moderate to high occupancy expected. Standard rates will apply.";
    $advice = "Slots might fill up fast. Booking in advance is recommended.";
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'time' => $time,
    'day_of_week' => $day_of_week,
    'predicted_occupancy' => $predicted_occupancy,
    'total_slots' => $total_slots,
    'occupied_slots' => $occupied_slots,
    'free_slots' => $free_slots,
    'status_class' => $status_class,
    'status_message' => $status_message,
    'advice' => $advice
]);
?>
