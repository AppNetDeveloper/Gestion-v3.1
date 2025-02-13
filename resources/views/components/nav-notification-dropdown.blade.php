<!-- Notifications Dropdown area -->
<div class="relative md:block hidden">
    <button id="notificationsDropdownButton"
      class="lg:h-[32px] lg:w-[32px] lg:bg-slate-50 lg:dark:bg-slate-900 dark:text-white text-slate-900 cursor-pointer
             rounded-full text-[20px] flex flex-col items-center justify-center"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false">
      <iconify-icon class="animate-tada text-slate-800 dark:text-white text-xl" icon="heroicons-outline:bell"></iconify-icon>
      <span id="notificationCount" class="absolute -right-1 lg:top-0 -top-[6px] h-4 w-4 bg-red-500 text-[8px] font-semibold flex flex-col items-center
             justify-center rounded-full text-white z-[99]">
        {{ \App\Models\Notification::where('user_id', auth()->id())->where('seen', 0)->count() }}
      </span>
    </button>
    <!-- Notifications Dropdown -->
    <div class="dropdown-menu z-10 hidden bg-white divide-y divide-slate-100 dark:divide-slate-900 shadow w-[335px]
         dark:bg-slate-800 border dark:border-slate-900 !top-[18px] rounded-md overflow-hidden lrt:origin-top-right rtl:origin-top-left">
      <div class="flex items-center justify-between py-4 px-4">
        <h3 class="text-sm font-Inter font-medium text-slate-700 dark:text-white">
          {{ __("Notifications") }}
        </h3>
        <a class="text-xs font-Inter font-normal underline text-slate-500 dark:text-white" href="{{ route('notifications.index') }}">
          {{ __("See All") }}
        </a>
      </div>
      <div class="divide-y divide-slate-100 dark:divide-slate-900" role="none">
        @foreach(\App\Models\Notification::where('user_id', auth()->id())->orderBy('created_at', 'desc')->take(5)->get() as $notif)
        <div class="bg-slate-100 dark:bg-slate-700 dark:bg-opacity-70 text-slate-800 block w-full px-4 py-2 text-sm relative">
          <div class="flex ltr:text-left rtl:text-right">
            <div class="flex-none ltr:mr-3 rtl:ml-3">
              <div class="h-8 w-8 flex items-center justify-center bg-white rounded-full">
                <iconify-icon icon="heroicons-outline:bell" class="text-slate-800 text-xl"></iconify-icon>
              </div>
            </div>
            <div class="flex-1">
              <a href="#"
                class="text-slate-600 dark:text-slate-300 text-sm font-medium mb-1 before:w-full before:h-full before:absolute
                       before:top-0 before:left-0">
                {{ $notif->message }}
              </a>
              <div class="text-slate-500 dark:text-slate-200 text-xs leading-4">
                {{ $notif->created_at->diffForHumans() }}
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
</div>

@push('scripts')
<script>
  // Escucha el evento de cierre (hidden) del dropdown (Bootstrap 5)
  var notificationsDropdownButton = document.getElementById('notificationsDropdownButton');
  notificationsDropdownButton.addEventListener('hidden.bs.dropdown', function () {
    fetch("{{ str_replace('http://', 'https://', route('notifications.markAsSeen')) }}", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": "{{ csrf_token() }}",
        "Content-Type": "application/json"
      },
      body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Actualiza el contador a 0.
        document.getElementById('notificationCount').innerText = '0';
      }
    });
  });
</script>
@endpush
