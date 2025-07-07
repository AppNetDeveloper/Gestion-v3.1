<div class="sidebar-wrapper group w-0 hidden xl:w-[248px] xl:block">
    <div id="bodyOverlay" class="w-screen h-screen fixed top-0 bg-slate-900 bg-opacity-50 backdrop-blur-sm z-10 hidden">
    </div>
    <div class="logo-segment">
        <x-application-logo />

        <div id="sidebar_type" class="cursor-pointer text-slate-900 dark:text-white text-lg">
            <iconify-icon class="sidebarDotIcon extend-icon text-slate-900 dark:text-slate-200" icon="fa-regular:dot-circle"></iconify-icon>
            <iconify-icon class="sidebarDotIcon collapsed-icon text-slate-900 dark:text-slate-200" icon="material-symbols:circle-outline"></iconify-icon>
        </div>
        <button class="sidebarCloseIcon text-2xl inline-block md:hidden">
            <iconify-icon class="text-slate-900 dark:text-slate-200" icon="clarity:window-close-line"></iconify-icon>
        </button>
    </div>
    <div id="nav_shadow" class="nav_shadow h-[60px] absolute top-[80px] nav-shadow z-[1] w-full transition-all duration-200 pointer-events-none opacity-0"></div>
    <div class="sidebar-menus bg-white dark:bg-slate-800 py-2 px-4 h-[calc(100%-80px)] z-50" id="sidebar_menus">
        <ul class="sidebar-menu">
            <li class="sidebar-menu-title">{{ __('MENU') }}</li>
            <li class="{{ request()->routeIs('dashboard.unified') ? 'active' : '' }}">
                <a href="{{ route('dashboard.unified') }}" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class="nav-icon" icon="heroicons-outline:home"></iconify-icon>
                        <span>{{ __('Dashboard') }}</span>
                    </span>
                </a>
            </li>

            <li class="sidebar-menu-title">{{ __('APPS') }}</li>
            {{-- Chat, Email, LinkedIn, Whatsapp, Telegram, Contacts, Campaigns (existentes) --}}
            <li><a href="{{ route('chat') }}" class="navItem {{ request()->routeIs('chat') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:chat"></iconify-icon><span>{{ __('Chat') }}</span></span></a></li>
            <li><a href="{{ route('emails.index') }}" class="navItem {{ request()->routeIs('emails.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:mail"></iconify-icon><span>{{ __('Email') }}</span></span></a></li>
            <li><a href="{{ route('linkedin.index') }}" class="navItem {{ request()->routeIs('linkedin.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:link"></iconify-icon><span>{{ __('LinkedIn') }}</span></span></a></li>
            <li><a href="{{ route('whatsapp.index') }}" class="navItem {{ request()->routeIs('whatsapp.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="hugeicons:message-01"></iconify-icon><span>{{ __('Whatsapp') }}</span></span></a></li>
            <li><a href="{{ route('telegram.index') }}" class="navItem {{ request()->routeIs('telegram.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="hugeicons:telegram"></iconify-icon><span>{{ __('telegram') }}</span></span></a></li>
            <li><a href="{{ route('contacts.index') }}" class="navItem {{ request()->routeIs('contacts.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="mdi:contact-outline"></iconify-icon><span>{{ __('Contacts') }}</span></span></a></li>
            <li><a href="{{ route('campaigns.index') }}" class="navItem {{ request()->routeIs('campaigns.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="proicons:send"></iconify-icon><span>{{ __('Campaigns') }}</span></span></a></li>

            <li class="sidebar-menu-title">{{ __('MANAGEMENT') }}</li>
            @can('menu services')
                <li class="{{ request()->routeIs('services.*') ? 'active' : '' }}">
                    <a href="{{ route('services.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:wrench-screwdriver"></iconify-icon>
                            <span>{{ __('Services') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            
            @can('menu services')
                <li class="{{ request()->routeIs('scrapings.*') ? 'active' : '' }}">
                    <a href="{{ route('scrapings.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:search"></iconify-icon>
                            <span>{{ __('Scraping') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('menu clients')
                <li class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">
                    <a href="{{ route('clients.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:users"></iconify-icon>
                            <span>{{ __('Clients') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('menu quotes')
                <li class="{{ request()->routeIs('quotes.*') ? 'active' : '' }}">
                    <a href="{{ route('quotes.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:document-text"></iconify-icon>
                            <span>{{ __('Quotes') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('menu projects')
                <li class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <a href="{{ route('projects.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:briefcase"></iconify-icon>
                            <span>{{ __('Projects') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('menu tasks') {{-- Permiso para ver "Mis Tareas" --}}
                <li class="{{ request()->routeIs('tasks.my') ? 'active' : '' }}">
                    <a href="{{ route('tasks.my') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:clipboard-list"></iconify-icon>
                            <span>{{ __('My Tasks') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('menu invoices')
                <li class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                    <a href="{{ route('invoices.index') }}" class="navItem">
                        <span class="flex items-center">
                            <iconify-icon class="nav-icon" icon="heroicons-outline:receipt-percent"></iconify-icon>
                            <span>{{ __('Invoices') }}</span>
                        </span>
                    </a>
                </li>
            @endcan
            @can('servermonitor show')
                <li><a href="{{ route('servermonitor.index') }}" class="navItem {{ request()->routeIs('servermonitor.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="solar:server-outline"></iconify-icon><span>{{ __('Server Monitor') }}</span></span></a></li>
            @endcan
            <li><a href="{{ route('kanban') }}" class="navItem {{ request()->routeIs('kanban') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:view-boards"></iconify-icon><span>{{ __('Kanban') }}</span></span></a></li>
            <li><a href="{{ route('shiftdays.kanban') }}" class="navItem {{ request()->routeIs('shiftdays.kanban') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="bi:kanban"></iconify-icon><span>{{ __('Shift Day') }}</span></span></a></li>
            @can('calendarindividual show')
            <li><a href="{{ route('calendar.index') }}" class="navItem {{ request()->routeIs('calendar') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:calendar"></iconify-icon><span>{{ __('Calendar') }}</span></span></a></li>
            @endcan
            @can('labcalendar show')
            <li><a href="{{ route('labor-calendar.index') }}" class="navItem {{ request()->routeIs('labor-calendar.index') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="tabler:calendar"></iconify-icon><span>{{ __('Labor Calendar') }}</span></span></a></li>
            @endcan
            <li><a href="{{ route('todo') }}" class="navItem {{ request()->routeIs('todo') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:clipboard-check"></iconify-icon><span>{{ __('Todo') }}</span></span></a></li>

            <li class="sidebar-menu-title">{{ __('PAGES') }}</li>
            <li class="{{ request()->routeIs('utility*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:view-boards"></iconify-icon>
                        <span>{{ __('Utility') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('utility.invoice') }}" class="navItem {{ request()->routeIs('utility.invoice') ? 'active' : '' }}">{{ __('Invoice') }}</a></li>
                    <li><a href="{{ route('utility.pricing') }}" class="navItem {{ request()->routeIs('utility.pricing') ? 'active' : '' }}">{{ __('Pricing') }}</a></li>
                    <li><a href="{{ route('utility.blog') }}" class="navItem {{ request()->routeIs('utility.blog') ? 'active' : '' }}">{{ __('Blog') }}</a></li>
                    <li><a href="{{ route('utility.blank') }}" class="navItem {{ request()->routeIs('utility.blank') ? 'active' : '' }}">{{ __('Blank Page') }}</a></li>
                    <li><a href="{{ route('utility.profile') }}" class="navItem {{ request()->routeIs('utility.profile') ? 'active' : '' }}">{{ __('Profile') }}</a></li>
                    <li><a href="{{ route('utility.404') }}" class="navItem {{ request()->routeIs('utility.404') ? 'active' : '' }}">{{ __('404 Pages') }}</a></li>
                    <li><a href="{{ route('utility.coming-soon') }}" class="navItem {{ request()->routeIs('utility.coming-soon') ? 'active' : '' }}">{{ __('Coming Soon') }}</a></li>
                    <li><a href="{{ route('utility.under-maintenance') }}" class="navItem {{ request()->routeIs('utility.under-maintenance') ? 'active' : '' }}">{{ __('Under Maintenance') }}</a></li>
                </ul>
            </li>
            <li class="sidebar-menu-title">{{ __('ELEMENTS') }}</li>
            <li class="{{ request()->routeIs('widget*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:view-grid-add"></iconify-icon>
                        <span>{{ __('Widgets') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('widget.basic') }}" class="navItem {{ request()->routeIs('widget.basic') ? 'active' : '' }}">{{ __('Basic') }}</a></li>
                    <li><a href="{{ route('widget.statistic') }}" class="navItem {{ request()->routeIs('widget.statistic') ? 'active' : '' }}">{{ __('Statistics') }}</a></li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('components*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:collection"></iconify-icon>
                        <span>{{ __('Components') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('components.typography') }}" class="navItem {{ request()->routeIs('components.typography') ? 'active' : '' }}">{{ __('Typography') }}</a></li>
                    <li><a href="{{ route('components.colors') }}" class="navItem {{ request()->routeIs('components.colors') ? 'active' : '' }}">{{ __('Colors') }}</a></li>
                    <li><a href="{{ route('components.alert') }}" class="navItem {{ request()->routeIs('components.alert') ? 'active' : '' }}">{{ __('Alert') }}</a></li>
                    <li><a href="{{ route('components.button') }}" class="navItem {{ request()->routeIs('components.button') ? 'active' : '' }}">{{ __('Button') }}</a></li>
                    <li><a href="{{ route('components.card') }}" class="navItem {{ request()->routeIs('components.card') ? 'active' : '' }}">{{ __('Card') }}</a></li>
                    <li><a href="{{ route('components.carousel') }}" class="navItem {{ request()->routeIs('components.carousel') ? 'active' : '' }}">{{ __('Carousel') }}</a></li>
                    <li><a href="{{ route('components.dropdown') }}" class="navItem {{ request()->routeIs('components.dropdown') ? 'active' : '' }}">{{ __('Dropdown') }}</a></li>
                    <li><a href="{{ route('components.image') }}" class="navItem {{ request()->routeIs('components.image') ? 'active' : '' }}">{{ __('Image') }}</a></li>
                    <li><a href="{{ route('components.modal') }}" class="navItem {{ request()->routeIs('components.modal') ? 'active' : '' }}">{{ __('Modal') }}</a></li>
                    <li><a href="{{ route('components.progress-bar') }}" class="navItem {{ request()->routeIs('components.progress-bar') ? 'active' : '' }}">{{ __('Progress bar') }}</a></li>
                    <li><a href="{{ route('components.placeholder') }}" class="navItem {{ request()->routeIs('components.placeholder') ? 'active' : '' }}">{{ __('Placeholder') }}</a></li>
                    <li><a href="{{ route('components.tab') }}" class="navItem {{ request()->routeIs('components.tab') ? 'active' : '' }}">{{ __('Tab & Accordion') }}</a></li>
                    <li><a href="{{ route('components.badges') }}" class="navItem {{ request()->routeIs('components.badges') ? 'active' : '' }}">{{ __('Badges') }}</a></li>
                    <li><a href="{{ route('components.pagination') }}" class="navItem {{ request()->routeIs('components.pagination') ? 'active' : '' }}">Pagination</a></li>
                    <li><a href="{{ route('components.video') }}" class="navItem {{ request()->routeIs('components.video') ? 'active' : '' }}">{{ __('Video') }}</a></li>
                    <li><a href="{{ route('components.tooltip') }}" class="navItem {{ request()->routeIs('components.tooltip') ? 'active' : '' }}">{{ __('Tooltip & Popover') }}</a></li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('forms*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:clipboard-list"></iconify-icon>
                        <span>{{ __('Forms') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('forms.input') }}" class="navItem {{ request()->routeIs('forms.input') ? 'active' : '' }}">{{ __('Input') }}</a></li>
                    <li><a href="{{ route('forms.input-group') }}" class="navItem {{ request()->routeIs('forms.input-group') ? 'active' : '' }}">{{ __('Input group') }}</a></li>
                    <li><a href="{{ route('forms.input-layout') }}" class="navItem {{ request()->routeIs('forms.input-layout') ? 'active' : '' }}">{{ __('Input layout') }}</a></li>
                    <li><a href="{{ route('forms.input-validation') }}" class="navItem {{ request()->routeIs('forms.input-validation') ? 'active' : '' }}">{{ __('Form validation') }}</a></li>
                    <li><a href="{{ route('forms.input-wizard') }}" class="navItem {{ request()->routeIs('forms.input-wizard') ? 'active' : '' }}">{{ __('Wizard') }}</a></li>
                    <li><a href="{{ route('forms.input-mask') }}" class="navItem {{ request()->routeIs('forms.input-mask') ? 'active' : '' }}">{{ __('Input mask') }}</a></li>
                    <li><a href="{{ route('forms.file-input') }}" class="navItem {{ request()->routeIs('forms.file-input') ? 'active' : '' }}">{{ __('File input') }}</a></li>
                    <li><a href="{{ route('forms.repeater') }}" class="navItem {{ request()->routeIs('forms.repeater') ? 'active' : '' }}">{{ __('From repeater') }}</a></li>
                    <li><a href="{{ route('forms.textarea') }}" class="navItem {{ request()->routeIs('forms.textarea') ? 'active' : '' }}">{{ __('Textarea') }}</a></li>
                    <li><a href="{{ route('forms.checkbox') }}" class="navItem {{ request()->routeIs('forms.checkbox') ? 'active' : '' }}">{{ __('Checkbox') }}</a></li>
                    <li><a href="{{ route('forms.radio') }}" class="navItem {{ request()->routeIs('forms.radio') ? 'active' : '' }}">{{ __('Radio button') }}</a></li>
                    <li><a href="{{ route('forms.switch') }}" class="navItem {{ request()->routeIs('forms.switch') ? 'active' : '' }}">{{ __('Switch') }}</a></li>
                    <li><a href="{{ route('forms.select') }}" class="navItem {{ request()->routeIs('forms.select') ? 'active' : '' }}">{{ __('Select') }}</a></li>
                    <li><a href="{{ route('forms.date-time-picker') }}" class="navItem {{ request()->routeIs('forms.date-time-picker') ? 'active' : '' }}">{{ __('Date time picker') }}</a></li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('table*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:table"></iconify-icon>
                        <span>{{ __('Tables') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('table.basic') }}" class="navItem {{ request()->routeIs('table.basic') ? 'active' : '' }}">{{ __('Basic Tables') }}</a></li>
                    <li><a href="{{ route('table.advance') }}" class="navItem {{ request()->routeIs('table.advance') ? 'active' : '' }}">{{ __('Advanced Table') }}</a></li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('chart*') ? 'active' : '' }}">
                <a href="javascript:void(0)" class="navItem">
                    <span class="flex items-center">
                        <iconify-icon class=" nav-icon" icon="heroicons-outline:chart-bar"></iconify-icon>
                        <span>{{ __('Chart') }}</span>
                    </span>
                    <iconify-icon class="icon-arrow" icon="heroicons-outline:chevron-right"></iconify-icon>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="{{ route('chart.apex') }}" class="navItem {{ request()->routeIs('chart.apex') ? 'active' : '' }}">{{ __('Apex Chart') }}</a></li>
                    <li><a href="{{ route('chart.index') }}" class="navItem {{ request()->routeIs('chart.index') ? 'active' : '' }}">{{ __('Chart js') }}</a></li>
                </ul>
            </li>
            <li><a href="{{ route('map') }}" class="navItem {{ request()->routeIs('map') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:map"></iconify-icon><span>{{ __('Map') }}</span></span></a></li>
            <li><a href="{{ route('icon') }}" class="navItem {{ request()->routeIs('icon') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="heroicons-outline:emoji-happy"></iconify-icon><span>{{ __('Icons') }}</span></span></a></li>
            <li><a href="{{ route('database-backups.index') }}" class="navItem {{ request()->is('database-backups*') ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="iconoir:database-backup"></iconify-icon><span>{{ __('Database Backup') }}</span></span></a></li>
            <li><a href="{{ route('general-settings.show') }}" class="navItem {{ (request()->is('general-settings*')) || (request()->is('users*')) || (request()->is('roles*')) || (request()->is('profiles*')) || (request()->is('permissions*')) ? 'active' : '' }}"><span class="flex items-center"><iconify-icon class="nav-icon" icon="material-symbols:settings-outline"></iconify-icon><span>{{ __('Settings') }}</span></span></a></li>
        </ul>
        <div class="bg-slate-900 mb-10 mt-24 p-4 relative text-center rounded-2xl text-white" id="sidebar_bottom_wizard">
            <img src="/images/svg/rabit.svg" alt="" class="mx-auto relative -mt-[73px]">
            <div class="max-w-[160px] mx-auto mt-6">
                <div class="widget-title font-Inter mb-1">Unlimited Access</div>
                <div class="text-xs font-light font-Inter">Upgrade your system to business plan</div>
            </div>
            <div class="mt-6">
                <button class="bg-white hover:bg-opacity-80 text-slate-900 text-sm font-Inter rounded-md w-full block py-2 font-medium">Upgrade</button>
            </div>
        </div>
        </div>
</div>
