<?php

return [
    'auth_failed' => 'Z-Heart rejected the API key. Check the key in your environment or rotate it in Z-Heart. Request id :id.:detail',
    'ip_not_allowed' => 'This server IP is not on the Z-Heart API key allowlist. Ask the operator to add your egress IP. Request id :id.:detail',
    'client_suspended' => 'The Z-Heart client is suspended. Contact the operator. Request id :id.:detail',
    'client_not_active' => 'The Z-Heart client is not active. Request id :id.:detail',
    'forbidden' => 'Z-Heart refused this request. Request id :id.:detail',
    'validation_failed' => 'Z-Heart rejected the invoice payload. Review line items and buyer fields, then try again. Request id :id.:detail',
    'production_csid_required' => 'Z-Heart requires a production CSID before submission. Request id :id.:detail',
    'submission_failed' => 'Z-Heart could not report or clear this invoice.',
    'server_error' => 'Z-Heart returned a server error. Retry later or contact support with request id :id.:detail',
    'generic_failure' => 'Z-Heart signing failed. Check configuration and try again.',
    'not_configured' => 'Z-Heart base URL or API key is missing for this deployment.',
    'health_ok' => 'Z-Heart health check passed.',
    'health_failed' => 'Z-Heart health check failed.',
];
