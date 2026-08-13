<?php

namespace App\Services;

use App\Models\Business;

/**
 * Applies a business's active tax lines to a quote subtotal.
 *
 * Each tax line is a simple percentage, always exclusive (added on top of
 * the subtotal, never backed out of an inclusive price), and multiple
 * active lines are purely additive — each computed independently against
 * the same subtotal, then summed. There is no compounding/tax-on-tax.
 * (This mirrors how Meccora, the sibling workshop app, handles multiple
 * shop tax lines on an invoice.)
 */
class TaxCalculator
{
    /**
     * @return array{subtotal: float, tax_lines: array<int, array{title: string, rate: float, amount: float}>, tax_total: float, total: float}
     */
    public function calculate(Business $business, float $subtotal): array
    {
        $subtotal = round($subtotal, 2);

        $taxLines = [];
        $taxTotal = 0.0;

        foreach ($business->shopTaxRates()->where('is_active', true)->orderBy('id')->get() as $taxRate) {
            $amount = round($subtotal * (float) $taxRate->rate / 100, 2);
            $taxTotal += $amount;

            $taxLines[] = [
                'title' => $taxRate->title,
                'rate' => (float) $taxRate->rate,
                'amount' => $amount,
            ];
        }

        $taxTotal = round($taxTotal, 2);

        return [
            'subtotal' => $subtotal,
            'tax_lines' => $taxLines,
            'tax_total' => $taxTotal,
            'total' => round($subtotal + $taxTotal, 2),
        ];
    }
}
