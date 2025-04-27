<?php
// Enable CORS for all origins (good for local testing)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight (CORS OPTIONS request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get raw POST input and decode it
// $rawData = file_get_contents("php://input");
// $data = json_decode($rawData, true);
$rawData = file_get_contents("php://input");
file_put_contents('php://stderr', "Received Data: " . $rawData . "\n");
$data = json_decode($rawData, true);

// Log raw input for debugging (optional)
// file_put_contents("debug_log.txt", "RAW DATA:\n$rawData\n\nPARSED:\n" . print_r($data, true));

// Validate data structure
if (!is_array($data) || !isset($data['id']) || !isset($data['status'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing id or status"
    ]);
    exit();
}

$orderId = (int) $data['id'];
$status = trim($data['status']);

// Optional: Validate status value
$validStatuses = ['Pending', 'Preparing', 'Complete'];


$status = trim($data['status']);
if ($status === 'Complete') $status = 'Complete';
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid status. Allowed values: Pending, Preparing, Complete"
    ]);
    exit();
}

// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "ordermenu";

$conn = new mysqli($servername, $username, $password, $database);

// Check DB connection
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Update query using prepared statement
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("si", $status, $orderId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Order status updated successfully."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No order found with that ID or status is already the same."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Execute failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
