<?php

namespace App\Http\Requests\Api\OnlineLearning;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParticipantPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'can_speak' => 'nullable|boolean',
            'can_use_camera' => 'nullable|boolean',
            'can_share_screen' => 'nullable|boolean',
            'can_chat' => 'nullable|boolean',
            'can_record' => 'nullable|boolean',
        ];
    }
}
