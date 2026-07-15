<?php

namespace App\Http\Requests\Meeting;

use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingAtaRequest extends FormRequest
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
            'ata' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ata.string' => 'A Ata deve ser um texto válido.',
            'ata.max' => 'A Ata deve ter no máximo :max caracteres.',
        ];
    }
}
