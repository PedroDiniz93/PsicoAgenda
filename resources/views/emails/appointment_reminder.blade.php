<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Lembrete de sessão</title>
    </head>
    <body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
        <h2 style="color: #0f172a;">Olá, {{ $patient?->name ?? 'Paciente' }}!</h2>
        <p>
            Este é um lembrete da sua sessão com
            <strong>{{ $psychologist?->name ?? config('app.name') }}</strong>.
        </p>
        <p>
            <strong>Data:</strong> {{ $startAt->format('d/m/Y') }}<br>
            <strong>Horário:</strong> {{ $startAt->format('H:i') }}
        </p>
        <p>
            Se precisar reagendar ou cancelar, responda este e-mail ou entre em contato conosco.
        </p>
        <p>Até breve!</p>
    </body>
</html>
