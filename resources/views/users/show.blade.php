@extends('layouts.app')

@section('title', $title . ' | Dashboard')

@section('content')
  @if (Auth::id() === $user->id)
    <div class="row">
      <div class="col-md-9 border-right">
        @include('module-tasks.partials.user-dashboard')
      </div>
      <div class="col-md-3">
        @include('module-meetings.partials.user-dashboard')
        <hr class="my-4">
        @include('projects.partials.user-dashboard')
      </div>
    </div>
  @else
    @include('users.partials.user-info')
  @endif

@endsection
