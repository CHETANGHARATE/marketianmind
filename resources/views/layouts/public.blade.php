@extends('layouts.base')

@section('content')
    <div class="min-h-screen flex flex-col bg-slate-50">
        <x-navbar />

        <main class="flex-1">
            @yield('subcontent')
        </main>

        <x-footer />
    </div>
@endsection
