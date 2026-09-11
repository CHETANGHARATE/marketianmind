<?php

namespace Tests\Feature;

use App\Enums\ReferralStatus;
use App\Enums\UserRole;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_student_referrals(): void
    {
        $response = $this->get(route('student.referrals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_can_view_their_referral_dashboard(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $friend = User::factory()->create(['name' => 'Friend One', 'role' => UserRole::STUDENT]);

        Referral::create([
            'referrer_id' => $student->id,
            'referred_id' => $friend->id,
            'referral_code' => $student->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $response = $this->actingAs($student)->get(route('student.referrals.index'));

        $response->assertOk();
        $response->assertSee('Refer Friends');
        $response->assertSee($student->getReferralCode());
        $response->assertSee('Friend One');
        $response->assertSee('Registered');
    }

    public function test_student_cannot_see_other_students_referrals(): void
    {
        $studentA = User::factory()->create(['role' => UserRole::STUDENT]);
        $studentB = User::factory()->create(['role' => UserRole::STUDENT]);
        $friendB = User::factory()->create(['name' => 'Secret Friend B', 'role' => UserRole::STUDENT]);

        Referral::create([
            'referrer_id' => $studentB->id,
            'referred_id' => $friendB->id,
            'referral_code' => $studentB->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $response = $this->actingAs($studentA)->get(route('student.referrals.index'));

        $response->assertOk();
        $response->assertDontSee('Secret Friend B');
    }
}