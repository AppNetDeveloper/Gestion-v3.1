<?php

use App\Http\Controllers\ChartController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\WidgetsController;
use App\Http\Controllers\SetLocaleController;
use App\Http\Controllers\ComponentsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\TimeControlStatusController;
use App\Http\Controllers\TimeControlStatusRulesController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TimeControlController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImageLogoController;
use App\Http\Controllers\LinkedinController;
use App\Http\Controllers\OllamaController;
use App\Http\Controllers\TaskerLinkedinController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AutoProcessController;
use App\Exports\ContactsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShiftDayController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\HostListController;
use App\Http\Controllers\LaborCalendarController;
use Webklex\IMAP\Facades\Client;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ScrapingTaskController; // Importa el controlador
use App\Http\Controllers\ServiceController; // Controlador de Servicios
use App\Http\Controllers\ClientController;  // Importar Controlador de Clientes
use App\Http\Controllers\QuoteController;   // Importar Controlador de Presupuestos
use App\Http\Controllers\ProjectController; // <-- Importar ProjectController
use App\Http\Controllers\TaskController; // <-- Importar TaskController


require __DIR__ . '/auth.php';

Route::get('/', function () {
    return to_route('login');
});

Route::group(['middleware' => ['auth', 'verified']], function () {
    // Dashboards
    Route::get('dashboard-analytic', [HomeController::class, 'analyticDashboard'])->name('dashboards.analytic');
    Route::get('dashboard-ecommerce', [HomeController::class, 'ecommerceDashboard'])->name('dashboards.ecommerce');
    Route::get('dashboard', [HomeController::class, 'unifiedDashboard'])->middleware(['auth'])->name('dashboard.unified');
    Route::get('get-time-control-section', [HomeController::class, 'getTimeControlSection'])->middleware('auth');
    // Locale
    Route::get('setlocale/{locale}', SetLocaleController::class)->name('setlocale');

    // User
    Route::resource('users', UserController::class);
    // Permission
    Route::resource('permissions', PermissionController::class)->except(['show']);
    // Roles
    Route::resource('roles', RoleController::class);
    // Profiles
    Route::resource('profiles', ProfileController::class)->only(['index', 'update'])->parameter('profiles', 'user');
    // Env
    Route::singleton('general-settings', GeneralSettingController::class);
    Route::post('general-settings-logo', [GeneralSettingController::class, 'logoUpdate'])->name('general-settings.logo');
    //control time
    Route::get('controltime', [TimeControlStatusController::class, 'index'])->name('controltime.index');
    Route::get('controltime-show', [TimeControlStatusController::class, 'show'])->name('controltime.show');
    Route::get('controltime-edit', [TimeControlStatusController::class, 'edit'])->name('controltime.edit');

     //control time rules
     Route::get('controltimerules', [TimeControlStatusRulesController::class, 'index'])->name('controltimerules.index');
     Route::get('controltimerules-show', [TimeControlStatusRulesController::class, 'show'])->name('controltimerules.show');
     Route::get('controltimerules-edit', [TimeControlStatusRulesController::class, 'edit'])->name('controltimerules.edit');

      //shift
      Route::get('shift', [ShiftController::class, 'index'])->name('shift.index');
      //Route::get('shift-show', [ShifController::class, 'show'])->name('shift.show');
      //Route::get('shift-edit', [ShifController::class, 'edit'])->name('shift.edit');

      //add new time control
      Route::post('add-new-time-control', [TimeControlController::class, 'addNewTimeControl'])->name('add-new-time-control.index');

      //debug
      Route::get('/debug', 'App\Http\Controllers\DebugController@index');


    //APPS
    Route::get('chat', [AppsController::class, 'chat'])->name('chat');
    Route::get('email', [AppsController::class, 'email'])->name('email');
    Route::get('kanban', [AppsController::class, 'kanban'])->name('kanban');
    Route::get('todo', [AppsController::class, 'todo'])->name('todo');
    Route::get('project', [AppsController::class, 'projects'])->name('project');
    Route::get('project-details', [AppsController::class, 'projectDetails'])->name('project-details');

    // UTILITY
    Route::get('utility-invoice', [UtilityController::class, 'invoice'])->name('utility.invoice');
    Route::get('utility-pricing', [UtilityController::class, 'pricing'])->name('utility.pricing');
    Route::get('utility-blog', [UtilityController::class, 'blog'])->name('utility.blog');
    Route::get('utility-blog-details', [UtilityController::class, 'blogDetails'])->name('utility.blog-details');
    Route::get('utility-blank', [UtilityController::class, 'blank'])->name('utility.blank');
    Route::get('utility-settings', [UtilityController::class, 'settings'])->name('utility.settings');
    Route::get('utility-profile', [UtilityController::class, 'profile'])->name('utility.profile');
    Route::get('utility-404', [UtilityController::class, 'error404'])->name('utility.404');
    Route::get('utility-coming-soon', [UtilityController::class, 'comingSoon'])->name('utility.coming-soon');
    Route::get('utility-under-maintenance', [UtilityController::class, 'underMaintenance'])->name('utility.under-maintenance');

    //Linkedin
    Route::get('/linkedin', [LinkedinController::class, 'index'])->name('linkedin.index');
    Route::get('/linkedin/auth', [LinkedinController::class, 'redirectToLinkedIn'])->name('linkedin.auth');
    Route::get('/callback', [LinkedinController::class, 'handleLinkedInCallback'])->name('linkedin.callback');
    Route::post('/linkedin/post', [LinkedinController::class, 'publishPost'])->name('linkedin.post');
    Route::delete('/linkedin/disconnect', [LinkedinController::class, 'disconnect'])->name('linkedin.disconnect');
    Route::put('/linkedin/{id}', [LinkedinController::class, 'update'])->name('tasker-linkedin.update');



    // Ruta unificada para el dashboard de monitoreo y gestión
    Route::get('servermonitor', [ServerMonitorController::class, 'index'])->name('servermonitor.index');

    // Ruta para obtener el último dato de monitoreo vía AJAX
    Route::get('servermonitor/latest/{host}', [ServerMonitorController::class, 'getLatest'])
        ->name('servermonitor.latest');
        // Nueva ruta para obtener el historial (últimos 20 registros)
    Route::get('servermonitor/history/{host}', [ServerMonitorController::class, 'getHistory'])
    ->name('servermonitor.history');
    Route::resource('hosts', HostListController::class);
    Route::patch('/hosts/toggle/{host}', [HostListController::class, 'toggle'])->name('hosts.toggle');


    //campañas public

    Route::middleware('auth')->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/data', [CampaignController::class, 'data'])->name('campaigns.data');
        Route::put('/campaigns/{id}', [CampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    });

    //calendario laboral
    Route::get('/labor-calendar', [LaborCalendarController::class, 'index'])->name('labor-calendar.index');
    Route::get('/labor-calendar/fetch', [LaborCalendarController::class, 'fetch'])->name('labor-calendar.fetch');
    Route::post('/labor-calendar/save-non-working', [LaborCalendarController::class, 'saveNonWorking'])->name('labor-calendar.saveNonWorking');
    Route::post('/labor-calendar/store', [LaborCalendarController::class, 'store'])->name('labor-calendar.store');
    Route::post('/labor-calendar/update/{id}', [LaborCalendarController::class, 'update'])->name('labor-calendar.update');
   // Route::post('/labor-calendar/destroy/{id}', [LaborCalendarController::class, 'destroy'])->name('labor-calendar.destroy');
    Route::delete('/labor-calendar/destroy/{id}', [LaborCalendarController::class, 'destroy'])->name('labor-calendar.destroy');

    //ShiftDay
    Route::get('shiftdays/kanban', [ShiftDayController::class, 'kanban'])->name('shiftdays.kanban');
    Route::post('/shift-days/{shiftDay}/update-users', [ShiftDayController::class, 'updateUsers'])->name('shiftday.updateUsers');
    Route::delete('/shift-days/{shiftDay}/users', [ShiftDayController::class, 'destroyUsers'])->name('shift-days.destroy-users');

    // Emails Rutas protegidas por el middleware de autenticación
    Route::middleware(['auth'])->group(function () {
        // Ruta para listar los correos
        Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');

        // Ruta para mostrar el detalle de un correo (usando el UID)
        Route::get('/emails/{uid}', [EmailController::class, 'show'])->name('emails.show');

        // Ruta para actualizar la configuración IMAP
        Route::post('/emails/settings', [EmailController::class, 'updateSettings'])->name('emails.settings.update');
        Route::get('/emails/attachment/{messageUid}/{attachmentIndex}', [EmailController::class, 'downloadAttachment'])->name('emails.attachment.download');
        Route::post('/emails/smtp/update', [EmailController::class, 'updateSmtpSettings'])->name('emails.smtp.update');
        Route::get('/emails/compose', [EmailController::class, 'compose'])->name('emails.compose');
        Route::post('/emails/send', [EmailController::class, 'send'])->name('emails.send');
        Route::post('/emails/{uid}/reply', [EmailController::class, 'reply'])->name('emails.reply');
        Route::post('/emails/{uid}/delete', [EmailController::class, 'delete'])->name('emails.delete');
    });

    //rutas para notificaciones
    Route::middleware('auth')->group(function () {
        // Vista completa de notificaciones
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        // Retorna los datos en JSON para DataTables
        Route::get('/notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
        // Marca las notificaciones como vistas (AJAX)
        Route::post('/notifications/mark-as-seen', [NotificationController::class, 'markAsSeen'])->name('notifications.markAsSeen');
    });

    //export contacts a excel
    Route::get('/export-contacts', function () {
        return Excel::download(new ContactsExport, 'contacts.xlsx');
    })->name('contacts.export');

    //calendario usuario
    Route::middleware('auth')->group(function () {
        // Muestra la vista del calendario (usando la vista 'calendar.index')
        Route::get('/calendar', [EventController::class, 'index'])->name('calendar.index');

        // Ruta para obtener los eventos (para FullCalendar vía AJAX)
        Route::get('/events', [EventController::class, 'fetchEvents'])->name('events.fetch');

        // Ruta para almacenar un nuevo evento
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{id}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('events.destroy');

    });
//rutas telegram
Route::middleware('auth')->group(function () {
    // Vista principal de Telegram
    Route::get('/telegram', [TelegramController::class, 'index'])->name('telegram.index');

    // Authentication
    Route::post('/telegram/request-code/{userId}', [TelegramController::class, 'requestCode'])->name('telegram.requestCode');
    Route::post('/telegram/verify-code/{userId}', [TelegramController::class, 'verifyCode'])->name('telegram.verifyCode');
    Route::post('/telegram/logout/{userId}', [TelegramController::class, 'logout'])->name('telegram.logout');
    Route::get('/telegram/session-status/{userId}', [TelegramController::class, 'sessionStatus'])->name('telegram.sessionStatus');
    Route::get('/telegram/all-session-status', [TelegramController::class, 'allSessionStatus'])->name('telegram.allSessionStatus');

    // Media
    Route::get('/telegram/download-media/{userId}/{peer}/{messageId}', [TelegramController::class, 'downloadMedia'])->name('telegram.downloadMedia');

    // Chats
    Route::get('/telegram/get-chat/{userId}', [TelegramController::class, 'getChat'])->name('telegram.getChat');
    Route::delete('/telegram/delete-chat/{userId}/{peer}', [TelegramController::class, 'deleteChat'])->name('telegram.deleteChat');
    Route::post('/telegram/send-message/{userId}/{groupId}', [TelegramController::class, 'sendMessage'])->name('telegram.sendMessage');
    // Messages
    Route::get('/telegram/get-messages/{userId}/{peer}', [TelegramController::class, 'getMessages'])->name('telegram.getMessages');
    Route::delete('/telegram/delete-message/{userId}/{peer}/{messageId}', [TelegramController::class, 'deleteMessage'])->name('telegram.deleteMessage');
    Route::post('/telegram/forward-message/{userId}/{fromPeer}/{toPeer}/{messageId}', [TelegramController::class, 'forwardMessage'])->name('telegram.forwardMessage');
    Route::post('/telegram/send-media/{userId}/{peer}', [TelegramController::class, 'sendMedia'])->name('telegram.sendMedia');
    Route::get('/telegram/search-messages/{userId}/{peer}', [TelegramController::class, 'searchMessages'])->name('telegram.searchMessages');

    // Groups
    Route::post('/telegram/send-group-message/{userId}/{groupId}/{message}', [TelegramController::class, 'sendGroupMessage'])->name('telegram.sendGroupMessage');
    Route::post('/telegram/leave-group/{userId}/{groupId}', [TelegramController::class, 'leaveGroup'])->name('telegram.leaveGroup');
    Route::post('/telegram/create-group/{userId}', [TelegramController::class, 'createGroup'])->name('telegram.createGroup');

    // Contacts
    Route::get('/telegram/get-contacts/{userId}', [TelegramController::class, 'getContacts'])->name('telegram.getContacts');
    Route::get('/telegram/search-contact/{userId}', [TelegramController::class, 'searchContact'])->name('telegram.searchContact');
    Route::get('/telegram/export-contacts/{userId}', [TelegramController::class, 'exportContacts'])->name('telegram.exportContacts');
    Route::post('/telegram/import-contacts/{userId}', [TelegramController::class, 'importContacts'])->name('telegram.importContacts');
    Route::get('/telegram/sync-contacts/{userId}', [TelegramController::class, 'syncContacts'])->name('telegram.syncContacts');


    // Sessions
    Route::get('/telegram/active-sessions', [TelegramController::class, 'activeSessions'])->name('telegram.activeSessions');
});

// Ruta para actualizar una tarea programada (en este ejemplo se usa el mismo controlador)
Route::put('/linkedin/{id}', [LinkedinController::class, 'update'])->name('tasker-linkedin.update');

//whatsapp viewer
Route::middleware(['auth'])->group(function () {
    // Ruta para ver la lista de contactos (teléfonos)
    Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');

    // Ruta para ver la conversación de un teléfono en específico
    Route::get('/whatsapp/{phone}', [WhatsappController::class, 'conversation'])->name('whatsapp.conversation');
    Route::post('/whatsapp/import-contacts', [WhatsappController::class, 'importContacts'])->name('whatsapp.importContacts');

});
    //para tranformar los videos en mp4 de whatsapp
Route::get('/decrypt-media', [MediaController::class, 'decrypt'])->name('decryptMedia');

// Rutas para eliminar mensajes (accionadas por AJAX)
Route::delete('/whatsapp/message/{id}', [WhatsappController::class, 'destroyMessage'])->name('whatsapp.message.destroy');
Route::delete('/whatsapp/chat/{phone}', [WhatsappController::class, 'destroyChat'])->name('whatsapp.chat.destroy');

//contactos rutas
Route::resource('contacts', ContactController::class)->middleware('auth');
Route::post('/import-contacts', [ContactController::class, 'import'])->name('contacts.import');


//auto response rutas para editar si se permite auto respuesta
Route::post('/auto-response-whatsapp', [AutoProcessController::class, 'updateWhatsapp'])->name('auto-response-whatsapp.update');
Route::post('/auto-response-telegram', [AutoProcessController::class, 'updateTelegram'])->name('auto-response-telegram.update');
Route::get('/auto-response-telegram-get', [AutoProcessController::class, 'getTelegram'])->middleware('auth');

Route::get('/whatsapp/messages/json/{phone}', [WhatsappController::class, 'getMessagesJson'])->name('whatsapp.messages.json')->middleware('auth');
Route::get('/whatsapp/contacts/json', [WhatsappController::class, 'getContactsJson'])->name('whatsapp.contacts.json')->middleware('auth');
Route::get('/auto-response-whatsapp-get', [AutoProcessController::class, 'getWhatsapp'])->name('auto-response-whatsapp.get')->middleware('auth');

//OLAMA
    Route::post('/ollama/process', [OllamaController::class, 'processPrompt'])->name('ollama.process');

    //TASKERLINKEDIN
        // Vista de tareas programadas
    Route::get('tasker-linkedin', [TaskerLinkedinController::class, 'index'])->name('tasker.linkedin.index');
        // Datos para DataTables (JSON)
    Route::get('tasker-linkedin/data', [TaskerLinkedinController::class, 'data'])->name('tasker.linkedin.data');
        // Guardar nueva tarea programada
    Route::post('tasker-linkedin/store', [TaskerLinkedinController::class, 'store'])->name('tasker.linkedin.store');
    //borrar tarea programada
    Route::delete('tasker-linkedin/{task}', [TaskerLinkedinController::class, 'destroy'])->name('tasker.linkedin.destroy');


    // ELEMENTS
    Route::get('widget-basic', [WidgetsController::class, 'basic'])->name('widget.basic');
    Route::get('widget-statistic', [WidgetsController::class, 'statistic'])->name('widget.statistic');
    Route::get('components-typography', [ComponentsController::class, 'typography'])->name('components.typography');
    Route::get('components-colors', [ComponentsController::class, 'color'])->name('components.colors');
    Route::get('components-alert', [ComponentsController::class, 'alert'])->name('components.alert');
    Route::get('components-button', [ComponentsController::class, 'button'])->name('components.button');
    Route::get('components-card', [ComponentsController::class, 'card'])->name('components.card');
    Route::get('components-carousel', [ComponentsController::class, 'carousel'])->name('components.carousel');
    Route::get('components-dropdown', [ComponentsController::class, 'dropdown'])->name('components.dropdown');
    Route::get('components-image', [ComponentsController::class, 'image'])->name('components.image');
    Route::get('components-modal', [ComponentsController::class, 'modal'])->name('components.modal');
    Route::get('components-progress-bar', [ComponentsController::class, 'progressBar'])->name('components.progress-bar');
    Route::get('components-placeholder', [ComponentsController::class, 'placeholder'])->name('components.placeholder');
    Route::get('components-tab', [ComponentsController::class, 'tab'])->name('components.tab');
    Route::get('components-badges', [ComponentsController::class, 'badges'])->name('components.badges');
    Route::get('components-pagination', [ComponentsController::class, 'pagination'])->name('components.pagination');
    Route::get('components-video', [ComponentsController::class, 'video'])->name('components.video');
    Route::get('components-tooltip', [ComponentsController::class, 'tooltip'])->name('components.tooltip');
    // FORMS
    Route::get('forms-input', [FormController::class, 'input'])->name('forms.input');
    Route::get('input-group', [FormController::class, 'group'])->name('forms.input-group');
    Route::get('input-layout', [FormController::class, 'layout'])->name('forms.input-layout');
    Route::get('forms-input-validation', [FormController::class, 'validation'])->name('forms.input-validation');
    Route::get('forms-input-wizard', [FormController::class, 'wizard'])->name('forms.input-wizard');
    Route::get('forms-input-mask', [FormController::class, 'inputMask'])->name('forms.input-mask');
    Route::get('forms-file-input', [FormController::class, 'fileInput'])->name('forms.file-input');
    Route::get('forms-repeater', [FormController::class, 'repeater'])->name('forms.repeater');
    Route::get('forms-textarea', [FormController::class, 'textarea'])->name('forms.textarea');
    Route::get('forms-checkbox', [FormController::class, 'checkbox'])->name('forms.checkbox');
    Route::get('forms-radio', [FormController::class, 'radio'])->name('forms.radio');
    Route::get('forms-switch', [FormController::class, 'switch'])->name('forms.switch');
    Route::get('forms-select', [FormController::class, 'select'])->name('forms.select');
    Route::get('forms-date-time-picker', [FormController::class, 'dateTimePicker'])->name('forms.date-time-picker');

    // TABLE
    Route::get('table-basic', [TableController::class, 'basic'])->name('table.basic');
    Route::get('table-advance', [TableController::class, 'advance'])->name('table.advance');

    // CHART
    Route::get('chart', [ChartController::class, 'index'])->name('chart.index');
    Route::get('chart-apex', [ChartController::class, 'apexchart'])->name('chart.apex');

    //Icon
    Route::get('map', function () {
        $breadcrumbsItems = [
            [
                'name' => 'Map',
                'url' => '/map',
                'active' => true
            ],

        ];
        return view('elements.map.index', [
            'pageTitle' => 'Map',
            'breadcrumbItems' => $breadcrumbsItems,
        ]);
    })->name('map');

    //Icon
    Route::get('icon', function () {
        $breadcrumbsItems = [
            [
                'name' => 'Icon',
                'url' => '/icon',
                'active' => true
            ],

        ];
        return view('elements.icon.icon', [
            'pageTitle' => 'Icon',
            'breadcrumbItems' => $breadcrumbsItems,
        ]);
    })->name('icon');

    // Database Backup
    Route::resource('database-backups', DatabaseBackupController::class);
    Route::get('database-backups-download/{fileName}', [DatabaseBackupController::class, 'databaseBackupDownload'])->name('database-backups.download');

    // image show
    Route::get('/images/{media}', [ImageController::class, 'show'])->name('image.show');





// Ruta para mostrar la página del mapa
Route::get('/live-owntrack-map', [MapController::class, 'index'])->name('map.index'); // ->middleware('auth'); // Añade middleware si requiere login

// Ruta API para obtener los datos de ubicación (convencional usar /api/)
// Podrías moverla a routes/api.php si usas autenticación API,
// pero para simplicidad con la sesión web, la dejamos aquí por ahora.
Route::get('/api/locations/latest', [MapController::class, 'getLatestLocations'])->name('api.locations.latest'); // ->middleware('auth');
// --- Nueva Ruta ver los puntos calientes permitidos
Route::get('/api/control-points', [MapController::class, 'getControlPoints'])->name('api.control-points');
Route::get('/api/history/user/{userId}/date/{date}', [MapController::class, 'getUserHistoryByDay'])
    ->where('userId', '[0-9]+') // Asegura que userId sea numérico
    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}') // Asegura formato AAAA-MM-DD
    ->name('api.history.user.day');


});
// --- Grupo de rutas para Scraping Tasks ---
Route::middleware(['auth'])->prefix('scraping-tasks')->name('scraping.tasks.')->group(function () {
    Route::get('/', [ScrapingTaskController::class, 'index'])->name('index')->middleware('can:scrapingtasks index');
    Route::post('/', [ScrapingTaskController::class, 'store'])->name('store')->middleware('can:scrapingtasks store');
    Route::get('/data', [ScrapingTaskController::class, 'data'])->name('data')->middleware('can:scrapingtasks index'); // Mismo permiso que index
    Route::put('/{task}', [ScrapingTaskController::class, 'update'])->name('update')->middleware('can:scrapingtasks update');
    Route::delete('/{task}', [ScrapingTaskController::class, 'destroy'])->name('destroy')->middleware('can:scrapingtasks delete');
    Route::get('/{task}/contacts', [ScrapingTaskController::class, 'showContacts'])->name('contacts')->middleware('can:scrapingtasks show_contacts');
});

