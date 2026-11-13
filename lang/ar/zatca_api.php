<?php

return [
    'compliance_csid_already_issued' => 'تم حفظ CSID امتثال لهذا الجهاز مسبقًا. انتقل إلى خطوة الاختبارات لتشغيل فحص فاتورة الامتثال — لا حاجة لإرسال OTP مرة أخرى.',
    'compliance_csid_failed' => 'تعذر الحصول على CSID امتثال من ZATCA. تحقق من OTP وحاول مجددًا، أو استخدم الاستيراد اليدوي إن صدرت البيانات من البوابة.',
    'compliance_check_failed' => 'لم ينجح فحص فاتورة الامتثال. تأكد من اختيار فاتورة صادرة صالحة وحاول مجددًا.',
    'production_csid_failed' => 'تعذر الحصول على CSID إنتاج. أكمل فحص الامتثال أولًا ثم حاول مجددًا.',
    'missing_compliance_steps' => 'تتطلب ZATCA ستة مستندات امتثال ناجحة (فاتورة وإشعار دائن وإشعار مدين قياسيين ومبسّطين لكل منهما) قبل إصدار CSID إنتاج.',
    'production_csid_with_reason' => ':code — :reason',
    'submission_failed' => 'لم تقبل ZATCA إرسال هذه الفاتورة. راجع الخطأ وأعد المحاولة عند الجاهزية.',
    'generic_failure' => 'أعادت ZATCA خطأ لم نتمكن من إكمال الطلب. حاول مجددًا أو راجع حالة بوابة المحاكاة.',
    'invalid_otp' => 'تم رفض رمز OTP. أنشئ OTP جديدًا من بوابة محاكاة ZATCA وأرسله مرة واحدة.',
    'accept_language_required' => 'رفضت بوابة ZATCA ترويسات لغة الطلب. تم تصحيح ذلك في التطبيق — أعد تشغيل الإجراء.',
    'invalid_request_with_reason' => 'رفضت ZATCA الطلب: :reason',
    'validation_errors' => 'أبلغت ZATCA: :summary',
    'validation_failed_status' => 'لم ينجح التحقق من ZATCA (الحالة: :status). راجع XML الفاتورة وبيانات الاعتماد ثم حاول مجددًا.',
];
