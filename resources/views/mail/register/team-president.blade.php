<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Registro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #2C3E50; /* Color primario de Futzo */
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            line-height: 1.6;
        }
        .content p {
            margin: 0 0 20px;
        }
        .content .credentials {
            background-color: #ECF0F1; /* Fondo suave para destacar */
            border: 1px solid #BDC3C7; /* Borde sutil */
            padding: 15px;
            border-radius: 4px;
        }
        .content .credentials p {
            margin: 0;
            font-weight: bold;
            color: #2C3E50; /* Color de texto primario de Futzo */
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #E74C3C; /* Color de acción llamativo */
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .button:hover {
            background-color: #C0392B; /* Efecto hover */
        }
        .footer {
            background-color: #ECF0F1;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #7F8C8D;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>¡Bienvenido a Futzo!</h1>
    </div>
    <div class="content">
        <p>Hola {{ $userName }},</p>
        <p>Has sido registrado como <strong>Director Técnico</strong> en Futzo.</p>
        <p>A continuación, encontrarás tus credenciales temporales:</p>
        <div class="credentials">
            <p>Usuario: <span>{{ $userEmail }}</span></p>
            <p>Contraseña: <span>{{ $temporaryPassword }}</span></p>
        </div>
        <p>Te recomendamos que inicies sesión y cambies tu contraseña lo antes posible para garantizar la seguridad de tu cuenta.</p>
        <a href="{{ $loginUrl }}" class="button">Iniciar Sesión</a>
    </div>
    <div class="footer">
        <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
        <p>© 2024 Futzo. Todos los derechos reservados.</p>
    </div>
</div>
</body>
</html>
