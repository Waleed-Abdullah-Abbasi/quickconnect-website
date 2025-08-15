<?php
require_once 'middleware/auth.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Get statistics
$stats = [
    'contacts' => 0,
    'services' => 0,
    'testimonials' => 0
];

try {
    // Get contact count
    $stmt = $pdo->query("SELECT COUNT(*) FROM contacts");
    $stats['contacts'] = $stmt->fetchColumn();

    // Get services count
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    $stats['services'] = $stmt->fetchColumn();

    // Get testimonials count
    $stmt = $pdo->query("SELECT COUNT(*) FROM testimonials");
    $stats['testimonials'] = $stmt->fetchColumn();

    // Get recent contacts
    $stmt = $pdo->prepare("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_contacts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recent_contacts = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickConnect Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .recent-contacts-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 0;
            transition: background 0.3s ease;
        }

        .contact-item:hover {
            background: rgba(0, 123, 255, 0.05);
            border-radius: 8px;
            margin: 0 -1rem;
            padding: 1rem;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
                <div class="position-sticky pt-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>QuickConnect
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contacts.php">
                                <i class="fas fa-envelope me-2"></i>Contacts
                                <?php if ($stats['contacts'] > 0): ?>
                                    <span class="badge bg-light text-dark ms-2"><?php echo $stats['contacts']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="services.php">
                                <i class="fas fa-cogs me-2"></i>Services
                                <span class="badge bg-light text-dark ms-2"><?php echo $stats['services']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="testimonials.php">
                                <i class="fas fa-star me-2"></i>Testimonials
                                <span class="badge bg-light text-dark ms-2"><?php echo $stats['testimonials']; ?></span>
                            </a>
                        </li>
                        <hr class="text-white-50">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>View Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h1 class="h2 mb-3">
                        <i class="fas fa-wave-square me-2"></i>Welcome back, Admin!
                    </h1>
                    <p class="mb-0">Here's what's happening with your QuickConnect platform today.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-primary me-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['contacts']; ?></h3>
                                    <p class="text-muted mb-0">Total Contacts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-success me-3">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['services']; ?></h3>
                                    <p class="text-muted mb-0">Active Services</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon bg-warning me-3">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['testimonials']; ?></h3>
                                    <p class="text-muted mb-0">Testimonials</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Contacts -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card recent-contacts-card">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2 text-primary"></i>Recent Contacts
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_contacts)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No contacts yet. They'll appear here when visitors submit the contact form.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_contacts as $contact): ?>
                                        <div class="contact-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-user me-2 text-primary"></i>
                                                        <?php echo htmlspecialchars($contact['name']); ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted">
                                                        <i class="fas fa-envelope me-2"></i>
                                                        <?php echo htmlspecialchars($contact['email']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <?php echo htmlspecialchars(substr($contact['message'], 0, 100)); ?>
                                                        <?php if (strlen($contact['message']) > 100): ?>...<?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($contact['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="contacts.php" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i>View All Contacts
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4">
                        <div class="card recent-contacts-card">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="services.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-2"></i>Add New Service
                                    </a>
                                    <a href="testimonials.php" class="btn btn-outline-success">
                                        <i class="fas fa-star me-2"></i>Add Testimonial
                                    </a>
                                    <a href="contacts.php" class="btn btn-outline-info">
                                        <i class="fas fa-envelope me-2"></i>View Messages
                                    </a>
                                    <hr>
                                    <a href="../index.php" target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-external-link-alt me-2"></i>Preview Site
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>