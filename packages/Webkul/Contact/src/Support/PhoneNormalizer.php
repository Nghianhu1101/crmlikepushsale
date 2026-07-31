<?php

namespace Webkul\Contact\Support;

class PhoneNormalizer
{
    /**
     * Normalize phone numbers for comparison and storage.
     */
    public function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '0084')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '84') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Accept practical Vietnamese mobile and landline lengths after normalization.
     */
    public function isValid(?string $phone): bool
    {
        return (bool) preg_match('/^0[1-9][0-9]{6,13}$/', $this->normalize($phone));
    }
}
