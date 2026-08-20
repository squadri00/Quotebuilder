@extends('layouts.public')

@section('title', 'Pricing — ' . config('app.name', 'Quotaire'))

@section('content')
    @include('partials.pricing-content', ['tiers' => $tiers, 'fxRates' => $fxRates ?? [], 'currencySymbols' => $currencySymbols ?? collect(), 'masterSymbol' => $masterSymbol ?? '$', 'masterSymbolAfter' => $masterSymbolAfter ?? false])
@endsection
