<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$tap_id = $_GET['tap_id'] ?? '';
$txn_id = (int) ($_GET['txn']  ?? 0);

if (empty($tap_id) || $txn_id <= 0) {
    header('Location: index.php?error=invalid_callback');
    exit;
}

// Prevent double-credit
if (transaction_already_captured($tap_id)) {
    header('Location: index.php?success=already_credited');
    exit;
}

// Verify charge with Tap API
$ch = curl_init(TAP_API_BASE . '/charges/' . $tap_id);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . TAP_SECRET_KEY,
        'accept: application/json',
    ],
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$charge = json_decode($response, true);

if ($http_code !== 200 || empty($charge['id'])) {
    update_transaction($txn_id, $tap_id, 'verify_failed');
    header('Location: index.php?error=verify_failed');
    exit;
}

$status = $charge['status'] ?? '';

if ($status === 'CAPTURED') {
    $amount = (float) $charge['amount'];
    $user   = get_user_simple();

    update_transaction($txn_id, $tap_id, 'captured');
    credit_wallet((int) $user['id'], $amount);

    header('Location: index.php?success=' . $amount);
    exit;
}

// Any other status (CANCELLED, FAILED, etc.)
update_transaction($txn_id, $tap_id, strtolower($status));
header('Location: index.php?error=' . urlencode('لم يتم الدفع — ' . $status));
exit;
