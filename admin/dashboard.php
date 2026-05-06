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
            <div class="col-md-3">
                <div class="glass p-4 stats-card">
                    <p class="text-muted small mb-1">Total Slots</p>
                    <h2 class="text-gradient mb-0"><?php echo $total_slots; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 stats-card" style="border-color: #ffaa00;">
                    <p class="text-muted small mb-1">Booked Slots</p>
                    <h2 class="mb-0" style="color: #ffaa00;"><?php echo $booked_slots; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 stats-card" style="border-color: #00ff88;">
                    <p class="text-muted small mb-1">Total Revenue</p>
                    <h2 class="mb-0" style="color: #00ff88;">₹<span id="stat-revenue">0</span></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 stats-card" style="border-color: var(--secondary);">
                    <p class="text-muted small mb-1">Occupancy</p>
                    <h2 class="mb-0" style="color: var(--secondary);"><span id="stat-occupancy">0</span>%</h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="glass p-4 h-100">
                    <h5 class="mb-4">Revenue <span class="text-gradient">Trends</span></h5>
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass p-4 h-100">
                    <h5 class="mb-4">Booking <span class="text-gradient">Status</span></h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="glass p-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Recent <span class="text-gradient">Bookings</span></h3>
                <button class="btn btn-outline-custom btn-sm" onclick="runCron()">
                    <i class="fas fa-sync me-1"></i> Release Expired
                </button>
            </div>
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
                        <tr class="fade-in">
                            <td><?php echo $booking['user_name']; ?></td>
                            <td><span class="badge bg-primary"><?php echo $booking['slot_number']; ?></span></td>
                            <td><?php echo $booking['vehicle_number']; ?> (<?php echo $booking['vehicle_model']; ?>)</td>
                            <td><?php echo $booking['arrival_time']; ?></td>
                            <td><?php echo $booking['duration']; ?> hrs</td>
                            <td>
                                <span class="badge bg-<?php echo $booking['status'] == 'Confirmed' ? 'success' : ($booking['status'] == 'Completed' ? 'info' : 'danger'); ?>">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($booking['status'] == 'Confirmed'): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function loadAnalytics() {
            const resp = await fetch('../api/get_analytics.php');
            const data = await resp.json();

            document.getElementById('stat-revenue').innerText = data.total_revenue.toLocaleString();
            document.getElementById('stat-occupancy').innerText = data.occupancy_rate;

            // Revenue Chart
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: data.revenue_chart.map(d => d.date),
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: data.revenue_chart.map(d => d.total),
                        borderColor: '#00f2ff',
                        backgroundColor: 'rgba(0, 242, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0a0' } },
                        x: { grid: { display: false }, ticks: { color: '#a0a0a0' } }
                    }
                }
            });

            // Status Chart
            const ctxStat = document.getElementById('statusChart').getContext('2d');
            new Chart(ctxStat, {
                type: 'doughnut',
                data: {
                    labels: data.status_distribution.map(d => d.status),
                    datasets: [{
                        data: data.status_distribution.map(d => d.count),
                        backgroundColor: ['#00ff88', '#ff4b4b', '#00f2ff'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#a0a0a0', padding: 20 } }
                    }
                }
            });
        }

        async function runCron() {
            const resp = await fetch('../api/cron_release_slots.php');
            const result = await resp.json();
            Swal.fire('Success', result.message, 'success').then(() => location.reload());
        }

        async function cancelBooking(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to cancel this booking?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4b4b',
                cancelButtonColor: 'rgba(255,255,255,0.1)',
                confirmButtonText: 'Yes, cancel it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const resp = await fetch('../api/admin_actions.php?action=cancel_booking&id=' + id);
                    const res = await resp.json();
                    if (res.success) {
                        Swal.fire('Cancelled!', 'Booking has been cancelled.', 'success').then(() => location.reload());
                    }
                }
            });
        }

        loadAnalytics();
    </script>
</body>
</html>
