<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body>
  <p>Ola {{ $recipient->name ?? 'usuario' }},</p>

  @yield('content')

  <p>Atenciosamente,<br>
    {{ config('app.name') }}</p>
</body>

</html>
