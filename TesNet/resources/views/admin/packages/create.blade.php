@extends('admin.layouts.hub')

@section('title', 'New Package — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-2xl w-full mr-auto">
    <a href="{{ route('admin.packages.index') }}" class="inline-flex items-center gap-1 admin-card-muted font-label-sm mb-4 hover:text-primary dark:hover:text-primary-fixed-dim">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to packages
    </a>
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface dark:text-inverse-on-surface mb-6">
        {{ request()->boolean('special') ? 'Create special day offer' : 'Create data package' }}
    </h1>
    <form method="POST" action="{{ route('admin.packages.store') }}" class="admin-card rounded-xl p-5 sm:p-8 soft-shadow border border-outline-variant/20 space-y-6">
        @csrf
        @include('admin.packages._form')
        <button type="submit" class="w-full sm:w-auto min-h-[48px] px-8 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm hover:opacity-90 transition-colors active:scale-[0.98]">
            Create package
        </button>
    </form>
</div>
@endsection
