<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>رمز التحقق لإعادة تعيين كلمة المرور</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f7f9fa; padding: 20px; text-align: right; direction: rtl;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">
        <h2 style="color: #1e293b; margin-top: 0; margin-bottom: 16px;">طلب إعادة تعيين كلمة المرور</h2>
        <p style="color: #475569; font-size: 16px; line-height: 1.6;">لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك. استخدم رمز التحقق التالي لإتمام العملية:</p>
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #2563eb; background: #eff6ff; padding: 14px 28px; border-radius: 8px; border: 1px dashed #93c5fd;">
                {{ $otp }}
            </span>
        </div>
        <p style="color: #64748b; font-size: 14px; line-height: 1.6;">هذا الرمز صالح لمدة 10 دقائق فقط ويمكن استخدامه لمرة واحدة.</p>
        <p style="color: #94a3b8; font-size: 12px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            إذا لم تقم بطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة بأمان.
        </p>
    </div>
</body>
</html>
