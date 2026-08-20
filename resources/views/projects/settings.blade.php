@extends('layouts.app')

@section('title', $title . ' | Configurações do Projeto')

@php
  $settingsUrl = route('projects.settings', $project);
@endphp

@pushOnce('styles')
  <style>
    .project-settings-nav {
      top: calc(70px + 1rem);
    }

    .project-settings-nav .nav-link {
      border-left: 3px solid transparent;
      color: #495057;
      font-weight: 500;
    }

    .project-settings-nav .nav-link:hover,
    .project-settings-nav .nav-link:focus {
      background-color: #f8f9fa;
      color: #234983;
    }

    .project-settings-nav .nav-link--danger:hover,
    .project-settings-nav .nav-link--danger:focus {
      color: #dc3545;
    }

    @media (max-width: 991.98px) {
      .project-settings-nav {
        position: static;
      }

      .project-settings-nav .nav {
        flex-direction: row;
        flex-wrap: nowrap;
        overflow-x: auto;
      }

      .project-settings-nav .nav-link {
        white-space: nowrap;
      }
    }
  </style>
@endPushOnce

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <aside class="col-12 col-lg-2 mb-4 mb-lg-0">
          <nav class="project-settings-nav sticky-top" aria-label="Seções das configurações do projeto">
            <h2 class="h6 text-uppercase text-muted small font-weight-bold px-3 mb-2">
              Configurações
            </h2>
            <div class="nav nav-pills flex-column">
              <a class="nav-link" href="{{ $settingsUrl }}#project-general-settings">
                <i class="fas fa-sliders-h fa-fw mr-2" aria-hidden="true"></i> Gerais
              </a>
              <a class="nav-link" href="{{ $settingsUrl }}#project-access-settings">
                <i class="fas fa-user-shield fa-fw mr-2" aria-hidden="true"></i> Acesso e permissões
              </a>
              @if ($project->isOrganizational() || $project->isSubproject())
                <a class="nav-link" href="{{ $settingsUrl }}#project-inheritance-settings">
                  <i class="fas fa-sitemap fa-fw mr-2" aria-hidden="true"></i> Herança de permissões
                </a>
              @endif
              <a class="nav-link" href="{{ $settingsUrl }}#project-classification-settings">
                <i class="fas fa-tags fa-fw mr-2" aria-hidden="true"></i> Classificação
              </a>
              <a class="nav-link" href="{{ $settingsUrl }}#project-notification-settings">
                <i class="fas fa-bell fa-fw mr-2" aria-hidden="true"></i> Notificações
              </a>
              <a class="nav-link" href="{{ $settingsUrl }}#project-members">
                <i class="fas fa-users fa-fw mr-2" aria-hidden="true"></i> Membros
              </a>
              <a class="nav-link" href="{{ $settingsUrl }}#project-modules-settings">
                <i class="fas fa-puzzle-piece fa-fw mr-2" aria-hidden="true"></i> Módulos
              </a>
              @can('viewActivity', $project)
                <a class="nav-link" href="{{ $settingsUrl }}#project-activity-settings">
                  <i class="fas fa-history fa-fw mr-2" aria-hidden="true"></i> Histórico
                </a>
              @endcan
              <a class="nav-link nav-link--danger" href="{{ $settingsUrl }}#project-danger-settings">
                <i class="fas fa-exclamation-triangle fa-fw mr-2" aria-hidden="true"></i> Área de risco
              </a>
            </div>
          </nav>
        </aside>

        <main class="col-12 col-lg-8">
          @include('projects.partials.show.settings-card')

          @if ($project->isOrganizational() || $project->isSubproject())
            <section id="project-inheritance-settings" class="mb-4">
              @include('projects.partials.show.subproject-permissions-card')
            </section>
          @endif

          <section id="project-notification-settings">
            @include('projects.partials.show.watch-settings-card')
          </section>

          <section id="project-members">
            @include('projects.partials.show.show-card-membros')
          </section>

          <section id="project-modules-settings">
            @include('projects.partials.show.show-card-modulos', ['showToggle' => true])
          </section>

          @can('viewActivity', $project)
            <section id="project-activity-settings">
              @include('projects.partials.show.activity-card')
            </section>
          @endcan

          <section id="project-danger-settings">
            @include('projects.partials.show.settings-danger-area')
          </section>
        </div>
      </div>
    </div>
  </div>
@endsection
