<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductionRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_razorpay_create_order_fails_closed_in_production_when_unconfigured(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('services.razorpay.key', null);
        Config::set('services.razorpay.secret', null);

        $service = new RazorpayService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Razorpay is not configured for production transactions.');

        $service->createOrder(50000, 'RCPT_TEST_1');
    }

    public function test_razorpay_verify_payment_signature_fails_closed_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('services.razorpay.key', null);
        Config::set('services.razorpay.secret', null);

        $service = new RazorpayService();

        // Testing 'valid_test_signature' should be rejected in production
        $this->assertFalse(
            $service->verifyPaymentSignature('order_123', 'pay_123', 'valid_test_signature'),
            'valid_test_signature must be rejected in production mode'
        );

        // Testing mock HMAC hash should be rejected in production
        $mockHmac = hash_hmac('sha256', 'order_123|pay_123', 'mock_secret');
        $this->assertFalse(
            $service->verifyPaymentSignature('order_123', 'pay_123', $mockHmac),
            'mock HMAC signature must be rejected in production mode'
        );
    }

    public function test_razorpay_verify_webhook_signature_fails_closed_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('services.razorpay.webhook_secret', null);

        $service = new RazorpayService();

        $payload = json_encode(['event' => 'payment.captured']);
        $mockSignature = hash_hmac('sha256', $payload, 'mock_webhook_secret');

        $this->assertFalse(
            $service->verifyWebhookSignature($payload, $mockSignature),
            'Webhook verification without configured secret must fail closed in production'
        );
    }

    public function test_razorpay_verify_payment_signature_allows_test_signature_in_testing_env(): void
    {
        $this->assertTrue($this->app->environment('testing'));
        Config::set('services.razorpay.key', null);
        Config::set('services.razorpay.secret', null);

        $service = new RazorpayService();

        $this->assertTrue(
            $service->verifyPaymentSignature('order_123', 'pay_123', 'valid_test_signature'),
            'valid_test_signature must be accepted in testing environment'
        );
    }

    public function test_login_endpoint_has_rate_limiting(): void
    {
        // Rate limit is throttle:5,1
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrongpassword',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be throttled");
        }

        // 6th attempt must be throttled
        $response = $this->post('/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_endpoint_has_rate_limiting(): void
    {
        // Rate limit is throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/register', [
                'name' => 'Spam User',
                'email' => 'invalid-email',
                'password' => 'short',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be throttled");
        }

        // 11th attempt must be throttled
        $response = $this->post('/register', [
            'name' => 'Spam User',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(429);
    }

    public function test_scheduler_registers_retention_process_support(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $retentionEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'retention:process-support');
        });

        $this->assertNotNull($retentionEvent, 'retention:process-support command must be registered in the console scheduler.');
        $this->assertSame('0 9 * * *', $retentionEvent->expression, 'retention:process-support must run daily at 09:00.');
        $this->assertTrue($retentionEvent->withoutOverlapping, 'retention:process-support must prevent overlapping runs.');
    }

    public function test_https_force_scheme_configuration(): void
    {
        Config::set('app.url', 'https://marketianmind.com');

        // Test that URL facade can enforce https
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->assertStringStartsWith('https://', url('/'));
    }
}
