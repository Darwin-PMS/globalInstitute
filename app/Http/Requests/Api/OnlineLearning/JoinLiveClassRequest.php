<?php

namespace App\Http\Requests\Api\OnlineLearning;

use Illuminate\Foundation\Http\FormRequest;

class JoinLiveClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mic_enabled' => 'nullable|boolean',
            'cam_enabled' => 'nullable|boolean',
        ];
    }
}
