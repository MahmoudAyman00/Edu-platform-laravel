<?php

return [
    // Generic
    'validation_failed' => 'فشل التحقق من البيانات.',
    'unauthenticated' => 'غير مسجل الدخول.',
    'forbidden' => 'غير مصرح لك بهذا الإجراء.',
    'not_found' => 'غير موجود.',
    'http_error' => 'حدث خطأ.',

    // Auth
    'auth.registered' => 'تم إنشاء الحساب بنجاح. تحقق من بريدك الإلكتروني.',
    'auth.logged_in' => 'تم تسجيل الدخول بنجاح.',
    'auth.logged_out' => 'تم تسجيل الخروج بنجاح.',
    'auth.email_verified' => 'تم تفعيل البريد الإلكتروني بنجاح.',
    'auth.verification_resent' => 'تم إرسال رابط التفعيل مجددًا.',
    'auth.password_email_sent' => 'تم إرسال رابط استعادة كلمة المرور.',
    'auth.password_reset' => 'تم إعادة تعيين كلمة المرور بنجاح.',
    'auth.invalid_credentials' => 'بيانات الدخول غير صحيحة.',
    'auth.email_not_verified' => 'يجب تفعيل البريد الإلكتروني قبل تسجيل الدخول.',
    'auth.email_already_verified' => 'البريد الإلكتروني مفعّل بالفعل.',
    'auth.invalid_token' => 'رمز التحقق غير صالح أو منتهي.',

    // Users
    'users.profile' => 'الملف الشخصي.',
    'users.updated' => 'تم تحديث الملف الشخصي.',
    'users.list' => 'قائمة المستخدمين.',
    'users.user_created' => 'تم إنشاء المستخدم.',
    'users.user_updated' => 'تم تحديث المستخدم.',
    'users.user_deleted' => 'تم حذف المستخدم.',
    'users.cannot_delete_self' => 'لا يمكنك حذف حسابك الخاص.',

    // Common
    'common.duplicate_locale' => 'اللغة مكررة في الترجمات.',

    // Categories
    'categories.list' => 'قائمة التصنيفات.',
    'categories.detail' => 'تفاصيل التصنيف.',
    'categories.created' => 'تم إنشاء التصنيف.',
    'categories.updated' => 'تم تحديث التصنيف.',
    'categories.deleted' => 'تم حذف التصنيف.',
    'categories.has_courses' => 'لا يمكن حذف التصنيف لأنه يحتوي على كورسات.',

    // Courses
    'courses.list' => 'قائمة الكورسات.',
    'courses.invalid_status' => 'حالة الكورس غير صالحة.',
    'courses.detail' => 'تفاصيل الكورس.',
    'courses.created' => 'تم إنشاء الكورس.',
    'courses.updated' => 'تم تحديث الكورس.',
    'courses.published' => 'تم نشر الكورس.',
    'courses.archived' => 'تم أرشفة الكورس.',

    // Lessons
    'lessons.created' => 'تم إنشاء الدرس.',
    'lessons.updated' => 'تم تحديث الدرس.',
    'lessons.deleted' => 'تم حذف الدرس.',
    'lessons.reordered' => 'تم إعادة ترتيب الدروس.',
    'lessons.upload_url' => 'رابط الرفع جاهز.',
    'lessons.invalid_order' => 'ترتيب الدروس غير صالح.',

    // Enrollments
    'enrollments.list' => 'اشتراكاتي.',
    'enrollments.enrolled' => 'تم الاشتراك في الكورس.',
    'enrollments.course_not_free' => 'هذا الكورس مدفوع. اشترك بعد الدفع.',
    'enrollments.course_not_available' => 'هذا الكورس غير متاح للاشتراك حاليًا.',
    'enrollments.no_access' => 'يجب الاشتراك في الكورس أولًا.',
    'enrollments.no_video' => 'لا يوجد فيديو لهذا الدرس بعد.',

    // Playback
    'playback.url' => 'رابط التشغيل جاهز.',

    // Progress
    'progress.completed' => 'تم تحديد الدرس كمكتمل.',
    'progress.detail' => 'نسبة التقدم في الكورس.',
    'progress.my_courses' => 'كورساتي.',

    // Exams
    'exams.list' => 'قائمة الامتحانات.',
    'exams.invalid_status' => 'حالة الامتحان غير صالحة.',
    'exams.detail' => 'تفاصيل الامتحان.',
    'exams.created' => 'تم إنشاء الامتحان.',
    'exams.updated' => 'تم تحديث الامتحان.',
    'exams.deleted' => 'تم حذف الامتحان.',
    'exams.published' => 'تم نشر الامتحان.',
    'exams.final_set' => 'تم تحديد الامتحان كنهائي.',
    'exams.question_added' => 'تمت إضافة السؤال.',
    'exams.question_updated' => 'تم تحديث السؤال.',
    'exams.question_deleted' => 'تم حذف السؤال.',
    'exams.questions_reordered' => 'تم إعادة ترتيب الأسئلة.',
    'exams.invalid_order' => 'ترتيب الأسئلة غير صالح.',
    'exams.attempt_started' => 'بدأت المحاولة.',
    'exams.attempt_submitted' => 'تم تسليم المحاولة.',
    'exams.max_attempts' => 'وصلت للحد الأقصى من المحاولات.',
    'exams.no_attempt' => 'لا توجد محاولة جارية.',
    'exams.attempt_expired' => 'انتهت مدة المحاولة.',
    'exams.invalid_answers' => 'إجابات غير صالحة.',
    'exams.invalid_options' => 'يجب إدخال خيارين على الأقل وخيار صحيح واحد فقط.',
    'exams.no_result' => 'لا توجد نتيجة بعد.',
    'exams.not_published' => 'الامتحان غير منشور.',

    // Payments
    'payments.list' => 'قائمة المدفوعات.',
    'payments.detail' => 'تفاصيل الدفعة.',
    'payments.initiated' => 'تم إنشاء طلب الدفع.',
    'payments.webhook_received' => 'تم استلام إشعار الدفع.',
    'payments.course_is_free' => 'هذا الكورس مجاني. اشترك مباشرة.',
    'payments.already_enrolled' => 'أنت مشترك في هذا الكورس بالفعل.',
    'payments.pending_exists' => 'يوجد طلب دفع سارٍ لهذا الكورس بالفعل.',
    'payments.invalid_signature' => 'توقيع غير صالح.',
    'payments.not_found' => 'الدفعة غير موجودة.',
    'payments.fawry_error' => 'خطأ في الاتصال بخدمة الدفع.',

    // Certificates
    'certificates.list' => 'شهاداتي.',
    'certificates.issued' => 'تم إصدار الشهادة.',
    'certificates.detail' => 'تفاصيل الشهادة.',
    'certificates.not_ready' => 'الشهادة لسه بتتجهز. حاول تاني بعد شوية.',
    'certificates.verified' => 'الشهادة سليمة.',

    // Notifications
    'notifications.list' => 'قائمة الإشعارات.',
    'notifications.unread_count' => 'عدد الإشعارات غير المقروءة.',
    'notifications.marked_read' => 'تم تحديد الإشعار كمقروء.',
    'notifications.all_marked_read' => 'تم تحديد كل الإشعارات كمقروءة.',

    // Notification texts (stored as key + params, translated on read)
    'notif.verify_email_title' => 'تفعيل البريد الإلكتروني',
    'notif.verify_email_body' => 'أهلًا :name، فعّل بريدك الإلكتروني للبدء.',
    'notif.reset_password_title' => 'استعادة كلمة المرور',
    'notif.reset_password_body' => 'استخدم الرابط المرسل لإعادة تعيين كلمة المرور.',
    'notif.enrolled_title' => 'تم اشتراكك في :course',
    'notif.enrolled_body' => 'أهلًا :name، تم اشتراكك في كورس :course بنجاح.',
    'notif.payment_succeeded_title' => 'تم الدفع بنجاح',
    'notif.payment_succeeded_body' => 'تم تأكيد دفعك لكورس :course.',
    'notif.payment_expired_title' => 'انتهت صلاحية الدفع',
    'notif.payment_expired_body' => 'انتهت صلاحية طلب الدفع الخاص بكورس :course.',
    'notif.payment_failed_title' => 'فشل الدفع',
    'notif.payment_failed_body' => 'فشل الدفع الخاص بكورس :course.',
    'notif.course_published_title' => 'كورس جديد: :course',
    'notif.course_published_body' => 'تم نشر كورس جديد: :course.',
    'notif.exam_result_title' => 'نتيجة الامتحان',
    'notif.exam_result_body' => 'نتيجتك في :exam هي :score. الحالة: :status.',
];
