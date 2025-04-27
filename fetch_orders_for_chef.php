<?php

// Enable CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$database = "ordermenu";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit;
}

// Fetch the orders from the database (only Pending or Preparing orders)
$query = "SELECT id, table_number, item_name, quantity, status FROM orders WHERE status IN ('Pending', 'Preparing')";
$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    echo json_encode(["success" => false, "message" => "Failed to fetch orders: " . $conn->error]);
    exit;
}

// Fetch all orders
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Successful fetch
echo json_encode(["success" => true, "orders" => $orders]);

// Close connection
$conn->close();
?>
