<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Quote;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function businesses(): StreamedResponse
    {
        $businesses = Business::withCount(['users', 'products', 'quotes'])
            ->where('is_template', false)
            ->with(['createdFromTemplate', 'industry'])
            ->orderBy('name')
            ->get();

        return $this->csvResponse('businesses-'.now()->format('Y-m-d').'.csv', function ($handle) use ($businesses) {
            fputcsv($handle, ['Name', 'Industry', 'Template Used', 'Signup Date', 'Status', 'Users', 'Products', 'Quotes']);

            foreach ($businesses as $business) {
                fputcsv($handle, [
                    $business->name,
                    $business->industry?->name ?? '',
                    $business->createdFromTemplate?->name ?? '',
                    $business->created_at->format('Y-m-d'),
                    $business->is_active ? 'Active' : 'Deactivated',
                    $business->users_count,
                    $business->products_count,
                    $business->quotes_count,
                ]);
            }
        });
    }

    public function quotes(): StreamedResponse
    {
        $quotes = Quote::whereHas('business', fn ($q) => $q->where('is_template', false))
            ->with(['business', 'product'])
            ->latest()
            ->get();

        return $this->csvResponse('quotes-'.now()->format('Y-m-d').'.csv', function ($handle) use ($quotes) {
            fputcsv($handle, ['Date', 'Business', 'Product', 'Customer Name', 'Customer Email', 'Final Price']);

            foreach ($quotes as $quote) {
                fputcsv($handle, [
                    $quote->created_at->format('Y-m-d H:i'),
                    $quote->business?->name ?? '',
                    $quote->product?->name ?? '',
                    $quote->customer_name,
                    $quote->customer_email,
                    number_format((float) $quote->final_price, 2),
                ]);
            }
        });
    }

    private function csvResponse(string $filename, callable $writeRows): StreamedResponse
    {
        return response()->streamDownload(function () use ($writeRows) {
            $handle = fopen('php://output', 'w');
            $writeRows($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
