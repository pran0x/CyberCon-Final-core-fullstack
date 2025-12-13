# CyberCon25 - PHP Website

Premier Cybersecurity Conference organized by Cyber Security Club - Uttara University

## Project Structure

```
.
├── action.php              # Form handler (registration & contact)
├── app/
│   └── classes/
│       └── Home.php        # Home controller
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── script.js      # Main JavaScript
│   ├── images/             # All images (logo, gallery, sponsors, etc.)
│   └── ticket/             # Ticket assets
├── composer.json           # Composer autoload configuration
├── index.php               # Main entry point
├── pages/
│   ├── home.php           # Home page template
│   └── terms.php          # Terms & conditions page
├── vendor/                 # Composer autoload files
└── README.md              # This file
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- Composer
- Web server (Apache/Nginx)

### Setup

1. Clone or download the project
2. Run: composer install
3. Configure web server to point to project root
4. Access via: http://localhost

## Running the Application

Development Server:
```bash
php -S localhost:8000
```

## Available Pages

- Home: index.php or index.php?page=home
- Terms & Conditions: index.php?page=terms

## Features

✅ Clean PHP architecture with MVC pattern
✅ PSR-4 Autoloading via Composer
✅ Bootstrap 5 responsive design
✅ Form validation and sanitization
✅ Registration system with ticket generation
✅ Contact form handler

## Contact

Email: cybersecurity@club.uttara.ac.bd
Phone: +880 1919399235

© 2025 Cyber Security Club - Uttara University
