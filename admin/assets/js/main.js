// QuickConnect - Enhanced Main JavaScript File
document.addEventListener('DOMContentLoaded', function () {
    console.log('QuickConnect: Initializing...');

    // Initialize all components with error handling
    try {
        initNavigation();
        initHeroAnimations();
        initCounters();
        initContactForm();
        initCarousel();
        initScrollAnimations();
        initParallax();
        console.log('QuickConnect: All components initialized successfully');
    } catch (error) {
        console.error('QuickConnect: Initialization error:', error);
    }
});

// ==================== NAVIGATION FUNCTIONALITY ====================
function initNavigation() {
    try {
        const navbar = document.querySelector('.navbar');
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

        if (!navbar) {
            console.warn('Navigation: Navbar not found');
            return;
        }

        // Enhanced navbar scroll effect
        window.addEventListener('scroll', function () {
            try {
                if (window.scrollY > 50) {
                    navbar.classList.add('navbar-scrolled', 'scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled', 'scrolled');
                }
            } catch (error) {
                console.error('Navigation scroll error:', error);
            }
        });

        // Enhanced smooth scrolling for navigation links
        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                try {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);

                    if (targetSection) {
                        const offsetTop = targetSection.offsetTop - 70;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }

                    // Close mobile menu if open
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    const navbarCollapse = document.querySelector('.navbar-collapse');

                    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                        if (navbarToggler) {
                            navbarToggler.click();
                        }
                    }
                } catch (error) {
                    console.error('Navigation click error:', error);
                }
            });
        });

        // Active section highlighting
        window.addEventListener('scroll', updateActiveNavLink);

    } catch (error) {
        console.error('Navigation initialization error:', error);
    }
}

function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    let current = '';

    sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        const sectionHeight = section.offsetHeight;
        if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
            current = section.getAttribute('id');
        }
    });

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
}

// ==================== HERO SECTION ANIMATIONS ====================
function initHeroAnimations() {
    try {
        // Fade in hero content
        const heroTitle = document.querySelector('.hero-title');
        const heroSubtitle = document.querySelector('.hero-subtitle');
        const heroButtons = document.querySelector('.hero-buttons');

        if (heroTitle) {
            setTimeout(() => heroTitle.classList.add('animate-fade-in'), 300);
        }
        if (heroSubtitle) {
            setTimeout(() => heroSubtitle.classList.add('animate-fade-in'), 600);
        }
        if (heroButtons) {
            setTimeout(() => heroButtons.classList.add('animate-fade-in'), 900);
        }

        // Scroll indicator animation
        const scrollIndicator = document.querySelector('.scroll-indicator');
        if (scrollIndicator) {
            scrollIndicator.addEventListener('click', function (e) {
                e.preventDefault();
                const aboutSection = document.querySelector('#about');
                if (aboutSection) {
                    aboutSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

    } catch (error) {
        console.error('Hero animations error:', error);
    }
}

// ==================== ENHANCED COUNTER ANIMATION ====================
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };

    const counterObserver = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                if (!counter.classList.contains('animated')) {
                    counter.classList.add('animated');
                    animateCounter(counter);
                }
                counterObserver.unobserve(counter);
            }
        });
    }, observerOptions);

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000;
    const increment = target / (duration / 16); // 60 FPS
    let current = 0;

    const updateCounter = () => {
        if (current < target) {
            current += increment;
            let displayValue = Math.ceil(current);

            // Format the display value
            if (element.getAttribute('data-suffix') === '%') {
                displayValue = displayValue + '%';
            } else if (displayValue >= 1000) {
                displayValue = (displayValue / 1000).toFixed(1) + 'K';
            }

            element.textContent = displayValue;
            requestAnimationFrame(updateCounter);
        } else {
            // Final display value
            let finalValue = target;
            if (element.getAttribute('data-suffix') === '%') {
                finalValue = target + '%';
            } else if (target >= 1000) {
                finalValue = (target / 1000).toFixed(1) + 'K';
            } else if (target === 98) {
                finalValue = '98%';
            }
            element.textContent = finalValue;
        }
    };

    updateCounter();
}

