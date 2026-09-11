<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDiscountType;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    /**
     * Display a listing of promotional coupons.
     */
    public function index(Request $request): View
    {
        $query = Coupon::query()->with('course');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($type = $request->input('type')) {
            $query->where('discount_type', $type);
        }

        $coupons = $query->latest()->paginate(15)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create(): View
    {
        $courses = Course::select('id', 'title')->orderBy('title')->get();

        return view('admin.coupons.create', compact('courses'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('discount_type') === CouponDiscountType::PERCENTAGE->value && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                },
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $discountValue = $validated['discount_type'] === CouponDiscountType::FIXED->value
            ? (int) round($validated['discount_value'] * 100)
            : (int) round($validated['discount_value']);

        $minOrderAmount = ! empty($validated['min_order_amount'])
            ? (int) round($validated['min_order_amount'] * 100)
            : null;

        $maxDiscountAmount = ! empty($validated['max_discount_amount'])
            ? (int) round($validated['max_discount_amount'] * 100)
            : null;

        $coupon = Coupon::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $discountValue,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_user_limit' => $validated['per_user_limit'],
            'is_active' => $request->boolean('is_active', true),
            'course_id' => $validated['course_id'] ?? null,
        ]);

        AuditLogger::log(
            'coupon_created',
            $coupon,
            "Created coupon '{$coupon->code}' ({$coupon->name}) with {$coupon->formattedDiscount()}."
        );

        return redirect()->route('admin.coupons.index')
            ->with('status', "Coupon '{$coupon->code}' created successfully!");
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit(Coupon $coupon): View
    {
        $courses = Course::select('id', 'title')->orderBy('title')->get();

        return view('admin.coupons.edit', compact('coupon', 'courses'));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('discount_type') === CouponDiscountType::PERCENTAGE->value && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                },
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $discountValue = $validated['discount_type'] === CouponDiscountType::FIXED->value
            ? (int) round($validated['discount_value'] * 100)
            : (int) round($validated['discount_value']);

        $minOrderAmount = ! empty($validated['min_order_amount'])
            ? (int) round($validated['min_order_amount'] * 100)
            : null;

        $maxDiscountAmount = ! empty($validated['max_discount_amount'])
            ? (int) round($validated['max_discount_amount'] * 100)
            : null;

        $coupon->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $discountValue,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_user_limit' => $validated['per_user_limit'],
            'is_active' => $request->boolean('is_active', true),
            'course_id' => $validated['course_id'] ?? null,
        ]);

        AuditLogger::log(
            'coupon_updated',
            $coupon,
            "Updated coupon '{$coupon->code}'."
        );

        return redirect()->route('admin.coupons.index')
            ->with('status', "Coupon '{$coupon->code}' updated successfully!");
    }

    /**
     * Toggle the active status of the coupon.
     */
    public function toggleStatus(Coupon $coupon): RedirectResponse
    {
        $newStatus = ! $coupon->is_active;
        $coupon->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'activated' : 'deactivated';

        AuditLogger::log(
            'coupon_status_toggled',
            $coupon,
            "Coupon '{$coupon->code}' was {$statusText}."
        );

        return redirect()->route('admin.coupons.index')
            ->with('status', "Coupon '{$coupon->code}' {$statusText} successfully.");
    }

    /**
     * Remove or deactivate the specified coupon.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;

        if ($coupon->usages()->exists()) {
            // If coupon has been used in past orders, deactivate to preserve audit & financial history
            $coupon->update(['is_active' => false]);

            AuditLogger::log(
                'coupon_deactivated',
                $coupon,
                "Coupon '{$code}' has historical usages and was deactivated instead of deleted."
            );

            return redirect()->route('admin.coupons.index')
                ->with('status', "Coupon '{$code}' has historical redemptions and was deactivated instead of deleted.");
        }

        AuditLogger::log(
            'coupon_deleted',
            $coupon,
            "Deleted unused coupon '{$code}'."
        );

        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('status', "Coupon '{$code}' deleted successfully.");
    }
}