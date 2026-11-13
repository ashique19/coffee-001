<?php

return [
    'compliance_csid_already_issued' => 'A compliance CSID is already stored for this device. Continue to the Tests step to run the compliance invoice check — you do not need to submit the OTP again.',
    'compliance_csid_failed' => 'Could not obtain a compliance CSID from ZATCA. Check the OTP and try again, or use manual import if the portal already issued credentials.',
    'compliance_check_failed' => 'The compliance invoice check did not succeed. Confirm you selected a valid issued invoice and try again.',
    'production_csid_failed' => 'Could not obtain a production CSID. Complete the compliance check first, then try again.',
    'missing_compliance_steps' => 'ZATCA requires six successful compliance documents (standard and simplified invoice, credit note, and debit note each) before issuing a production CSID.',
    'production_csid_with_reason' => ':code — :reason',
    'submission_failed' => 'ZATCA did not accept this invoice submission. Review the error and retry when ready.',
    'generic_failure' => 'ZATCA returned an error we could not complete. Try again or check your simulation portal status.',
    'invalid_otp' => 'The OTP was rejected. Generate a new OTP in the ZATCA simulation portal and submit it once.',
    'accept_language_required' => 'The ZATCA gateway rejected the request language headers. This has been corrected in the app — please run the action again.',
    'invalid_request_with_reason' => 'ZATCA rejected the request: :reason',
    'validation_errors' => 'ZATCA reported: :summary',
    'validation_failed_status' => 'ZATCA validation did not pass (status: :status). Review the invoice XML and onboarding credentials, then try again.',
];
