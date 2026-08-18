@extends('projects.layouts.project')


@section('title', $title . ' | Detalhes do Projeto')

@section('project-content')
  <div data-file-reference-context-type="project" data-file-reference-context-id="{{ $project->id }}">
    @include('projects.partials.show.join-inherited-banner')

    <div class="row">
      <div class="col-md-8">
        <x-projects::show.descricao-card :project="$project" />
        @include('comments.partials.thread', ['commentable' => $project, 'cardClass' => 'content-surface'])
      </div>
      <div class="col-md-4">
        <x-projects::show.tipo-card :project="$project" />
        <x-files.list class="options-surface" :owner="$project" :files="$files" :links="$links" />
        <x-projects::show.membros-preview-card :project="$project" />
        @include('projects.partials.show.show-card-modulos')
        <x-mentions.incoming-card class="options-surface" :target="$project" empty-message="Este projeto ainda não foi mencionado." />
        @if ($agendaMeetings->isNotEmpty())
          <x-projects::show.pautas-card :project="$project" :meetings="$agendaMeetings" />
        @endif
        <x-projects::show.subprojects-card :project="$project" type="preview" />
      </div>
    </div>
  </div>
@endsection
