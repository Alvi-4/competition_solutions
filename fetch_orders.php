<?php
// Allow requests from any origin (you can specify a specific origin instead of '*' for better security)
header("Access-Control-Allow-Origin: *");
// Allow specific methods (GET, POST, etc.)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// Allow specific headers (such as Content-Type, Authorization)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests (needed for some requests like POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Fetch the orders from the database including the 'status'
$query = "SELECT table_number, item_name, quantity, status FROM orders";
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
