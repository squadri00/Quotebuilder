<?php

namespace App\Models\Concerns;

/**
 * Shared by Business and Customer — both have the identical
 * address_line1/2, city, state_province, postal_code, country columns.
 * Turns them into up to 3 printable lines (street, city/state/postal,
 * country), skipping anything blank, for use on the quotation PDF.
 */
trait HasFormattedAddress
{
    public function addressLines(): array
    {
        $lines = [];

        $street = implode(', ', array_filter([$this->address_line1, $this->address_line2]));
        if ($street !== '') {
            $lines[] = $street;
        }

        $cityLine = trim(implode(', ', array_filter([
            $this->city,
            trim(($this->state_province ?? '').' '.($this->postal_code ?? '')),
        ])));
        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }

        if (! empty($this->country)) {
            $lines[] = $this->country;
        }

        return $lines;
    }
}
