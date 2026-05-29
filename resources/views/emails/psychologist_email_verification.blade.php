<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Código de validação</title>
    </head>
    <body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
        <h2 style="color: #0f172a;">Olá, {{ $user->name }}!</h2>
        <p>Use o código abaixo para validar seu e-mail no {{ config('app.name') }}.</p>
        <p style="font-size: 28px; font-weight: 700; letter-spacing: 4px;">
            {{ $code }}
        </p>
        <p>Este código expira em 24 horas.</p>
        <p>
            Validade:
            <strong>{{ $expiresAt->copy()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</strong>
        </p>
        <p>Se você não solicitou este acesso, ignore este e-mail.</p>
    </body>
</html>
