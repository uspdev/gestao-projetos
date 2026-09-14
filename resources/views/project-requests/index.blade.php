@extends('projects.layouts.project')

@section('title', $title . ' | Solicitações')

@section('project-content')
  <div class="card shadow-sm">
    @include('project-requests.partials.header')

    <div class="card-body p-2">
      @if ($projectRequests->isEmpty())
        <div class="alert alert-light border text-muted mb-0">
          Nenhuma Solicitação foi recebida neste Projeto.
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>Status</th>
                <th>Título</th>
                <th>Sistema cliente</th>
                <th>Recebida em</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($projectRequests as $projectRequest)
                <tr>
                  <td>
                    <span class="badge badge-{{ $projectRequest->status->color() }}">
                      {{ $projectRequest->status->label() }}
                    </span>
                  </td>
                  <td>
                    <a href="{{ route('projects.requests.show', [$project, $projectRequest]) }}"
                      class="text-decoration-none">
                      {{ $projectRequest->title }}
                    </a>
                  </td>
                  <td>{{ $projectRequest->clientSystem->name }}</td>
                  <td><x-local-datetime :date="$projectRequest->created_at" /></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if ($projectRequests->hasPages())
          <div class="mt-3">
            {{ $projectRequests->links() }}
          </div>
        @endif
      @endif
    </div>
  </div>
@endsection
