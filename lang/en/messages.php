<?php

return [
    // Generic
    'validation_failed' => 'Validation failed.',
    'unauthenticated' => 'Unauthenticated.',
    'forbidden' => 'Forbidden.',
    'not_found' => 'Not found.',
    'http_error' => 'An error occurred.',

    // Auth
    'auth.registered' => 'Account created. Please verify your email.',
    'auth.logged_in' => 'Logged in successfully.',
    'auth.logged_out' => 'Logged out successfully.',
    'auth.email_verified' => 'Email verified successfully.',
    'auth.verification_resent' => 'Verification link resent.',
    'auth.password_email_sent' => 'Password reset link sent.',
    'auth.password_reset' => 'Password reset successfully.',
    'auth.invalid_credentials' => 'Invalid credentials.',
    'auth.email_not_verified' => 'You must verify your email before logging in.',
    'auth.email_already_verified' => 'Email is already verified.',
    'auth.invalid_token' => 'Invalid or expired token.',

    // Users
    'users.profile' => 'Profile.',
    'users.updated' => 'Profile updated.',
    'users.list' => 'Users list.',
    'users.user_created' => 'User created.',
    'users.user_updated' => 'User updated.',
    'users.user_deleted' => 'User deleted.',
    'users.cannot_delete_self' => 'You cannot delete your own account.',

    // Common
    'common.duplicate_locale' => 'Duplicate locale in translations.',

    // Categories
    'categories.list' => 'Categories list.',
    'categories.detail' => 'Category details.',
    'categories.created' => 'Category created.',
    'categories.updated' => 'Category updated.',
    'categories.deleted' => 'Category deleted.',
    'categories.has_courses' => 'Cannot delete the category because it has courses.',

    // Courses
    'courses.list' => 'Courses list.',
    'courses.invalid_status' => 'Invalid course status.',
    'courses.detail' => 'Course details.',
    'courses.created' => 'Course created.',
    'courses.updated' => 'Course updated.',
    'courses.published' => 'Course published.',
    'courses.archived' => 'Course archived.',

    // Lessons
    'lessons.created' => 'Lesson created.',
    'lessons.updated' => 'Lesson updated.',
    'lessons.deleted' => 'Lesson deleted.',
    'lessons.reordered' => 'Lessons reordered.',
    'lessons.upload_url' => 'Upload URL ready.',
    'lessons.invalid_order' => 'Invalid lessons order.',

    // Enrollments
    'enrollments.list' => 'My enrollments.',
    'enrollments.enrolled' => 'Enrolled in the course.',
    'enrollments.course_not_free' => 'This course is paid. Enroll after payment.',
    'enrollments.course_not_available' => 'This course is not available for enrollment.',
    'enrollments.no_access' => 'You must enroll in the course first.',
    'enrollments.no_video' => 'This lesson has no video yet.',

    // Playback
    'playback.url' => 'Playback URL ready.',

    // Progress
    'progress.completed' => 'Lesson marked as completed.',
    'progress.detail' => 'Course progress.',
    'progress.my_courses' => 'My courses.',

    // Exams
    'exams.list' => 'Exams list.',
    'exams.invalid_status' => 'Invalid exam status.',
    'exams.detail' => 'Exam details.',
    'exams.created' => 'Exam created.',
    'exams.updated' => 'Exam updated.',
    'exams.deleted' => 'Exam deleted.',
    'exams.published' => 'Exam published.',
    'exams.final_set' => 'Exam set as final.',
    'exams.question_added' => 'Question added.',
    'exams.question_updated' => 'Question updated.',
    'exams.question_deleted' => 'Question deleted.',
    'exams.questions_reordered' => 'Questions reordered.',
    'exams.invalid_order' => 'Invalid questions order.',
    'exams.attempt_started' => 'Attempt started.',
    'exams.attempt_submitted' => 'Attempt submitted.',
    'exams.max_attempts' => 'Maximum attempts reached.',
    'exams.no_attempt' => 'No active attempt.',
    'exams.attempt_expired' => 'Attempt time expired.',
    'exams.invalid_answers' => 'Invalid answers.',
    'exams.invalid_options' => 'At least two options with exactly one correct are required.',
    'exams.no_result' => 'No result yet.',
    'exams.not_published' => 'Exam is not published.',

    // Payments
    'payments.list' => 'Payments list.',
    'payments.detail' => 'Payment details.',
    'payments.initiated' => 'Payment request created.',
    'payments.webhook_received' => 'Payment notification received.',
    'payments.course_is_free' => 'This course is free. Enroll directly.',
    'payments.already_enrolled' => 'You are already enrolled in this course.',
    'payments.pending_exists' => 'A live payment request already exists for this course.',
    'payments.invalid_signature' => 'Invalid signature.',
    'payments.not_found' => 'Payment not found.',
    'payments.fawry_error' => 'Payment service connection error.',

    // Certificates
    'certificates.list' => 'My certificates.',
    'certificates.issued' => 'Certificate issued.',
    'certificates.detail' => 'Certificate details.',
    'certificates.not_ready' => 'The certificate is still being prepared. Try again shortly.',
    'certificates.verified' => 'Certificate is valid.',

    // Notifications
    'notifications.list' => 'Notifications list.',
    'notifications.unread_count' => 'Unread notifications count.',
    'notifications.marked_read' => 'Notification marked as read.',
    'notifications.all_marked_read' => 'All notifications marked as read.',

    // Notification texts (stored as key + params, translated on read)
    'notif.verify_email_title' => 'Verify your email',
    'notif.verify_email_body' => 'Hi :name, verify your email to get started.',
    'notif.reset_password_title' => 'Reset your password',
    'notif.reset_password_body' => 'Use the sent link to reset your password.',
    'notif.enrolled_title' => 'Enrolled in :course',
    'notif.enrolled_body' => 'Hi :name, you enrolled in :course.',
    'notif.payment_succeeded_title' => 'Payment succeeded',
    'notif.payment_succeeded_body' => 'Your payment for :course was confirmed.',
    'notif.payment_expired_title' => 'Payment expired',
    'notif.payment_expired_body' => 'Your payment request for :course expired.',
    'notif.payment_failed_title' => 'Payment failed',
    'notif.payment_failed_body' => 'Your payment for :course failed.',
    'notif.course_published_title' => 'New course: :course',
    'notif.course_published_body' => 'A new course was published: :course.',
    'notif.exam_result_title' => 'Exam result',
    'notif.exam_result_body' => 'Your result in :exam is :score. Status: :status.',
];
