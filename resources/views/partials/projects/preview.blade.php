@once
    <style>
        .project-preview-card {
            cursor: pointer;
            border-radius: 0.9rem;
            overflow: hidden;
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .project-preview-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            min-height: 3.35rem;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.16;
            color: #1f2937;
        }

        .project-preview-status {
            font-size: 0.86rem;
            font-weight: 700;
            padding: 0.42rem 0.6rem;
        }

        .project-preview-description {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            min-height: 2.7rem;
        }

        .project-preview-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 1.1rem 2.2rem rgba(0, 0, 0, 0.2);
        }
    </style>
@endonce

<div class="card mb-3 shadow-sm position-relative project-preview-card">
    <div class="card-body d-flex flex-column h-100 p-3">
        <div class="d-flex align-items-start mb-2">
            <h5 class="card-title project-preview-title mb-0 mr-2">{{ $project->name }}</h5>
            <span class="badge {{ $project->status->color() }} align-self-start mt-0 project-preview-status">
                {{ $project->status->label() }}
            </span>
        </div>
        @auth
            <div class="mb-1">
                <span class="badge {{ $project->userRole(Auth::user())?->color() ?? 'badge-light border text-muted' }}">
                    {{ $project->userRole(Auth::user())?->label() ?? 'Sem role' }}
                </span>
            </div>
        @endauth
        <p class="card-text text-muted mt-1 mb-0 project-preview-description">
            {{ Str::limit($project->description ?: 'Sem descrição.', 140) }}
        </p>
        <a href="{{ route('projects.show', $project->id) }}" class="stretched-link"
            aria-label="Acessar projeto {{ $project->name }}"></a>
    </div>
</div>
