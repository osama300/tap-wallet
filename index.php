<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$user         = get_user_simple();
$transactions = get_transactions((int) $user['id']);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$success_msg = '';
if ($success && $success !== 'already_credited') {
    $success_msg = 'تم شحن المحفظة بنجاح بمبلغ ' . number_format((float)$success, 2) . ' ريال';
} elseif ($success === 'already_credited') {
    $success_msg = 'تم إضافة الرصيد مسبقاً';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محفظتي</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <?php if ($success_msg): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Wallet Balance -->
    <div class="wallet-card">
        <div class="label">رصيد محفظتك</div>
        <div class="balance">
            <span class="currency">SAR</span><?= number_format($user['balance'], 2) ?>
        </div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
    </div>

    <!-- Charge Form -->
    <div class="charge-card">
        <h2>شحن المحفظة</h2>
        <form method="POST" action="charge.php" id="chargeForm">
            <div class="amounts-grid">
                <?php foreach ([50, 100, 200, 500, 1000, 2000] as $preset): ?>
                <button type="button"
                        class="amount-btn"
                        onclick="selectAmount(<?= $preset ?>)">
                    <?= $preset ?> <small style="font-size:11px">ريال</small>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="custom-row">
                <input type="number"
                       name="amount"
                       id="amountInput"
                       placeholder="أو أدخل مبلغاً مخصصاً"
                       min="1"
                       max="10000"
                       step="0.01"
                       required>
            </div>

            <button type="submit" class="btn-charge">
                الدفع عبر Tap Payments &larr;
            </button>
        </form>
    </div>

    <!-- Transaction History -->
    <div class="txn-card">
        <h2>آخر العمليات</h2>
        <?php if (empty($transactions)): ?>
            <p class="empty-txn">لا توجد عمليات بعد</p>
        <?php else: ?>
        <ul class="txn-list">
            <?php foreach ($transactions as $txn): ?>
            <?php
                $badge_class = match($txn['status']) {
                    'captured'  => 'badge-captured',
                    'pending'   => 'badge-pending',
                    'failed'    => 'badge-failed',
                    'cancelled' => 'badge-cancelled',
                    default     => 'badge-default',
                };
                $status_label = match($txn['status']) {
                    'captured'  => 'ناجحة',
                    'pending'   => 'معلقة',
                    'failed'    => 'فاشلة',
                    'cancelled' => 'ملغاة',
                    default     => $txn['status'],
                };
            ?>
            <li class="txn-item">
                <div>
                    <div style="font-size:13px;color:#64748b;margin-bottom:2px">
                        <?= htmlspecialchars($txn['created_at']) ?>
                    </div>
                    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                </div>
                <div class="txn-amount">+<?= number_format($txn['amount'], 2) ?> SAR</div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

</div>

<script>
function selectAmount(val) {
    document.getElementById('amountInput').value = val;
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
    event.target.closest('.amount-btn').classList.add('active');
}

// Clear active state when user types custom amount
document.getElementById('amountInput').addEventListener('input', function() {
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
});
</script>
</body>
</html>
