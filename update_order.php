<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username = "root";
$password = "";
$database = "ordermenu";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$orderId = (int)$data['order_id'];
$status = $conn->real_escape_string($data['status']);

$query = "UPDATE orders SET status = '$status' WHERE order_id = $orderId";

if ($conn->query($query)) {
    echo json_encode(["success" => true, "message" => "Order updated"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Update failed"]);
}

$conn->close();
?>