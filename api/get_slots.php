<?php
header('Content-Type: application/json');
require_once '../config.php';

$sql = "SELECT * FROM slots";
$result = $conn->query($sql);

$slots = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
}

echo json_encode($slots);
?>
