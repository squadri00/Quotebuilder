<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;

/**
 * Every quote source (public self-serve, staff-entered, Super Admin
 * template demo) ends up here to turn "whatever name/email/phone/address
 * was typed on this one quote" into a persistent Customer row — findOrCreate()
 * matches by (business_id, email) and fills in any newly-provided details
 * without erasing ones already on file from a previous quote.
 */
class CustomerResolver
{
    public function findOrCreate(Business $business, array $data): Customer
    {
        $email = strtolower(trim($data['email']));

        $customer = Customer::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('email', $email)
            ->first();

        $attributes = array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state_province' => $data['state_province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($customer) {
            $customer->fill($attributes)->save();

            return $customer;
        }

        return Customer::create(array_merge([
            'business_id' => $business->id,
            'email' => $email,
            'name' => $data['name'] ?? $email,
        ], $attributes));
    }

    /**
     * The phone/address to freeze into Quote.meta['customer_contact'] at
     * creation time — see Quote::customerContact()'s docblock for why this
     * is snapshotted rather than read live off the Customer later.
     */
    public function snapshotContact(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        return array_filter([
            'phone' => $customer->phone,
            'address_lines' => $customer->addressLines(),
        ], fn ($value) => ! empty($value));
    }
}
