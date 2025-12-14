/**
 * CyberCon 2025 - JavaScript
 * Custom interactions and animations
 */

// ===================================
// Navbar Scroll Effect
// ===================================
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ===================================
// Smooth Scrolling
// ===================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '#navbarNav') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.offsetTop - navbarHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    navbarToggler.click();
                }
            }
        }
    });
});

// ===================================
// Scroll to Top Button
// ===================================
const scrollTopBtn = document.getElementById('scrollTop');

window.addEventListener('scroll', function() {
    if (window.scrollY > 300) {
        scrollTopBtn.classList.add('visible');
    } else {
        scrollTopBtn.classList.remove('visible');
    }
});

scrollTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// ===================================
// Particle Animation for Hero - Subtle minimalist style
// ===================================
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    const particleCount = 30; // Reduced for minimalist look
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.style.position = 'absolute';
        particle.style.width = Math.random() * 2 + 1 + 'px'; // Smaller particles
        particle.style.height = particle.style.width;
        particle.style.background = 'rgba(255, 255, 255, ' + (Math.random() * 0.2 + 0.1) + ')'; // White, subtle
        particle.style.borderRadius = '50%';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.boxShadow = '0 0 5px rgba(255, 255, 255, 0.2)';
        
        // Slow, subtle animation
        const duration = Math.random() * 10 + 15; // Slower: 15-25s
        const delay = Math.random() * 3;
        
        particle.style.animation = `floatParticle ${duration}s ${delay}s infinite ease-in-out`;
        
        particlesContainer.appendChild(particle);
    }
}

// Add particle animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes floatParticle {
        0%, 100% {
            transform: translate(0, 0);
            opacity: 0;
        }
        10% {
            opacity: 0.3;
        }
        90% {
            opacity: 0.3;
        }
        100% {
            transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===================================
// Intersection Observer for Animations
// ===================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Add animation to sections
document.addEventListener('DOMContentLoaded', function() {
    // Initialize particles
    createParticles();
    
    // Observe sections for scroll animations
    const animatedElements = document.querySelectorAll('.news-card, .speaker-card, .village-card, .pricing-card, .highlight-card');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Counter animation for stats (if needed)
    animateCounters();
    
    // Active nav link highlighting
    updateActiveNavLink();
    
    // Form validation
    setupFormValidation();
});

// ===================================
// Counter Animation
// ===================================
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
}

// ===================================
// Active Navigation Link
// ===================================
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    
    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });
}

