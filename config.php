<?php

define('TAP_SECRET_KEY', 'sk_test_GLUaSWi7UQjlSXFpdBg0MszHcyPCh');
define('TAP_PUBLIC_KEY', 'pk_test_7wYr4gUqS6oBhzHRaXGkTmyP');
define('TAP_API_BASE',   'https://api.tap.company/v2');
define('TAP_CURRENCY',   'SAR');

// Change this to your actual domain when deploying
define('APP_URL', 'http://localhost:8000');
define('CALLBACK_URL', APP_URL . '/callback.php');

define('DB_PATH', __DIR__ . '/wallet.db');
