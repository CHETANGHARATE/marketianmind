<?php

namespace Tests\Feature;

use App\Enums\ReferralStatus;
use App\Enums\UserRole;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_referrals(): void
    {
        $response = $this->get(route('admin.referrals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_referrals(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);

        $response = $this->actingAs($student)->get(route('admin.referrals.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_referrals_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $referrer = User::factory()->create(['name' => 'Referrer Alice', 'role' => UserRole::STUDENT]);
        $referred = User::factory()->create(['name' => 'Referred Bob', 'role' => UserRole::STUDENT]);

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'referral_code' => $referrer->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.referrals.index'));

        $response->assertOk();
        $response->assertSee('Referral Program Management');
        $response->assertSee('Referrer Alice');
        $response->assertSee('Referred Bob');
    }

    public function test_admin_can_filter_referrals_by_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $u1 = User::factory()->create(['role' => UserRole::STUDENT]);
        $u2 = User::factory()->create(['name' => 'Student Registered', 'role' => UserRole::STUDENT]);
        $u3 = User::factory()->create(['name' => 'Student Converted', 'role' => UserRole::STUDENT]);

        Referral::create([
            'referrer_id' => $u1->id,
            'referred_id' => $u2->id,
            'referral_code' => $u1->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        Referral::create([
            'referrer_id' => $u1->id,
            'referred_id' => $u3->id,
            'referral_code' => $u1->getReferralCode(),
            'status' => ReferralStatus::CONVERTED,
            'converted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.referrals.index', ['status' => 'converted']));

        $response->assertOk();
        $response->assertSee('Student Converted');
        $response->assertDontSee('Student Registered');
    }

    public function test_admin_can_search_referrals(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $referrer = User::factory()->create(['name' => 'UniqueReferrerXYZ', 'role' => UserRole::STUDENT]);
        $referred = User::factory()->create(['name' => 'TargetReferredABC', 'role' => UserRole::STUDENT]);

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'referral_code' => $referrer->getReferralCode(),
            'status' => ReferralStatus::REGISTERED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.referrals.index', ['search' => 'UniqueReferrerXYZ']));

        $response->assertOk();
        $response->assertSee('UniqueReferrerXYZ');
        $response->assertSee('TargetReferredABC');
    }
}