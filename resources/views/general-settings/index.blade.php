<x-app-layout>
    <div class="space-y-8">
        <div>
            <x-breadcrumb :page-title="$pageTitle" :breadcrumb-items="$breadcrumbItems" />
        </div>

        <div class="space-y-5">
            <div class="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-6">
                {{-- Botón 1: Configuración de la empresa --}}
                @can('company show')
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="heroicons:building-office-2"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Company Settings') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Set up your company profile, add your company logo, and more') }}
                            </div>
                            <a href="{{ route('general-settings.edit') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Botón 2: Usuario --}}
                @can('user show')
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="heroicons:user-circle"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('User') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage system user(Add, edit delete users).') }}
                            </div>
                            <a href="{{ route('users.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Manage user') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Botón 3: ROL --}}
                @can('role show')
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="heroicons:lock-closed"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('ROLE') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage Role (Add, Edit, Delete role)') }}
                            </div>
                            <a href="{{ route('roles.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Botón 4: Permission --}}
                @can('permission show')
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="heroicons:lock-closed"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Permission') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage Permission (Add, Edit, Delete Permission)') }}
                            </div>
                            <a href="{{ route('permissions.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Botones 5, 6 y 7: Time Control (Status, Rules y Shift) --}}
                @can('timecontrolstatus show')
                {{-- Botón 5: Time Control Status --}}
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="mingcute:time-fill"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Time Control Status') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage Time Control Status (Add, Edit, Delete)') }}
                            </div>
                            <a href="{{ route('controltime.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Botón 6: Time Control Rules --}}
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="ic:twotone-more-time"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Time Control Rules') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage Rules (Add, Edit, Delete Rules)') }}
                            </div>
                            <a href="{{ route('permissions.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Botón 7: Shift --}}
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="tdesign:user-time"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Shift') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Manage Shift (Add, Edit, Delete)') }}
                            </div>
                            <a href="{{ route('shift.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                {{-- Botón 8: Profile Settings (libre, sin restricción) --}}
                <div class="card">
                    <div class="card-body p-6">
                        <div class="space-y-6">
                            <div class="flex space-x-3 items-center rtl:space-x-reverse">
                                <div class="flex-none h-8 w-8 rounded-full bg-slate-800 dark:bg-slate-700 text-slate-300 flex items-center justify-center text-lg">
                                    <iconify-icon icon="heroicons:user"></iconify-icon>
                                </div>
                                <div class="flex-1 text-base text-slate-900 dark:text-white font-medium">
                                    {{ __('Profile Settings') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-300 text-sm">
                                {{ __('Set up your profile, add your profile photo, and more') }}
                            </div>
                            <a href="{{ route('profiles.index') }}" class="inline-flex items-center space-x-3 rtl:space-x-reverse text-sm capitalize font-medium text-slate-600 dark:text-slate-300">
                                <span>{{ __('Chnage Settings') }}</span>
                                <iconify-icon icon="heroicons:arrow-right"></iconify-icon>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
