<?php
/**
 * CyberCon Registration API
 * 
 * GET  /api/registrations.php - Fetch all registrations or filter by parameters
 * POST /api/registrations.php - Create a new registration
 * GET  /api/registrations.php?id={ticket_id} - Fetch specific registration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class RegistrationAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Handle GET requests - Fetch registrations
     */
    public function handleGet() {
        if (!$this->conn) {
            return $this->sendResponse(500, false, 'Database connection failed');
        }

        try {
            // Get specific registration by ticket_id
            if (isset($_GET['id'])) {
                $ticketId = htmlspecialchars($_GET['id']);
                $query = "SELECT * FROM registrations WHERE ticket_id = :ticket_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':ticket_id', $ticketId);
                $stmt->execute();
                
                $registration = $stmt->fetch();
                
                if ($registration) {
                    return $this->sendResponse(200, true, 'Registration found', $registration);
                } else {
                    return $this->sendResponse(404, false, 'Registration not found');
                }
            }

            // Get all registrations with optional filters
            $query = "SELECT * FROM registrations";
            $conditions = [];
            $params = [];

            // Filter by email
            if (isset($_GET['email'])) {
                $conditions[] = "email = :email";
                $params[':email'] = htmlspecialchars($_GET['email']);
            }

            // Filter by university
            if (isset($_GET['university'])) {
                $conditions[] = "university LIKE :university";
                $params[':university'] = '%' . htmlspecialchars($_GET['university']) . '%';
            }

            // Filter by status
            if (isset($_GET['status'])) {
                $conditions[] = "status = :status";
                $params[':status'] = htmlspecialchars($_GET['status']);
            }

            // Filter by date range
            if (isset($_GET['from_date'])) {
                $conditions[] = "registration_date >= :from_date";
                $params[':from_date'] = htmlspecialchars($_GET['from_date']);
            }

            if (isset($_GET['to_date'])) {
                $conditions[] = "registration_date <= :to_date";
                $params[':to_date'] = htmlspecialchars($_GET['to_date']) . ' 23:59:59';
            }

            // Add conditions to query
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            // Order by registration date (newest first)
            $query .= " ORDER BY registration_date DESC";

            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = ($page - 1) * $limit;
            
            $query .= " LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $registrations = $stmt->fetchAll();

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM registrations";
            if (!empty($conditions)) {
                $countQuery .= " WHERE " . implode(" AND ", $conditions);
            }
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch()['total'];

            return $this->sendResponse(200, true, 'Registrations fetched successfully', [
                'registrations' => $registrations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);

        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return $this->sendResponse(500, false, 'Failed to fetch registrations');
        }
    }

    /**
     * Handle POST requests - Create new registration
     */
    public function handlePost() {
        if (!$this->conn) {
            return $this->sendResponse(500, false, 'Database connection failed');
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $requiredFields = ['fullName', 'studentId', 'email', 'phone', 'university', 
                          'ticketType', 'paymentMethod', 'paymentNumber', 'transactionId'];
        
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                return $this->sendResponse(400, false, "Missing required field: $field");
            }
        }

        try {
            // Validate email domain
            $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return $this->sendResponse(400, false, 'Invalid email format');
            }

            $allowedDomains = ['cuet.ac.bd', 'aust.edu', 'du.ac.bd', 'buet.ac.bd', 'nsu.edu.bd'];
            $emailDomain = substr(strrchr($email, "@"), 1);
            if (!in_array($emailDomain, $allowedDomains)) {
                return $this->sendResponse(400, false, 'Email must be from an approved university domain');
            }

            // Check if email already registered
            $checkQuery = "SELECT id FROM registrations WHERE email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                return $this->sendResponse(409, false, 'Email already registered');
            }

            // Generate unique ticket ID
            $ticketId = $this->generateTicketId();

            // Prepare insert query
            $query = "INSERT INTO registrations 
                     (ticket_id, full_name, student_id, email, phone, university, 
                      ticket_type, payment_method, payment_number, transaction_id, status) 
                     VALUES 
                     (:ticket_id, :full_name, :student_id, :email, :phone, :university, 
                      :ticket_type, :payment_method, :payment_number, :transaction_id, 'pending')";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':ticket_id', $ticketId);
            $stmt->bindParam(':full_name', $input['fullName']);
            $stmt->bindParam(':student_id', $input['studentId']);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $input['phone']);
            $stmt->bindParam(':university', $input['university']);
            $stmt->bindParam(':ticket_type', $input['ticketType']);
            $stmt->bindParam(':payment_method', $input['paymentMethod']);
            $stmt->bindParam(':payment_number', $input['paymentNumber']);
            $stmt->bindParam(':transaction_id', $input['transactionId']);

            if ($stmt->execute()) {
                // Fetch the created registration
                $selectQuery = "SELECT * FROM registrations WHERE ticket_id = :ticket_id";
                $selectStmt = $this->conn->prepare($selectQuery);
                $selectStmt->bindParam(':ticket_id', $ticketId);
                $selectStmt->execute();
                $registration = $selectStmt->fetch();

                return $this->sendResponse(201, true, 'Registration successful', $registration);
            } else {
                return $this->sendResponse(500, false, 'Failed to create registration');
            }

        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            
            // Check for duplicate entry
            if ($e->getCode() == 23000) {
                return $this->sendResponse(409, false, 'Registration already exists');
            }
            
            return $this->sendResponse(500, false, 'Database error occurred');
        }
    }

    /**
     * Generate unique ticket ID
     * Format: CC-YYYY-XXXX (e.g., CC-2025-1A2B)
     */
    private function generateTicketId() {
        $year = date('Y');
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < 4; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return "CC-{$year}-{$randomString}";
    }

    /**
     * Send JSON response
     */
    private function sendResponse($statusCode, $success, $message, $data = null) {
        http_response_code($statusCode);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
}

// Initialize API and handle request
$api = new RegistrationAPI();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $api->handleGet();
        break;
    
    case 'POST':
        $api->handlePost();
        break;
    
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
