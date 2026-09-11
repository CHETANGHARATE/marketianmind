<?php

namespace App\Services;

use App\Mail\CertificateIssuedMail;
use App\Mail\CourseCompletionMail;
use App\Mail\CourseEnrollmentMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\TestMail;
use App\Mail\WelcomeStudentMail;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TransactionalMailService
{
    /**
     * Send welcome email to newly registered student.
     */
    public function sendWelcome(User $user): bool
    {
        return $this->safelySend($user->email, new WelcomeStudentMail($user));
    }

    /**
     * Send order confirmation email upon successful payment or free checkout.
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        $email = $order->user ? $order->user->email : null;
        if (! $email) {
            return false;
        }

        return $this->safelySend($email, new OrderConfirmationMail($order));
    }

    /**
     * Send course enrollment confirmation email to student.
     */
    public function sendCourseEnrollment(User $user, Course $course): bool
    {
        return $this->safelySend($user->email, new CourseEnrollmentMail($user, $course));
    }

    /**
     * Send course completion congratulations email.
     */
    public function sendCourseCompletion(User $user, Course $course): bool
    {
        return $this->safelySend($user->email, new CourseCompletionMail($user, $course));
    }

    /**
     * Send certificate ready notification email.
     */
    public function sendCertificateIssued(Certificate $certificate): bool
    {
        $email = $certificate->user ? $certificate->user->email : null;
        if (! $email) {
            return false;
        }

        return $this->safelySend($email, new CertificateIssuedMail($certificate));
    }

    /**
     * Send test email to verify SMTP configuration.
     */
    public function sendTestMail(string $toEmail): bool
    {
        return $this->safelySend($toEmail, new TestMail($toEmail));
    }

    /**
     * Safely deliver mailable with error trapping to protect business transactions.
     */
    protected function safelySend(string $recipientEmail, Mailable $mailable): bool
    {
        try {
            Mail::to($recipientEmail)->send($mailable);
            return true;
        } catch (Throwable $e) {
            Log::warning('Transactional email delivery failed', [
                'recipient' => $recipientEmail,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}