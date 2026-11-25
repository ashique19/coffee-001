<?php

$en = require __DIR__.'/../en/business.php';

$en['invoicing'] = array_merge($en['invoicing'] ?? [], [
    'intro' => 'ইনভয়েস শুধু Z-Heart-এর মাধ্যমে স্বাক্ষরিত হবে, নাকি স্বাক্ষরের পর ZATCA-তেও পাঠানো হবে, তা বেছে নিন।',
    'integration_hint' => 'এটি বন্ধ থাকলেও ইনভয়েস Z-Heart-এর মাধ্যমে স্বাক্ষরিত হয়, তবে ZATCA-তে পাঠানো হয় না।',
]);

$en['zheart'] = array_merge($en['zheart'] ?? [], [
    'connection_heading' => 'সংযোগ',
    'lead' => 'আপনার Z-Heart অ্যাকাউন্টের তথ্য লিখুন। সার্ভারের ডিফল্ট রাখতে খালি রাখুন।',
    'base_url' => 'বেস URL',
    'base_url_from_env' => 'সার্ভার .env থেকে',
    'api_key' => 'API কী',
    'new_api_key' => 'নতুন API কী',
    'key_status_saved' => 'সংরক্ষিত কী (…:last4)',
    'key_status_env' => 'সার্ভার .env কী ব্যবহৃত হচ্ছে (…:last4)',
    'store_key_in_db' => 'কী ডাটাবেসে সংরক্ষণ করুন',
    'replace_key' => 'কী পরিবর্তন করুন',
    'remove_key' => 'সরান',
    'remove_confirm' => 'সংরক্ষিত API কী সরাতে চান? সেট করা থাকলে সার্ভার পরিবেশ ভেরিয়েবলে ফিরে যাবে।',
    'key_removed' => 'সংরক্ষিত API কী সরানো হয়েছে।',
    'test_connection' => 'সংযোগ পরীক্ষা',
    'test_connected_heading' => 'সংযোগ সফল',
    'test_success_client' => 'সংযুক্ত — :name (:environment)। অনবোর্ড: :onboarded।',
    'test_not_configured' => 'পরীক্ষার আগে বেস URL ও API কী যোগ করুন।',
    'test_http_status' => 'HTTP :status',
    'test_request_id' => 'রিকোয়েস্ট আইডি: :id',
    'test_reason_not_configured' => 'বেস URL বা API কী নেই।',
    'test_reason_unreachable' => 'Z-Heart-এ পৌঁছানো যায়নি — বেস URL ও নেটওয়ার্ক পরীক্ষা করুন।',
    'test_reason_bad_key' => 'প্রমাণীকরণ ব্যর্থ — API কী পরীক্ষা করুন।',
    'test_reason_ip_not_allowed' => 'এই সার্ভার IP Z-Heart অ্যালাউলিস্টে নেই।',
    'test_reason_outdated' => 'Z-Heart পুরনো — GET /api/v1/auth সমর্থন করে এমন ভার্সনে আপডেট করুন।',
    'test_reason_auth_failed' => 'পৌঁছানো গেছে, কিন্তু প্রমাণীকরণ সফল হয়নি।',
    'test_warn_csid_expiring' => 'CSID ৩০ দিনের মধ্যে মেয়াদোত্তীর্ণ হবে — আগেই অনবোর্ডিং নবায়ন করুন।',
    'test_warn_vat_mismatch' => 'VAT মেলেনি: ব্যবসার সেটিংস (:local) বনাম Z-Heart ক্লায়েন্ট (:remote)।',
    'card_client_name' => 'ক্লায়েন্টের নাম',
    'card_vat' => 'VAT নং',
    'card_cr' => 'CR নং',
    'card_environment' => 'পরিবেশ',
    'card_active' => 'ক্লায়েন্ট স্ট্যাটাস',
    'card_onboarding' => 'অনবোর্ডিং',
    'card_csid_expires' => 'CSID মেয়াদ শেষ',
    'card_last_zatca' => 'শেষ ZATCA কল',
    'card_api_key' => 'API কী',
    'card_key_expires' => 'কী-এর মেয়াদ',
    'card_ip_allowlist' => 'IP অ্যালাউলিস্ট',
    'active_yes' => 'সক্রিয়',
    'active_no' => 'নিষ্ক্রিয়',
    'allowlist_on' => 'চালু',
    'allowlist_off' => 'বন্ধ',
    'onboarded_yes' => 'হ্যাঁ',
    'onboarded_no' => 'না',
    'onboarded_unknown' => 'অজানা',
]);

$en['legal'] = array_merge($en['legal'] ?? [], [
        'address_en' => 'স্টোরফ্রন্ট ঠিকানা (ইংরেজি)',
        'address_ar' => 'স্টোরফ্রন্ট ঠিকানা (আরবি)',
        'managed_by_zheart' => 'Z-Heart দ্বারা পরিচালিত। পরিবর্তন করতে আমাদের সাথে যোগাযোগ করুন।',
        'source_zheart' => 'আপনার সর্বশেষ Z-Heart ইনভয়েস অনুযায়ী।',
        'source_local' => 'এখনও কোনো Z-Heart ইনভয়েস নেই। এগুলো আগে এই অ্যাপে সংরক্ষিত তথ্য; নতুন ইনভয়েস Z-Heart ব্যবহার করে।',
        'storefront_heading' => 'স্টোরফ্রন্ট ঠিকানা',
        'storefront_hint' => 'আপনার ওয়েবসাইটে দেখানো হয়। ইনভয়েসে বিক্রেতার ঠিকানা Z-Heart থেকে ছাপা হয়।',
        'seller_id' => 'বিক্রেতা আইডি',
        'not_set' => 'সেট করা নেই',
]);

return $en;
