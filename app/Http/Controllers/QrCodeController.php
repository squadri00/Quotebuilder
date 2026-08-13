<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Generates QR codes for the public quote flow — either scoped to one
 * product (skips straight to that product's quote wizard) or to a whole
 * business (lands on a product-picker page first, for businesses with
 * more than one product). Both point at pages hosted by QuoteBuilder
 * itself, so neither needs the business to have their own website.
 *
 * Unlike a "self-registration" style QR code, these URLs carry no secret
 * token — the target pages are already fully public with no login
 * required, so there's nothing here that needs to be revocable.
 */
class QrCodeController extends Controller
{
    public function show(Request $request, Product $product): Response
    {
        $url = route('quote.show', [$product->business, $product]);
        $filename = $product->business->slug . '-' . $product->slug . '-quote-qr.png';

        return $this->pngResponse($request, $url, $filename);
    }

    public function business(Request $request): Response
    {
        $business = $request->user()->business;
        $url = route('quote.picker', $business);
        $filename = $business->slug . '-quote-qr.png';

        return $this->pngResponse($request, $url, $filename);
    }

    private function pngResponse(Request $request, string $url, string $filename): Response
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $url,
            size: 500,
            margin: 16,
        ))->build();

        $headers = ['Content-Type' => $result->getMimeType()];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        return response($result->getString(), 200, $headers);
    }
}
