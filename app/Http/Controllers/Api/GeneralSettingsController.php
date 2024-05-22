<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralSettingsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class GeneralSettingsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $logo = Media::where('collection_name', 'logo')->first();
        $favicon = Media::where('collection_name', 'favicon')->first();
        $darkLogo = Media::where('collection_name', 'dark_logo')->first();
        $guestLogo = Media::where('collection_name', 'guest_logo')->first();
        $guestBackground = Media::where('collection_name', 'guest_background')->first();

        $data = [
            'logo' => $logo ? route('logo.show', $logo->id) : null,
            'favicon' => $favicon ? route('logo.show', $favicon->id) : null,
            'dark_logo' => $darkLogo ? route('logo.show', $darkLogo->id) : null,
            'guest_logo' => $guestLogo ? route('logo.show', $guestLogo->id) : null,
            'guest_background' => $guestBackground ? route('logo.show', $guestBackground->id) : null,
        ];

        return $this->responseWithSuccess('General Settings', GeneralSettingsResource::make((object)$data));
    }

}
