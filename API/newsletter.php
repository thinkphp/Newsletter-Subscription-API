<?php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$config = require __DIR__ . '/config-database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Database configuration
require_once('config-database.php');

// Function to send JSON response and exit
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Function to log errors
function logError($message) {
    error_log("[Newsletter Error] " . $message);
}

try {
    // Check if MySQLi extension is loaded
    if (!extension_loaded('mysqli')) {
        throw new Exception('Extensia MySQLi nu este instalată pe server');
    }

    // Connect to MySQL database
    $db = new mysqli(
        $config['host'], 
        $config['username'], 
        $config['password'], 
        $config['dbname']
    );

    // Check connection
    if ($db->connect_error) {
        throw new Exception('Nu se poate conecta la baza de date MySQL: ' . $db->connect_error);
    }

    // Set charset to UTF-8
    $db->set_charset("utf8mb4");

    // Create table if it doesn't exist
    $create_table = "
        CREATE TABLE IF NOT EXISTS newsletter_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if (!$db->query($create_table)) {
        throw new Exception('Nu s-a putut crea tabela newsletter_emails: ' . $db->error);
    }

    // Log for debugging (first time setup)
    logError("MySQL database connection successful");

} catch (Exception $e) {
    logError("Database connection error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Eroare la conectarea bazei de date: ' . $e->getMessage(),
        'debug_info' => [
            'mysqli_loaded' => extension_loaded('mysqli'),
            'host' => $db_config['host'],
            'database' => $db_config['database']
        ]
    ], 500);
}

// Process POST request (subscription)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $raw_input = file_get_contents('php://input');

        if (empty($raw_input)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nu s-au primit date'
            ], 400);
        }

        $input = json_decode($raw_input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Date JSON invalide: ' . json_last_error_msg()
            ], 400);
        }

        // Validate input
        if (empty($input['email'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Email-ul este obligatoriu'
            ], 400);
        }

        $email = trim($input['email']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Adresa de email nu este validă'
            ], 400);
        }

        // Check if email already exists
        $check_stmt = $db->prepare("SELECT id FROM newsletter_emails WHERE email = ?");
        if (!$check_stmt) {
            throw new Exception('Prepare statement failed: ' . $db->error);
        }
        
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            sendJsonResponse([
                'success' => false,
                'message' => 'Acest email este deja abonat'
            ], 409);
        }
        $check_stmt->close();

        // Get user IP address
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $user_ip = $_SERVER['HTTP_X_REAL_IP'];
        }

        // Insert email into database
        $insert_stmt = $db->prepare("INSERT INTO newsletter_emails (email, ip_address) VALUES (?, ?)");
        if (!$insert_stmt) {
            throw new Exception('Prepare statement failed: ' . $db->error);
        }

        $insert_stmt->bind_param('ss', $email, $user_ip);

        if ($insert_stmt->execute()) {
            $subscriber_id = $db->insert_id;
            $insert_stmt->close();

            // Log for debugging
            logError("Newsletter subscription: Email = $email, IP = $user_ip, ID = $subscriber_id");

            sendJsonResponse([
                'success' => true,
                'message' => 'Te-ai abonat cu succes!',
                'subscriber_id' => $subscriber_id
            ]);

        } else {
            $insert_stmt->close();
            throw new Exception('Nu s-a putut salva în baza de date: ' . $db->error);
        }

    } catch (Exception $e) {
        logError("POST request error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Eroare la procesarea cererii: ' . $e->getMessage()
        ], 500);
    }
}

