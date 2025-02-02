<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Models\LinkedinToken;
use Auth;
use Illuminate\Support\Facades\Redirect;

class LinkedinController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $tokenUrl;
    private $shareUrl;

    public function __construct()
    {
        $this->clientId = env('LINKEDIN_CLIENT_ID');
        $this->clientSecret = env('LINKEDIN_CLIENT_SECRET');
        $this->redirectUri = env('LINKEDIN_REDIRECT_TOKEN');
        $this->tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
        $this->shareUrl = 'https://api.linkedin.com/v2/ugcPosts';
    }

    public function redirectToLinkedIn()
    {
        $authUrl = "https://www.linkedin.com/oauth/v2/authorization?response_type=code" .
            "&client_id={$this->clientId}" .
            "&redirect_uri={$this->redirectUri}" .
            "&scope=r_liteprofile%20r_emailaddress%20w_member_social";

        return Redirect::away($authUrl);
    }

    public function handleLinkedInCallback(Request $request)
    {
        $code = $request->query('code');
    
        if (!$code) {
            return redirect('/linkedin')->with('error', 'Authorization failed.');
        }
    
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    
        $data = $response->json();
    
        if (isset($data['access_token'])) {
            $userId = auth()->id();
    
            if (!$userId) {
                return redirect('/linkedin')->with('error', 'No hay usuario autenticado.');
            }
    
            // Guardar el token en la base de datos
            LinkedinToken::updateOrCreate(
                ['user_id' => $userId],
                ['token' => $data['access_token']]
            );
    
            \Log::info('Token guardado para el usuario: ' . $userId);
    
            // Redirigir a /linkedin con mensaje de éxito
            return redirect('/linkedin')->with('success', 'LinkedIn conectado exitosamente.');
        } else {
            return redirect('/linkedin')->with('error', 'No se pudo obtener el token de LinkedIn.');
        }
    }
    
    

    public function index()
    {
        $token = LinkedinToken::where('user_id', Auth::id())->first();
        return view('linkedin.index', compact('token'));
    }

    public function publishPost(Request $request)
    {
        $token = LinkedinToken::where('user_id', Auth::id())->first();
        if (!$token) {
            return response()->json(['error' => 'No LinkedIn token found.'], 401);
        }

        $content = $request->input('content', 'Publicación de prueba en LinkedIn desde Laravel.');
        $memberId = $this->getLinkedInProfile()['id'];

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

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token->token}",
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0'
        ])->post($this->shareUrl, $postData);

        if ($response->successful()) {
            return response()->json(['message' => 'Post publicado exitosamente.']);
        } else {
            return response()->json(['error' => 'Error al publicar', 'details' => $response->json()], 400);
        }
    }

    public function getLinkedInProfile()
    {
        $token = LinkedinToken::where('user_id', auth()->id())->first();

        if (!$token) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token->token}",
        ])->get('https://api.linkedin.com/v2/me');

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
    public function disconnect()
    {
        $token = LinkedinToken::where('user_id', auth()->id())->first();

        if (!$token) {
            return response()->json(['error' => 'No hay cuenta de LinkedIn vinculada.'], 400);
        }

        $token->delete();

        return response()->json(['success' => 'Has desvinculado tu cuenta de LinkedIn correctamente.']);
    }


}
