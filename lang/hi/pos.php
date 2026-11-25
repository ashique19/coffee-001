<?php

$en = require __DIR__.'/../en/pos.php';

return array_replace($en, [
    'receipt_preview' => [
        'paid' => 'भुगतान प्राप्त हुआ',
        'title' => 'रसीद :number',
        'print' => 'प्रिंट',
        'new_order' => 'नया ऑर्डर',
        'close' => 'बंद करें',
    ],
]);
