<?php

return [
    'compliance_csid_already_issued' => 'इस डिवाइस के लिए compliance CSID पहले से सहेजा है। Tests चरण पर जाकर compliance इनवॉइस जाँच चलाएँ — OTP दोबारा भेजने की ज़रूरत नहीं.',
    'compliance_csid_failed' => 'ZATCA से compliance CSID नहीं मिला। OTP जाँचें या पोर्टल से मैन्युअल इम्पोर्ट करें.',
    'compliance_check_failed' => 'Compliance इनवॉइस जाँच सफल नहीं हुई। सही जारी इनवॉइस चुनकर फिर कोशिश करें.',
    'production_csid_failed' => 'Production CSID नहीं मिला। पहले compliance जाँच पूरी करें.',
    'submission_failed' => 'ZATCA ने यह इनवॉइस स्वीकार नहीं की। त्रुटि देखकर फिर कोशिश करें.',
    'generic_failure' => 'ZATCA ने ऐसी त्रुटि लौटाई जिसे पूरा नहीं किया जा सका। फिर कोशिश करें.',
    'invalid_otp' => 'OTP अस्वीकार किया गया। नया OTP बनाएँ और एक बार जमा करें.',
    'accept_language_required' => 'ZATCA ने भाषा हेडर अस्वीकार किए। ऐप में सुधार हो चुका है — फिर चलाएँ.',
    'invalid_request_with_reason' => 'ZATCA ने अनुरोध अस्वीकार किया: :reason',
    'validation_errors' => 'ZATCA ने बताया: :summary',
    'validation_failed_status' => 'ZATCA सत्यापन पास नहीं हुआ (status: :status). इनवॉइस XML और क्रेडेंशियल जाँचें.',
];
