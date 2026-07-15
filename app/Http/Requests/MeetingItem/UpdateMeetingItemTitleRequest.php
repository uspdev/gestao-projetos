<?php

namespace App\Http\Requests\MeetingItem;

use App\Enums\Meeting\MeetingStatus;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingItemTitleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title'))]);
    }

    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        $project = $this->route('project');
        $meetingItem = $this->route('meetingItem');

        if (! $meeting instanceof Meeting || ! $project instanceof Project || ! $meetingItem instanceof MeetingItem) {
            return false;
        }

        if ($meetingItem->meeting_id !== $meeting->id || $meetingItem->title === null) {
            return false;
        }

        if (! $this->user()->can('update', [$meeting, $project])) {
            return false;
        }

        if ($meeting->status === MeetingStatus::COMPLETED) {
            throw new AuthorizationException('Não é possível editar itens de uma reunião já concluída.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título do item independente.',
            'title.string' => 'O título do item independente é inválido.',
            'title.min' => 'O título do item independente deve ter pelo menos :min caracteres.',
            'title.max' => 'O título do item independente não pode exceder :max caracteres.',
        ];
    }
}
