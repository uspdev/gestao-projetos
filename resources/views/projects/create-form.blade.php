@extends('layouts.app')

@section('title', 'Novo Projeto')

@section('content')
  @php
    $visibilityValue = old('visibility', \App\Enums\Project\ProjectVisibility::PRIVATE->value);
    $permissionValue = old('permission_inheritance', \App\Enums\Project\ProjectPermissionInheritance::FULL->value);
    $projectTypeValue = old('project_type_id', $projectType->id);
    $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
    $activeModules = $projectType->modules->filter(fn($module) => (bool) ($module->pivot?->enabled ?? false))->values();
    $phasesEnabled = $projectType->isModuleEnabled('phases');
    $availablePhases = $projectType->phases ?? collect();
    $phaseValue = old('phase_id', $availablePhases->first()?->id);
  @endphp

  <div class="container-fluid">
    @include('projects.partials.create.header')

    <div class="row">
      <div class="col-lg-8">
        @include('projects.partials.create.form')
      </div>

      <div class="col-lg-4">
        @include('projects.partials.create.sidebar')
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @include('projects.partials.create.scripts')
@endpush
