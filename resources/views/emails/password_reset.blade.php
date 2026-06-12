<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Redefinição de senha</title>
</head>
<body style="margin:0;background:#f9f9f8;color:#1a1c1c;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f9f9f8;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e2e2;border-radius:16px;padding:32px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 16px;color:#415f76;font-family:Georgia,serif;font-size:28px;line-height:1.3;">Redefinição de senha</h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#42474c;">Olá, {{ $user->name }}.</p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#42474c;">Recebemos uma solicitação para redefinir a senha da sua conta. Use o botão abaixo para criar uma nova senha.</p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $resetUrl }}" style="display:inline-block;border-radius:8px;background:#415f76;color:#ffffff;font-size:15px;font-weight:700;padding:14px 20px;text-decoration:none;">Redefinir senha</a>
                            </p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#73787d;">Este link expira em {{ $expiresAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#73787d;">Se você não solicitou a redefinição, ignore este e-mail.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
