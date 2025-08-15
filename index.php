<?php
// Start session and include necessary files
session_start();

// Initialize arrays
$services = [];
$testimonials = [];
$db_connected = false;

// Try to include database connection
try {
    require_once 'config/database.php';

    if (isset($pdo) && $pdo !== null) {
        $db_connected = true;
        error_log("Database connection successful");
    } else {
        error_log("Database connection is null");
    }
} catch (Exception $e) {
    error_log("Error including database config: " . $e->getMessage());
}

// Load services from database
if ($db_connected && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Loaded " . count($services) . " services from database");
    } catch (PDOException $e) {
        error_log("Error loading services: " . $e->getMessage());
        $services = []; // Reset to empty array
    }
}

// Load testimonials from database
if ($db_connected && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
        $stmt->execute();
        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Loaded " . count($testimonials) . " testimonials from database");
    } catch (PDOException $e) {
        error_log("Error loading testimonials: " . $e->getMessage());
        $testimonials = []; // Reset to empty array
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickConnect - Transform Your Business Online</title>
    <meta name="description" content="Professional business landing page with integrated contact management system">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
        /* Additional CSS to fix text overflow issues */
        .testimonial-card {
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 0 15px;
            overflow: hidden;
        }

        .testimonial-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #666;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .testimonial-author h5 {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .testimonial-author p {
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .author-image {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .testimonial-rating {
            margin-top: 1rem;
        }

        #testimonialCarousel .carousel-control-prev,
        #testimonialCarousel .carousel-control-next {
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 123, 255, 0.1);
            border-radius: 50%;
            border: 2px solid #007bff;
        }

        #testimonialCarousel .carousel-control-prev {
            left: -70px;
        }

        #testimonialCarousel .carousel-control-next {
            right: -70px;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 20px;
            height: 20px;
            background-color: #007bff;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .testimonial-card {
                margin: 0 5px;
                padding: 1.5rem;
            }

            .testimonial-text {
                font-size: 1rem;
            }

            #testimonialCarousel .carousel-control-prev,
            #testimonialCarousel .carousel-control-next {
                display: none;
            }
        }

        /* Ensure carousel container doesn't overflow */
        .carousel-inner {
            overflow: hidden;
        }

        .carousel-item {
            transition: transform 0.6s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#home">
                <i class="fas fa-bolt text-primary"></i> QuickConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary ms-2 px-3" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row min-vh-100 align-items-center">
                <div class="col-lg-8 mx-auto text-center text-white">
                    <h1 class="hero-title mb-4 animate-fade-in">
                        Transform Your Business <span class="text-primary">Online</span>
                    </h1>
                    <p class="hero-subtitle mb-5 animate-fade-in-delay">
                        Professional landing pages with integrated contact management.
                        Increase your leads by 200% and streamline customer communication.
                    </p>
                    <div class="hero-stats mb-5">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3 class="counter" data-count="500">0</h3>
                                    <p>Happy Clients</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3 class="counter" data-count="1200">0</h3>
                                    <p>Projects Completed</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item">
                                    <h3 class="counter" data-count="98">0</h3>
                                    <p>Success Rate %</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-buttons">
                        <a href="#contact" class="btn btn-primary btn-lg me-3">Get Started</a>
                        <a href="#services" class="btn btn-outline-light btn-lg">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <a href="#about"><i class="fas fa-chevron-down"></i></a>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-image">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&h=400&fit=crop"
                            alt="Our Team" class="img-fluid rounded shadow">
                        <div class="experience-badge">
                            <h4>5+</h4>
                            <p>Years Experience</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-header mb-4">
                        <h2 class="section-title">About QuickConnect</h2>
                        <p class="section-subtitle">Revolutionizing Business Communication</p>
                    </div>
                    <p class="lead mb-4">
                        We specialize in creating powerful business landing pages that don't just look great—they
                        convert visitors into customers and streamline your entire communication process.
                    </p>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="feature-item mb-3">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Responsive Design</span>
                            </div>
                            <div class="feature-item mb-3">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>SEO Optimized</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item mb-3">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Contact Management</span>
                            </div>
                            <div class="feature-item mb-3">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Analytics Dashboard</span>
                            </div>
                        </div>
                    </div>
                    <a href="#contact" class="btn btn-primary">Start Your Project</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">Complete Digital Solutions for Your Business</p>
            </div>
            <div class="row" id="services-container">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="service-card h-100">
                                <div class="service-icon">
                                    <i class="<?php echo htmlspecialchars($service['icon'] ?? 'fas fa-cog'); ?>"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($service['title'] ?? ''); ?></h4>
                                <p><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
                                <?php if (!empty($service['price'])): ?>
                                    <div class="service-price">
                                        <span class="price">$<?php echo htmlspecialchars($service['price']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($service['features'])): ?>
                                    <div class="service-features">
                                        <ul class="list-unstyled">
                                            <?php
                                            $features = is_string($service['features']) ? json_decode($service['features'], true) : $service['features'];
                                            if (is_array($features)):
                                                foreach ($features as $feature):
                                            ?>
                                                    <li><i class="fas fa-check text-primary me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                                            <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default services if none found in database -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="service-card h-100">
                            <div class="service-icon">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <h4>Web Development</h4>
                            <p>Custom websites and web applications built with modern technologies.</p>
                            <div class="service-price">
                                <span class="price">Starting at $999</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="service-card h-100">
                            <div class="service-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4>Mobile Apps</h4>
                            <p>Native and cross-platform mobile applications for iOS and Android.</p>
                            <div class="service-price">
                                <span class="price">Starting at $1499</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="service-card h-100">
                            <div class="service-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4>Digital Marketing</h4>
                            <p>SEO, social media marketing, and online advertising campaigns.</p>
                            <div class="service-price">
                                <span class="price">Starting at $799</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">What Our Clients Say</h2>
                <p class="section-subtitle">Trusted by businesses worldwide</p>
            </div>
            <div class="row">
                <div class="col-lg-10 col-xl-8 mx-auto">
                    <div id="testimonialCarousel" class="carousel slide position-relative" data-bs-ride="carousel"
                        data-bs-interval="5000">
                        <div class="carousel-inner" id="testimonials-container">
                            <?php if (!empty($testimonials)): ?>
                                <?php foreach ($testimonials as $index => $testimonial): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="testimonial-card text-center">
                                            <div class="testimonial-content">
                                                <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                                                <p class="testimonial-text">
                                                    "<?php echo htmlspecialchars($testimonial['testimonial_text'] ?? ''); ?>"
                                                </p>
                                                <div class="testimonial-author">
                                                    <?php if (!empty($testimonial['client_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($testimonial['client_image']); ?>"
                                                            alt="<?php echo htmlspecialchars($testimonial['client_name'] ?? ''); ?>"
                                                            class="author-image">
                                                    <?php endif; ?>
                                                    <h5><?php echo htmlspecialchars($testimonial['client_name'] ?? ''); ?></h5>
                                                    <p class="text-muted">
                                                        <?php echo htmlspecialchars($testimonial['client_position'] ?? ''); ?>
                                                        <?php if (!empty($testimonial['client_company'])): ?>
                                                            at <?php echo htmlspecialchars($testimonial['client_company']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if (!empty($testimonial['rating'])): ?>
                                                        <div class="testimonial-rating">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Default testimonial if none found in database -->
                                <div class="carousel-item active">
                                    <div class="testimonial-card text-center">
                                        <div class="testimonial-content">
                                            <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                                            <p class="testimonial-text">
                                                "QuickConnect transformed our online presence completely. Our leads increased by 300% in just 3 months!"
                                            </p>
                                            <div class="testimonial-author">
                                                <h5>John Smith</h5>
                                                <p class="text-muted">CEO at TechCorp</p>
                                                <div class="testimonial-rating">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (count($testimonials) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-primary text-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">Ready to transform your business? Let's talk!</p>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="contact-form-wrapper">
                        <form id="contactForm" class="contact-form" action="contact_submit.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="firstName" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="firstName" name="first_name"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="lastName" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="company" class="form-label">Company</label>
                                        <input type="text" class="form-control" id="company" name="company">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="inquiryType" class="form-label">Inquiry Type</label>
                                        <select class="form-control" id="inquiryType" name="inquiry_type">
                                            <option value="general">General Inquiry</option>
                                            <option value="sales">Sales</option>
                                            <option value="support">Support</option>
                                            <option value="partnership">Partnership</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-group">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5"
                                        required></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-light btn-lg">
                                    <span class="btn-text">Send Message</span>
                                    <span class="btn-loading d-none">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Sending...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> QuickConnect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <h4>Thank You!</h4>
                    <p>Your message has been sent successfully. We'll get back to you soon!</p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
</body>

</html>