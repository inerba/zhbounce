<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="tallstackui_darkTheme()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased" x-cloak x-data="{ name: null }" x-on:name-updated.window="name = $event.detail.name" x-bind:class="{ 'dark bg-gray-800': darkTheme, 'bg-gray-100': !darkTheme }">
    <x-layout>
        <x-slot:top>
            <x-dialog />
            <x-toast />
        </x-slot:top>
        <x-slot:header>
            <x-layout.header>
                <x-slot:left>
                    <div class="dark:text-dark-100 text-dark-800 flex items-center gap-2 text-xl font-bold">
                        Zero Hard Bounce
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="order-first size-8">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10 18h4" />
                            <path d="M3 8a9 9 0 0 1 9 9v1l1.428 -4.285a12 12 0 0 1 6.018 -6.938l.554 -.277" />
                            <path d="M15 6h5v5" />
                        </svg>
                    </div>
                </x-slot:left>
                <x-slot:right>
                    <x-theme-switch />
                    {{-- <x-dropdown>
                        <x-slot:action>
                            <div>
                                <button class="cursor-pointer" x-on:click="show = !show">
                                    <span class="text-primary-500 text-base font-semibold" x-text="name"></span>
                                </button>
                            </div>
                        </x-slot:action>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown.items :text="__('Profile')" :href="route('user.profile')" />
                            <x-dropdown.items :text="__('Logout')" onclick="event.preventDefault(); this.closest('form').submit();" separator />
                        </form>
                    </x-dropdown> --}}
                </x-slot:right>
            </x-layout.header>
        </x-slot:header>
        <x-slot:menu>
            <x-side-bar>
                <x-slot:brand>

                    <a href="/" class="mt-8 flex items-center justify-center text-2xl font-bold uppercase dark:text-white">
                        <img src="/icon.png" alt="Zero Hard Bounce" class="mr-2 w-full max-w-24">
                    </a>
                </x-slot:brand>
                {{-- <x-side-bar.item text="Dashboard" icon="tabler.sun" :route="route('dashboard')" /> --}}

                <x-side-bar.item text="Pulisci lista" :route="route('email.clean')">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-mail-spark">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M19 22.5a4.75 4.75 0 0 1 3.5 -3.5a4.75 4.75 0 0 1 -3.5 -3.5a4.75 4.75 0 0 1 -3.5 3.5a4.75 4.75 0 0 1 3.5 3.5" />
                            <path d="M11.5 19h-6.5a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v5" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                    </x-slot:icon>
                </x-side-bar.item>

                <x-side-bar.item text="Unisci Liste" :route="route('email.compare')">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-layers-intersect-2">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M8 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                            <path d="M4 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                            <path d="M9 15l6 -6" />
                        </svg>
                    </x-slot:icon>
                </x-side-bar.item>
            </x-side-bar>
        </x-slot:menu>
        {{ $slot }}
    </x-layout>
    @livewireScripts
</body>

</html>
