<?php

namespace App\Http\Requests\Api\OnlineLearning;

use Illuminate\Foundation\Http\FormRequest;

class StartRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'layout' => 'nullable|in:speaker,grid,speaker-lightbox',
        ];
    }
}
