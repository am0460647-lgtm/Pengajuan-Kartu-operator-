<?php
require_once '../config/config.php';
if (!isset($_GET['invoice'])) { die("Invoice tidak valid."); }
$invoice_id = $conn->real_escape_string($_GET['invoice']);

$result = $conn->query("SELECT * FROM orders WHERE invoice_id = '$invoice_id'");
$order = $result->fetch_assoc();

if (!$order) { die("Order tidak ditemukan."); }

if ($order['status'] == 'Pending') {
    // 1. Set status lokal ke Paid
    $conn->query("UPDATE orders SET status = 'Paid' WHERE invoice_id = '$invoice_id'");
    
    // 2. TEMBAK API OTOMATIS KE INFERPANEL
    $post_data = [
        'api_id'   => INFER_API_ID,
        'api_key'  => INFER_API_KEY,
        'action'   => 'add',
        'service'  => $order['service_id'],
        'target'   => $order['target'],
        'quantity' => $order['quantity']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, INFER_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $res_data = json_decode($response, true);
    
    // Jika server penyedia sukses menerima orderan
    if (isset($res_data['order'])) {
        $p_id = $res_data['order'];
        $conn->query("UPDATE orders SET status = 'Processing', provider_order_id = '$p_id' WHERE invoice_id = '$invoice_id'");
    } else {
        $error_msg = isset($res_data['error']) ? $conn->real_escape_string($res_data['error']) : 'API Error';
        $conn->query("UPDATE orders SET status = 'Error', provider_order_id = '$error_msg' WHERE invoice_id = '$invoice_id'");
    }
}
header("Location: ../payment.php?invoice=" . $invoice_id);
exit();
?>