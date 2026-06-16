<a href="{{ route('projects.settings', $project) }}"
  class="btn btn-sm
     {{ Route::currentRouteName() === 'projects.settings' ? 'btn-warning' : 'btn-outline-warning' }}">
  <i class="fas fa-cog"></i>
</a>
