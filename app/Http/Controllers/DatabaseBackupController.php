<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Backup\Helpers\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\BackupDestination\Backup;
use Illuminate\Contracts\Foundation\Application;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;
use Spatie\Backup\Config\MonitoredBackupsConfig;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;

class DatabaseBackupController extends Controller

{


    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        if (auth()->user()->cannot('database_backup viewAny')) {
            abort(403);
        }

        $breadcrumbsItems = [
            [
                'name' => 'Database Backup',
                'url' => route('database-backups.index'),
                'active' => true
            ],
        ];

        $databaseBackupList = $this->backupList();
        $files = $this->getFiles(env('BACKUP_STORAGE', 'local'));


        return view('database-backup.index', [
            'databaseBackupList' => $databaseBackupList,
            'files' => $files,
            'breadcrumbItems' => $breadcrumbsItems,
            'pageTitle' => 'Database Backup'
        ]);
    }

    public function getFiles(string $disk = '')
    {
        if ($disk) {
            $activeDisk = $disk;
        }

        $backupDestination = BackupDestination::create($activeDisk, config('backup.backup.name'));

        return $backupDestination
            ->backups()
            ->map(function (Backup $backup) {
                $size = method_exists($backup, 'sizeInBytes') ? $backup->sizeInBytes() : $backup->size();

                return [
                    'path' => $backup->path(),
                    'file_name' => explode('/', $backup->path())[1],
                    'date' => $backup->date()->format('Y-m-d H:i:s'),
                    'size' => Format::humanReadableSize($size),
                ];
            })
            ->toArray();
    }

    public function backupList(): array
    {
        // ➊ Convertimos el array a MonitoredBackupsConfig
        $monitoredConfig = MonitoredBackupsConfig::fromArray(
            config('backup.monitor_backups')
        );

        // ➋ Obtenemos los estados con la nueva firma
        return BackupDestinationStatusFactory::createForMonitorConfig($monitoredConfig)
            ->map(function (BackupDestinationStatus $status) {
                $destination = $status->backupDestination();

                return [
                    'name'        => $destination->backupName(),
                    'disk'        => $destination->diskName(),
                    'reachable'   => $destination->isReachable(),
                    'healthy'     => $status->isHealthy(),
                    'amount'      => $destination->backups()->count(),
                    'newest'      => optional($destination->newestBackup())
                                        ?->date()?->diffForHumans() ?? 'Sin copias',
                    'usedStorage' => Format::humanReadableSize($destination->usedStorage()),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse
     */
    public function create()
    {
        if (auth()->user()->cannot('database_backup create')) {
            abort(403);
        }

        Artisan::call(env('MODEL_BACKUP', 'backup:run --only-db'));
        return back()->with('message', 'Database backup created successfully!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function destroy(int $id)
{
    if (auth()->user()->cannot('database_backup delete')) {
        abort(403);
    }

    $files = $this->getFiles(env('BACKUP_STORAGE', 'local'));
    $deletingFile = $files[$id];

    $backupDisk = env('BACKUP_STORAGE', 'local');
    $backupPath = $deletingFile['path'];

    try {
        if (Storage::disk($backupDisk)->delete($backupPath)) {
            return back()->with(['message' => 'File Deleted Successfully!']);
        } else {
            return back()->with('error', __('Backup file not found.'));
        }
    } catch (\Exception $e) {
        Log::error("Error al eliminar el archivo de respaldo: " . $e->getMessage());
        return back()->with('error', __('Error deleting backup file.'));
    }
}


    public function databaseBackupDownload(string $fileName)
    {
        if (auth()->user()->cannot('database_backup download')) {
            abort(403);
        }

        $backupDisk = env('BACKUP_STORAGE', 'local');
        $backupPath = config('backup.backup.name') . '/' . $fileName;

        try {
            if (!Storage::disk($backupDisk)->exists($backupPath)) {
                return back()->with('error', __('Backup file not found.'));
            }

            $stream = Storage::disk($backupDisk)->readStream($backupPath);
            $mimeType = Storage::disk($backupDisk)->mimeType($backupPath);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (UnableToReadFile $e) {
            Log::error("Error al leer el archivo de respaldo: " . $e->getMessage());
            return back()->with('error', __('Error downloading backup file.'));
        }
    }

    
}
