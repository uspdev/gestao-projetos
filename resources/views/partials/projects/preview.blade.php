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
        <div class="text-muted" style="font-size: 0.8rem; opacity: 0.5;" title="Métricas em breve">
            <i class="fas fa-chart-line"></i>
        </div>
    </x-slot>
</x-card.preview>
