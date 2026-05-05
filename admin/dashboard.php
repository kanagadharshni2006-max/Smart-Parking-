<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../auth.html');
    exit;
}

// Fetch stats
$total_slots = $conn->query("SELECT COUNT(*) FROM slots")->fetch_row()[0];
$booked_slots = $conn->query("SELECT COUNT(*) FROM slots WHERE status = 'Booked'")->fetch_row()[0];
$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'User'")->fetch_row()[0];

// Fetch recent bookings
$recent_bookings = $conn->query("SELECT b.*, u.name as user_name, s.slot_number 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.id 
                                JOIN slots s ON b.slot_id = s.id 
                                ORDER BY b.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | SmartPark</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg scrolled">
        <div class="container">
            <a class="navbar-brand" href="#">Admin<span class="text-gradient">Panel</span></a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_slots.php">Manage Slots</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../api/auth.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass p-4 text-center">
                    <h1 class="text-gradient"><?php echo $total_slots; ?></h1>
                    <p class="text-muted mb-0">Total Slots</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass p-4 text-center">
                    <h1 class="text-gradient"><?php echo $booked_slots; ?></h1>
                    <p class="text-muted mb-0">Currently Booked</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass p-4 text-center">
                    <h1 class="text-gradient"><?php echo $total_users; ?></h1>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
        </div>

        <div class="glass p-5">
            <h3 class="mb-4">Recent <span class="text-gradient">Bookings</span></h3>
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Slot</th>
                            <th>Vehicle</th>
                            <th>Arrival</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['user_name']; ?></td>
                            <td><span class="badge bg-primary"><?php echo $booking['slot_number']; ?></span></td>
                            <td><?php echo $booking['vehicle_number']; ?> (<?php echo $booking['vehicle_model']; ?>)</td>
                            <td><?php echo $booking['arrival_time']; ?></td>
                            <td><?php echo $booking['duration']; ?> hrs</td>
                            <td>
                                <span class="badge bg-<?php echo $booking['status'] == 'Confirmed' ? 'success' : 'danger'; ?>">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function cancelBooking(id) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                const resp = await fetch('../api/admin_actions.php?action=cancel_booking&id=' + id);
                const result = await resp.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            }
        }
    </script>
</body>
</html>
