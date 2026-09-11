<?php

namespace Tests\Feature;

use App\Enums\CouponDiscountType;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery-' . uniqid(),
            'short_description' => 'Comprehensive marketing masterclass',
            'price' => 2000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_admin_coupons(): void
    {
        $response = $this->get(route('admin.coupons.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_coupons(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.coupons.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_coupons_index(): void
    {
        Coupon::create([
            'code' => 'SAVE20',
            'name' => 'Save 20 Percent',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertOk();
        $response->assertSee('Coupons & Discounts', false);
        $response->assertSee('SAVE20');
        $response->assertSee('Save 20 Percent');
    }

    public function test_admin_can_search_and_filter_coupons(): void
    {
        Coupon::create([
            'code' => 'ALPHA10',
            'name' => 'Alpha 10',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'BETA20',
            'name' => 'Beta 20',
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 20000, // ₹200
            'is_active' => false,
        ]);

        // Search by code
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.coupons.index', ['search' => 'ALPHA']));
        $searchResponse->assertSee('ALPHA10');
        $searchResponse->assertDontSee('BETA20');

        // Filter by status inactive
        $statusResponse = $this->actingAs($this->admin)->get(route('admin.coupons.index', ['status' => 'inactive']));
        $statusResponse->assertSee('BETA20');
        $statusResponse->assertDontSee('ALPHA10');

        // Filter by type fixed
        $typeResponse = $this->actingAs($this->admin)->get(route('admin.coupons.index', ['type' => 'fixed']));
        $typeResponse->assertSee('BETA20');
        $typeResponse->assertDontSee('ALPHA10');
    }

    public function test_admin_can_view_create_coupon_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.create'));
        $response->assertOk();
        $response->assertSee('Create Promotional Coupon');
    }

    public function test_admin_can_create_percentage_coupon(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'FOUNDER30',
            'name' => 'Founder 30 Percent Off',
            'description' => '30 percent discount for early founders',
            'discount_type' => 'percentage',
            'discount_value' => 30,
            'min_order_amount' => 1000,
            'max_discount_amount' => 500,
            'per_user_limit' => 2,
            'usage_limit' => 100,
            'is_active' => '1',
            'course_id' => $this->course->id,
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('coupons', [
            'code' => 'FOUNDER30',
            'name' => 'Founder 30 Percent Off',
            'discount_type' => 'percentage',
            'discount_value' => 30,
            'min_order_amount' => 100000, // converted to paise
            'max_discount_amount' => 50000, // converted to paise
            'per_user_limit' => 2,
            'usage_limit' => 100,
            'is_active' => 1,
            'course_id' => $this->course->id,
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon_created',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_fixed_amount_coupon(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'FLAT500',
            'name' => 'Flat ₹500 Discount',
            'discount_type' => 'fixed',
            'discount_value' => 500, // in rupees
            'per_user_limit' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'FLAT500',
            'discount_type' => 'fixed',
            'discount_value' => 50000, // 500 * 100 paise
            'course_id' => null, // all courses
        ]);
    }

    public function test_admin_cannot_create_coupon_with_duplicate_code(): void
    {
        Coupon::create([
            'code' => 'DUPLICATE',
            'name' => 'Existing Coupon',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'duplicate', // test case-insensitivity
            'name' => 'New Coupon',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'per_user_limit' => 1,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_admin_cannot_create_percentage_coupon_over_100(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'INVALID150',
            'name' => 'Invalid Discount',
            'discount_type' => 'percentage',
            'discount_value' => 150,
            'per_user_limit' => 1,
        ]);

        $response->assertSessionHasErrors(['discount_value']);
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'UPDATE10',
            'name' => 'Original Name',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.coupons.update', $coupon), [
            'code' => 'UPDATE20',
            'name' => 'Updated Name',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'per_user_limit' => 3,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'UPDATE20',
            'name' => 'Updated Name',
            'discount_value' => 20,
            'per_user_limit' => 3,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon_updated',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_toggle_coupon_active_status(): void
    {
        $coupon = Coupon::create([
            'code' => 'TOGGLEME',
            'name' => 'Toggle Coupon',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.coupons.toggle', $coupon));
        $response->assertRedirect(route('admin.coupons.index'));

        $coupon->refresh();
        $this->assertFalse($coupon->is_active);

        // Toggle back
        $this->actingAs($this->admin)->patch(route('admin.coupons.toggle', $coupon));
        $coupon->refresh();
        $this->assertTrue($coupon->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon_status_toggled',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_delete_unused_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'UNUSED',
            'name' => 'Unused Coupon',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.coupons.destroy', $coupon));
        $response->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon_deleted',
        ]);
    }

    public function test_admin_deactivates_coupon_with_historical_usages_instead_of_deleting(): void
    {
        $coupon = Coupon::create([
            'code' => 'HISTORIC',
            'name' => 'Historic Coupon',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'MM-ORD-TEST-001',
            'amount' => 180000,
            'currency' => 'INR',
            'status' => 'paid',
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $this->student->id,
            'order_id' => $order->id,
            'discount_amount' => 20000,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.coupons.destroy', $coupon));
        $response->assertRedirect(route('admin.coupons.index'));

        // Assert record still exists in DB but is inactive
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coupon_deactivated',
        ]);
    }
}