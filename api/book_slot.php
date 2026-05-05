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
$total_price = 15.00; // Fixed for demo, can be calculated

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
    $insert_sql = "INSERT INTO bookings (user_id, slot_id, vehicle_number, vehicle_model, arrival_time, duration, total_price) 
                   VALUES ($user_id, $slot_id, '$vehicle_number', '$vehicle_model', '$arrival_time', $duration, $total_price)";
    $conn->query($insert_sql);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking confirmed!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
