<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\OllamaTasker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessCampaignOllamaTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:process-campaign-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Campaign tasks: create Ollama tasks for campaign details, check for responses, and send campaigns when the start time is reached.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("Starting infinite loop for processing Campaign tasks with Ollama integration.");
        $this->info("Starting infinite loop for processing Campaign tasks with Ollama integration.");

        while (true) {
            try {
                // Obtiene todos los campaign_ids (únicos) de campaign_details.
                $campaignIds = CampaignDetail::distinct()->pluck('campaign_id');

                if ($campaignIds->isEmpty()) {
                    Log::info("No pending campaign details found. Sleeping for 10 seconds.");
                    $this->info("No pending campaign details found. Sleeping for 10 seconds.");
                    sleep(10);
                    continue;
                }

                foreach ($campaignIds as $campaignId) {
                    try {
                        $campaign = Campaign::find($campaignId);
                        if (!$campaign) {
                            Log::warning("Campaign ID {$campaignId} not found. Skipping.");
                            continue;
                        }

                        // Procesar solo la primera línea pendiente para la campaña.
                        $campaignDetail = CampaignDetail::where('campaign_id', $campaignId)
                            ->where(function ($q) {
                                $q->whereNull('text')->orWhere('text', '');
                            })
                            ->orderBy('id', 'asc')
                            ->first();

                        if (!$campaignDetail) {
                            continue;
                        }

                        // --- Preprocesamiento: Crear en OllamaTasker si no existe ---
                        if (empty($campaignDetail->text)) {
                            if (is_null($campaignDetail->ollama_tasker_id)) {
                                $this->info("CampaignDetail ID {$campaignDetail->id} has no OllamaTasker associated. Creating new OllamaTasker.");
                                Log::info("CampaignDetail ID {$campaignDetail->id} has no OllamaTasker associated. Creating new OllamaTasker.");

                                $prompt_start = "Crea una publicación profesional y atractiva, pero sin escribir nada de cabezal,sin poner algo como Aqui tienes una publicacion etc. siguiendo estas directrices:";

                                switch ($campaign->model) {
                                    case 'whatsapp':
                                        $prompt_end = " Mantén un tono profesional, cercano y humano, con un mensaje breve para WhatsApp.";
                                        break;
                                    case 'sms':
                                        $prompt_end = " Mantén un tono profesional y conciso, ya que es un SMS con pocos caracteres.";
                                        break;
                                    case 'telegram':
                                        $prompt_end = " Mantén un tono profesional y cercano, con un mensaje adecuado para Telegram.";
                                        break;
                                    case 'email':
                                        $prompt_end = " Mantén un tono profesional, cercano y humano, con un mensaje adecuado para email.";
                                        break;
                                    default:
                                        $prompt_end = " Mantén un tono profesional y humano.";
                                        break;
                                }
                                $lineInfo = " [Contacto ID: {$campaignDetail->contact_id}]";
                                $finalPrompt = trim($prompt_start . " " . $campaign->prompt . " " . $prompt_end);

                                $ollamaTask = new OllamaTasker();
                                $ollamaTask->prompt = $finalPrompt;
                                $ollamaTask->model = env('OLLAMA_MODEL_DEFAULT');
                                $ollamaTask->response = null;
                                $ollamaTask->error = null;
                                $ollamaTask->save();

                                $campaignDetail->ollama_tasker_id = $ollamaTask->id;
                                $campaignDetail->save();

                                $this->info("Created OllamaTasker with ID {$ollamaTask->id} for CampaignDetail ID {$campaignDetail->id}.");
                                Log::info("Created OllamaTasker with ID {$ollamaTask->id} for CampaignDetail ID {$campaignDetail->id}.");
                                continue;
                            } else {
                                $ollamaTask = OllamaTasker::find($campaignDetail->ollama_tasker_id);
                                if (!$ollamaTask) {
                                    $this->error("OllamaTasker with ID {$campaignDetail->ollama_tasker_id} not found for CampaignDetail ID {$campaignDetail->id}. Resetting field.");
                                    Log::error("OllamaTasker with ID {$campaignDetail->ollama_tasker_id} not found for CampaignDetail ID {$campaignDetail->id}. Resetting field.");
                                    $campaignDetail->ollama_tasker_id = null;
                                    $campaignDetail->save();
                                    continue;
                                }
                                if (empty($ollamaTask->response)) {
                                    $this->info("OllamaTasker ID {$ollamaTask->id} still has no response for CampaignDetail ID {$campaignDetail->id}.");
                                    Log::info("OllamaTasker ID {$ollamaTask->id} still has no response for CampaignDetail ID {$campaignDetail->id}.");
                                    continue;
                                } else {
                                    $campaignDetail->text = $ollamaTask->response;
                                    $campaignDetail->save();
                                    $this->info("CampaignDetail ID {$campaignDetail->id} updated with response from OllamaTasker ID {$ollamaTask->id}.");
                                    Log::info("CampaignDetail ID {$campaignDetail->id} updated with response from OllamaTasker ID {$ollamaTask->id}.");
                                }
                            }
                        }

                        // --- Envío: Evaluar si se puede enviar la línea ---
                        if (!empty($campaignDetail->text)) {
                            $canSend = false;
                            if ($campaign->campaign_start) {
                                $campaignStartTime = Carbon::parse($campaign->campaign_start);
                                if (Carbon::now()->gte($campaignStartTime->addSecond(1))) {
                                    $canSend = true;
                                } else {
                                    $this->info("Campaign ID {$campaign->id} start time ({$campaignStartTime}) not reached yet. Not sending CampaignDetail ID {$campaignDetail->id}.");
                                    Log::info("Campaign ID {$campaign->id} start time ({$campaignStartTime}) not reached yet. Not sending CampaignDetail ID {$campaignDetail->id}.");
                                }
                            } else {
                                $canSend = true;
                            }
                            if ($canSend) {
                                $this->sendCampaign($campaign, $campaignDetail);
                            }
                        }
                    } catch (Exception $ex) {
                        Log::error("Error processing campaign ID {$campaignId}: " . $ex->getMessage());
                        $this->error("Error processing campaign ID {$campaignId}: " . $ex->getMessage());
                        // Continúa con la siguiente campaña sin detener el bucle principal.
                    }
                }
            } catch (Exception $e) {
                Log::error("Exception in processing loop: " . $e->getMessage());
                $this->error("Exception in processing loop: " . $e->getMessage());
            }
            sleep(10);
        }
        return 0;
    }

    private function sendCampaign(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $model = $campaign->model;
        try {
            switch ($model) {
                case 'whatsapp':
                    $this->sendWhatsapp($campaign, $campaignDetail);
                    break;
                case 'email':
                    $this->sendEmail($campaign, $campaignDetail);
                    break;
                case 'sms':
                    $this->sendSms($campaign, $campaignDetail);
                    break;
                case 'telegram':
                    $this->sendTelegram($campaign, $campaignDetail);
                    break;
                default:
                    $this->info("Model {$model} not recognized. Skipping CampaignDetail ID {$campaignDetail->id}.");
                    Log::warning("Model {$model} not recognized. Skipping CampaignDetail ID {$campaignDetail->id}.");
                    break;
            }
        } catch (Exception $ex) {
            Log::error("Error sending campaign for CampaignDetail ID {$campaignDetail->id}: " . $ex->getMessage());
            $this->error("Error sending campaign for CampaignDetail ID {$campaignDetail->id}: " . $ex->getMessage());
        }
    }

    private function sendWhatsapp(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending WhatsApp campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        Log::info("Sending WhatsApp campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        // Lógica real de envío.
        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function sendEmail(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending Email campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        Log::info("Sending Email campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function sendSms(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending SMS campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        Log::info("Sending SMS campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function sendTelegram(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending Telegram campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        Log::info("Sending Telegram campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");
        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function checkCampaignCompletion(Campaign $campaign)
    {
        $pendingDetails = $campaign->details()->count();
        if ($pendingDetails === 0) {
            $campaign->status = 'completed';
            $campaign->save();
            $this->info("Campaign ID {$campaign->id} is completed. No more pending campaign details.");
            Log::info("Campaign ID {$campaign->id} is completed. No more pending campaign details.");
        }
    }
}
