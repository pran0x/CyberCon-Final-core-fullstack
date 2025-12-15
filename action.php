<?php
/**
 * CyberCon25 - Action Handler
 * Process form submissions (Registration & Contact)
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set content type
header('Content-Type: application/json');

// Start session
session_start();

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
    
    switch ($action) {
        case 'register':
            handleRegistration();
            break;
        
        case 'contact':
            handleContact();
            break;
        
        default:
            $response['message'] = "Invalid action";
            echo json_encode($response);
            break;
    }
} else {
    $response['message'] = "Invalid request method";
    echo json_encode($response);
}

/**
 * Handle Registration Form
 */
function handleRegistration() {
    global $response;
    
    // Get form data
    $fullName = isset($_POST['fullName']) ? sanitize_input($_POST['fullName']) : '';
    $studentId = isset($_POST['studentId']) ? sanitize_input($_POST['studentId']) : '';
    $university = isset($_POST['university']) ? sanitize_input($_POST['university']) : '';
    $department = isset($_POST['department']) ? sanitize_input($_POST['department']) : '';
    $batch = isset($_POST['batch']) ? sanitize_input($_POST['batch']) : '';
    $section = isset($_POST['section']) ? sanitize_input($_POST['section']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $queries = isset($_POST['queries']) ? sanitize_input($_POST['queries']) : '';
    $paymentMethod = isset($_POST['paymentMethod']) ? sanitize_input($_POST['paymentMethod']) : '';
    $paymentNumber = isset($_POST['paymentNumber']) ? sanitize_input($_POST['paymentNumber']) : '';
    $transactionId = isset($_POST['transactionId']) ? sanitize_input($_POST['transactionId']) : '';
    $ticketPrice = isset($_POST['ticketPrice']) ? sanitize_input($_POST['ticketPrice']) : '200 BDT';
    
    // Validation
    $errors = array();
    
    if (empty($fullName) || strlen($fullName) < 2) {
        $errors[] = "Valid name is required";
    }
    
    if (empty($studentId)) {
        $errors[] = "Student ID is required";
    }
    
    if (empty($email) || !validate_email($email)) {
        $errors[] = "Valid university email is required";
    }
    
    // Check if email domain is correct
    if (!preg_match('/@(uttara\.ac\.bd|uttarauniversity\.edu\.bd)$/', $email)) {
        $errors[] = "Must use @uttara.ac.bd or @uttarauniversity.edu.bd email";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($transactionId)) {
        $errors[] = "Transaction ID is required";
    }
    
    if (empty($paymentMethod)) {
        $errors[] = "Payment method is required";
    }
    
    // Check for errors
    if (!empty($errors)) {
        $response['message'] = implode(", ", $errors);
        echo json_encode($response);
        return;
    }
    
    // Generate unique 4-digit ticket ID
    $ticketId = generateUniqueTicketId();
    
    // Here you would typically:
    // 1. Save to database
    // 2. Send confirmation email
    // 3. Generate ticket
    
    // For now, we'll simulate success
    $response['success'] = true;
    $response['message'] = "Registration successful!";
    $response['ticketId'] = $ticketId;
    $response['email'] = $email;
    
    // Optional: Save to file or database
    saveRegistration(array(
        'ticketId' => $ticketId,
        'fullName' => $fullName,
        'studentId' => $studentId,
        'email' => $email,
        'phone' => $phone,
        'queries' => $queries,
        'paymentMethod' => $paymentMethod,
        'paymentNumber' => $paymentNumber,
        'transactionId' => $transactionId,
        'ticketPrice' => $ticketPrice,
        'registrationDate' => date('Y-m-d H:i:s')
    ));
    
    echo json_encode($response);
}

/**
 * Handle Contact Form
 */
function handleContact() {
    global $response;
    
    // Get form data
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : 'Contact Form Submission';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    
    // Validation
    $errors = array();
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters";
    }
    
    if (empty($email) || !validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters";
    }
    
    // Check for errors
    if (!empty($errors)) {
        $response['message'] = implode(", ", $errors);
        echo json_encode($response);
        return;
    }
    
    // Email configuration
    $to = "cybersecurity@club.uttara.ac.bd";
    $email_subject = "CyberCon Contact: " . $subject;
    
    // Email body
    $email_body = "You have received a new message from the CyberCon website contact form.\n\n";
    $email_body .= "Here are the details:\n\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Subject: $subject\n\n";
    $email_body .= "Message:\n$message\n";
    
    // Email headers
    $headers = "From: noreply@cybercon.org\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email (uncomment when ready)
    // if (mail($to, $email_subject, $email_body, $headers)) {
        $response['success'] = true;
        $response['message'] = "Thank you! Your message has been sent successfully.";
    // } else {
    //     $response['message'] = "Sorry, there was an error sending your message.";
    // }
    
    echo json_encode($response);
}

/**
 * Generate unique 4-digit ticket ID
 */
function generateUniqueTicketId() {
    $attempts = 0;
    $maxAttempts = 10;
    $filename = __DIR__ . '/registrations.json';
    
    // Read existing registrations to check for duplicates
    $existingIds = [];
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $registrations = json_decode($content, true);
        if (is_array($registrations)) {
            foreach ($registrations as $reg) {
                if (isset($reg['ticketId'])) {
                    $existingIds[] = $reg['ticketId'];
                }
            }
        }
    }
    
    while ($attempts < $maxAttempts) {
        // Generate ID using time * random number, then get last 4 digits
        $timeComponent = microtime(true) * 1000; // milliseconds
        $randomComponent = rand(1000, 9999);
        $combined = $timeComponent * $randomComponent;
        
        // Get last 4 digits and ensure it's 4 digits
        $ticketId = str_pad(substr((string)abs((int)$combined), -4), 4, '0', STR_PAD_LEFT);
        
        // Check if ticket ID already exists
        if (!in_array($ticketId, $existingIds)) {
            return $ticketId;
        }
        
        $attempts++;
        usleep(1000); // Wait 1ms before retry
    }
    
    // Fallback: use timestamp-based ID
    return str_pad(substr((string)time(), -4), 4, '0', STR_PAD_LEFT);
}

/**
 * Save registration to file
 * (In production, you should save to a database)
 */
function saveRegistration($data) {
    $filename = __DIR__ . '/registrations.json';
    
    // Read existing data
    $registrations = array();
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $registrations = json_decode($content, true);
        if (!is_array($registrations)) {
            $registrations = array();
        }
    }
    
    // Add new registration
    $registrations[] = $data;
    
    // Save to file
    file_put_contents($filename, json_encode($registrations, JSON_PRETTY_PRINT));
}
