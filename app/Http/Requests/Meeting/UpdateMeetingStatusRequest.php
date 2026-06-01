<?php

namespace App\Http\Requests\Meeting;

use App\Enums\Meeting;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Meeting\MeetingStatus;

class UpdateMeetingStatusRequest extends FormRequest
{

    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');
        $project = $this->route('project');

        if (! $meeting || ! $project) {
            return false;
        }

        return $this->user()->can('update', [$meeting, $project]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(MeetingStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'É necessario definir um status para a reunião.',
            'status.enum' => 'O status selecionado é invalido.',
        ];
    }
}