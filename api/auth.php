<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if ($action == 'register') {
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
    }
} 
elseif ($action == 'login') {
    $email = $conn->real_escape_string($data['email']);
    $password = $data['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            echo json_encode([
                'success' => true, 
                'role' => $user['role'],
                'name' => $user['name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
}
elseif ($action == 'logout') {
    session_destroy();
    header('Location: ../index.html');
    exit;
}
elseif ($action == 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true, 
            'name' => $_SESSION['user_name'], 
            'role' => $_SESSION['role']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}
?>
