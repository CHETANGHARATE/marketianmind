<?php

namespace App\Services;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationStatus;
use App\Enums\AutomationTrigger;
use App\Mail\MarketingAutomationMail;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Lead;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MarketingAutomationService
{
    /**
     * Dispatch an automation trigger for a given recipient.
     */
    public function dispatchTrigger(
        AutomationTrigger|string $trigger,
        Model $recipient,
        array $context = [],
        ?string $referenceId = null
    ): int {
        $triggerValue = $trigger instanceof AutomationTrigger ? $trigger->value : (string) $trigger;
        $recipientType = ($recipient instanceof Lead) ? 'lead' : 'user';
        $reference = ($referenceId !== null && $referenceId !== '') ? (string) $referenceId : 'default';

        $automations = Automation::active()
            ->trigger($triggerValue)
            ->with('template')
            ->get();

        $dispatched = 0;

        foreach ($automations as $automation) {
            // Check condition matching
            if (!empty($automation->conditions)) {
                if (!$this->matchesConditions($recipient, $automation->conditions, $context)) {
                    continue;
                }
            }

            $scheduledAt = now()->addMinutes(max(0, (int) $automation->delay_minutes));

            $execution = AutomationExecution::firstOrCreate(
                [
                    'automation_id' => $automation->id,
                    'recipient_type' => $recipientType,
                    'recipient_id' => $recipient->id,
                    'trigger_event' => $triggerValue,
                    'reference_id' => $reference,
                ],
                [
                    'scheduled_at' => $scheduledAt,
                    'status' => AutomationExecutionStatus::PENDING->value,
                    'channel' => $automation->channel ?? 'email',
                    'metadata' => $context,
                ]
            );

            if ($execution->wasRecentlyCreated) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    /**
     * Determine if a recipient matches the specified conditions.
     */
    public function matchesConditions(Model $recipient, ?array $conditions, array $context = []): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $expectedValue) {
            // Check attribute on recipient model
            if (isset($recipient->{$field}) || array_key_exists($field, $recipient->getAttributes())) {
                $actualValue = $recipient->{$field};
                if ($actualValue instanceof \BackedEnum) {
                    $actualValue = $actualValue->value;
                }

                if ((string) $actualValue !== (string) $expectedValue) {
                    return false;
                }
            } elseif (array_key_exists($field, $context)) {
                // Check in execution context metadata
                if ((string) $context[$field] !== (string) $expectedValue) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Process due automation executions.
     */
    public function processDueExecutions(int $batchSize = 50): array
    {
        $dueExecutions = AutomationExecution::with(['automation.template', 'automation.whatsappTemplate'])
            ->where('status', AutomationExecutionStatus::PENDING->value)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit($batchSize)
            ->get();

        $stats = [
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($dueExecutions as $execution) {
            $stats['processed']++;

            // Transition to processing state
            $execution->status = AutomationExecutionStatus::PROCESSING;
            $execution->save();

            try {
                $automation = $execution->automation;

                if (!$automation) {
                    $execution->status = AutomationExecutionStatus::SKIPPED;
                    $execution->failure_reason = 'Automation rule no longer exists.';
                    $execution->save();
                    $stats['skipped']++;
                    continue;
                }

                // If paused, revert to pending so it runs when resumed
                if ($automation->status === AutomationStatus::PAUSED) {
                    $execution->status = AutomationExecutionStatus::PENDING;
                    $execution->save();
                    continue;
                }

                // If archived or not active, skip
                if ($automation->status !== AutomationStatus::ACTIVE) {
                    $execution->status = AutomationExecutionStatus::SKIPPED;
                    $execution->failure_reason = "Automation is in {$automation->status->value} state.";
                    $execution->save();
                    $stats['skipped']++;
                    continue;
                }

                $isWhatsApp = ($execution->channel === 'whatsapp' || ($automation->channel === 'whatsapp'));

                if ($isWhatsApp) {
                    $whatsappTemplate = $automation->whatsappTemplate;
                    if (!$whatsappTemplate) {
                        $execution->status = AutomationExecutionStatus::FAILED;
                        $execution->failure_reason = 'Associated WhatsApp template was not found.';
                        $execution->save();
                        $stats['failed']++;
                        continue;
                    }

                    $recipient = $execution->getRecipient();
                    if (!$recipient) {
                        $execution->status = AutomationExecutionStatus::SKIPPED;
                        $execution->failure_reason = 'Recipient entity no longer exists.';
                        $execution->save();
                        $stats['skipped']++;
                        continue;
                    }

                    $phone = $recipient->phone_normalized ?? $recipient->phone;
                    if (empty($phone)) {
                        $execution->status = AutomationExecutionStatus::FAILED;
                        $execution->failure_reason = 'Recipient does not have a valid phone number.';
                        $execution->save();
                        $stats['failed']++;
                        continue;
                    }

                    // Check opt-in if marketing template
                    if ($whatsappTemplate->category->value === 'marketing' && method_exists($recipient, 'hasWhatsAppOptIn') && !$recipient->hasWhatsAppOptIn()) {
                        $execution->status = AutomationExecutionStatus::SKIPPED;
                        $execution->failure_reason = 'Recipient has not opted in to WhatsApp marketing communications.';
                        $execution->save();
                        $stats['skipped']++;
                        continue;
                    }

                    // Verify conditions still apply
                    if (!empty($automation->conditions)) {
                        if (!$this->matchesConditions($recipient, $automation->conditions, $execution->metadata ?? [])) {
                            $execution->status = AutomationExecutionStatus::SKIPPED;
                            $execution->failure_reason = 'Recipient no longer meets automation criteria.';
                            $execution->save();
                            $stats['skipped']++;
                            continue;
                        }
                    }

                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    $result = $whatsappService->sendTemplateMessage(
                        recipient: $recipient,
                        template: $whatsappTemplate,
                        context: $execution->metadata ?? [],
                        referenceType: 'automation_execution',
                        referenceId: (string) $execution->id
                    );

                    if ($result->success && $result->message) {
                        $execution->whatsapp_message_id = $result->message->id;
                        $execution->status = AutomationExecutionStatus::SENT;
                        $execution->executed_at = now();
                        $execution->failure_reason = null;
                        $execution->save();
                        $stats['sent']++;
                    } else {
                        $execution->status = AutomationExecutionStatus::FAILED;
                        $execution->failure_reason = $result->error ?? 'WhatsApp message delivery failed.';
                        if ($result->message) {
                            $execution->whatsapp_message_id = $result->message->id;
                        }
                        $execution->save();
                        $stats['failed']++;
                    }
                    continue;
                }

                $template = $automation->template;
                if (!$template) {
                    $execution->status = AutomationExecutionStatus::FAILED;
                    $execution->failure_reason = 'Associated marketing template was not found.';
                    $execution->save();
                    $stats['failed']++;
                    continue;
                }

                $recipient = $execution->getRecipient();
                if (!$recipient) {
                    $execution->status = AutomationExecutionStatus::SKIPPED;
                    $execution->failure_reason = 'Recipient entity no longer exists.';
                    $execution->save();
                    $stats['skipped']++;
                    continue;
                }

                $email = $recipient->email;
                if (empty($email)) {
                    $execution->status = AutomationExecutionStatus::FAILED;
                    $execution->failure_reason = 'Recipient does not have a valid email address.';
                    $execution->save();
                    $stats['failed']++;
                    continue;
                }

                // Check Unsubscribe Status
                if (MarketingUnsubscribe::isUnsubscribed($email)) {
                    $execution->status = AutomationExecutionStatus::SKIPPED;
                    $execution->failure_reason = 'Recipient has opted out / unsubscribed from marketing communications.';
                    $execution->save();
                    $stats['skipped']++;
                    continue;
                }

                // Verify conditions still apply
                if (!empty($automation->conditions)) {
                    if (!$this->matchesConditions($recipient, $automation->conditions, $execution->metadata ?? [])) {
                        $execution->status = AutomationExecutionStatus::SKIPPED;
                        $execution->failure_reason = 'Recipient no longer meets automation criteria.';
                        $execution->save();
                        $stats['skipped']++;
                        continue;
                    }
                }

                // Prepare variable context
                $unsubscribeUrl = $this->generateUnsubscribeUrl($email);
                $context = $this->buildVariableContext($recipient, $execution->metadata ?? [], $unsubscribeUrl);

                $subject = $this->renderTemplate($template->subject, $context);
                $bodyHtml = $this->renderTemplate($template->body_html, $context);

                // Send email
                Mail::to($email)->send(new MarketingAutomationMail($subject, $bodyHtml, $unsubscribeUrl));

                // Mark execution sent
                $execution->status = AutomationExecutionStatus::SENT;
                $execution->executed_at = now();
                $execution->failure_reason = null;
                $execution->save();

                $stats['sent']++;
            } catch (\Throwable $e) {
                Log::error("Automation execution #{$execution->id} failed: " . $e->getMessage(), [
                    'exception' => $e,
                ]);

                $execution->status = AutomationExecutionStatus::FAILED;
                $execution->failure_reason = Str::limit($e->getMessage(), 500);
                $execution->save();

                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Build the replacement dictionary for template variables.
     */
    public function buildVariableContext(Model $recipient, array $metadata = [], ?string $unsubscribeUrl = null): array
    {
        $context = [
            'unsubscribe_url' => $unsubscribeUrl ?? '',
        ];

        if ($recipient instanceof Lead) {
            $context['lead.name'] = $recipient->name ?? '';
            $context['lead.email'] = $recipient->email ?? '';
            $context['lead.phone'] = $recipient->phone ?? '';
            $context['lead.company_name'] = $recipient->company_name ?? '';
            $context['lead.status'] = $recipient->status instanceof \BackedEnum ? $recipient->status->value : (string) $recipient->status;
        } elseif ($recipient instanceof User) {
            $context['user.name'] = $recipient->name ?? '';
            $context['user.email'] = $recipient->email ?? '';
        }

        // Check if course is in metadata
        if (!empty($metadata['course_id'])) {
            $course = Course::find($metadata['course_id']);
            if ($course) {
                $context['course.title'] = $course->title;
                $context['course.url'] = route('courses.show', $course->slug);
            }
        }

        // Check if bundle is in metadata
        if (!empty($metadata['bundle_id'])) {
            $bundle = Bundle::find($metadata['bundle_id']);
            if ($bundle) {
                $context['bundle.title'] = $bundle->title;
                $context['bundle.url'] = route('bundles.show', $bundle->slug);
            }
        }

        // Merge raw metadata as fallback
        foreach ($metadata as $key => $val) {
            if (is_scalar($val) && !isset($context[$key])) {
                $context[$key] = (string) $val;
            }
        }

        return $context;
    }

    /**
     * Safely render template content by replacing placeholders.
     */
    public function renderTemplate(string $content, array $context): string
    {
        $output = $content;

        foreach ($context as $key => $value) {
            $escapedValue = e($value);
            // Replace both {{ key }} and {{key}}
            $output = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/i', $escapedValue, $output);
        }

        // Strip any remaining unmapped placeholders {{ ... }}
        $output = preg_replace('/\{\{[\s\S]*?\}\}/', '', $output);

        return $output;
    }

    /**
     * Generate a tamper-proof unsubscribe URL for the given email.
     */
    public function generateUnsubscribeUrl(string $email): string
    {
        return URL::signedRoute('marketing.unsubscribe', ['email' => strtolower(trim($email))]);
    }
}
