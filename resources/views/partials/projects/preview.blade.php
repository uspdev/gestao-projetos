@once
    <style>
        .project-preview-card {
            cursor: pointer;
            border-radius: 0.9rem;
            overflow: hidden;
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .project-preview-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 1.1rem 2.2rem rgba(0, 0, 0, 0.2);
        }

        .project-preview-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.3;
            color: #1f2937;
        }

        .project-preview-role {
            font-size: 0.85rem;
        }
    </style>
@endonce

<div class="card mb-3 shadow-sm position-relative project-preview-card border-left-primary">
    
    <div class="card-body p-3">
        
        {{-- CABEÇALHO: Título (Esquerda) e Status (Direita) --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="m-0 pr-2 project-preview-title" title="{{ $project->name }}">
                {{ $project->name }}
            </h5>
            <span class="badge {{ $project->status->color() }} text-nowrap shadow-sm mt-1">
                {{ $project->status->label() }}
            </span>
        </div>

        {{-- RODAPÉ: Role do Usuário (e espaço para futuras métricas) --}}
        <div class="d-flex justify-content-between align-items-end mt-2">
            
            {{-- Lado Esquerdo do Rodapé (Role) --}}
            <div class="project-preview-role">
                @auth
                    @php
                        $userRole = $project->userRole(Auth::user());
                    @endphp
                    <span class="text-muted mr-1"><i class="fas fa-user-circle"></i> Meu papel:</span>
                    <span class="badge {{ $userRole?->color() ?? 'badge-light border text-muted' }}">
                        {{ $userRole?->label() ?? 'Sem vínculo' }}
                    </span>
                @endauth
            </div>

            {{-- Lado Direito do Rodapé --}}
            <div class="text-muted" style="font-size: 0.8rem; opacity: 0.5;" title="Métricas em breve">
                <i class="fas fa-chart-line"></i>
            </div>

        </div>

        {{-- Link clicável por todo o card --}}
        <a href="{{ route('projects.show', $project->id) }}" class="stretched-link" aria-label="Acessar projeto {{ $project->name }}"></a>
    </div>
</div>