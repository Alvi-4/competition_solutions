<?php

// Enable CORS if needed (especially if testing from a different device)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Database credentials
$servername = "localhost";
$username = "root"; // default for XAMPP
$password = ""; // default is empty for XAMPP
$database = "ordermenu";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit;
}

$rawData = file_get_contents("php://input");
file_put_contents('php://stderr', "Received Data: " . $rawData . "\n");
$data = json_decode($rawData, true);

// Check if data is valid
if (!isset($data['table']) || !isset($data['order'])) {
    echo json_encode(["success" => false, "message" => "Invalid data format. Please ensure table and order fields are provided."]);
    exit;
}

// Validate table number
$tableNumber = $data['table'];
if (!is_numeric($tableNumber) || $tableNumber <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid table number."]);
    exit;
}

// Validate order items
$orderItems = $data['order'];
if (empty($orderItems)) {
    echo json_encode(["success" => false, "message" => "No items in the order."]);
    exit;
}

foreach ($orderItems as $item) {
    if (!isset($item['item']) || !isset($item['quantity'])) {
        echo json_encode(["success" => false, "message" => "Each order item must have a valid 'item' and 'quantity'."]);
        exit;
    }
    if (empty($item['item']) || !is_numeric($item['quantity']) || $item['quantity'] <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid item name or quantity."]);
        exit;
    }
}

// Insert each item into the database
foreach ($orderItems as $item) {
    $itemName = $conn->real_escape_string($item['item']); // Sanitize input
    $quantity = $item['quantity'];

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO orders (table_number, item_name, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $tableNumber, $itemName, $quantity);

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to insert item: " . $stmt->error]);
        exit;
    }
}

// Successful insertion response
echo json_encode(["success" => true, "message" => "Order received successfully."]);

// Close connection
$conn->close();
?>
