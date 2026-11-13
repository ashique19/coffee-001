<?php

return [
    'compliance_csid_already_issued' => 'এই ডিভাইসের জন্য compliance CSID ইতিমধ্যে সংরক্ষিত। Tests ধাপে গিয়ে compliance ইনভয়েস চেক চালান — OTP আবার জমা দেওয়ার দরকার নেই.',
    'compliance_csid_failed' => 'ZATCA থেকে compliance CSID পাওয়া যায়নি। OTP যাচাই করুন বা পোর্টাল থেকে ম্যানুয়াল ইম্পোর্ট করুন.',
    'compliance_check_failed' => 'Compliance ইনভয়েস চেক সফল হয়নি। সঠিক ইস্যুকৃত ইনভয়েস বেছে নিয়ে আবার চেষ্টা করুন.',
    'production_csid_failed' => 'Production CSID পাওয়া যায়নি। আগে compliance চেক সম্পন্ন করুন.',
    'submission_failed' => 'ZATCA এই ইনভয়েস গ্রহণ করেনি। ত্রুটি দেখে আবার চেষ্টা করুন.',
    'generic_failure' => 'ZATCA একটি ত্রুটি ফিরিয়েছে যা সম্পন্ন করা যায়নি। আবার চেষ্টা করুন.',
    'invalid_otp' => 'OTP প্রত্যাখ্যাত হয়েছে। নতুন OTP নিন এবং একবার জমা দিন.',
    'accept_language_required' => 'ZATCA ভাষা হেডার প্রত্যাখ্যান করেছে। অ্যাপে ঠিক করা হয়েছে — আবার চালান.',
    'invalid_request_with_reason' => 'ZATCA অনুরোধ প্রত্যাখ্যান করেছে: :reason',
    'validation_errors' => 'ZATCA জানিয়েছে: :summary',
    'validation_failed_status' => 'ZATCA যাচাই সফল হয়নি (status: :status). ইনভয়েস XML ও credentials পরীক্ষা করুন.',
];
