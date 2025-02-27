<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\OllamaTasker;
use App\Services\EmailSenderService;
use Carbon\Carbon;
use Exception;
use App\Models\User;

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

    protected EmailSenderService $emailSender;

    public function __construct(EmailSenderService $emailSender)
    {
        parent::__construct();
        $this->emailSender = $emailSender;
    }

    public function handle()
    {

        $this->info("Starting infinite loop for processing Campaign tasks with Ollama integration.");

        while (true) {
            try {
                // Obtiene todos los campaign_ids (únicos) de campaign_details.
                $campaignIds = CampaignDetail::distinct()->pluck('campaign_id');

                if ($campaignIds->isEmpty()) {

                    $this->info("No pending campaign details found. Sleeping for 10 seconds.");
                    sleep(10);
                    continue;
                }

                foreach ($campaignIds as $campaignId) {
                    try {
                        $campaign = Campaign::find($campaignId);
                        if (!$campaign) {
                            $this->info("Campaign ID {$campaignId} not found. Skipping.");
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
                                $prompt_start = "Crea una publicación profesional y atractiva.
                                No incluyas encabezados ni frases introductorias (por ejemplo, 'Aquí tienes una publicación').
                                No uses placeholders ni datos de firma genéricos (por ejemplo, '[Tu Nombre]', '[Tu Cargo]', '[Tu Empresa]', '[Tu Ciudad]', '[Tu País]', '[Tu Correo electrónico]', '[Tu Teléfono]', '[Tu Web]', '[Su Nombre]', '[Su Teléfono]', '[Su Web]').
                                No inventes ni modifiques ningún dato de contacto (números de teléfono, correos electrónicos o páginas web) que aparezcan en el contenido original.
                                Si se incluyen datos de contacto, mantenlos exactamente como están.
                                No agregues información adicional, comentarios ni formatos extra.
                                Sigue estrictamente estas indicaciones y utiliza el mismo idioma del contenido original:";

                                switch ($campaign->model) {
                                    case 'whatsapp':
                                        $prompt_end = "Mantén un tono profesional, cercano y humano, con un mensaje breve y directo para WhatsApp.
                                No añadas nada más que el texto, sin comentarios ni explicaciones adicionales.";
                                        break;

                                    case 'sms':
                                        $prompt_end = "Mantén un tono profesional y conciso, adecuado para un SMS con pocos caracteres.
                                No añadas ningún comentario ni información adicional fuera del texto.";
                                        break;

                                    case 'telegram':
                                        $prompt_end = "Mantén un tono profesional y cercano, con un mensaje adecuado para Telegram.
                                No añadas comentarios ni explicaciones adicionales, solo el texto.";
                                        break;

                                    case 'email':
                                        $prompt_end = "Mantén un tono profesional, cercano y humano, creando un mensaje de email que contenga EXACTAMENTE dos secciones en líneas separadas:
                                1) 'asunto:' (representa el título del email)
                                2) 'cuerpo:' (contiene el mensaje completo)
                                Es MUY IMPORTANTE respetar estos marcadores sin modificarlos y no añadir más líneas ni secciones (por ejemplo, nada de 'Cuerpo:' adicionales).
                                No inventes datos de contacto (números de teléfono, correos electrónicos, webs) ni añadas comentarios o formato JSON.
                                No incluyas placeholders ni firmas genéricas si no están en el texto original.
                                No modifiques esos marcadores ni añadas otros. No incluyas nada más, ni comentarios ni información adicional.
                                Respeta el texto que se te proporcione sin inventar ni alterar el contenido.
                                No uses '** **' para resaltar información importante, ni antes ni después de 'asunto:' y 'cuerpo:'.";
                                        break;

                                    default:
                                        $prompt_end = "Mantén un tono profesional y humano.
                                Solo incluye el texto final sin comentarios ni explicaciones adicionales.";
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

                                continue;
                            } else {
                                $ollamaTask = OllamaTasker::find($campaignDetail->ollama_tasker_id);
                                if (!$ollamaTask) {
                                    $this->error("OllamaTasker with ID {$campaignDetail->ollama_tasker_id} not found for CampaignDetail ID {$campaignDetail->id}. Resetting field.");

                                    $campaignDetail->ollama_tasker_id = null;
                                    $campaignDetail->save();
                                    continue;
                                }
                                if (empty($ollamaTask->response)) {
                                    $this->info("OllamaTasker ID {$ollamaTask->id} still has no response for CampaignDetail ID {$campaignDetail->id}.");
                                    continue;
                                } else {
                                    $campaignDetail->text = $ollamaTask->response;
                                    $campaignDetail->save();
                                    $this->info("CampaignDetail ID {$campaignDetail->id} updated with response from OllamaTasker ID {$ollamaTask->id}.");
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
                                    $this->info("Campaign ID {$campaign->id} start time ({$campaignStartTime}) reached. Sending CampaignDetail ID {$campaignDetail->id}.");
                                }else {
                                    $this->info("Campaign ID {$campaign->id} start time ({$campaignStartTime}) not reached yet. Not sending CampaignDetail ID {$campaignDetail->id}.");

                                }
                            } else {
                                $canSend = true;
                                $this->info("Campaign ID {$campaign->id} has no start time set. Sending CampaignDetail ID {$campaignDetail->id}.");
                            }
                            if ($canSend) {
                                $this->info("Condición de envío cumplida para CampaignDetail ID {$campaignDetail->id}. Procediendo a sendCampaign.");
                                $this->sendCampaign($campaign, $campaignDetail);
                            } else {
                                $this->info("No se cumple la condición de envío para CampaignDetail ID {$campaignDetail->id}.");
                            }

                        }
                    } catch (Exception $ex) {
                        $this->error("Error processing campaign ID {$campaignId}: " . $ex->getMessage());
                    }
                }
            } catch (Exception $e) {
                $this->info("Error processing campaign details. Removing error from application.");
            }
            sleep(10);
        }
        return 0;
    }

    private function sendCampaign(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $model = $campaign->model;
        $this->info("Sending campaign ID {$campaign} to {$model} with details: " . json_encode($campaignDetail));
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
                    break;
            }
        } catch (Exception $ex) {
            $this->error("Error sending campaign for CampaignDetail ID {$campaignDetail->id}: " . $ex->getMessage());
        }
    }

    private function sendWhatsapp(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending WhatsApp campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");

        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function sendEmail(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending Email campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}.");

        // Obtener el email del destinatario a partir de la relación 'contact'.
        $recipientEmail = $campaignDetail->contact->email ?? null;
        if (!$recipientEmail) {
            $this->info("No recipient email found for CampaignDetail ID {$campaignDetail->id}. Skipping.");
            return;
        }

        // Inicializar asunto y cuerpo.
        $subject = $campaign->email_subject ?? 'Campaña de correo';
        $body = $campaignDetail->text;

        /**
         * Explicación de los patrones:
         * - ^ y $ marcan inicio y fin de la cadena.
         * - \*{0,2} permite de 0 a 2 asteriscos. Ejemplo: "**asunto", "*asunto", "asunto".
         * - \s* permite espacios en blanco antes o después de la palabra clave.
         * - (.*?) captura el contenido (asunto o cuerpo) de forma no codiciosa.
         * - 'is' al final del regex: i → case-insensitive, s → modo "dotall" (el . también captura saltos de línea).
         */
        // 1) Buscar si el texto viene con "asunto" y "cuerpo" (en español):
        if (preg_match('/^\*{0,2}\s*asunto\s*\*{0,2}:\s*(.*?)\s*\*{0,2}\s*cuerpo\s*\*{0,2}:\s*(.*)$/is', $body, $matches)) {
            $subject = trim($matches[1]);
            $body    = trim($matches[2]);
        }
        // 2) Si no, buscar si viene con "subject" y "body" (en inglés):
        elseif (preg_match('/^\*{0,2}\s*subject\s*\*{0,2}:\s*(.*?)\s*\*{0,2}\s*body\s*\*{0,2}:\s*(.*)$/is', $body, $matches)) {
            $subject = trim($matches[1]);
            $body    = trim($matches[2]);
        }

        // Puedes añadir más patrones si lo deseas (por ejemplo, solo "asunto:" sin asteriscos),
        // o capturar mayúsculas/minúsculas. Lo anterior ya es case-insensitive.

        // Datos adicionales, por ejemplo, si deseas incluir un enlace de acción.
        $extra = [
            'action_url' => $campaign->action_url ?? ''
        ];

        // Se utiliza un remitente "sistema" (por ejemplo, el usuario con ID especificado).
        $sender = User::find($campaign->user_id);

        try {
            // Enviar el correo usando el servicio centralizado.
            $this->emailSender->send($sender, $recipientEmail, $subject, $body, $extra);
            $this->info("Email sent to {$recipientEmail} for CampaignDetail ID {$campaignDetail->id}.");
        } catch (\Exception $ex) {
            $this->error("Error sending email for CampaignDetail ID {$campaignDetail->id}: " . $ex->getMessage());
        }

        // Una vez enviado, eliminamos el CampaignDetail y verificamos si la campaña ha finalizado.
        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }




    private function sendSms(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending SMS campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");

        $campaignDetail->delete();
        $this->checkCampaignCompletion($campaign);
    }

    private function sendTelegram(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->info("Sending Telegram campaign for CampaignDetail ID {$campaignDetail->id}, Contact ID {$campaignDetail->contact_id}. Message: {$campaignDetail->text}");

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
        }
    }
}
