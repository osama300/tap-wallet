<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

if (!$amount || $amount < 1 || $amount > 10000) {
    header('Location: index.php?error=invalid_amount');
    exit;
}

$amount = round($amount, 2);
$user   = get_user_simple();
$txn_id = create_pending_transaction((int) $user['id'], $amount);

// Build Tap charge payload
$payload = [
    'amount'   => $amount,
    'currency' => TAP_CURRENCY,
    'customer' => [
        'first_name' => explode(' ', $user['name'])[0],
        'last_name'  => explode(' ', $user['name'])[1] ?? '',
        'email'      => $user['email'],
    ],
    'source'    => ['id' => 'src_all'],   // show all available payment methods
    'redirect'  => ['url' => CALLBACK_URL . '?txn=' . $txn_id],
    'reference' => ['transaction' => 'WAL-TXN-' . $txn_id],
    'metadata'  => ['txn_id' => $txn_id, 'user_id' => $user['id']],
];

// Call Tap API
$ch = curl_init(TAP_API_BASE . '/charges');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . TAP_SECRET_KEY,
        'Content-Type: application/json',
        'accept: application/json',
    ],
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($http_code !== 200 || empty($data['transaction']['url'])) {
    $error = $data['errors'][0]['description'] ?? 'فشل الاتصال بالبوابة';
    update_transaction($txn_id, '', 'failed');
    header('Location: index.php?error=' . urlencode($error));
    exit;
}

// Save Tap charge ID immediately
update_transaction($txn_id, $data['id'], 'pending');

// Redirect customer to Tap payment page
header('Location: ' . $data['transaction']['url']);
exit;
