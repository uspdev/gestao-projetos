@extends('layouts.app')

@section('title', $project->name . ' - Reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="card mb-4 shadow-sm">
        <div class="card-header h5 d-flex align-items-center justify-content-between">
          <span><i class="far fa-calendar-alt mr-1"></i> {{ $meeting->title }}</span>
          <div class="d-flex align-items-center gap-2">
            <span class="badge {{ $meeting->status?->color() ?? 'badge-light text-dark' }}">
              {{ $meeting->status?->label() ?? '-' }}
            </span>
            @can('update', [$meeting, $project])
              <a href="{{ route('projects.meetings.edit', [$project, $meeting]) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit"></i>
              </a>
            @endcan
            @can('delete', [$meeting, $project])
              <form method="POST" action="{{ route('projects.meetings.destroy', [$project, $meeting]) }}"
                class="d-inline-block" onsubmit="return confirm('Deseja remover esta reuniao?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            @endcan
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <small class="text-muted d-block">Data e hora</small>
                <strong>
                  <x-local-date :date="$meeting->scheduled_at" empty="-" />
                </strong>
              </div>
              <div class="mb-3">
                <small class="text-muted d-block">Local</small>
                <strong>{{ $meeting->location ?? '-' }}</strong>
              </div>
            </div>
            <div class="col-md-6">
              <small class="text-muted d-block">Projetos vinculados</small>
              <ul class="list-unstyled mb-0">
                @foreach ($meeting->projects as $linkedProject)
                  <li>
                    <a href="{{ route('projects.show', $linkedProject) }}" class="text-decoration-none">
                      {{ $linkedProject->name }}
                    </a>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>

          <hr>

          <div>
            <small class="text-muted d-block">Notas</small>
            @if ($meeting->notes)
              <div class="text-dark mt-2">
                <x-markdown-content :text="$meeting->notes" />
              </div>
            @else
              <div class="text-muted">Nenhuma nota registrada.</div>
            @endif
          </div>
        </div>
      </div>

        @include('projects.meetings.partials.items-list', [
          'meetingItems' => $meetingItems,
          'meeting' => $meeting,
          'project' => $project,
        ])
      @include('projects.meetings.partials.items-form', [
          'project' => $project,
          'meeting' => $meeting,
          'meetingItems' => $meetingItems,
          'meetingProjects' => $meetingProjects,
      ])

      @include('comments.partials.thread', ['commentable' => $meeting])
    </div>
  </div>
@endsection
