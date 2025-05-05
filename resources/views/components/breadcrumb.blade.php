@props(['breadcrumbItems' => [], 'pageTitle'=>'Default Title'])

<div class="flex items-center justify-between">
    {{-- Breadcrumb title start --}}
    <h5 class="text-textColor font-Inter font-medium md:text-2xl mr-4 dark:text-white mb-1 sm:mb-0">
        {{ __($pageTitle) }}
    </h5>

    {{-- Breadcrumb list start --}}
    <ul class="m-0 p-0 list-none">
        {{-- Home Link --}}
        {{-- Intenta generar la ruta, usa '#' como fallback si no existe --}}
        @php
            $homeUrl = '#'; // URL por defecto si la ruta no existe
            try {
                // *** CAMBIA 'dashboard.unified' por el nombre real de tu ruta principal/dashboard ***
                $homeUrl = route('dashboard'); // O 'home', etc.
            } catch (\Exception $e) {
                 \Illuminate\Support\Facades\Log::warning("Breadcrumb: Ruta 'dashboard' no encontrada.");
            }
        @endphp
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ $homeUrl }}" class="breadcrumbList">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                {{-- Solo mostrar separador si hay más items --}}
                @if(!empty($breadcrumbItems))
                    <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
                @endif
            </a>
        </li>

        {{-- Loop through provided breadcrumb items --}}
        @if(!empty($breadcrumbItems))
            @foreach ($breadcrumbItems as $breadcrumbItem)
                {{-- Comprobar si es el elemento activo --}}
                @if(isset($breadcrumbItem['active']) && $breadcrumbItem['active'])
                    {{-- Active Item (Current Page) - Mostrar como texto, no enlace --}}
                    <li class="inline-block active">
                        <span class="breadcrumbList breadcrumbActive text-slate-500 dark:text-slate-400">
                             {{-- Asegurarse de que 'name' existe --}}
                            {{ __( $breadcrumbItem['name'] ?? '...' ) }}
                        </span>
                    </li>
                @else
                    {{-- Not Active Item (Link) --}}
                    <li class="inline-block relative text-sm text-primary-500 font-Inter">
                        {{-- Comprobar si 'url' y 'name' existen --}}
                        @if(isset($breadcrumbItem['url']) && isset($breadcrumbItem['name']))
                            <a href="{{ $breadcrumbItem['url'] }}" class="breadcrumbList">
                                {{ __($breadcrumbItem['name']) }}
                            </a>
                            {{-- Añadir separador si no es el último item del bucle --}}
                            @if (!$loop->last)
                                <iconify-icon icon="heroicons-outline:chevron-right" class="relative top-[3px] text-slate-500 rtl:rotate-180"></iconify-icon>
                            @endif
                        @else
                             {{-- Mostrar solo nombre si falta URL (o nombre) --}}
                             <span class="breadcrumbList text-slate-500 dark:text-slate-400">
                                {{ __( $breadcrumbItem['name'] ?? '...' ) }}
                             </span>
                             @if (!$loop->last)
                                <iconify-icon icon="heroicons-outline:chevron-right" class="relative top-[3px] text-slate-500 rtl:rotate-180"></iconify-icon>
                             @endif
                        @endif
                    </li>
                @endif
            @endforeach
        @endif
    </ul>
</div>
