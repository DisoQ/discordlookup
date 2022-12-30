@extends('layouts.app')

@section('title', __('Terms of Service'))
@section('description', __('Terms of Service of DiscordLookup.com'))
@section('keywords', 'terms, service, terms of service, terms of use, use, legal')
@section('robots', 'noindex, nofollow')

@section('content')

    <div class="container mt-5">
        <div class="page-header mt-2">
            <h2 class="pb-2 fw-bold">{{ __('Terms of Service') }}</h2>
        </div>

        <div class="user-select-none">
            @if(\Illuminate\Support\Facades\View::exists('legal.content.terms-of-service'))
                @include('legal.content.terms-of-service')
            @endif
        </div>
    </div>

@endsection
