<?php

return [
    'auth_failed' => 'رفض Z-Heart مفتاح API. تحقق من المفتاح أو جدّده في Z-Heart. معرّف الطلب :id.:detail',
    'ip_not_allowed' => 'عنوان IP هذا الخادم غير مدرج في قائمة السماح لمفتاح Z-Heart. اطلب إضافة IP الخروج. معرّف الطلب :id.:detail',
    'client_suspended' => 'عميل Z-Heart موقوف. تواصل مع المشغّل. معرّف الطلب :id.:detail',
    'client_not_active' => 'عميل Z-Heart غير نشط. معرّف الطلب :id.:detail',
    'forbidden' => 'رفض Z-Heart هذا الطلب. معرّف الطلب :id.:detail',
    'validation_failed' => 'رفض Z-Heart بيانات الفاتورة. راجع البنود وبيانات المشتري ثم أعد المحاولة. معرّف الطلب :id.:detail',
    'production_csid_required' => 'يتطلّب Z-Heart شهادة CSID للإنتاج قبل الإرسال. معرّف الطلب :id.:detail',
    'submission_failed' => 'تعذّر على Z-Heart الإبلاغ أو الاعتماد لهذه الفاتورة.',
    'server_error' => 'أعاد Z-Heart خطأ خادم. أعد المحاولة لاحقاً أو تواصل مع الدعم مع معرّف الطلب :id.:detail',
    'generic_failure' => 'فشل التوقيع عبر Z-Heart. تحقق من الإعدادات وأعد المحاولة.',
    'not_configured' => 'عنوان Z-Heart أو مفتاح API غير مهيأ لهذا النشر.',
    'health_ok' => 'نجح فحص صحة Z-Heart.',
    'health_failed' => 'فشل فحص صحة Z-Heart.',
];
