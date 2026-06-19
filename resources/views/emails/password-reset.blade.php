<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #EF4444, #DC2626); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; }
        .button { display: inline-block; background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; }
        .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div class="container">
            <div class="header">
                <h1>Réinitialisation de mot de passe</h1>
            </div>
            <div class="content">
                <h2>Bonjour {{ $user->name }},</h2>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                <p style="text-align: center; margin: 30px 0;">
                    <!-- Lien vers le frontend -->
                    <a href="{{ $frontendUrl }}/reset-password?token={{ $token }}&email={{ $user->email }}" class="button">
                        Réinitialiser mon mot de passe
                    </a>
                </p>
                <p style="color: #888; font-size: 12px;">Ce lien expirera dans 60 minutes.</p>
                <p style="color: #888; font-size: 12px;">Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} PER ANKH. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</body>
</html>