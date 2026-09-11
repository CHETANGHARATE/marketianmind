@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Thank you for your order!</h1>
    <p class="text">
        Your order has been confirmed and your lifetime access to the course is now active. Here are your transaction details:
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Order Number</span>
            <span class="info-value">#{{ $order->order_number ?? $order->id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Course</span>
            <span class="info-value">{{ $order->course ? $order->course->title : 'Course Access' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $order->created_at->format('M d, Y') }}</span>
        </div>
        @if($order->coupon_code)
            <div class="info-row">
                <span class="info-label">Coupon Applied</span>
                <span class="info-value">{{ $order->coupon_code }} (-₹{{ number_format($order->discount_amount, 2) }})</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">Amount Paid</span>
            <span class="info-value" style="color: #059669; font-size: 15px;">
                {{ $order->amount > 0 ? '₹' . number_format($order->amount, 2) : 'Free' }}
            </span>
        </div>
    </div>

    <div class="btn-box">
        @if($order->course)
            <a href="{{ route('student.courses.show', $order->course) }}" class="btn">
                Start Learning Now &rarr;
            </a>
        @else
            <a href="{{ route('student.my-learning') }}" class="btn">
                Go to My Learning &rarr;
            </a>
        @endif
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        You can review your complete purchase receipt anytime in your <a href="{{ route('student.orders.show', $order) }}" style="color: #4f46e5;">Order History</a>.
    </p>
@endsection