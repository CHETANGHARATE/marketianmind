<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::create([
            'title' => 'Social Media Growth for Retail',
            'slug' => 'social-media-growth-retail',
            'short_description' => 'Drive foot traffic and online orders.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_can_view_contact_page_with_course_options(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('Contact Marketian Mind');
        $response->assertSee('Social Media Growth for Retail');
    }

    public function test_guest_can_submit_contact_form_successfully(): void
    {
        $data = [
            'name' => 'Rahul Verma',
            'email' => 'rahul@vermaelectronics.com',
            'phone' => '+91 9876543210',
            'course_id' => $this->course->id,
            'subject' => 'Retail Marketing Inquiry',
            'message' => 'We want to know if this course covers local Google business listings.',
            'website' => '', // Honeypot blank
        ];

        $response = $this->post(route('contact.submit'), $data);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'name' => 'Rahul Verma',
            'email' => 'rahul@vermaelectronics.com',
            'course_id' => $this->course->id,
            'source' => 'contact_page',
            'status' => LeadStatus::NEW->value,
        ]);
    }

    public function test_contact_submission_fails_with_invalid_email(): void
    {
        $data = [
            'name' => 'Bad Email User',
            'email' => 'not-an-email',
            'message' => 'Hello team.',
        ];

        $response = $this->post(route('contact.submit'), $data);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_honeypot_spam_submission_is_rejected(): void
    {
        $data = [
            'name' => 'Spam Bot 3000',
            'email' => 'spambot@marketing-crawler.com',
            'message' => 'Buy cheap crypto now!',
            'website' => 'http://spam-link.com', // Filled honeypot
        ];

        $response = $this->post(route('contact.submit'), $data);

        $response->assertSessionHasErrors('website');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_guest_can_submit_course_specific_inquiry(): void
    {
        $data = [
            'name' => 'Anita Desai',
            'email' => 'anita@craftstudio.in',
            'course_id' => $this->course->id,
            'source' => 'course_landing_faq',
            'subject' => 'Pre-enrollment question',
            'message' => 'Do you provide invoices with GST details?',
            'website' => '',
        ];

        $response = $this->from(route('courses.show', $this->course))->post(route('leads.store'), $data);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('lead_success');

        $this->assertDatabaseHas('leads', [
            'name' => 'Anita Desai',
            'email' => 'anita@craftstudio.in',
            'course_id' => $this->course->id,
            'source' => 'course_landing_faq',
            'status' => LeadStatus::NEW->value,
        ]);
    }

    public function test_json_lead_capture_submission_returns_json_response(): void
    {
        $data = [
            'name' => 'Sanjay Patel',
            'email' => 'sanjay@patelfoods.com',
            'message' => 'Interested in corporate group training.',
            'website' => '',
        ];

        $response = $this->postJson(route('leads.store'), $data);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('leads', [
            'email' => 'sanjay@patelfoods.com',
        ]);
    }

    public function test_matched_user_helper_identifies_registered_students(): void
    {
        $user = User::factory()->create([
            'email' => 'student@marketianmind.com',
            'role' => UserRole::STUDENT,
        ]);

        $lead = Lead::create([
            'name' => 'Existing Student',
            'email' => 'student@marketianmind.com',
            'message' => 'Question about my next certificate.',
            'status' => LeadStatus::NEW->value,
        ]);

        $this->assertNotNull($lead->matchedUser());
        $this->assertEquals($user->id, $lead->matchedUser()->id);
    }
}