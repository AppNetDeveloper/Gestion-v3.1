<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskerLinkedin;
use App\Models\LinkedinToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProcessLinkedinPublishTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasker:publish-linkedin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled LinkedIn tasks whose publish_date is due';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting infinite loop for publishing LinkedIn tasks.");

        // Bucle infinito
        while (true) {
            try {
                $now = Carbon::now();
                $this->info("Current time: " . $now->toDateTimeString());

                // Buscar todas las tareas con estado 'processing' y publish_date definido
                $tasks = TaskerLinkedin::where('status', 'processing')
                    ->whereNotNull('publish_date')
                    ->get();

                $this->info("Found " . $tasks->count() . " tasks with status 'processing'");

                // Filtrar tareas cuyo publish_date es menor o igual que la fecha y hora actual
                $dueTasks = $tasks->filter(function ($task) use ($now) {
                    // Si publish_date ya está casteado a Carbon, se puede comparar directamente.
                    // Sino, se usa Carbon::parse().
                    return Carbon::parse($task->publish_date)->lessThanOrEqualTo($now);
                });

                $this->info("Found " . $dueTasks->count() . " tasks due for publishing");

                if ($dueTasks->isEmpty()) {
                    $this->info("No tasks to publish. Sleeping for 10 seconds.");
                    sleep(10);
                    continue;
                }

                foreach ($dueTasks as $task) {
                    $this->info("Publishing task ID: " . $task->id);
                    $this->info("Task publish_date: " . Carbon::parse($task->publish_date)->toDateTimeString());

                    // Obtener el token de LinkedIn para el usuario de la tarea
                    $token = LinkedinToken::where('user_id', $task->user_id)->first();
                    if (!$token) {
                        $this->error("No LinkedIn token found for task ID: {$task->id}");
                        $task->error = "No LinkedIn token found.";
                        $task->status = 'failed';
                        $task->save();
                        continue;
                    }

                    // Obtener el perfil de LinkedIn para extraer el memberId
                    $profile = $this->getLinkedInProfile($token);
                    if (!$profile || !isset($profile['id'])) {
                        $this->error("Failed to retrieve LinkedIn profile for task ID: {$task->id}");
                        $task->error = "Failed to retrieve LinkedIn profile.";
                        $task->status = 'failed';
                        $task->save();
                        continue;
                    }
                    $memberId = $profile['id'];
                    $content = $task->response; // Se asume que la tarea ya tiene response generada

                    // Preparar la carga útil para publicar en LinkedIn
                    $postData = [
                        "author" => "urn:li:person:$memberId",
                        "lifecycleState" => "PUBLISHED",
                        "specificContent" => [
                            "com.linkedin.ugc.ShareContent" => [
                                "shareCommentary" => [
                                    "text" => $content
                                ],
                                "shareMediaCategory" => "NONE"
                            ]
                        ],
                        "visibility" => [
                            "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC"
                        ]
                    ];

                    $this->info("Publishing post for task ID: {$task->id}");
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$token->token}",
                        'Content-Type'  => 'application/json',
                        'X-Restli-Protocol-Version' => '2.0.0'
                    ])->post('https://api.linkedin.com/v2/ugcPosts', $postData);

                    if ($response->successful()) {
                        $this->info("Task ID {$task->id} published successfully.");
                        $task->status = 'completed';
                        $task->response = "Published successfully at " . Carbon::now()->toDateTimeString();
                        $task->save();
                    } else {
                        $this->error("Error publishing task ID {$task->id}: " . $response->body());
                        $task->error = "Error publishing: " . $response->body();
                        $task->status = 'failed';
                        $task->save();
                    }
                }
            } catch (\Exception $e) {
                $this->error("Exception in publish loop: " . $e->getMessage());
            }
            sleep(10);
        }

        // Nunca se alcanza este return, pero se requiere por la firma del método.
        return 0;
    }

    /**
     * Retrieve the LinkedIn profile using the token.
     *
     * @param  \App\Models\LinkedinToken  $token
     * @return array|null
     */
    private function getLinkedInProfile($token)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token->token}",
        ])->get('https://api.linkedin.com/v2/me');

        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }
}
