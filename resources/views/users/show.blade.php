@extends('layouts.app')

@section('title', $title . ' | Dashboard')

@section('content')
  @if (Auth::id() === $user->id)
    <div id="{{ deep_link_fragment($user) }}" class="row" tabindex="-1" data-deep-link-target>
      <div class="col-md-9 border-right">
        @include('module-tasks.partials.user-dashboard')
        @include('watches.partials.user-dashboard')
      </div>
      <div class="col-md-3">
        @include('module-meetings.partials.user-dashboard')
        <hr class="my-4">
        <x-mentions.incoming-card :target="$user" empty-message="Você ainda não foi mencionado." />
        <hr class="my-4">
        @include('projects.partials.user-dashboard')
      </div>
    </div>
  @else
    <div id="{{ deep_link_fragment($user) }}" tabindex="-1" data-deep-link-target>
      @include('users.partials.user-info')
    </div>
  @endif

@endsection
