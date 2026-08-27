<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SettingApiController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SiteSetting::pluck('setting_value', 'setting_key')->toArray();

        return response()->json(['data' => $settings]);
    }
}
