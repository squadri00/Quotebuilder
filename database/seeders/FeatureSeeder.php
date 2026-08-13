<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * The starting set of gate-able features plans can include. Safe to
 * re-run — updates existing rows by key rather than duplicating them.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'key' => 'email_notifications',
                'name' => 'Email Notifications',
                'description' => 'Sends email alerts to the business when a customer submits a quote.',
            ],
            [
                'key' => 'pdf_download',
                'name' => 'PDF Download',
                'description' => 'Lets customers download their quote as a PDF.',
            ],
            [
                'key' => 'quote_inbox',
                'name' => 'Quote Inbox',
                'description' => "Shows a list of all submitted quotes in the business's Admin Portal.",
            ],
            [
                'key' => 'quote_status_tracking',
                'name' => 'Quote Status Tracking',
                'description' => 'Lets the business mark each quote as New, Contacted, Won, or Lost.',
            ],
            [
                'key' => 'quote_customer_link',
                'name' => 'Shareable Quote Link',
                'description' => 'Gives each quote a unique link the customer can revisit later.',
            ],
            [
                'key' => 'custom_branding',
                'name' => 'Custom Branding',
                'description' => "Lets the business replace QuoteBuilder's default styling with their own logo and colors.",
            ],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(['key' => $feature['key']], $feature);
        }
    }
}
