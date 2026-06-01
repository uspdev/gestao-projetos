<?php

namespace App\Http\Requests\MeetingItem;

use App\Enums\Meeting\MeetingStatus;
use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingItemNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        $project = $this->route('project');

        if (!$meeting instanceof Meeting || !$project instanceof Project) {
            return false;
        }

        if (!$this->user()->can('update', [$meeting, $project])) {
            return false;
        }

        if ($meeting->status === MeetingStatus::COMPLETED) {
            throw new AuthorizationException('Nao e possivel atualizar notas de uma reuniao ja concluida.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.string' => 'As notas devem ser um texto valido.',
            'notes.max' => 'As notas devem ter no maximo :max caracteres.',
        ];
    }
}
