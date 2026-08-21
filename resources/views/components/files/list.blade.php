@props(['owner', 'files', 'links', 'sharedFiles' => null, 'sharedLinks' => null])

@include('components.files.files-css')

@php
  $uploadRoute = match ($owner::class) {
      \App\Models\Project::class => route('projects.files.store', $owner),
      \App\Models\Task::class => route('tasks.files.store', $owner),
      \App\Models\Meeting::class => route('meetings.files.store', $owner),
  };
  $linkRoute = match ($owner::class) {
      \App\Models\Project::class => route('projects.links.store', $owner),
      \App\Models\Task::class => route('tasks.links.store', $owner),
      \App\Models\Meeting::class => route('meetings.links.store', $owner),
  };
  $componentId = 'files-' . $owner->getMorphClass() . '-' . $owner->id;
  $ownedVisibleFiles = $files
      ->getCollection()
      ->filter(fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media));
  $sharedVisibleFiles = collect($sharedFiles ?? [])->filter(
      fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media),
  );
  $sharedMediaIds = $sharedVisibleFiles->pluck('id');
  $visibleFiles = $ownedVisibleFiles->concat($sharedVisibleFiles)->unique('id')->values();
  $ownedVisibleLinks = $links
      ->getCollection()
      ->filter(fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link));
  $sharedVisibleLinks = collect($sharedLinks ?? [])->filter(
      fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link),
  );
  $sharedLinkIds = $sharedVisibleLinks->pluck('id');
  $visibleLinks = $ownedVisibleLinks->concat($sharedVisibleLinks)->unique('id')->values();
  $rasterMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
  $imageFiles = $visibleFiles->filter(fn($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $documentFiles = $visibleFiles->reject(fn($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $initialTab = $imageFiles->isNotEmpty()
      ? 'images'
      : ($documentFiles->isNotEmpty()
          ? 'documents'
          : ($visibleLinks->isNotEmpty()
              ? 'links'
              : 'images'));
  $linkModalId = $componentId . '-add-links-modal';
  $imageModalId = $componentId . '-image-preview-modal';
  $shareableFileGroups = collect();
  $shareableLinkGroups = collect();
  $canManageFileShares =
      $owner instanceof \App\Models\Meeting && \Illuminate\Support\Facades\Gate::allows('manageFileShares', $owner);
  $canManageLinkShares =
      $owner instanceof \App\Models\Meeting &&
      \Illuminate\Support\Facades\Schema::hasTable('links') &&
      \Illuminate\Support\Facades\Gate::allows('manageLinkShares', $owner);
  if ($canManageFileShares || $canManageLinkShares) {
      $sharedMediaIds = $owner->sharedFiles()->pluck('media.id');
      $sharedIds = $sharedVisibleLinks->pluck('id');
      $linkedProjects = $owner->projects()->get();
      $agendaOwners = $owner->meetingItems()->with('discussable')->get()->pluck('discussable');
      // Monta as possíveis fontes de links e Arquivos compartilháveis:
      // 1. projetos vinculados diretamente à reunião;
      // 2. projetos presentes na pauta e ainda não listados acima;
      // 3. tarefas presentes na pauta.
      $sources = $linkedProjects
          ->map(fn($project) => ['label' => "Projeto vinculado: {$project->name}", 'owner' => $project])
          ->concat(
              $agendaOwners
                  ->filter(fn($item) => $item instanceof \App\Models\Project)
                  ->reject(fn($project) => $linkedProjects->contains('id', $project->id))
                  ->unique('id')
                  ->map(fn($project) => ['label' => "Projeto na pauta: {$project->name}", 'owner' => $project]),
          )
          ->concat(
              $agendaOwners
                  ->filter(fn($item) => $item instanceof \App\Models\Task)
                  ->unique('id')
                  ->map(fn($task) => ['label' => "Tarefa na pauta: {$task->title}", 'owner' => $task]),
          );

      if ($canManageFileShares) {
          $shareableFileGroups = $sources
              ->map(function (array $source) use ($sharedMediaIds) {
                  $available = $source['owner']
                      ->media()
                      ->with('uploader')
                      ->latest()
                      ->get()
                      ->reject(fn($media) => $sharedMediaIds->contains($media->id))
                      ->filter(fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media))
                      ->values();
                  return ['label' => $source['label'], 'files' => $available];
              })
              ->filter(fn(array $group) => $group['files']->isNotEmpty())
              ->values();
      }

      if ($canManageLinkShares) {
          // Para cada projeto ou tarefa encontrado, busca os links que ainda podem
          // ser compartilhados com a reunião.
          $shareableLinkGroups = $sources
              ->map(function (array $source) use ($sharedIds) {
                  $available = $source['owner']
                      ->links()
                      ->with('creator')
                      ->latest()
                      ->get()
                      ->reject(fn($link) => $sharedIds->contains($link->id))
                      ->filter(fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link))
                      ->values();
                  return ['label' => $source['label'], 'links' => $available];
              })
              ->filter(fn(array $group) => $group['links']->isNotEmpty())
              ->values();
      }
  }

  // Retorna a classe de ícone Font Awesome adequada à extensão do arquivo.
  // Caso a extensão não esteja mapeada, usa um ícone de arquivo genérico.
  $documentIcon = function ($media): string {
      return match (strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION))) {
          'pdf' => 'far fa-file-pdf',
          'doc', 'docx', 'odt', 'rtf' => 'far fa-file-word',
          'xls', 'xlsx', 'ods', 'csv' => 'far fa-file-excel',
          'ppt', 'pptx', 'odp' => 'far fa-file-powerpoint',
          'txt', 'md', 'log' => 'far fa-file-alt',
          'zip', 'rar', '7z', 'tar', 'gz' => 'far fa-file-archive',
          default => 'far fa-file',
      };
  };
