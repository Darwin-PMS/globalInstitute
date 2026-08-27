<?php

namespace App\Http\Requests\Api\OnlineLearning;

use Illuminate\Foundation\Http\FormRequest;

class SendLiveClassMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
            'type' => 'nullable|in:message,announcement',
        ];
    }
}
