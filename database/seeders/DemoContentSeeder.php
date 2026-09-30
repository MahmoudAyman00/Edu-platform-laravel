<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\ExamStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Exam;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Course::count() > 0) {
            return;
        }

        $category = Category::with('translations')->first() ?? Category::create();

        if ($category->translations()->count() === 0) {
            $category->translations()->createMany([
                ['locale' => 'ar', 'title' => 'عام', 'description' => null],
                ['locale' => 'en', 'title' => 'General', 'description' => null],
            ]);
        }

        $course = Course::create([
            'category_id' => $category->id,
            'price' => 0,
            'status' => CourseStatus::PUBLISHED,
        ]);
        $course->translations()->createMany([
            ['locale' => 'ar', 'title' => 'كورس تجريبي', 'description' => 'محتوى تجريبي.'],
            ['locale' => 'en', 'title' => 'Demo Course', 'description' => 'Demo content.'],
        ]);

        foreach (['الدرس الأول|Lesson One', 'الدرس الثاني|Lesson Two'] as $i => $titles) {
            [$ar, $en] = explode('|', $titles);
            $lesson = $course->lessons()->create(['sort' => $i + 1]);
            $lesson->translations()->createMany([
                ['locale' => 'ar', 'title' => $ar],
                ['locale' => 'en', 'title' => $en],
            ]);
        }

        $exam = Exam::create([
            'course_id' => $course->id,
            'status' => ExamStatus::PUBLISHED,
            'is_final' => true,
            'pass_score' => 50,
            'max_attempts' => 3,
        ]);
        $exam->translations()->createMany([
            ['locale' => 'ar', 'title' => 'الامتحان النهائي التجريبي'],
            ['locale' => 'en', 'title' => 'Demo Final Exam'],
        ]);

        $question = $exam->questions()->create(['points' => 10, 'sort' => 1]);
        $question->translations()->createMany([
            ['locale' => 'ar', 'text' => 'سؤال تجريبي؟'],
            ['locale' => 'en', 'text' => 'Demo question?'],
        ]);
        foreach ([[true, 'صح|Right'], [false, 'غلط|Wrong']] as [$correct, $texts]) {
            [$ar, $en] = explode('|', $texts);
            $option = $question->options()->create(['is_correct' => $correct]);
            $option->translations()->createMany([
                ['locale' => 'ar', 'text' => $ar],
                ['locale' => 'en', 'text' => $en],
            ]);
        }
    }
}
