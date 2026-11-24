<?php

return [
    // Authentication & Account Lifecycle
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'logout_success' => 'تم تسجيل الخروج بنجاح',
    'registered_success' => 'تم إنشاء الحساب بنجاح',
    'profile_retrieved' => 'تم استرجاع بيانات الملف الشخصي بنجاح',
    'user_not_found' => 'المستخدم غير موجود',
    'account_deactivated' => 'هذا الحساب معطل حالياً، يرجى التواصل مع الإدارة',
    'invalid_credentials' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',

    // Password Reset & OTP
    'otp_sent' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح',
    'otp_invalid_or_expired' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
    'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح',
    'mail_otp_subject' => 'رمز التحقق لإعادة تعيين كلمة المرور',

    // Global HTTP & Exception Messages
    'validation_failed' => 'بيانات الطلب غير صالحة',
    'unauthenticated' => 'يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر',
    'unauthorized' => 'غير مصرح لك بالقيام بهذا الإجراء',
    'not_found' => 'المصدر المطلوب غير موجود',
    'too_many_requests' => 'تم تجاوز عدد المحاولات المسموح بها، يرجى المحاولة لاحقاً',
    'server_error' => 'حدث خطأ أثناء معالجة الطلب',

    // Settings Module
    'settings_retrieved' => 'تم استرجاع الإعدادات بنجاح',
    'setting_retrieved' => 'تم استرجاع الإعداد بنجاح',
    'setting_created' => 'تم إنشاء الإعداد بنجاح',
    'setting_updated' => 'تم تحديث الإعداد بنجاح',
    'setting_deleted' => 'تم حذف الإعداد بنجاح',
    'setting_not_found' => 'الإعداد المطلوب غير موجود',

    // Categories Module
    'categories_retrieved' => 'تم استرجاع الأقسام بنجاح',
    'category_retrieved' => 'تم استرجاع بيانات القسم بنجاح',
    'category_created' => 'تم إنشاء القسم بنجاح',
    'category_updated' => 'تم تحديث بيانات القسم بنجاح',
    'category_deleted' => 'تم حذف القسم بنجاح',
    'category_not_found' => 'القسم المطلوب غير موجود',
    'category_has_courses' => 'لا يمكن حذف هذا القسم لأنه يحتوي على كورسات مرتبطة به',
];
