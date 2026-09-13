<?php

namespace App\Services;

class PhoneNormalizationService
{
    /**
     * Normalize a phone number to standard E.164 format (+919876543210).
     */
    public function normalize(?string $phone, ?string $country = 'India'): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Clean whitespaces, hyphens, parentheses, and dots
        $cleaned = trim($phone);
        $hasLeadingPlus = str_starts_with($cleaned, '+');
        $digitsOnly = preg_replace('/\D/', '', $cleaned);

        if (empty($digitsOnly)) {
            return null;
        }

        $countryUpper = strtoupper(trim((string) $country));
        $isIndia = in_array($countryUpper, ['INDIA', 'IN', '91', '+91', ''], true);

        // Indian Number Handling
        if ($isIndia) {
            // 10 digits: e.g. 9876543210
            if (strlen($digitsOnly) === 10) {
                $candidate = '+91' . $digitsOnly;
            }
            // 11 digits starting with 0: e.g. 09876543210
            elseif (strlen($digitsOnly) === 11 && str_starts_with($digitsOnly, '0')) {
                $candidate = '+91' . substr($digitsOnly, 1);
            }
            // 12 digits starting with 91: e.g. 919876543210
            elseif (strlen($digitsOnly) === 12 && str_starts_with($digitsOnly, '91')) {
                $candidate = '+' . $digitsOnly;
            }
            // If leading plus with other digits
            elseif ($hasLeadingPlus && strlen($digitsOnly) >= 10 && strlen($digitsOnly) <= 15) {
                $candidate = '+' . $digitsOnly;
            } else {
                return null;
            }
        } else {
            // International number
            if ($hasLeadingPlus) {
                $candidate = '+' . $digitsOnly;
            } elseif (strlen($digitsOnly) >= 10 && strlen($digitsOnly) <= 15) {
                // Fallback assume international with leading plus if long enough
                $candidate = '+' . $digitsOnly;
            } else {
                return null;
            }
        }

        // Validate standard E.164: + followed by 8 to 15 digits
        if (preg_match('/^\+[1-9]\d{7,14}$/', $candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Format a phone number specifically for Meta WhatsApp Cloud API (digits without leading plus).
     */
    public function toMetaFormat(?string $phone, ?string $country = 'India'): ?string
    {
        $normalized = $this->normalize($phone, $country);
        if (! $normalized) {
            return null;
        }

        return ltrim($normalized, '+');
    }

    /**
     * Determine if a phone number can be normalized to valid E.164.
     */
    public function isValid(?string $phone, ?string $country = 'India'): bool
    {
        return $this->normalize($phone, $country) !== null;
    }
}
