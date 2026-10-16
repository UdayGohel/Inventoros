<?php

namespace Plugins\GstBilling\Src;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The customer's GST profile, edited in the customer form's Business Details
 * card (fields under plugin_data.gst-billing) and stored in gst_customer_profiles.
 */
class GstCustomerProfile
{
    public const FIELDS = 'plugin_data.gst-billing';

    public const REGISTRATION_TYPES = ['Regular', 'Composition', 'Unregistered', 'SEZ'];

    // 2-digit state code, PAN (5 letters, 4 digits, 1 letter), entity number, "Z", check character.
    private const GSTIN_PATTERN = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';

    /**
     * Rules for the customer form. Every field is optional, so a client that
     * sends no GST fields (REST, or a user who cannot see them) still passes.
     */
    public static function rules(): array
    {
        $f = self::FIELDS;

        return [
            $f => ['nullable', 'array'],
            "{$f}.registration_type" => ['nullable', Rule::in(self::REGISTRATION_TYPES)],
            "{$f}.gstin" => [
                'nullable',
                "required_if:{$f}.registration_type,Regular,Composition,SEZ",
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($f) {
                    $gstin = strtoupper(trim((string) $value));
                    if (! preg_match(self::GSTIN_PATTERN, $gstin)) {
                        $fail('Enter a valid 15-character GSTIN, e.g. 24AAAAA0000A1Z5.');

                        return;
                    }
                    $stateCode = request()->input("{$f}.state_code");
                    if ($stateCode && substr($gstin, 0, 2) !== $stateCode) {
                        $fail('The GSTIN starts with '.substr($gstin, 0, 2).', which does not match the selected state ('.$stateCode.').');
                    }
                },
            ],
            "{$f}.state_code" => ['nullable', "required_with:{$f}.gstin", Rule::in(array_keys(GstStateCodes::getAll()))],
            "{$f}.trade_name" => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Readable field names for the standard validation messages. */
    public static function attributeNames(): array
    {
        $f = self::FIELDS;

        return [
            "validation.attributes.{$f}.gstin" => 'GSTIN',
            "validation.attributes.{$f}.state_code" => 'GST state',
            "validation.attributes.{$f}.registration_type" => 'GST registration type',
            "validation.attributes.{$f}.trade_name" => 'GST trade name',
        ];
    }

    public static function find(?int $customerId): ?object
    {
        return $customerId ? DB::table('gst_customer_profiles')->where('customer_id', $customerId)->first() : null;
    }

    /**
     * Save the profile from a validated customer form. Does nothing when the
     * request carried no GST fields, so a save that could not show them keeps
     * the stored profile.
     */
    public static function saveFromRequest(int $customerId, array $validated): void
    {
        $fields = $validated['plugin_data']['gst-billing'] ?? null;
        if (! is_array($fields)) {
            return;
        }

        $gstin = trim((string) ($fields['gstin'] ?? ''));

        DB::table('gst_customer_profiles')->updateOrInsert(
            ['customer_id' => $customerId],
            [
                'gstin' => $gstin === '' ? null : strtoupper($gstin),
                'trade_name' => ($fields['trade_name'] ?? '') ?: null,
                'state_code' => ($fields['state_code'] ?? '') ?: null,
                'registration_type' => ($fields['registration_type'] ?? '') ?: 'Unregistered',
                'updated_at' => now(),
            ]
        );
    }
}
