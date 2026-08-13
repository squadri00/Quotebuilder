<?php

namespace App\Services;

/**
 * A staff-entered, one-off discount on a single internal quote — separate
 * from Rules (pre-configured, automatic, tied to answer combinations) and
 * from price_override (a blunt "replace the final number" escape hatch).
 * Applied to the RulesEngine's pre-tax subtotal so tax is correctly
 * computed on the discounted amount, not the original one — see
 * InternalQuoteController::calculate() and its Super Admin mirror.
 */
class DiscountCalculator
{
    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPES = [self::TYPE_FIXED, self::TYPE_PERCENTAGE];

    /**
     * @return array{amount: float, meta: ?array}
     */
    public function apply(float $subtotal, ?string $type, ?float $value): array
    {
        if (! $type || ! $value || $value <= 0) {
            return ['amount' => 0.0, 'meta' => null];
        }

        $amount = $type === self::TYPE_PERCENTAGE
            ? round($subtotal * (min($value, 100) / 100), 2)
            : round($value, 2);

        $amount = min($amount, $subtotal);

        return [
            'amount' => $amount,
            'meta' => [
                'type' => $type,
                'value' => (float) $value,
                'amount' => $amount,
            ],
        ];
    }
}
