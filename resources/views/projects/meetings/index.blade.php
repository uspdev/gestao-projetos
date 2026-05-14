@extends('layouts.app')

@section('title', $project->name . ' - Reunioes')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="card mb-4 shadow-sm">
        <div class="card-header h5 d-flex align-items-center justify-content-between">
          <span><i class="far fa-calendar-alt mr-1"></i> Reunioes</span>
          @can('create', [\App\Models\Meeting::class, $project])
            <a href="{{ route('projects.meetings.create', $project) }}" class="btn btn-sm btn-outline-success">
              <i class="fas fa-plus"></i> Nova reuniao
            </a>
          @endcan
        </div>

        <div class="card-body">
          @if ($meetings->isEmpty())
            <div class="alert alert-light border text-muted mb-0">Nenhuma reuniao cadastrada.</div>
          @else
            <div class="list-group">
              @foreach ($meetings as $meeting)
                <a href="{{ route('projects.meetings.show', [$project, $meeting]) }}"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                  <div>
                    <div class="font-weight-bold">{{ $meeting->title }}</div>
                    <small class="text-muted">
                      <x-local-date :date="$meeting->scheduled_at" empty="-" />
                      @if ($meeting->location)
                        <span class="ml-1">- {{ $meeting->location }}</span>
                      @endif
                    </small>
                  </div>
                  <span class="badge {{ $meeting->status?->color() ?? 'badge-light text-dark' }}">
                    {{ $meeting->status?->label() ?? '-' }}
                  </span>
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
