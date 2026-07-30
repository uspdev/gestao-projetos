<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body>
  <p>
    Mensagem enviada para: {{ $recipient->name ?? 'usuário' }}<br>
    @isset($projectRole)
      Sua função neste projeto: {{ $projectRole }}<br>
    @endisset
    Este é um e-mail automático — não responda.
  </p>

  @yield('content')

</body>

</html>
