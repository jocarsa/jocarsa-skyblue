<?php
// unsuscribe.php
//
// Este script desuscribe un email de la lista de envíos.
// Recibe el parámetro GET "email" y guarda la acción en la base de datos SQLite ubicada en ../databases/unsuscribe.db.
// Se registran: fecha/hora (automática), dirección IP y User Agent.
// Luego, muestra un mensaje de confirmación en español con un diseño moderno similar al de la plantilla de mailing.

header('Content-Type: text/html; charset=utf-8');

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($email == '') {
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <title>Desuscripción</title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        body {
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            padding: 2rem;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        p {
            font-size: 1.1rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>Error</h1>
    <p>No se proporcionó un correo electrónico válido para desuscribirse.</p>
</div>
</body>
</html>";
    exit;
}

$dbPath = __DIR__ . '/../databases/unsuscribe.db';

try {
    // Conectar a la base de datos (se crea si no existe)
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear la tabla de desuscripciones (si no existe)
    $createTableSQL = "CREATE TABLE IF NOT EXISTS unsubscribes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        ip TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTableSQL);

    // Obtener la IP y el User Agent del usuario
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    // Insertar el registro de desuscripción
    $stmt = $pdo->prepare("INSERT INTO unsubscribes (email, ip, user_agent) VALUES (:email, :ip, :user_agent)");
    $stmt->execute([
        ':email'      => $email,
        ':ip'         => $ip,
        ':user_agent' => $userAgent
    ]);

    // Mostrar mensaje de confirmación en español
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <title>Desuscripción</title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        body {
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            padding: 2rem;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        p {
            font-size: 1.1rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>¡Gracias!</h1>
    <p>Su correo electrónico <strong>" . htmlspecialchars($email) . "</strong> ha sido desuscrito de nuestra lista de envíos.</p>
</div>
</body>
</html>";

} catch (Exception $e) {
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <title>Error en la Desuscripción</title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        body {
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            padding: 2rem;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        p {
            font-size: 1.1rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>Error</h1>
    <p>No se pudo procesar su desuscripción. Por favor, inténtelo de nuevo más tarde.</p>
</div>
</body>
</html>";
    exit;
}
?>
