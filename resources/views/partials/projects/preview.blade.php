<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <h5 class="card-title">{{ $project->name }}</h5>
        <span class="badge {{ $project->status->color() }}">
            {{ $project->status->label() }}
        </span>
        <p class="card-text text-muted">{{ Str::limit($project->description, 80) }}</p>
        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-eye"></i> Acessar Projeto
        </a>
    </div>
</div>