@endphp

<section {{ $attributes->class(['card', 'my-4', 'file-browser']) }} aria-labelledby="{{ $componentId }}-heading"
  data-file-browser>
  @include('components.files.partials.header', [
      'owner' => $owner,
      'uploadRoute' => $uploadRoute,
      'linkModalId' => $linkModalId,
      'componentId' => $componentId,
      'shareableFileGroups' => $shareableFileGroups,
      'shareableLinkGroups' => $shareableLinkGroups,
  ])

  <div class="card-body p-0">
    @include('components.files.partials.tabs', [
        'componentId' => $componentId,
        'initialTab' => $initialTab,
        'imageFiles' => $imageFiles,
        'documentFiles' => $documentFiles,
        'visibleLinks' => $visibleLinks,
    ])

    <div class="file-browser-scroll" data-file-browser-scroll>
      @include('components.files.partials.image-panel', [
          'componentId' => $componentId,
          'imageModalId' => $imageModalId,
          'initialTab' => $initialTab,
          'imageFiles' => $imageFiles,
          'sharedMediaIds' => $sharedMediaIds,
          'owner' => $owner,
      ])
      @include('components.files.partials.document-panel', [
          'componentId' => $componentId,
          'initialTab' => $initialTab,
          'documentFiles' => $documentFiles,
          'sharedMediaIds' => $sharedMediaIds,
          'documentIcon' => $documentIcon,
          'owner' => $owner,
      ])
      @include('components.files.partials.link-panel', [
          'componentId' => $componentId,
          'initialTab' => $initialTab,
          'visibleLinks' => $visibleLinks,
          'sharedLinkIds' => $sharedLinkIds,
          'owner' => $owner,
      ])
    </div>
    @include('components.files.partials.pagination', ['files' => $files, 'links' => $links])
  </div>
</section>

@include('components.files.partials.add-links-modal', [
    'owner' => $owner,
    'linkRoute' => $linkRoute,
    'linkModalId' => $linkModalId,
    'componentId' => $componentId,
])
@include('components.files.partials.share-links-modal', [
    'owner' => $owner,
    'componentId' => $componentId,
    'shareableFileGroups' => $shareableFileGroups,
    'shareableLinkGroups' => $shareableLinkGroups,
])
