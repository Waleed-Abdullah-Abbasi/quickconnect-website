<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("INSERT INTO testimonials (client_name, client_position, client_company, testimonial_text, rating, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['position'] ?? '',
                    $_POST['company'],
                    $_POST['message'],
                    $_POST['rating'],
                    $_POST['status'] === 'active' ? 1 : 0
                ]);
                $success = "Testimonial added successfully!";
                break;

            case 'edit':
                $stmt = $db->prepare("UPDATE testimonials SET client_name = ?, client_position = ?, client_company = ?, testimonial_text = ?, rating = ?, is_active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['position'] ?? '',
                    $_POST['company'],
                    $_POST['message'],
                    $_POST['rating'],
                    $_POST['status'] === 'active' ? 1 : 0,
                    $_POST['id']
                ]);
                $success = "Testimonial updated successfully!";
                // Redirect to clear the edit parameter from URL
                header('Location: testimonials.php?updated=1');
                exit();
                break;

            case 'delete':
                $stmt = $db->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Testimonial deleted successfully!";
                break;
        }
    }
}

// Check for update success message
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = "Testimonial updated successfully!";
}

// Get testimonials
$stmt = $db->prepare("SELECT * FROM testimonials ORDER BY created_at DESC");
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get testimonial for editing
$editTestimonial = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - QuickConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link text-white-50" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a class="nav-link text-white-50" href="contacts.php">
                            <i class="fas fa-envelope me-2"></i>Contacts
                        </a>
                        <a class="nav-link text-white-50" href="services.php">
                            <i class="fas fa-cogs me-2"></i>Services
                        </a>
                        <a class="nav-link text-white active" href="testimonials.php">
                            <i class="fas fa-star me-2"></i>Testimonials
                        </a>
                        <a class="nav-link text-white-50" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-star me-2"></i>Testimonials Management</h2>
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus me-2"></i>Add Testimonial
                    </button>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Testimonials Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($testimonials)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No testimonials found</h5>
                                <p class="text-muted">Click "Add Testimonial" to create your first testimonial.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Company</th>
                                            <th>Message</th>
                                            <th>Rating</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testimonials as $testimonial): ?>
                                            <tr>
                                                <td><?php echo isset($testimonial['id']) ? $testimonial['id'] : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars($testimonial['client_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($testimonial['client_position'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($testimonial['client_company'] ?? 'N/A'); ?></td>
                                                <td><?php echo substr(htmlspecialchars($testimonial['testimonial_text'] ?? 'N/A'), 0, 50) . '...'; ?></td>
                                                <td>
                                                    <span class="rating-stars">
                                                        <?php
                                                        $rating = isset($testimonial['rating']) ? (int)$testimonial['rating'] : 0;
                                                        for ($i = 1; $i <= 5; $i++):
                                                        ?>
                                                            <i class="fas fa-star<?php echo $i <= $rating ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $isActive = isset($testimonial['is_active']) ? $testimonial['is_active'] : 0;
                                                    $badgeClass = $isActive ? 'success' : 'secondary';
                                                    $statusText = $isActive ? 'Active' : 'Inactive';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($testimonial['created_at']) && $testimonial['created_at']) {
                                                        echo date('M j, Y', strtotime($testimonial['created_at']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($testimonial['id'])): ?>
                                                        <a href="?edit=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Testimonial Modal -->
    <div class="modal fade" id="addTestimonialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Testimonial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="add_name" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="add_position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="add_position" name="position">
                        </div>

                        <div class="mb-3">
                            <label for="add_company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="add_company" name="company">
                        </div>

                        <div class="mb-3">
                            <label for="add_message" class="form-label">Testimonial Message</label>
                            <textarea class="form-control" id="add_message" name="message" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="add_rating" class="form-label">Rating</label>
                            <select class="form-select" id="add_rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="add_status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Testimonial</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Testimonial Modal -->
    <div class="modal fade" id="editTestimonialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Testimonial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <?php if ($editTestimonial): ?>
                            <input type="hidden" name="id" value="<?php echo $editTestimonial['id'] ?? ''; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name"
                                value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_name'] ?? '') : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="edit_position" name="position"
                                value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_position'] ?? '') : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="edit_company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="edit_company" name="company"
                                value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_company'] ?? '') : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="edit_message" class="form-label">Testimonial Message</label>
                            <textarea class="form-control" id="edit_message" name="message" rows="4" required><?php echo $editTestimonial ? htmlspecialchars($editTestimonial['testimonial_text'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="edit_rating" class="form-label">Rating</label>
                            <select class="form-select" id="edit_rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($editTestimonial && isset($editTestimonial['rating']) && $editTestimonial['rating'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active" <?php echo ($editTestimonial && isset($editTestimonial['is_active']) && $editTestimonial['is_active'] == 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editTestimonial && isset($editTestimonial['is_active']) && $editTestimonial['is_active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Testimonial</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteTestimonial(id) {
            if (confirm('Are you sure you want to delete this testimonial?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function showAddModal() {
            // Clear the form
            document.getElementById('add_name').value = '';
            document.getElementById('add_position').value = '';
            document.getElementById('add_company').value = '';
            document.getElementById('add_message').value = '';
            document.getElementById('add_rating').value = '';
            document.getElementById('add_status').value = 'active';

            var modal = new bootstrap.Modal(document.getElementById('addTestimonialModal'));
            modal.show();
        }

        // Show edit modal if editing
        <?php if ($editTestimonial): ?>
            window.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('editTestimonialModal'));
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>

</html>