// ==================== ENHANCED CONTACT FORM FUNCTIONALITY ====================
function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Reset previous error states
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Enhanced validation with improved error handling
        if (!validateForm()) {
            return;
        }

        // Show loading state
        setButtonLoading(true);

        try {
            const formData = new FormData(form);

            // Add CSRF token if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('csrf_token', csrfToken.getAttribute('content'));
            }

            const response = await fetch('contact_submit.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Enhanced response handling with better error reporting
            const text = await response.text();
            console.log('Raw response:', text); // Debug logging

            let data;
            try {
                data = JSON.parse(text);
                console.log('Parsed response:', data); // Debug logging
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response text:', text);
                throw new Error('Server returned invalid JSON response: ' + text.substring(0, 200));
            }

            if (data.success) {
                // Show success modal
                showSuccessModal(data.message || 'Thank you! Your message has been sent successfully.');

                // Reset form
                resetForm();

                // Track successful submission (if analytics available)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        event_category: 'Contact',
                        event_label: 'Success'
                    });
                }

            } else {
                // Show error message with debug info if available
                let errorMessage = data.message || 'An error occurred. Please try again.';
                showErrorMessage(errorMessage);

                // Log debug info if available
                if (data.debug && data.debug.length > 0) {
                    console.log('Debug info:', data.debug);
                }
            }

        } catch (error) {
            console.error('Form submission error:', error);
            showErrorMessage('There was an error sending your message: ' + error.message);

            // Track failed submission
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_submit', {
                    event_category: 'Contact',
                    event_label: 'Error'
                });
            }
        } finally {
            setButtonLoading(false);
        }
    });

    // Real-time validation
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });

        input.addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });

    function setButtonLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            if (btnText) btnText.classList.add('d-none');
            if (btnLoading) btnLoading.classList.remove('d-none');
        } else {
            submitBtn.disabled = false;
            if (btnText) btnText.classList.remove('d-none');
            if (btnLoading) btnLoading.classList.add('d-none');
        }
    }

    function resetForm() {
        form.reset();
        form.classList.remove('was-validated');

        // Clear validation classes
        const fields = form.querySelectorAll('.form-control');
        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });

        // Remove any error alerts
        const errorAlert = document.getElementById('form-error-alert');
        if (errorAlert) {
            errorAlert.remove();
        }
    }

    function showErrorMessage(message) {
        // Create or update error alert
        let errorAlert = document.getElementById('form-error-alert');

        if (!errorAlert) {
            errorAlert = document.createElement('div');
            errorAlert.id = 'form-error-alert';
            errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span class="error-message"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            form.appendChild(errorAlert);
        }

        errorAlert.querySelector('.error-message').textContent = message;
        errorAlert.classList.remove('d-none');

        // Auto hide after 8 seconds
        setTimeout(() => {
            if (errorAlert && !errorAlert.classList.contains('d-none')) {
                const alert = new bootstrap.Alert(errorAlert);
                alert.close();
            }
        }, 8000);
    }
}

function validateForm() {
    const form = document.getElementById('contactForm');
    const requiredFields = ['firstName', 'lastName', 'email', 'message'];
    let isValid = true;

    // Enhanced validation for required fields
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && !validateField(field)) {
            isValid = false;
        }
    });

    // Additional validation for other fields
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';

    // Check required fields
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }

    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }

    // Phone validation
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        message = 'Please enter a valid phone number';
    }

    // Message length validation
    if (field.name === 'message' && value && value.length < 10) {
        isValid = false;
        message = 'Message must be at least 10 characters long';
    }

    // Name validation (no numbers or special chars)
    if ((field.name === 'firstName' || field.name === 'lastName') && value && !/^[a-zA-Z\s]*$/.test(value)) {
        isValid = false;
        message = 'Name should only contain letters and spaces';
    }

    // Update field state
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        if (feedback) feedback.textContent = message;
    }

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

