<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Lesson;
use Database\Seeders\CourseDatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_course_category_with_valid_attributes(): void
    {
        $category = CourseCategory::create([
            'name' => 'SEO Mastery',
            'slug' => 'seo-mastery',
            'description' => 'Learn search engine optimization.',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('course_categories', [
            'name' => 'SEO Mastery',
            'slug' => 'seo-mastery',
            'status' => 'active',
        ]);
        $this->assertTrue($category->isActive());
    }

    public function test_course_category_slug_and_name_must_be_unique(): void
    {
        CourseCategory::create([
            'name' => 'Social Media',
            'slug' => 'social-media',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->expectException(QueryException::class);

        CourseCategory::create([
            'name' => 'Social Media',
            'slug' => 'social-media',
            'status' => CategoryStatus::ACTIVE,
        ]);
    }

    public function test_can_create_course_associated_with_category(): void
    {
        $category = CourseCategory::create([
            'name' => 'Email Marketing',
            'slug' => 'email-marketing',
        ]);

        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Cold Email Outreach',
            'slug' => 'cold-email-outreach',
            'short_description' => 'Learn how to generate leads via cold email.',
            'description' => 'Comprehensive cold email curriculum for founders.',
            'price' => 1499.00,
            'discount_price' => 799.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'featured' => true,
            'estimated_duration' => '3 hours',
        ]);

        $this->assertDatabaseHas('courses', [
            'slug' => 'cold-email-outreach',
            'course_category_id' => $category->id,
            'status' => 'published',
            'is_free' => 0,
            'featured' => 1,
        ]);

        $this->assertInstanceOf(CourseCategory::class, $course->category);
        $this->assertEquals($category->id, $course->category->id);
        $this->assertTrue($category->courses->contains($course));
        $this->assertTrue($course->isPublished());
        $this->assertTrue($course->hasDiscount());
        $this->assertEquals(799.00, $course->effectivePrice());
    }

    public function test_deleting_course_category_nullifies_course_category_id(): void
    {
        $category = CourseCategory::create([
            'name' => 'Growth Hacking',
            'slug' => 'growth-hacking',
        ]);

        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Startup Growth 101',
            'slug' => 'startup-growth-101',
            'short_description' => 'Grow your startup quickly.',
            'description' => 'Full growth hacking course.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->assertEquals(0.00, $course->effectivePrice());

        $category->delete();

        $course->refresh();
        $this->assertNull($course->course_category_id);
        $this->assertNull($course->category);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'course_category_id' => null,
        ]);
    }

    public function test_can_create_course_modules_with_sort_ordering(): void
    {
        $course = Course::create([
            'title' => 'Performance Ads',
            'slug' => 'performance-ads',
            'short_description' => 'Meta & Google Ads mastery.',
            'description' => 'Learn how to run profitable ads.',
        ]);

        $mod2 = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 2: Meta Ads',
            'sort_order' => 2,
        ]);

        $mod1 = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Advertising Mindset',
            'sort_order' => 1,
        ]);

        $modules = $course->modules;
        $this->assertCount(2, $modules);
        $this->assertEquals('Module 1: Advertising Mindset', $modules->first()->title);
        $this->assertEquals('Module 2: Meta Ads', $modules->last()->title);
    }

    public function test_deleting_course_cascades_and_deletes_modules(): void
    {
        $course = Course::create([
            'title' => 'Conversion Optimization',
            'slug' => 'conversion-optimization',
            'short_description' => 'Improve landing page conversion.',
            'description' => 'In-depth CRO guide.',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Wireframing',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('course_modules', ['id' => $module->id]);

        $course->delete();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
    }

    public function test_can_create_lessons_and_retrieve_via_course_module(): void
    {
        $course = Course::create([
            'title' => 'Content Marketing',
            'slug' => 'content-marketing',
            'short_description' => 'Blogging and content creation.',
            'description' => 'Drive organic traffic with high-value content.',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Content Research',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Finding Customer Questions',
            'slug' => 'finding-customer-questions',
            'lesson_type' => LessonType::VIDEO,
            'duration' => '10 mins',
            'is_preview' => true,
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->assertDatabaseHas('lessons', [
            'slug' => 'finding-customer-questions',
            'is_preview' => 1,
            'lesson_type' => 'video',
            'status' => 'published',
        ]);

        $this->assertTrue($lesson->isPreview());
        $this->assertTrue($lesson->isPublished());
        $this->assertTrue($lesson->isVideo());
        $this->assertInstanceOf(CourseModule::class, $lesson->module);
        $this->assertTrue($module->lessons->contains($lesson));
    }

    public function test_deleting_course_module_cascades_and_deletes_lessons(): void
    {
        $course = Course::create([
            'title' => 'Brand Storytelling',
            'slug' => 'brand-storytelling',
            'short_description' => 'Tell your brand story.',
            'description' => 'Connect emotionally with customers.',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Origin Story',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Crafting the Hook',
            'slug' => 'crafting-the-hook',
            'lesson_type' => LessonType::TEXT,
            'is_preview' => false,
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);

        $module->delete();

        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_course_has_many_through_lessons_relationship(): void
    {
        $course = Course::create([
            'title' => 'Influencer Marketing',
            'slug' => 'influencer-marketing',
            'short_description' => 'Partner with niche creators.',
            'description' => 'Guide to nano and micro influencer campaigns.',
        ]);

        $module1 = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Outreach',
            'sort_order' => 1,
        ]);

        $module2 = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 2: Contracts & Deals',
            'sort_order' => 2,
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module1->id,
            'title' => 'Outreach Email Script',
            'slug' => 'outreach-email-script',
            'lesson_type' => LessonType::TEXT,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module2->id,
            'title' => 'Deliverables Agreement Checklist',
            'slug' => 'deliverables-agreement-checklist',
            'lesson_type' => LessonType::PDF,
            'sort_order' => 1,
        ]);

        $lessons = $course->lessons;
        $this->assertCount(2, $lessons);
        $this->assertTrue($lessons->contains($lesson1));
        $this->assertTrue($lessons->contains($lesson2));
    }

    public function test_course_database_seeder_executes_safely_and_idempotently(): void
    {
        $seeder = new CourseDatabaseSeeder();
        $seeder->run();

        $this->assertDatabaseHas('course_categories', [
            'slug' => 'digital-marketing',
            'name' => 'Digital Marketing',
        ]);

        $this->assertDatabaseHas('courses', [
            'slug' => 'digital-marketing-for-small-businesses',
            'status' => 'published',
            'featured' => 1,
        ]);

        $this->assertDatabaseHas('course_modules', [
            'title' => 'Marketing Foundation',
        ]);

        $this->assertDatabaseHas('course_modules', [
            'title' => 'Social Media Marketing',
        ]);

        $this->assertDatabaseHas('lessons', [
            'slug' => 'understanding-your-customer',
            'is_preview' => 1,
        ]);

        $this->assertDatabaseHas('lessons', [
            'slug' => 'creating-your-social-media-strategy',
            'is_preview' => 0,
        ]);

        // Run second time to verify idempotency (no duplicates or exceptions)
        $seeder->run();

        $this->assertEquals(1, CourseCategory::where('slug', 'digital-marketing')->count());
        $this->assertEquals(1, Course::where('slug', 'digital-marketing-for-small-businesses')->count());
        $this->assertEquals(2, CourseModule::count());
        $this->assertEquals(2, Lesson::count());
    }
}
