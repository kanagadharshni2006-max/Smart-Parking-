<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../auth.html');
    exit;
}

$slots = $conn->query("SELECT * FROM slots ORDER BY slot_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Slots | SmartPark</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_slots.php">Manage Slots</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../api/auth.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2>Manage <span class="text-gradient">Parking Slots</span></h2>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSlotModal">
                    <i class="fas fa-plus me-2"></i>Add Slot
                </button>
            </div>
        </div>

        <div class="glass p-5">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Slot #</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($slot = $slots->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $slot['slot_number']; ?></td>
                            <td><?php echo $slot['floor']; ?></td>
                            <td><?php echo $slot['vehicle_type']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $slot['status'] == 'Available' ? 'success' : ($slot['status'] == 'Booked' ? 'danger' : 'warning'); ?>">
                                    <?php echo $slot['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSlot(<?php echo $slot['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Slot Modal -->
    <div class="modal fade" id="addSlotModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="glass modal-content p-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Add New Parking Slot</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="add-slot-form">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Slot Number (e.g. A-33)</label>
                            <input type="text" name="slot_number" class="form-control-custom w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Floor</label>
                            <input type="text" name="floor" class="form-control-custom w-100" value="B1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Vehicle Type</label>
                            <select name="vehicle_type" class="form-control-custom w-100">
                                <option value="Car">Car</option>
                                <option value="Motorcycle">Motorcycle</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn glass" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Add Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Add Slot Handler
        document.getElementById('add-slot-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const resp = await fetch('../api/admin_actions.php?action=add_slot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await resp.json();

            if (result.success) {
                location.reload();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        });

        async function deleteSlot(id) {
            if (confirm('Are you sure you want to delete this slot?')) {
                const resp = await fetch('../api/admin_actions.php?action=delete_slot&id=' + id);
                const result = await resp.json();
                if (result.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            }
        }
    </script>
</body>
</html>
