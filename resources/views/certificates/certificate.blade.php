<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; text-align: center; padding: 60px 40px; color: #1f2937; }
        .frame { border: 6px double #1d4ed8; padding: 50px 30px; }
        h1 { font-size: 34px; color: #1d4ed8; margin-bottom: 10px; }
        .name { font-size: 28px; font-weight: bold; margin: 20px 0; }
        .course { font-size: 22px; margin: 10px 0 30px; }
        .meta { font-size: 14px; color: #4b5563; margin-top: 40px; }
    </style>
</head>
<body>
<div class="frame">
    <h1>{{ $title ?? 'شهادة إتمام' }}</h1>
    <p>{{ $subtitle ?? 'تشهد المنصة بأن' }}</p>
    <div class="name">{{ $student_name }}</div>
    <p>{{ $completed_label ?? 'قد أتم بنجاح كورس' }}</p>
    <div class="course">{{ $course_title }}</div>
    <div class="meta">
        <div>{{ $serial_label ?? 'الرقم المسلسل' }}: {{ $serial_number }}</div>
        <div>{{ $date_label ?? 'تاريخ الإصدار' }}: {{ $issued_at }}</div>
    </div>
</div>
</body>
</html>
