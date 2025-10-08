<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sydney Markets - Dashboard</title>

    <!-- Buyer Dashboard Assets -->
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/typography.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/spacing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/weekly-planner.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/quotes-system.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/dashboard/user-dropdown.css') }}">

    {{-- Communication Hub CSS (Direct Load) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/ai-assistant.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/quote-inbox.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/messaging.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-animations.css') }}">

    @livewireStyles
    @stack('styles')
</head>
<body>
    <!-- Main Dashboard Livewire Component -->
    <livewire:buyer.dashboard />

    @livewireScripts
</body>
</html>