@extends('layouts.public')

@section('title', 'Pricing — ' . config('app.name', 'Runwrk'))

@section('content')
    @include('partials.pricing-content', ['tiers' => $tiers])
@endsection
