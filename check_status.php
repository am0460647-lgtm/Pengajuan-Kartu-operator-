<?php
require_once '../config/config.php';
header('Content-Type: application/json');
if (isset($_GET['invoice'])) {
    $invoice_id = $conn->real_escape_string($_GET['invoice']);
    $result = $conn->query("SELECT status FROM orders WHERE invoice_id = '$invoice_id'");
    $order = $result->fetch_assoc();
    if ($order) {
        echo json_encode(['status' => $order['status']]);
        exit();
    }
}
echo json_encode(['status' => 'Not Found']);
?>