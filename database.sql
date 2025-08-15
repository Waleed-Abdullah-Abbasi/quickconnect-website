-- Create database
CREATE DATABASE IF NOT EXISTS quickconnect_db;

USE quickconnect_db;

-- Contacts table
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    service VARCHAR(100) DEFAULT 'general',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(100) NOT NULL,
    price VARCHAR(50),
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Testimonials table
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(200) NOT NULL,
    company VARCHAR(200),
    position VARCHAR(200),
    testimonial TEXT NOT NULL,
    rating INT DEFAULT 5,
    client_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO
    admin_users (username, password, email)
VALUES (
        'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin@quickconnect.com'
    );

-- Insert sample services
INSERT INTO
    services (
        title,
        description,
        icon,
        price,
        features,
        display_order
    )
VALUES (
        'Landing Page Design',
        'Professional, conversion-optimized landing pages that capture leads and drive sales.',
        'fas fa-paint-brush',
        'Starting at $499',
        '["Responsive Design", "SEO Optimized", "Contact Forms", "Analytics Integration"]',
        1
    ),
    (
        'Contact Management',
        'Complete CRM system to manage and track all your customer interactions.',
        'fas fa-users',
        'Starting at $299',
        '["Lead Tracking", "Email Integration", "Custom Fields", "Reporting Dashboard"]',
        2
    ),
    (
        'SEO Optimization',
        'Boost your search engine rankings and drive organic traffic to your site.',
        'fas fa-search',
        'Starting at $399',
        '["Keyword Research", "On-page SEO", "Technical SEO", "Performance Monitoring"]',
        3
    ),
    (
        'Analytics & Reporting',
        'Comprehensive analytics to track your website performance and visitor behavior.',
        'fas fa-chart-line',
        'Starting at $199',
        '["Google Analytics", "Custom Dashboards", "Conversion Tracking", "Monthly Reports"]',
        4
    );

-- Insert sample testimonials
INSERT INTO
    testimonials (
        client_name,
        company,
        position,
        testimonial,
        rating,
        display_order
    )
VALUES (
        'Sarah Johnson',
        'Tech Innovations Inc.',
        'Marketing Director',
        'QuickConnect transformed our online presence completely. Our lead generation increased by 300% within the first month!',
        5,
        1
    ),
    (
        'Michael Chen',
        'Global Solutions Ltd.',
        'CEO',
        'The team delivered exactly what we needed. Professional, efficient, and the results speak for themselves.',
        5,
        2
    ),
    (
        'Emma Rodriguez',
        'Creative Agency Pro',
        'Founder',
        'Outstanding service and support. The contact management system has streamlined our entire customer communication process.',
        5,
        3
    );