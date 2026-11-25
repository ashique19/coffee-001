<?php

$en = require __DIR__.'/../en/business.php';

return array_replace($en, [
    'legal' => array_merge($en['legal'], [
            'address_en' => 'स्टोरफ्रंट पता (अंग्रेज़ी)',
            'address_ar' => 'स्टोरफ्रंट पता (अरबी)',
            'managed_by_zheart' => 'Z-Heart द्वारा प्रबंधित। बदलने के लिए हमसे संपर्क करें।',
            'source_zheart' => 'आपके नवीनतम Z-Heart इनवॉइस के अनुसार।',
            'source_local' => 'अभी कोई Z-Heart इनवॉइस नहीं। ये इस ऐप में पहले सहेजे गए विवरण हैं; नए इनवॉइस Z-Heart का उपयोग करते हैं।',
            'storefront_heading' => 'स्टोरफ्रंट पता',
            'storefront_hint' => 'आपकी वेबसाइट पर दिखाया जाता है। इनवॉइस पर विक्रेता का पता Z-Heart से छपता है।',
            'seller_id' => 'विक्रेता आईडी',
            'not_set' => 'सेट नहीं',
    ]),
    'nav' => 'व्यवसाय',
    'title' => 'व्यवसाय सेटिंग्स',
    'subtitle' => 'ब्रांड, कानूनी, संपर्क और समय — स्टोरफ्रंट, रसीद और इनवॉइस के लिए।',
]);
