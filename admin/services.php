<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Initialize variables
$success_message = '';
$error_message = '';
$edit_service = null;

// Function to validate and format JSON features
function formatFeatures($features)
{
    $features = trim($features);

    // If empty, return empty JSON array
    if (empty($features)) {
        return '[]';
    }

    // If it's already valid JSON, return as is
    if (json_decode($features) !== null) {
        return $features;
    }

    // If it's a simple text list (line-separated), convert to JSON array
    $lines = array_filter(array_map('trim', explode("\n", $features)));
    if (!empty($lines)) {
        return json_encode($lines);
    }

    // Fallback to empty JSON array
    return '[]';
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $price = trim($_POST['price'] ?? '');
                $features = trim($_POST['features'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $display_order = (int)($_POST['display_order'] ?? 0);

                if (empty($title) || empty($description)) {
                    throw new Exception("Title and description are required.");
                }

                // Handle empty fields - keep as empty strings, but format features as JSON
                $icon = empty($icon) ? '' : $icon;
                $price = empty($price) ? '' : $price;
                $features = formatFeatures($features); // This will always return valid JSON

                $stmt = $pdo->prepare("INSERT INTO services (title, description, icon, price, features, is_active, display_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $icon, $price, $features, $is_active, $display_order]);
                $success_message = "Service created successfully!";

                // Redirect to prevent form resubmission and clear edit state
                header('Location: services.php?success=' . urlencode($success_message));
                exit();
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $price = trim($_POST['price'] ?? '');
                $features = trim($_POST['features'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $display_order = (int)($_POST['display_order'] ?? 0);

                if ($id <= 0 || empty($title) || empty($description)) {
                    throw new Exception("Invalid data provided.");
                }

                // Handle empty fields - keep as empty strings, but format features as JSON
                $icon = empty($icon) ? '' : $icon;
                $price = empty($price) ? '' : $price;
                $features = formatFeatures($features); // This will always return valid JSON

                $stmt = $pdo->prepare("UPDATE services SET title = ?, description = ?, icon = ?, price = ?, features = ?, is_active = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $description, $icon, $price, $features, $is_active, $display_order, $id]);
                $success_message = "Service updated successfully!";

                // Redirect to prevent form resubmission and clear edit state
                header('Location: services.php?success=' . urlencode($success_message));
                exit();
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception("Invalid service ID.");
                }

                $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$id]);
                $success_message = "Service deleted successfully!";

                // Redirect to prevent form resubmission
                header('Location: services.php?success=' . urlencode($success_message));
                exit();
                break;

            case 'toggle_status':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception("Invalid service ID.");
                }

                $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                $success_message = "Service status updated successfully!";

                // Redirect to prevent form resubmission
                header('Location: services.php?success=' . urlencode($success_message));
                exit();
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Fetch all services
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY display_order ASC, created_at DESC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching services: " . $e->getMessage();
    $services = [];
}

// Get service for editing if requested
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_service) {
            $error_message = "Service not found.";
        }
    } catch (Exception $e) {
        $error_message = "Error fetching service: " . $e->getMessage();
    }
}

