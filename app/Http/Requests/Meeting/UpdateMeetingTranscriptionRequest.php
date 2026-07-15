<?php

namespace App\Http\Requests\Meeting;

use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingTranscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        $project = $this->route('project');

        return $meeting instanceof Meeting
            && $project instanceof Project
            && $this->user()->can('update', [$meeting, $project]);
    }

    public function rules(): array
    {
        return [
            'transcription' => ['nullable', 'string', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'transcription.string' => 'A Transcrição deve ser um texto válido.',
            'transcription.max' => 'A Transcrição deve ter no máximo :max caracteres.',
        ];
    }
}
