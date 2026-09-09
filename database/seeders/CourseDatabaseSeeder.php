<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or retrieve Category
        $category = CourseCategory::updateOrCreate(
            ['slug' => 'digital-marketing'],
            [
                'name' => 'Digital Marketing',
                'description' => 'Practical digital marketing strategies tailored for small businesses, founders, and local entrepreneurs.',
                'status' => CategoryStatus::ACTIVE,
            ]
        );

        // 2. Create or retrieve Course
        $course = Course::updateOrCreate(
            ['slug' => 'digital-marketing-for-small-businesses'],
            [
                'course_category_id' => $category->id,
                'title' => 'Digital Marketing for Small Businesses',
                'short_description' => 'Master practical online marketing, social media strategies, and customer acquisition without an expensive agency.',
                'description' => 'A complete, step-by-step digital marketing curriculum specifically built for small business owners, startup founders, and service providers. Learn how to identify your ideal customer, set up high-converting marketing funnels, and drive organic and paid traffic effectively.',
                'thumbnail' => null,
                'instructor_name' => 'Marketian Mind Team',
                'price' => 1999.00,
                'discount_price' => 999.00,
                'is_free' => false,
                'status' => CourseStatus::PUBLISHED,
                'featured' => true,
                'estimated_duration' => '6 hours',
            ]
        );

        // 3. Create or retrieve Module 1
        $module1 = CourseModule::updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'Marketing Foundation',
            ],
            [
                'description' => 'Understand the core concepts of value proposition, customer persona, and marketing fundamentals.',
                'sort_order' => 1,
            ]
        );

        // Lesson 1 under Module 1
        Lesson::updateOrCreate(
            ['slug' => 'understanding-your-customer'],
            [
                'course_module_id' => $module1->id,
                'title' => 'Understanding Your Customer',
                'description' => 'Discover how to define your ideal buyer persona and pinpoint their core pain points.',
                'lesson_type' => LessonType::VIDEO,
                'video_url' => null,
                'content' => 'In this lesson, you will learn how to profile your customer accurately, conduct fast market research, and speak your customer\'s language.',
                'duration' => '15 mins',
                'is_preview' => true,
                'sort_order' => 1,
                'status' => LessonStatus::PUBLISHED,
            ]
        );

        // 4. Create or retrieve Module 2
        $module2 = CourseModule::updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'Social Media Marketing',
            ],
            [
                'description' => 'Learn how to leverage organic social channels to build brand trust and generate customer inquiries.',
                'sort_order' => 2,
            ]
        );

        // Lesson 2 under Module 2
        Lesson::updateOrCreate(
            ['slug' => 'creating-your-social-media-strategy'],
            [
                'course_module_id' => $module2->id,
                'title' => 'Creating Your Social Media Strategy',
                'description' => 'Step-by-step blueprint to build a weekly social media content calendar that drives engagement.',
                'lesson_type' => LessonType::VIDEO,
                'video_url' => null,
                'content' => 'Develop an actionable content calendar, master hook formulas, and consistently publish high-performing posts.',
                'duration' => '20 mins',
                'is_preview' => false,
                'sort_order' => 1,
                'status' => LessonStatus::PUBLISHED,
            ]
        );
    }
}
