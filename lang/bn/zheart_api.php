<?php

return [
    'auth_failed' => 'Z-Heart API কী প্রত্যাখ্যান করেছে। কী পরীক্ষা করুন বা Z-Heart-এ রোটেট করুন। অনুরোধ id :id.:detail',
    'ip_not_allowed' => 'এই সার্ভারের IP Z-Heart API কী-এর অনুমতির তালিকায় নেই। egress IP যোগ করতে অপারেটরকে বলুন। অনুরোধ id :id.:detail',
    'client_suspended' => 'Z-Heart ক্লায়েন্ট স্থগিত। অপারেটরের সাথে যোগাযোগ করুন। অনুরোধ id :id.:detail',
    'client_not_active' => 'Z-Heart ক্লায়েন্ট সক্রিয় নয়। অনুরোধ id :id.:detail',
    'forbidden' => 'Z-Heart এই অনুরোধ প্রত্যাখ্যান করেছে। অনুরোধ id :id.:detail',
    'validation_failed' => 'Z-Heart ইনভয়েস পেলোড প্রত্যাখ্যান করেছে। লাইন ও ক্রেতার ক্ষেত্র পরীক্ষা করে আবার চেষ্টা করুন। অনুরোধ id :id.:detail',
    'production_csid_required' => 'জমা দেওয়ার আগে Z-Heart প্রোডাকশন CSID চায়। অনুরোধ id :id.:detail',
    'submission_failed' => 'Z-Heart এই ইনভয়েস রিপোর্ট বা ক্লিয়ার করতে পারেনি।',
    'server_error' => 'Z-Heart সার্ভার ত্রুটি দিয়েছে। পরে আবার চেষ্টা করুন বা সহায়তায় অনুরোধ id :id পাঠান।:detail',
    'generic_failure' => 'Z-Heart সাইনিং ব্যর্থ। কনফিগারেশন দেখে আবার চেষ্টা করুন।',
    'not_configured' => 'এই সেটআপে Z-Heart base URL বা API কী নেই।',
    'health_ok' => 'Z-Heart হেলথ চেক সফল।',
    'health_failed' => 'Z-Heart হেলথ চেক ব্যর্থ।',
];
