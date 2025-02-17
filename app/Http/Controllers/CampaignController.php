<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    /**
     * Muestra el listado de campañas del usuario.
     */
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Muestra el formulario para crear una nueva campaña.
     */
    public function create()
    {
        return view('campaigns.create');
    }

    /**
     * Almacena la campaña y crea los detalles para cada contacto.
     */
    public function store(Request $request)
    {
        $request->validate([
            'prompt'         => 'required|string',
            'campaign_start' => 'nullable|date',
            'model'          => 'required|in:whatsapp,email,sms,telegram',
        ]);

        // Crear la campaña
        $campaign = Campaign::create([
            'user_id'       => Auth::id(),
            'prompt'        => $request->input('prompt'),
            'status'        => 'pending',
            'campaign_start'=> $request->input('campaign_start'),
            'model'         => $request->input('model'),
        ]);

        // Obtener los contactos del usuario
        $contacts = Contact::where('user_id', Auth::id())->get();

        foreach ($contacts as $contact) {
            // Filtrar contactos según el tipo de campaña:
            if (in_array($campaign->model, ['whatsapp', 'sms']) && empty($contact->phone)) {
                continue; // Si es whatsapp o sms y no hay phone, se salta el contacto
            }
            if ($campaign->model === 'email' && empty($contact->email)) {
                continue; // Si es email y no hay email, se salta el contacto
            }
            if ($campaign->model === 'telegram' && empty($contact->telegram)) {
                continue; // Si es telegram y no hay telegram, se salta el contacto
            }

            // Determinar el valor de reemplazo para el placeholder {contact}
            // Se intenta usar el nombre y, si no existe, se utiliza el dato correspondiente al modelo
            $replacement = $contact->name;
            if (empty($replacement)) {
                if (in_array($campaign->model, ['whatsapp', 'sms'])) {
                    $replacement = $contact->phone;
                } elseif ($campaign->model === 'email') {
                    $replacement = $contact->email;
                } elseif ($campaign->model === 'telegram') {
                    $replacement = $contact->telegram;
                }
            }

            // Reemplazar el placeholder {contact} en el prompt
            $customPrompt = str_replace('{contact}', $replacement, $campaign->prompt);

            // Generar el texto único para la campaña (aquí se simula, reemplaza con tu integración real)
            $uniqueText = $this->generateCampaignText($customPrompt, $contact);

            // Crear la entrada en campaign_detail
            CampaignDetail::create([
                'campaign_id' => $campaign->id,
                'contact_id'  => $contact->id,
            ]);
        }

        return redirect()->route('campaigns.index')
                         ->with('success', 'Campaign created successfully.');
    }


    /**
     * Método simulado para generar el texto de la campaña.
     * Reemplaza este método con la integración real a la API de Ollama.
     */
    protected function generateCampaignText($prompt, $contact)
    {
        // Aquí podrías realizar la llamada a la API y retornar la respuesta
        return $prompt;
    }
    public function data()
    {
        $campaigns = Campaign::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $campaigns]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'prompt'         => 'required|string',
            'campaign_start' => 'nullable|date',
        ]);

        // Buscar la campaña que pertenece al usuario autenticado
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        // Actualizar la campaña
        $campaign->update([
            'prompt'         => $request->input('prompt'),
            'campaign_start' => $request->input('campaign_start'),
        ]);

        return response()->json(['success' => 'Campaign updated successfully.']);
    }

    public function destroy($id)
    {
        // Buscar la campaña que pertenece al usuario autenticado
        $campaign = Campaign::where('user_id', Auth::id())->findOrFail($id);

        // Eliminar la campaña (y sus detalles, si tienes configurado cascade delete en la migración)
        $campaign->delete();

        return response()->json(['success' => 'Campaign deleted successfully.']);
    }

}
