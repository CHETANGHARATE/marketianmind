<?php

namespace App\Services;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LeadService
{
    /**
     * Create a new lead or deduplicate with an existing unconverted lead.
     */
    public function createOrDeduplicateLead(array $data, ?string $ip = null): Lead
    {
        $email = strtolower(trim($data['email']));

        // Check for existing unconverted lead with same email
        $existingLead = Lead::query()
            ->where('email', $email)
            ->whereNull('converted_user_id')
            ->latest()
            ->first();

        if ($existingLead) {
            $phoneNormalizer = app(\App\Services\PhoneNormalizationService::class);
            // Update contact / interest details if newly provided
            $updates = [];
            if (! empty($data['phone'])) {
                $updates['phone'] = $data['phone'];
                $updates['phone_normalized'] = $phoneNormalizer->normalize($data['phone'], $data['country'] ?? $existingLead->country ?? 'India');
            }
            if (isset($data['whatsapp_opt_in'])) {
                $updates['whatsapp_opt_in'] = (bool) $data['whatsapp_opt_in'];
                if ($updates['whatsapp_opt_in']) {
                    $updates['whatsapp_opted_in_at'] = now();
                    $updates['whatsapp_opted_out_at'] = null;
                    $updates['whatsapp_consent_source'] = $data['source'] ?? 'website';
                } else {
                    $updates['whatsapp_opted_out_at'] = now();
                }
            }
            if (! empty($data['company_name'])) {
                $updates['company_name'] = $data['company_name'];
            }
            if (! empty($data['job_title'])) {
                $updates['job_title'] = $data['job_title'];
            }
            if (! empty($data['city'])) {
                $updates['city'] = $data['city'];
            }
            if (! empty($data['state'])) {
                $updates['state'] = $data['state'];
            }
            if (! empty($data['course_id'])) {
                $updates['course_id'] = $data['course_id'];
            }
            if (! empty($data['bundle_id'])) {
                $updates['bundle_id'] = $data['bundle_id'];
            }

            $updates['last_contacted_at'] = now();

            // If previously closed or lost, re-open as NEW inquiry
            if (in_array($existingLead->status, [LeadStatus::LOST, LeadStatus::CLOSED, LeadStatus::NOT_INTERESTED], true)) {
                $updates['status'] = LeadStatus::NEW;
            }

            if (! empty($updates)) {
                $existingLead->update($updates);
            }

            $inquiryDetail = $data['message'] ?? $data['subject'] ?? 'New website contact form submission';
            $existingLead->recordActivity(
                'inquiry_submitted',
                "Additional inquiry submitted via {$data['source']}: " . Str::limit($inquiryDetail, 100),
                ['ip' => $ip, 'source' => $data['source'] ?? 'website']
            );

            // Record as internal note for easy reading in CRM
            $existingLead->notes()->create([
                'user_id' => null,
                'content' => "Repeated Public Inquiry ({$data['source']}):\nSubject: " . ($data['subject'] ?? 'N/A') . "\nMessage: " . ($data['message'] ?? 'N/A'),
            ]);

            return $existingLead;
        }

        $phoneNormalizer = app(\App\Services\PhoneNormalizationService::class);
        $phone = $data['phone'] ?? null;
        $country = $data['country'] ?? 'India';
        $phoneNormalized = !empty($phone) ? $phoneNormalizer->normalize($phone, $country) : null;
        $whatsappOptIn = !empty($data['whatsapp_opt_in']);

        // New lead creation with safe defaults
        $payload = [
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'phone_normalized' => $phoneNormalized,
            'whatsapp_opt_in' => $whatsappOptIn,
            'whatsapp_opted_in_at' => $whatsappOptIn ? now() : null,
            'whatsapp_consent_source' => $whatsappOptIn ? ($data['source'] ?? 'website') : null,
            'company_name' => $data['company_name'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $country,
            'course_id' => $data['course_id'] ?? null,
            'bundle_id' => $data['bundle_id'] ?? null,
            'source' => $data['source'] ?? 'website',
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => LeadStatus::NEW->value,
            'priority' => LeadPriority::MEDIUM->value,
            'last_contacted_at' => now(),
            'ip_address' => $ip,
        ];

        $lead = Lead::create($payload);

        $lead->recordActivity(
            'created',
            "Lead inquiry captured from source '{$lead->source}'",
            ['ip' => $ip, 'source' => $lead->source]
        );

        app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
            \App\Enums\AutomationTrigger::LEAD_CREATED,
            $lead,
            ['source' => $lead->source, 'course_id' => $lead->course_id, 'bundle_id' => $lead->bundle_id],
            'lead_created_' . $lead->id
        );

        return $lead;
    }

    /**
     * Explicitly convert a lead to a student user in a safe database transaction.
     *
     * @throws \Exception If already converted
     */
    public function convertLeadToStudent(Lead $lead, ?User $actor = null, ?string $password = null): User
    {
        if ($lead->isConverted() && $lead->converted_user_id) {
            $user = User::find($lead->converted_user_id);
            if ($user) {
                return $user;
            }
        }

        return DB::transaction(function () use ($lead, $actor, $password) {
            // Check for existing registered user matching lead's email
            $user = User::where('email', strtolower($lead->email))->first();

            if (! $user) {
                // Generate a secure random password if none provided
                $plainPassword = $password ?? Str::random(16);

                $user = User::create([
                    'name' => $lead->name,
                    'email' => strtolower($lead->email),
                    'password' => Hash::make($plainPassword),
                    'role' => UserRole::STUDENT,
                    'email_verified_at' => now(),
                    'phone' => $lead->phone,
                    'phone_normalized' => $lead->phone_normalized,
                    'whatsapp_opt_in' => (bool) $lead->whatsapp_opt_in,
                    'whatsapp_opted_in_at' => $lead->whatsapp_opted_in_at,
                    'whatsapp_consent_source' => $lead->whatsapp_consent_source,
                ]);
            } else {
                if (empty($user->phone) && !empty($lead->phone)) {
                    $user->phone = $lead->phone;
                    $user->phone_normalized = $lead->phone_normalized;
                    if ($lead->whatsapp_opt_in && !$user->whatsapp_opt_in) {
                        $user->whatsapp_opt_in = true;
                        $user->whatsapp_opted_in_at = $lead->whatsapp_opted_in_at ?? now();
                        $user->whatsapp_consent_source = $lead->whatsapp_consent_source ?? 'lead_conversion';
                    }
                    $user->save();
                }
            }

            $lead->update([
                'status' => LeadStatus::CONVERTED,
                'converted_at' => now(),
                'converted_user_id' => $user->id,
            ]);

            $lead->recordActivity(
                'converted',
                "Lead converted to student user '{$user->name}' (ID: {$user->id})",
                ['user_id' => $user->id, 'user_email' => $user->email],
                $actor
            );

            return $user;
        });
    }

    /**
     * Automatically convert any matching unconverted leads upon student registration or purchase.
     */
    public function autoConvertMatchingLeads(User $user, string $trigger = 'registration'): void
    {
        $unconvertedLeads = Lead::query()
            ->where('email', strtolower($user->email))
            ->whereNull('converted_user_id')
            ->get();

        foreach ($unconvertedLeads as $lead) {
            $lead->update([
                'status' => LeadStatus::CONVERTED,
                'converted_at' => now(),
                'converted_user_id' => $user->id,
            ]);

            $lead->recordActivity(
                'converted',
                "Automatically converted to student upon {$trigger} (User ID: {$user->id})",
                ['trigger' => $trigger, 'user_id' => $user->id]
            );
        }
    }
}
