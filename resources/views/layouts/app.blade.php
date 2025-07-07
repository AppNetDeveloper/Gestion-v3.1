<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="light nav-floating">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />
    <title>{{ config('app.name', 'AppNetDeveloper') }}</title>

    {{-- Scripts de Vite (CSS principal) --}}
    @vite(['resources/css/app.scss'])

    {{-- PWA (si corresponde) --}}
    @laravelPWA

    {{-- chart.js u otras librerías globales --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!--
      AÑADE ESTA LÍNEA PARA INYECTAR LOS STYLES
      que empujes con @push('styles')
    -->
    @stack('styles')
</head>

<body class="font-inter dashcode-app" id="body_class">
    <div class="app-wrapper">

        <!-- BEGIN: Sidebar Navigation -->
        <x-sidebar-menu />
        <!-- End: Sidebar -->

        <!-- BEGIN: Settings -->
        <x-dashboard-settings />
        <!-- End: Settings -->

        <div class="flex flex-col justify-between min-h-screen">
            <div>
                <!-- BEGIN: header -->
                <x-dashboard-header />
                <!-- BEGIN: header -->

                <div class="content-wrapper transition-all duration-150 ltr:ml-0 xl:ltr:ml-[248px] rtl:mr-0 xl:rtl:mr-[248px]" id="content_wrapper">
                    <div class="page-content">
                        <div class="transition-all duration-150 container-fluid" id="page_layout">
                            <main id="content_layout">
                                @isset($slot)
                                    {{ $slot }}
                                @else
                                    @yield('content')
                                @endisset
                            </main>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BEGIN: footer -->
            <x-dashboard-footer />
            <!-- BEGIN: footer -->

        </div>
    </div>

    {{-- Cargar jQuery explícitamente primero --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    {{-- Scripts de Vite (JS principales después de jQuery) --}}
    @vite(['resources/js/custom/store.js', 'resources/js/app.js', 'resources/js/main.js'])

    {{-- Inyecta los scripts que empujes desde tus vistas --}}
    @stack('scripts')
</body>
</html>