Route::middleware(['auth'])->group(function () {

    // Rutas para Services
    // IMPORTANTE: La ruta específica 'services/data' debe ir ANTES de Route::resource
    Route::get('services/data', [ServiceController::class, 'data'])->name('services.data');
    Route::resource('services', ServiceController::class);

    // Rutas para Clients
    Route::get('clients/data', [ClientController::class, 'data'])->name('clients.data'); // Para DataTables AJAX
    Route::resource('clients', ClientController::class);

    // Rutas para Quotes (Presupuestos)
    Route::get('quotes/data', [QuoteController::class, 'data'])->name('quotes.data'); // Para DataTables AJAX
    Route::resource('quotes', QuoteController::class);
    Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept'); // <-- NUEVA RUTA ACEPTAR
    Route::post('quotes/{quote}/reject', [QuoteController::class, 'reject'])->name('quotes.reject'); // <-- NUEVA RUTA RECHAZAR
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'exportPdf'])->name('quotes.pdf');
    Route::post('quotes/{quote}/send', [QuoteController::class, 'sendEmail'])->name('quotes.send');
    Route::post('quotes/{quote}/convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('quotes.convertToInvoice');

      // Rutas para Projects (Proyectos)
      Route::get('projects/data', [ProjectController::class, 'data'])->name('projects.data'); // Para DataTables AJAX
      Route::resource('projects', ProjectController::class);// <-- NUEVA RUTA


    // Tasks (Anidadas bajo proyectos y rutas para tareas del usuario)
    Route::get('projects/{project}/tasks/data', [TaskController::class, 'data'])->name('projects.tasks.data');
    Route::resource('projects.tasks', TaskController::class)->shallow();

    // *** NUEVA RUTA PARA "MIS TAREAS" ***
    Route::get('/my-tasks', [TaskController::class, 'myTasks'])->name('tasks.my');
    Route::get('/my-tasks/data', [TaskController::class, 'myTasksData'])->name('tasks.my.data'); // Para DataTables de "Mis Tareas"
});

// Grupo de rutas públicas
Route::group([], function () {
    Route::get('/logo/{media}', [ImageLogoController::class, 'show'])->name('logo.show');


});
