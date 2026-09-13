<?php

namespace App\Enums;

enum AutomationTrigger: string
{
    case LEAD_CREATED = 'lead_created';
    case LEAD_STATUS_CHANGED = 'lead_status_changed';
    case LEAD_QUALIFIED = 'lead_qualified';
    case STUDENT_REGISTERED = 'student_registered';
    case COURSE_ENROLLED = 'course_enrolled';
    case BUNDLE_PURCHASED = 'bundle_purchased';
    case COURSE_COMPLETED = 'course_completed';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LEAD_CREATED => 'Lead Created',
            self::LEAD_STATUS_CHANGED => 'Lead Status Changed',
            self::LEAD_QUALIFIED => 'Lead Qualified',
            self::STUDENT_REGISTERED => 'Student Registered',
            self::COURSE_ENROLLED => 'Course Enrolled',
            self::BUNDLE_PURCHASED => 'Bundle Purchased',
            self::COURSE_COMPLETED => 'Course Completed',
        };
    }

    /**
     * Detailed description of when the trigger fires.
     */
    public function description(): string
    {
        return match ($this) {
            self::LEAD_CREATED => 'Fires immediately when a new inbound lead is captured.',
            self::LEAD_STATUS_CHANGED => 'Fires when an administrator or system changes a lead status.',
            self::LEAD_QUALIFIED => 'Fires specifically when a lead transitions to Qualified status.',
            self::STUDENT_REGISTERED => 'Fires when a new user registers an account on the platform.',
            self::COURSE_ENROLLED => 'Fires when a student successfully enrolls in an individual course.',
            self::BUNDLE_PURCHASED => 'Fires when a customer purchases a course bundle.',
            self::COURSE_COMPLETED => 'Fires when a student completes 100% of a course lessons.',
        };
    }

    /**
     * Recipient entity type ('lead' or 'user').
     */
    public function recipientType(): string
    {
        return match ($this) {
            self::LEAD_CREATED, self::LEAD_STATUS_CHANGED, self::LEAD_QUALIFIED => 'lead',
            self::STUDENT_REGISTERED, self::COURSE_ENROLLED, self::BUNDLE_PURCHASED, self::COURSE_COMPLETED => 'user',
        };
    }

    /**
     * Available template variables for this trigger.
     *
     * @return array<int, string>
     */
    public function availableVariables(): array
    {
        $common = ['{{ unsubscribe_url }}'];

        if ($this->recipientType() === 'lead') {
            return array_merge([
                '{{ lead.name }}',
                '{{ lead.email }}',
                '{{ lead.phone }}',
                '{{ lead.company_name }}',
                '{{ lead.status }}',
            ], $common);
        }

        $userVars = [
            '{{ user.name }}',
            '{{ user.email }}',
        ];

        if ($this === self::COURSE_ENROLLED || $this === self::COURSE_COMPLETED) {
            $userVars[] = '{{ course.title }}';
            $userVars[] = '{{ course.url }}';
        }

        if ($this === self::BUNDLE_PURCHASED) {
            $userVars[] = '{{ bundle.title }}';
            $userVars[] = '{{ bundle.url }}';
        }

        return array_merge($userVars, $common);
    }

    /**
     * Get all possible values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