// Get statistics for sidebar
$stats = ['services' => count($services)];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM contacts");
    $stats['contacts'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM testimonials");
    $stats['testimonials'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['contacts'] = 0;
    $stats['testimonials'] = 0;
}

// Function to display features nicely
function displayFeatures($features)
{
    if (empty($features)) return '';

    $decoded = json_decode($features, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return implode(', ', $decoded);
    }

    return $features; // Return as-is if not valid JSON
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - QuickConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .table th {
            background: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }

        .service-icon {
            font-size: 2rem;
            color: #667eea;
        }

        .status-badge {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .action-buttons .btn {
            margin: 0 2px;
        }

        .price-display {
            font-weight: 600;
            color: #28a745;
        }

        .description-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .display-order-badge {
            background: #6c757d;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .features-help {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        QuickConnect
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="contacts.php">
                            <i class="fas fa-envelope me-2"></i> Contacts
                            <?php if ($stats['contacts'] > 0): ?>
                                <span class="badge bg-light text-dark ms-2"><?php echo $stats['contacts']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link active" href="services.php">
                            <i class="fas fa-cogs me-2"></i> Services
                            <span class="badge bg-light text-dark ms-2"><?php echo $stats['services']; ?></span>
                        </a>
                        <a class="nav-link" href="testimonials.php">
                            <i class="fas fa-star me-2"></i> Testimonials
                            <?php if ($stats['testimonials'] > 0): ?>
                                <span class="badge bg-light text-dark ms-2"><?php echo $stats['testimonials']; ?></span>
                            <?php endif; ?>
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i> View Site
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Services Management</h2>
                            <p class="text-muted">Manage your service offerings and pricing</p>
                        </div>
                        <button class="btn btn-primary" onclick="showAddModal()">
                            <i class="fas fa-plus me-2"></i>Add New Service
                        </button>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Services Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Services (<?php echo count($services); ?>)</h5>
                                <div class="d-flex gap-2">
                                    <small class="text-muted">
                                        Active: <?php echo count(array_filter($services, fn($s) => $s['is_active'])); ?> |
                                        Inactive: <?php echo count(array_filter($services, fn($s) => !$s['is_active'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($services)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-cogs"></i>
                                    <h5>No services found</h5>
                                    <p>Start by adding your first service to showcase your offerings.</p>
                                    <button class="btn btn-primary" onclick="showAddModal()">
                                        <i class="fas fa-plus me-2"></i>Add Your First Service
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">Order</th>
                                                <th style="width: 35%;">Service</th>
                                                <th style="width: 20%;">Price</th>
                                                <th style="width: 15%;">Status</th>
                                                <th style="width: 15%;">Created</th>
                                                <th style="width: 10%;" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $service): ?>
                                                <tr>
                                                    <td>
                                                        <span class="display-order-badge"><?php echo $service['display_order']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-start">
                                                            <?php if (!empty($service['icon'])): ?>
                                                                <i class="<?php echo htmlspecialchars($service['icon']); ?> service-icon me-3 mt-1"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-cog service-icon me-3 mt-1"></i>
                                                            <?php endif; ?>
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($service['title']); ?></h6>
                                                                <p class="text-muted mb-0 small description-preview">
                                                                    <?php echo htmlspecialchars($service['description']); ?>
                                                                </p>
                                                                <?php
                                                                $displayFeatures = displayFeatures($service['features']);
                                                                if (!empty($displayFeatures)):
                                                                ?>
                                                                    <small class="text-info">
                                                                        <i class="fas fa-list me-1"></i><?php echo htmlspecialchars($displayFeatures); ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($service['price'])): ?>
                                                            <span class="price-display"><?php echo htmlspecialchars($service['price']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Contact for pricing</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                                            <button type="submit" class="btn p-0 border-0 bg-transparent">
                                                                <span class="badge status-badge bg-<?php echo $service['is_active'] ? 'success' : 'secondary'; ?>">
                                                                    <i class="fas fa-<?php echo $service['is_active'] ? 'check' : 'pause'; ?> me-1"></i>
                                                                    <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y', strtotime($service['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons">
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="showEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)"
                                                                title="Edit Service">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteService(<?php echo $service['id']; ?>)"
                                                                title="Delete Service">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
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
    </div>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="serviceForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus me-2"></i>Add New Service
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="serviceId">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Service Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" id="serviceTitle"
                                        required maxlength="100"
                                        placeholder="Enter service title">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" id="displayOrder" min="0"
                                        value="0" placeholder="0">
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Icon Class</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-icons"></i>
                                        </span>
                                        <input type="text" class="form-control" name="icon" id="serviceIcon" maxlength="50"
                                            placeholder="e.g., fas fa-laptop-code">
                                    </div>
                                    <small class="form-text text-muted">
                                        Use Font Awesome icon classes.
                                        <a href="https://fontawesome.com/icons" target="_blank">Browse icons</a>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="text" class="form-control" name="price" id="servicePrice" maxlength="50"
                                        placeholder="e.g., $99, From $50, Contact for pricing">
                                    <small class="form-text text-muted">Enter any pricing format</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" id="serviceDescription" rows="4" required
                                placeholder="Describe your service in detail..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Features</label>
                            <textarea class="form-control" name="features" id="serviceFeatures" rows="3"
                                placeholder="Enter features one per line, or as JSON array..."></textarea>
                            <div class="features-help">
                                <strong>Features Input Options:</strong><br>
                                <strong>Option 1 - Simple List:</strong> Enter one feature per line<br>
                                <code>Custom Development<br>Database Design<br>API Integration</code><br><br>
                                <strong>Option 2 - JSON Array:</strong> For advanced users<br>
                                <code>["Custom Development", "Database Design", "API Integration"]</code>
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-eye me-1"></i>
                                Active (visible on website)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-plus me-2"></i>Create Service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this service? This action cannot be undone.</p>
                    <p class="text-muted">The service will be permanently removed from your website.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Delete Service
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let serviceToDelete = null;

        // Function to show Add New Service modal
        function showAddModal() {
            // Reset form
            document.getElementById('serviceForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('serviceId').value = '';
            document.getElementById('displayOrder').value = '0';
            document.getElementById('is_active').checked = true;

            // Update modal title and button
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add New Service';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Create Service';

            // Show modal
            const serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
            serviceModal.show();
        }

        // Function to show Edit Service modal
        function showEditModal(service) {
            // Populate form with service data
            document.getElementById('formAction').value = 'update';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceTitle').value = service.title;
            document.getElementById('serviceDescription').value = service.description;
            document.getElementById('serviceIcon').value = service.icon || '';
            document.getElementById('servicePrice').value = service.price || '';

            // Handle features - convert JSON back to readable format for editing
            let featuresValue = service.features || '';
            if (featuresValue) {
                try {
                    const parsed = JSON.parse(featuresValue);
                    if (Array.isArray(parsed)) {
                        featuresValue = parsed.join('\n');
                    }
                } catch (e) {
                    // If it's not valid JSON, keep as is
                }
            }
            document.getElementById('serviceFeatures').value = featuresValue;

            document.getElementById('displayOrder').value = service.display_order;
            document.getElementById('is_active').checked = service.is_active == 1;

            // Update modal title and button
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Service';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Service';

            // Show modal
            const serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
            serviceModal.show();
        }

        // Function to handle service deletion
        function deleteService(id) {
            serviceToDelete = id;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function confirmDelete() {
            if (serviceToDelete) {
                document.getElementById('deleteId').value = serviceToDelete;
                document.getElementById('deleteForm').submit();
            }
        }

        // Auto-show edit modal if editing (only when specifically requested via URL)
        <?php if ($edit_service && isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showEditModal(<?php echo json_encode($edit_service); ?>);
            });
        <?php endif; ?>

        // Form validation
        document.getElementById('serviceForm').addEventListener('submit', function(e) {
            const title = this.querySelector('input[name="title"]').value.trim();
            const description = this.querySelector('textarea[name="description"]').value.trim();

            if (!title || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>

</html>
