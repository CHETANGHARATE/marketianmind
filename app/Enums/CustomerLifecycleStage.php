<?php

namespace App\Enums;

enum CustomerLifecycleStage: string
{
    case LEAD = 'lead';
    case INTERESTED_PROSPECT = 'interested_prospect';
    case FIRST_TIME_BUYER = 'first_time_buyer';
    case ACTIVE_STUDENT = 'active_student';
    case ENGAGED_LEARNER = 'engaged_learner';
    case COURSE_COMPLETER = 'course_completer';
    case ACCESS_EXPIRING = 'access_expiring';
    case EXPIRED_STUDENT = 'expired_student';
    case RETURNING_CUSTOMER = 'returning_customer';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LEAD => 'Lead',
            self::INTERESTED_PROSPECT => 'Interested Prospect',
            self::FIRST_TIME_BUYER => 'First-Time Buyer',
            self::ACTIVE_STUDENT => 'Active Student',
            self::ENGAGED_LEARNER => 'Engaged Learner',
            self::COURSE_COMPLETER => 'Course Completer',
            self::ACCESS_EXPIRING => 'Access Expiring',
            self::EXPIRED_STUDENT => 'Expired Student',
            self::RETURNING_CUSTOMER => 'Returning Customer',
        };
    }

    /**
     * Detailed business description of this lifecycle stage.
     */
    public function description(): string
    {
        return match ($this) {
            self::LEAD => 'Inbound inquiry captured without verified purchase or enrollment.',
            self::INTERESTED_PROSPECT => 'Prospective student who registered or expressed high interest without completing a purchase.',
            self::FIRST_TIME_BUYER => 'Customer with a verified first purchase whose active access has begun.',
            self::ACTIVE_STUDENT => 'Enrolled student with currently valid course access.',
            self::ENGAGED_LEARNER => 'Active student who has recorded learning activity within the last 30 days.',
            self::COURSE_COMPLETER => 'Student who has achieved 100% completion in at least one course.',
            self::ACCESS_EXPIRING => 'Student whose active access validity ends within the next 30 days.',
            self::EXPIRED_STUDENT => 'Customer whose course access period has ended without active renewal.',
            self::RETURNING_CUSTOMER => 'Customer with multiple verified purchases or a completed course renewal.',
        };
    }

    /**
     * Tailwind CSS badge styling classes for admin interfaces.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::LEAD => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            self::INTERESTED_PROSPECT => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
            self::FIRST_TIME_BUYER => 'bg-teal-500/10 text-teal-400 border-teal-500/20',
            self::ACTIVE_STUDENT => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
            self::ENGAGED_LEARNER => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::COURSE_COMPLETER => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::ACCESS_EXPIRING => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
            self::EXPIRED_STUDENT => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            self::RETURNING_CUSTOMER => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
        };
    }

    /**
     * All values as array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
