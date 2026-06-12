<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body>
  <p>
    Mensagem enviada para: {{ $recipient->name ?? 'usuario' }}<br>
    Seu papel neste projeto: <br>
    Este é um email automático - não responda.
  </p>

  @yield('content')

</body>

</html>
