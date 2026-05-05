<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action == 'cancel_booking') {
    $id = (int)$_GET['id'];
    
    // Get slot_id first to free it up
    $res = $conn->query("SELECT slot_id FROM bookings WHERE id = $id");
    if ($res->num_rows > 0) {
        $slot_id = $res->fetch_assoc()['slot_id'];
        
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE bookings SET status = 'Cancelled' WHERE id = $id");
            $conn->query("UPDATE slots SET status = 'Available' WHERE id = $slot_id");
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to cancel']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
}
elseif ($action == 'add_slot') {
    $data = json_decode(file_get_contents('php://input'), true);
    $slot_num = $conn->real_escape_string($data['slot_number']);
    $floor = $conn->real_escape_string($data['floor']);
    $type = $conn->real_escape_string($data['vehicle_type']);

    $sql = "INSERT INTO slots (slot_number, floor, vehicle_type) VALUES ('$slot_num', '$floor', '$type')";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Slot number already exists']);
    }
}
elseif ($action == 'delete_slot') {
    $id = (int)$_GET['id'];
    // Check if slot has bookings
    $check = $conn->query("SELECT id FROM bookings WHERE slot_id = $id");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete slot with existing bookings']);
    } else {
        $conn->query("DELETE FROM slots WHERE id = $id");
        echo json_encode(['success' => true]);
    }
}
?>
