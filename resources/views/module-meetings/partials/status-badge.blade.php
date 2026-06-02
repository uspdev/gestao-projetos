@can('update', [$meeting, $project])
  <div class="dropdown">
    <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button"
      id="meeting-status-dropdown-{{ $meeting->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
      title="Alterar status da reuniao">
      <span class="badge badge-{{ $meeting->status?->color() ?? 'light' }}">
        {{ $meeting->status?->label() ?? '-' }}
      </span>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="meeting-status-dropdown-{{ $meeting->id }}">
      @foreach (\App\Enums\Meeting\MeetingStatus::cases() as $status)
        <form method="POST" action="{{ route('meetings.updateMeetingStatus', [$project, $meeting]) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ $status->value }}">
          <button type="submit" class="dropdown-item small" @disabled($meeting->status?->value === $status->value)>
            <span class="badge badge-{{ $status->color() }}" style="font-size: .75rem;">{{ $status->label() }}</span>
            @if ($meeting->status?->value === $status->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="badge badge-{{ $meeting->status?->color() ?? 'light' }}">
    {{ $meeting->status?->label() ?? '-' }}
  </span>
@endcan