// Process GET request (view subscribers - for admin)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // View all subscribers
        if (isset($_GET['admin']) && $_GET['admin'] === 'view') {
            $query = "SELECT id, email, created_at, ip_address FROM newsletter_emails ORDER BY created_at DESC";
            $result = $db->query($query);

            if (!$result) {
                throw new Exception('Query failed: ' . $db->error);
            }

            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = [
                    'id' => $row['id'],
                    'email' => $row['email'],
                    'created_at' => $row['created_at'],
                    'ip_address' => $row['ip_address']
                ];
            }

            sendJsonResponse([
                'success' => true,
                'emails' => $emails,
                'total' => count($emails)
            ]);
        }
        // Export CSV for admin
        elseif (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $query = "SELECT email, created_at FROM newsletter_emails ORDER BY created_at DESC";
            $result = $db->query($query);

            if (!$result) {
                throw new Exception('Query failed: ' . $db->error);
            }

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');

            // Create CSV output
            $output = fopen('php://output', 'w');

            // CSV header
            fputcsv($output, ['Email', 'Data Abonarii'], ';');

            // Data
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [$row['email'], $row['created_at']], ';');
            }

            fclose($output);
            $db->close();
            exit;
        }
        // Simple statistics
        elseif (isset($_GET['stats'])) {
            // Total number of subscribers
            $total_result = $db->query("SELECT COUNT(*) as count FROM newsletter_emails");
            $total_row = $total_result->fetch_assoc();
            $total_subscribers = $total_row['count'];

            // Subscribers from last week
            $week_result = $db->query("
                SELECT COUNT(*) as count FROM newsletter_emails
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $week_row = $week_result->fetch_assoc();
            $week_subscribers = $week_row['count'];

            // Subscribers from last month
            $month_result = $db->query("
                SELECT COUNT(*) as count FROM newsletter_emails
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $month_row = $month_result->fetch_assoc();
            $month_subscribers = $month_row['count'];

            // Last 5 subscriptions
            $recent_query = "SELECT email, created_at FROM newsletter_emails ORDER BY created_at DESC LIMIT 5";
            $recent_result = $db->query($recent_query);
            $recent_subscribers = [];
            while ($row = $recent_result->fetch_assoc()) {
                $recent_subscribers[] = $row;
            }

            sendJsonResponse([
                'success' => true,
                'stats' => [
                    'total_subscribers' => $total_subscribers,
                    'this_week' => $week_subscribers,
                    'this_month' => $month_subscribers,
                    'growth_rate' => $total_subscribers > 0 ? round(($week_subscribers / $total_subscribers) * 100, 2) : 0,
                    'recent_subscribers' => $recent_subscribers
                ]
            ]);
        }
        // Delete a subscriber (for admin)
        elseif (isset($_GET['delete']) && !empty($_GET['id'])) {
            $id = intval($_GET['id']);
            $delete_stmt = $db->prepare("DELETE FROM newsletter_emails WHERE id = ?");
            
            if (!$delete_stmt) {
                throw new Exception('Prepare statement failed: ' . $db->error);
            }
            
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                $affected_rows = $delete_stmt->affected_rows;
                $delete_stmt->close();
                
                if ($affected_rows > 0) {
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Abonatul a fost șters cu succes'
                    ]);
                } else {
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Nu s-a găsit abonatul cu ID-ul specificat'
                    ], 404);
                }
            } else {
                $delete_stmt->close();
                throw new Exception('Nu s-a putut șterge abonatul: ' . $db->error);
            }
        }
        // Test endpoint
        elseif (isset($_GET['check'])) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Newsletter API funcționează!',
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'mysqli_version' => mysqli_get_server_info($db),
                    'database' => $config['dbname'],
                    'host' => $config['host']
                ]
            ]);
        }
        else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Parametri GET nevalizi. Folosește ?test pentru testare.'
            ], 400);
        }

    } catch (Exception $e) {
        logError("GET request error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Eroare la procesarea cererii: ' . $e->getMessage()
        ], 500);
    }
}

// Unsupported HTTP method
else {
    sendJsonResponse([
        'success' => false,
        'message' => 'Metodă HTTP nesuportată: ' . $_SERVER['REQUEST_METHOD']
    ], 405);
}

// Close connection
if (isset($db)) {
    $db->close();
}
?>