function showSuccessModal(message) {
    // Try to use existing modal first
    let modal = document.getElementById('successModal');
    if (modal) {
        // Update message in existing modal
        const messageElement = modal.querySelector('.modal-message, .modal-body p');
        if (messageElement) {
            messageElement.textContent = message;
        }

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        return;
    }

    // Create modal if it doesn't exist
    modal = createSuccessModal();
    document.body.appendChild(modal);

    // Update message
    const messageElement = modal.querySelector('.modal-message');
    if (messageElement) {
        messageElement.textContent = message;
    }

    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function createSuccessModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'successModal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'successModalLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 text-center">
                    <div class="w-100">
                        <div class="success-icon mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="modal-title" id="successModalLabel">Thank You!</h4>
                    </div>
                </div>
                <div class="modal-body text-center">
                    <p class="modal-message">Your message has been sent successfully. We'll get back to you soon!</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;

    return modal;
}

// ==================== CAROUSEL FUNCTIONALITY ====================
function initCarousel() {
    try {
        const carousel = document.querySelector('#testimonialsCarousel');
        if (!carousel) return;

        // Auto-advance carousel every 5 seconds
        setInterval(() => {
            const nextButton = carousel.querySelector('.carousel-control-next');
            if (nextButton) {
                nextButton.click();
            }
        }, 5000);

        // Pause on hover
        carousel.addEventListener('mouseenter', function () {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                bsCarousel.pause();
            }
        });

        carousel.addEventListener('mouseleave', function () {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                bsCarousel.cycle();
            }
        });

    } catch (error) {
        console.error('Carousel initialization error:', error);
    }
}

// ==================== SCROLL ANIMATIONS ====================
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll, .fade-in-up, .fade-in-left, .fade-in-right');

    const animationObserver = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                animationObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    animatedElements.forEach(element => {
        animationObserver.observe(element);
    });
}

// ==================== PARALLAX EFFECT ====================
function initParallax() {
    const heroSection = document.getElementById('home');
    const parallaxElements = document.querySelectorAll('.parallax-element');

    function updateParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;

        if (heroSection) {
            heroSection.style.transform = `translateY(${rate}px)`;
        }

        parallaxElements.forEach(element => {
            const speed = element.getAttribute('data-speed') || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }

    // Throttle scroll events for better performance
    let ticking = false;
    window.addEventListener('scroll', function () {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
            setTimeout(() => { ticking = false; }, 16);
        }
    });
}

// ==================== UTILITY FUNCTIONS ====================
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed custom-alert`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';

    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 8 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 8000);
}

function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return unsafe;
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ==================== PAGE LOADING ====================
window.addEventListener('load', function () {
    // Hide loading spinner if it exists
    const loader = document.querySelector('.page-loader');
    if (loader) {
        loader.classList.add('fade-out');
        setTimeout(() => loader.remove(), 500);
    }

    // Trigger initial animations
    const heroSection = document.querySelector('#home');
    if (heroSection) {
        heroSection.classList.add('loaded');
    }
});

// ==================== PERFORMANCE OPTIMIZATIONS ====================
// Lazy load images
document.addEventListener('DOMContentLoaded', function () {
    const lazyImages = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function (entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        lazyImages.forEach(img => {
            img.src = img.getAttribute('data-src');
        });
    }
});

// ==================== ERROR HANDLING ====================
window.addEventListener('error', function (e) {
    console.error('JavaScript error:', e.error);
    // You can send error reports to your analytics service here
});

window.addEventListener('unhandledrejection', function (e) {
    console.error('Unhandled promise rejection:', e.reason);
    // You can send error reports to your analytics service here
});

// ==================== EXPORT FOR EXTERNAL USE ====================
window.QuickConnect = {
    showAlert,
    validateForm,
    showSuccessModal,
    escapeHtml
};
