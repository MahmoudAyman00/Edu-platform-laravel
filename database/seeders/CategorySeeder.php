<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (Category::count() > 0) {
            return;
        }

        $categories = [
            ['ar' => ['title' => 'برمجة', 'description' => 'كورسات البرمجة وتطوير البرمجيات.'], 'en' => ['title' => 'Programming', 'description' => 'Programming and software development courses.']],
            ['ar' => ['title' => 'تصميم', 'description' => 'كورسات التصميم الجرافيكي وتجربة المستخدم.'], 'en' => ['title' => 'Design', 'description' => 'Graphic design and UX courses.']],
            ['ar' => ['title' => 'لغات', 'description' => 'كورسات تعلم اللغات.'], 'en' => ['title' => 'Languages', 'description' => 'Language learning courses.']],
            ['ar' => ['title' => 'إدارة أعمال', 'description' => 'كورسات إدارة الأعمال والتسويق.'], 'en' => ['title' => 'Business', 'description' => 'Business administration and marketing courses.']],
        ];

        foreach ($categories as $locales) {
            $category = Category::create();

            foreach ($locales as $locale => $fields) {
                $category->translations()->create([
                    'locale' => $locale,
                    'title' => $fields['title'],
                    'description' => $fields['description'],
                ]);
            }
        }
    }
}
