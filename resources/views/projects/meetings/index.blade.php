@extends('layouts.app')

@section('title', $project->name . ' - Reunioes')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-lg-4 mb-4 mb-lg-0">
          <div class="card shadow-sm">
            <div class="card-header h5 d-flex align-items-center justify-content-between">
              <span><i class="far fa-calendar-alt mr-1"></i> Reunioes</span>
              <span class="badge badge-secondary">{{ $meetings->count() }}</span>
            </div>

            <div class="card-body d-flex flex-column">
              <p class="text-muted mb-4">
                Acompanhe as reunioes do projeto em uma visualizacao mais direta e responsiva.
              </p>

              @can('create', [\App\Models\Meeting::class, $project])
                <a href="{{ route('projects.meetings.create', $project) }}" class="btn btn-success btn-block mt-auto">
                  <i class="fas fa-plus"></i> Nova reuniao
                </a>
              @endcan
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          @if ($meetings->isEmpty())
            <div class="alert alert-light border text-muted mb-0">Nenhuma reuniao cadastrada.</div>
          @else
            <div class="row">
              @foreach ($meetings as $meeting)
                <div class="col-md-6 mb-4">
                  @include('projects.meetings.partials.index-item', [
                      'project' => $project,
                      'meeting' => $meeting,
                  ])
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