// ===================================
// Form Validation
// ===================================
function setupFormValidation() {
    const contactForm = document.querySelector('.contact-form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const name = contactForm.querySelector('input[placeholder="Your Name"]').value;
            const email = contactForm.querySelector('input[type="email"]').value;
            const subject = contactForm.querySelector('input[placeholder="Subject"]').value;
            const message = contactForm.querySelector('textarea').value;
            
            // Simple validation
            if (!name || !email || !message) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showNotification('Please enter a valid email address.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            // Create form data
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('subject', subject);
            formData.append('message', message);
            
            // Submit via AJAX
            fetch('contact-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    contactForm.reset();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again later.', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
}

// ===================================
// Email Validation
// ===================================
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ===================================
// Notification System
// ===================================
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add notification styles
    notification.style.position = 'fixed';
    notification.style.top = '100px';
    notification.style.right = '20px';
    notification.style.padding = '1rem 1.5rem';
    notification.style.borderRadius = '10px';
    notification.style.background = type === 'success' ? 
        'linear-gradient(135deg, #00d4ff, #2e4aa8)' : 
        'linear-gradient(135deg, #ff6b35, #ff4500)';
    notification.style.color = '#fff';
    notification.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
    notification.style.zIndex = '10000';
    notification.style.animation = 'slideInRight 0.3s ease';
    notification.style.fontWeight = '600';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add notification animations
const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .notification-content i {
        font-size: 1.5rem;
    }
`;
document.head.appendChild(notificationStyle);

// ===================================
// Loading Animation (Optional)
// ===================================
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
    
    // Add smooth appearance
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.style.opacity = '0';
        setTimeout(() => {
            heroSection.style.transition = 'opacity 0.5s ease';
            heroSection.style.opacity = '1';
        }, 100);
    }
});

// ===================================
// Mobile Menu Enhancement
// ===================================
const navbarToggler = document.querySelector('.navbar-toggler');
const navbarCollapse = document.querySelector('.navbar-collapse');

if (navbarToggler) {
    navbarToggler.addEventListener('click', function() {
        // Add custom animation or tracking here if needed
    });
}

// ===================================
// Dynamic Year Update
// ===================================
function updateYear() {
    const yearElements = document.querySelectorAll('.current-year');
    const currentYear = new Date().getFullYear();
    yearElements.forEach(el => {
        el.textContent = currentYear;
    });
}

updateYear();

// ===================================
// Schedule Tab Animation
// ===================================
const scheduleTabs = document.querySelectorAll('#scheduleTabs button');
scheduleTabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(e) {
        const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
        const scheduleItems = targetPane.querySelectorAll('.schedule-item');
        
        scheduleItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-30px)';
            
            setTimeout(() => {
                item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, index * 100);
        });
    });
});

// ===================================
// Image Lazy Loading Enhancement
// ===================================
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ===================================
// Countdown Timer (Optional for event)
// ===================================
function startCountdown(targetDate) {
    const countdownElement = document.getElementById('countdown');
    if (!countdownElement) return;
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = targetDate - now;
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownElement.innerHTML = `
            <div class="countdown-item">
                <span class="countdown-value">${days}</span>
                <span class="countdown-label">Days</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-value">${hours}</span>
                <span class="countdown-label">Hours</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-value">${minutes}</span>
                <span class="countdown-label">Minutes</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-value">${seconds}</span>
                <span class="countdown-label">Seconds</span>
            </div>
        `;
        
        if (distance < 0) {
            clearInterval(countdownInterval);
            countdownElement.innerHTML = '<h3>Event Started!</h3>';
        }
    }
    
    updateCountdown();
    const countdownInterval = setInterval(updateCountdown, 1000);
}

// Example: Start countdown to September 15, 2025
// startCountdown(new Date('September 15, 2025 09:00:00').getTime());

// Typewriter Effect for Hero Title
var TxtType = function(el, toRotate, period) {
    this.toRotate = toRotate;
    this.el = el;
    this.loopNum = 0;
    this.period = parseInt(period, 10) || 2000;
    this.txt = '';
    this.tick();
    this.isDeleting = false;
};

TxtType.prototype.tick = function() {
    var i = this.loopNum % this.toRotate.length;
    var fullTxt = this.toRotate[i];

    if (this.isDeleting) {
        this.txt = fullTxt.substring(0, this.txt.length - 1);
    } else {
        this.txt = fullTxt.substring(0, this.txt.length + 1);
    }

    this.el.innerHTML = '<span class="wrap">'+this.txt+'</span>';

    var that = this;
    var delta = 200 - Math.random() * 100;

    if (this.isDeleting) { delta /= 2; }

    if (!this.isDeleting && this.txt === fullTxt) {
        delta = this.period;
        this.isDeleting = true;
    } else if (this.isDeleting && this.txt === '') {
        this.isDeleting = false;
        this.loopNum++;
        delta = 500;
    }

    setTimeout(function() {
        that.tick();
    }, delta);
};

window.addEventListener('DOMContentLoaded', function() {
    var elements = document.getElementsByClassName('typewrite');
    for (var i=0; i<elements.length; i++) {
        var toRotate = elements[i].getAttribute('data-type');
        var period = elements[i].getAttribute('data-period');
        if (toRotate) {
            new TxtType(elements[i], JSON.parse(toRotate), period);
        }
    }
    // CSS is now in cybercon-style.css - no need to inject
});

// ===================================
// Hero Slideshow Functionality
// ===================================
let slideIndex = 1;
let slideTimer;

// Initialize slideshow when page loads
document.addEventListener('DOMContentLoaded', function() {
    showSlide(slideIndex);
    // Auto-advance slides every 5 seconds
    startAutoSlide();
});

function changeSlide(n) {
    clearTimeout(slideTimer);
    showSlide(slideIndex += n);
    startAutoSlide();
}

function currentSlide(n) {
    clearTimeout(slideTimer);
    showSlide(slideIndex = n);
    startAutoSlide();
}

function showSlide(n) {
    let slides = document.getElementsByClassName("slide");
    let indicators = document.getElementsByClassName("indicator");
    
    if (!slides.length) return; // Exit if no slides found
    
    if (n > slides.length) {
        slideIndex = 1;
    }
    if (n < 1) {
        slideIndex = slides.length;
    }
    
    // Mark current active slide to slide out to the left
    for (let i = 0; i < slides.length; i++) {
        if (slides[i].classList.contains("active")) {
            slides[i].classList.add("slide-out");
            slides[i].classList.remove("active");
        }
    }
    
    // Deactivate all indicators
    for (let i = 0; i < indicators.length; i++) {
        indicators[i].classList.remove("active");
    }
    
    // Set new slide as active (slides in from right)
    slides[slideIndex - 1].classList.remove("slide-out");
    slides[slideIndex - 1].classList.add("active");
    
    // Activate current indicator
    if (indicators.length > 0) {
        indicators[slideIndex - 1].classList.add("active");
    }
}

function startAutoSlide() {
    slideTimer = setTimeout(function() {
        slideIndex++;
        showSlide(slideIndex);
        startAutoSlide();
    }, 5000); // Change slide every 5 seconds
}

// Pause slideshow on hover
document.addEventListener('DOMContentLoaded', function() {
    const slideshow = document.querySelector('.hero-slideshow');
    if (slideshow) {
        slideshow.addEventListener('mouseenter', function() {
            clearTimeout(slideTimer);
        });
        
        slideshow.addEventListener('mouseleave', function() {
            startAutoSlide();
        });
    }
});

// ===================================
// Registration Form Functions
// ===================================

// Show registration form
function showRegistrationForm(ticketType = 'Early Bird') {
    const formSection = document.getElementById('registration-form');
    const ticketTypeInput = document.getElementById('selectedTicketType');
    const ticketPriceInput = document.getElementById('ticketPrice');
    const passDisplay = document.getElementById('selectedPassDisplay');
    const priceDisplay = document.getElementById('selectedPriceDisplay');
    const paymentFeeDisplay = document.getElementById('paymentFeeDisplay');
    
    // Define ticket prices and names
    const ticketInfo = {
        'Early Bird': { price: '200 BDT', name: 'Student Pass (Early Bird)', amount: '200' },
        'Regular': { price: '300 BDT', name: 'Regular Pass', amount: '300' },
        'VIP': { price: '500 BDT', name: 'VIP Pass', amount: '500' }
    };
    
    const selected = ticketInfo[ticketType] || ticketInfo['Early Bird'];
    
    // Update form fields
    if (ticketTypeInput) ticketTypeInput.value = ticketType;
    if (ticketPriceInput) ticketPriceInput.value = selected.price;
    if (passDisplay) passDisplay.textContent = selected.name;
    if (priceDisplay) priceDisplay.textContent = '৳' + selected.price;
    if (paymentFeeDisplay) paymentFeeDisplay.textContent = selected.price;
    
    if (formSection) {
        formSection.style.display = 'block';
        // Scroll to form
        setTimeout(() => {
            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}

// Hide registration form
function hideRegistrationForm() {
    const formSection = document.getElementById('registration-form');
    if (formSection) {
        formSection.style.display = 'none';
    }
}

// Validate email domain
function validateEmailDomain(input) {
    const email = input.value;
    const validDomains = ['@uttara.ac.bd', '@uttarauniversity.edu.bd'];
    const isValid = validDomains.some(domain => email.endsWith(domain));
    
    if (email && !isValid) {
        input.setCustomValidity('Must use @uttara.ac.bd or @uttarauniversity.edu.bd email');
    } else {
        input.setCustomValidity('');
    }
}

// Format phone number
function formatPhoneNumber(input) {
    let value = input.value;
    
    // Ensure it starts with +880
    if (!value.startsWith('+880')) {
        value = '+880';
    }
    
    // Remove any non-digit characters except the + at the start
    value = '+880' + value.substring(4).replace(/\D/g, '');
    
    // Limit to 14 characters (+880 and 10 digits)
    if (value.length > 14) {
        value = value.substring(0, 14);
    }
    
    input.value = value;
}

// Submit registration form
function submitCyberConRegistration(event) {
    event.preventDefault();
    
    const form = document.getElementById('cyberconRegistrationForm');
    const formData = new FormData(form);
    const loadingOverlay = document.getElementById('loadingOverlayForm');
    const alertContainer = document.getElementById('form-alert-container');
    
    // Show loading overlay
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    // Clear previous alerts
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
    
    // Convert FormData to JSON
    const jsonData = {
        fullName: formData.get('fullName'),
        studentId: formData.get('studentId'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        university: formData.get('university'),
        department: formData.get('department'),
        batch: formData.get('batch'),
        section: formData.get('section'),
        ticketType: formData.get('ticketType'),
        paymentMethod: formData.get('paymentMethod'),
        paymentNumber: formData.get('paymentNumber'),
        transactionId: formData.get('transactionId')
    };
    
    // Submit to API
    fetch('/api/registrations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        if (data.success) {
            // Show success modal
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            document.getElementById('modalTicketId').textContent = data.data.ticket_id || 'CC-2025-XXXX';
            document.getElementById('modalEmail').textContent = data.data.email || '';
            modal.show();
            
            // Reset form
            form.reset();
            hideRegistrationForm();
        } else {
            // Show error alert
            if (alertContainer) {
                alertContainer.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        // Hide loading overlay
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        // Show error alert
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> An error occurred. Please try again later.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        console.error('Error:', error);
    });
    
    return false;
}

// Enable terms checkbox handler
document.addEventListener('DOMContentLoaded', function() {
    const agreeTerms = document.getElementById('agreeTerms');
    const submitBtn = document.getElementById('submitRegBtn');
    
    if (agreeTerms && submitBtn) {
        agreeTerms.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
        });
    }
});
