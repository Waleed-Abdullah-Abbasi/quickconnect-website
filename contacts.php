<?php
require_once 'middleware/auth.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Enhanced database structure check
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM contacts LIKE 'is_read'");
    $column_exists = $stmt->rowCount() > 0;

    if (!$column_exists) {
        // Add is_read column if it doesn't exist
        $pdo->exec("ALTER TABLE contacts ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        error_log("Added is_read column to contacts table");
    } else {
        // Verify the column type is correct
        $pdo->exec("ALTER TABLE contacts MODIFY COLUMN is_read TINYINT(1) DEFAULT 0");
    }
} catch (PDOException $e) {
    error_log("Database structure check error: " . $e->getMessage());
    // Continue execution even if column modification fails
}

// Handle contact deletion
if (isset($_POST['delete_contact'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$_POST['contact_id']]);
        $success_message = "Contact deleted successfully!";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $error_message = "Error deleting contact: " . $e->getMessage();
    }
}

// Handle mark as read/unread
if (isset($_POST['toggle_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET is_read = NOT COALESCE(is_read, 0) WHERE id = ?");
        $stmt->execute([$_POST['contact_id']]);
        $success_message = "Contact status updated!";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $error_message = "Error updating contact: " . $e->getMessage();
    }
}

// Handle reply functionality
if (isset($_POST['send_reply'])) {
    $contact_id = $_POST['contact_id'];
    $reply_message = $_POST['reply_message'];
    $contact_email = $_POST['contact_email'];
    $contact_name = $_POST['contact_name'];

    try {
        // Mark the contact as read when replying
        $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
        $stmt->execute([$contact_id]);

        // In a real implementation, you would send the email here
        // mail($contact_email, "Re: Your QuickConnect Inquiry", $reply_message, $headers);

        $success_message = "Reply sent successfully to " . htmlspecialchars($contact_name) . "!";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $error_message = "Error sending reply: " . $e->getMessage();
    }
}

// Get all contacts with improved filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

try {
    // Main contacts query (this part should work fine)
    $sql = "SELECT id, name, email, message, created_at, 
            CASE WHEN is_read IS NULL THEN 0 ELSE is_read END as is_read_safe 
            FROM contacts WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }

    if ($filter === 'unread') {
        $sql .= " AND (is_read IS NULL OR is_read = 0)";
    } elseif ($filter === 'read') {
        $sql .= " AND is_read = 1";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // FIXED: Statistics query with escaped column names
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read IS NULL OR is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`
                  FROM contacts";
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure stats array has proper structure
    if (!$stats) {
        $stats = ['total' => 0, 'unread' => 0, 'read' => 0];
    }
} catch (PDOException $e) {
    error_log("Contacts error: " . $e->getMessage());
    $contacts = [];
    $stats = ['total' => 0, 'unread' => 0, 'read' => 0];
    $error_message = "Error fetching contacts: " . $e->getMessage();
}

// Helper function to safely get is_read value
function getIsReadValue($contact)
{
    return isset($contact['is_read_safe']) ? (bool)$contact['is_read_safe'] : false;
}

// Function to generate reply template
function generateReplyTemplate($contact)
{
    $template = "Dear " . htmlspecialchars($contact['name']) . ",\n\n";
    $template .= "Thank you for contacting QuickConnect. We have received your message:\n\n";
    $template .= "\"" . htmlspecialchars($contact['message']) . "\"\n\n";
    $template .= "We appreciate your interest and will get back to you shortly with the information you requested.\n\n";
    $template .= "Best regards,\n";
    $template .= "QuickConnect Team";
    return $template;
}

if (isset($_POST['bulk_action']) && !empty($_POST['selected_contacts'])) {
    $action = $_POST['bulk_action'];
    $contact_ids = $_POST['selected_contacts'];

    // Validate that all IDs are numeric
    $contact_ids = array_filter($contact_ids, 'is_numeric');

    if (!empty($contact_ids)) {
        try {
            $placeholders = str_repeat('?,', count($contact_ids) - 1) . '?';

            if ($action === 'mark_read') {
                $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id IN ($placeholders)");
                $stmt->execute($contact_ids);
                $success_message = "Selected contacts marked as read!";
            } elseif ($action === 'mark_unread') {
                $stmt = $pdo->prepare("UPDATE contacts SET is_read = 0 WHERE id IN ($placeholders)");
                $stmt->execute($contact_ids);
                $success_message = "Selected contacts marked as unread!";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
                $stmt->execute($contact_ids);
                $success_message = "Selected contacts deleted!";
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit;
        } catch (PDOException $e) {
            error_log("Bulk action error: " . $e->getMessage());
            $error_message = "Error performing bulk action: " . $e->getMessage();
        }
    } else {
        $error_message = "No valid contacts selected for bulk action.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Management - QuickConnect Admin</title>
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

        .contact-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .contact-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .contact-card.unread {
            border-left: 4px solid #007bff;
            background: #f8f9ff;
        }

        .contact-card.read {
            opacity: 0.85;
            background: #f8f9fa;
        }

        .stats-card {
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .filter-tabs {
            border-bottom: 2px solid #f8f9fa;
        }

        .filter-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
        }

        .filter-tabs .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }

        .reply-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }

        .reply-form.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-lg {
            max-width: 800px;
        }

        .email-preview {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="contacts.php">
                                <i class="fas fa-envelope me-2"></i>Contacts
                                <?php if ($stats['total'] > 0): ?>
                                    <span class="badge bg-light text-dark ms-2"><?php echo $stats['total']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="services.php">
                                <i class="fas fa-cogs me-2"></i>Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="testimonials.php">
                                <i class="fas fa-star me-2"></i>Testimonials
                            </a>
                        </li>
                        <hr class="text-white-50">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.html" target="_blank">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-envelope me-2 text-primary"></i>Contact Management
                    </h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                <h3><?php echo $stats['total']; ?></h3>
                                <p class="mb-0">Total Contacts</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-envelope-open text-warning fa-2x mb-2"></i>
                                <h3 class="text-warning"><?php echo $stats['unread']; ?></h3>
                                <p class="mb-0 text-warning">Unread Messages</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <h3 class="text-success"><?php echo $stats['read']; ?></h3>
                                <p class="mb-0 text-success">Read Messages</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <ul class="nav nav-pills justify-content-center filter-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>"
                                            href="?filter=all&search=<?php echo urlencode($search); ?>">
                                            All (<?php echo $stats['total']; ?>)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>"
                                            href="?filter=unread&search=<?php echo urlencode($search); ?>">
                                            Unread (<?php echo $stats['unread']; ?>)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>"
                                            href="?filter=read&search=<?php echo urlencode($search); ?>">
                                            Read (<?php echo $stats['read']; ?>)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search contacts by name, email, or message..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Add this HTML after the search form for bulk actions -->
                <div class="row mb-3">
                    <div class="col-12">
                        <form method="POST" id="bulkActionForm">
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">Select All</label>
                                </div>
                                <select name="bulk_action" class="form-select" style="width: auto;">
                                    <option value="">Bulk Actions</option>
                                    <option value="mark_read">Mark as Read</option>
                                    <option value="mark_unread">Mark as Unread</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Contacts List -->
                <?php if (empty($contacts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">
                            <?php if (!empty($search) || $filter !== 'all'): ?>
                                No contacts found matching your criteria
                            <?php else: ?>
                                No contacts found
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted">
                            <?php if (!empty($search) || $filter !== 'all'): ?>
                                Try adjusting your search or filter settings.
                            <?php else: ?>
                                Contacts will appear here when visitors submit the contact form.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || $filter !== 'all'): ?>
                            <a href="contacts.php" class="btn btn-primary">
                                <i class="fas fa-refresh me-1"></i>View All Contacts
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($contacts as $contact):
                            $isRead = getIsReadValue($contact);
                        ?>
                            <div class="col-12 mb-3">
                                <div class="contact-card card <?php echo $isRead ? 'read' : 'unread'; ?> position-relative">
                                    <?php if (!$isRead): ?>
                                        <span class="badge bg-primary status-badge">New</span>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <!-- Add this right after the opening <div class="card-body"> -->
                                        <div class="position-absolute" style="top: 10px; left: 10px; z-index: 5;">
                                            <input class="form-check-input contact-checkbox" type="checkbox"
                                                name="selected_contacts[]" value="<?php echo $contact['id']; ?>">
                                        </div>
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="card-title mb-0 me-3">
                                                        <i class="fas fa-user me-2 text-primary"></i>
                                                        <?php echo htmlspecialchars($contact['name']); ?>
                                                    </h5>
                                                    <span class="badge <?php echo $isRead ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo $isRead ? 'Read' : 'Unread'; ?>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-envelope me-2"></i>
                                                    <?php echo htmlspecialchars($contact['email']); ?>
                                                </p>
                                                <p class="card-text">
                                                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Received: <?php echo date('M j, Y g:i A', strtotime($contact['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="btn-group-vertical" role="group">
                                                    <!-- Toggle Read Status -->
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                        <button type="submit" name="toggle_read"
                                                            class="btn btn-sm <?php echo $isRead ? 'btn-outline-secondary' : 'btn-outline-primary'; ?> mb-1">
                                                            <i class="fas fa-<?php echo $isRead ? 'eye-slash' : 'eye'; ?> me-1"></i>
                                                            <?php echo $isRead ? 'Mark Unread' : 'Mark Read'; ?>
                                                        </button>
                                                    </form>

                                                    <!-- Quick Reply Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-success mb-1"
                                                        onclick="toggleReplyForm(<?php echo $contact['id']; ?>)">
                                                        <i class="fas fa-reply me-1"></i>Quick Reply
                                                    </button>

                                                    <!-- Advanced Reply Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-info mb-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#replyModal<?php echo $contact['id']; ?>">
                                                        <i class="fas fa-edit me-1"></i>Compose Reply
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal<?php echo $contact['id']; ?>">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Reply Form -->
                                        <div id="replyForm<?php echo $contact['id']; ?>" class="reply-form">
                                            <form method="POST">
                                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                <input type="hidden" name="contact_email" value="<?php echo htmlspecialchars($contact['email']); ?>">
                                                <input type="hidden" name="contact_name" value="<?php echo htmlspecialchars($contact['name']); ?>">

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Quick Reply to <?php echo htmlspecialchars($contact['name']); ?>:</label>
                                                    <textarea name="reply_message" class="form-control" rows="4" placeholder="Type your reply here..." required><?php echo generateReplyTemplate($contact); ?></textarea>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="send_reply" class="btn btn-success btn-sm">
                                                        <i class="fas fa-paper-plane me-1"></i>Send Reply
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(<?php echo $contact['id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Reply Modal -->
                            <div class="modal fade" id="replyModal<?php echo $contact['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-reply me-2"></i>Compose Reply to <?php echo htmlspecialchars($contact['name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" id="advancedReplyForm<?php echo $contact['id']; ?>">
                                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                <input type="hidden" name="contact_email" value="<?php echo htmlspecialchars($contact['email']); ?>">
                                                <input type="hidden" name="contact_name" value="<?php echo htmlspecialchars($contact['name']); ?>">

                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">To:</label>
                                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($contact['email']); ?>" readonly>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Subject:</label>
                                                        <input type="text" class="form-control" value="Re: Your QuickConnect Inquiry" readonly>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Original Message:</label>
                                                    <div class="alert alert-light">
                                                        <small class="text-muted">From: <?php echo htmlspecialchars($contact['name']); ?> (<?php echo htmlspecialchars($contact['email']); ?>)</small><br>
                                                        <small class="text-muted">Date: <?php echo date('M j, Y g:i A', strtotime($contact['created_at'])); ?></small>
                                                        <hr>
                                                        <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Your Reply:</label>
                                                    <textarea name="reply_message" class="form-control" rows="8" required><?php echo generateReplyTemplate($contact); ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Email Preview:</label>
                                                    <div class="email-preview">
                                                        <div class="border-bottom pb-2 mb-2">
                                                            <strong>To:</strong> <?php echo htmlspecialchars($contact['email']); ?><br>
                                                            <strong>Subject:</strong> Re: Your QuickConnect Inquiry
                                                        </div>
                                                        <div id="previewContent<?php echo $contact['id']; ?>">
                                                            <?php echo nl2br(htmlspecialchars(generateReplyTemplate($contact))); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                            <button type="submit" form="advancedReplyForm<?php echo $contact['id']; ?>" name="send_reply" class="btn btn-success">
                                                <i class="fas fa-paper-plane me-1"></i>Send Reply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $contact['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this contact from <strong><?php echo htmlspecialchars($contact['name']); ?></strong>?</p>
                                            <p><small class="text-muted">This action cannot be undone.</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                <button type="submit" name="delete_contact" class="btn btn-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleReplyForm(contactId) {
            const form = document.getElementById('replyForm' + contactId);
            if (form.classList.contains('show')) {
                form.classList.remove('show');
            } else {
                // Hide all other reply forms
                document.querySelectorAll('.reply-form').forEach(f => f.classList.remove('show'));
                form.classList.add('show');
            }
        }

        // Update email preview in real-time
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('textarea[name="reply_message"]');
            textareas.forEach(textarea => {
                if (textarea.closest('.modal')) {
                    const contactId = textarea.closest('form').querySelector('input[name="contact_id"]').value;
                    const previewDiv = document.getElementById('previewContent' + contactId);

                    if (previewDiv) {
                        textarea.addEventListener('input', function() {
                            previewDiv.innerHTML = this.value.replace(/\n/g, '<br>');
                        });
                    }
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Add confirmation for bulk actions
        function confirmBulkAction(action) {
            return confirm(`Are you sure you want to ${action} the selected contacts?`);
        }

        // Search functionality enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            }
        });
        // Add this to your existing script section
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Select All functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const contactCheckboxes = document.querySelectorAll('.contact-checkbox');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    contactCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Update Select All when individual checkboxes change
            contactCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
                    selectAllCheckbox.checked = checkedBoxes.length === contactCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < contactCheckboxes.length;
                });
            });

            // Validate bulk action form
            const bulkActionForm = document.getElementById('bulkActionForm');
            if (bulkActionForm) {
                bulkActionForm.addEventListener('submit', function(e) {
                    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
                    const selectedAction = document.querySelector('select[name="bulk_action"]').value;

                    if (checkedBoxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one contact.');
                        return false;
                    }

                    if (!selectedAction) {
                        e.preventDefault();
                        alert('Please select an action.');
                        return false;
                    }

                    if (selectedAction === 'delete') {
                        if (!confirm('Are you sure you want to delete the selected contacts? This action cannot be undone.')) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>