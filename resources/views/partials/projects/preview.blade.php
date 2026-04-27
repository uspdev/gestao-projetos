@php
    $user = auth()->user();
    $userRole = $user ? $project->userRole($user) : null;
@endphp

<x-card.preview class="mb-3 shadow-sm border-left-primary" href="{{ route('projects.show', $project->id) }}"
    aria-label="Acessar projeto {{ $project->name }}">
    <x-slot name="header">
        <h5 class="m-0 pr-2 preview-card__title preview-card__title--project" title="{{ $project->name }}">
            {{ $project->name }}
        </h5>

        <span class="badge {{ $project->status->color() }} text-nowrap shadow-sm mt-1">
            {{ $project->status->label() }}
        </span>
    </x-slot>

    <x-slot name="body">
        <div class="preview-card__meta">
            @auth
                <span class="text-muted mr-1"><i class="fas fa-user-circle"></i> Meu papel:</span>
                <span class="badge {{ $userRole?->color() ?? 'badge-light border text-muted' }}">
                    {{ $userRole?->label() ?? 'Sem vínculo' }}
                </span>
            @endauth
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.25rem;">
            @foreach ($project->tagsWithType('projects') as $tag)
                <span class="badge {{ $tag->color }}" title="Tag">
                    <i class="fas fa-tag mr-1"></i>{{ $tag->name }}
                </span>
            @endforeach
        </div>
    </x-slot>
</x-card.preview>
