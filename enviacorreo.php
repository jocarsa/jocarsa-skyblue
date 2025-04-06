<?php
// Incluir el archivo de configuración para los ajustes SMTP
require_once 'config.php';

// Configurar locale para la fecha en español
setlocale(LC_TIME, 'es_ES.UTF-8');

// Determinar dominio y asunto
$domain  = $_SERVER['HTTP_HOST'];
$subject = "Mensaje de $domain";

// Capturar datos del formulario (GET o POST)
$formData    = [];
$dataForJson = []; // Para guardar como JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $formData[]        = [$key, $value];
        $dataForJson[$key] = $value;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach ($_GET as $key => $value) {
        $formData[]        = [$key, $value];
        $dataForJson[$key] = $value;
    }
}

// Agregar la URL de referencia a los datos
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Acceso directo o referencia no establecida';
$formData[]                     = ["URL de referencia", $referrer];
$dataForJson['URL de referencia'] = $referrer;

// ---- Datos adicionales del visitante ----
// IP del visitante
$visitorIP = $_SERVER['REMOTE_ADDR'];
$formData[]                     = ["IP del visitante", $visitorIP];
$dataForJson['IP del visitante'] = $visitorIP;

// Agente de Usuario del visitante
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'No proporcionado';
$formData[]                     = ["Agente de Usuario", $userAgent];
$dataForJson['Agente de Usuario'] = $userAgent;

// Fecha de envío en formato legible en español
$submissionDate = strftime('%A, %d de %B de %Y %H:%M:%S');
$formData[]                     = ["Fecha de envío", $submissionDate];
$dataForJson['Fecha de envío']  = $submissionDate;

// Generar tabla HTML para el cuerpo del correo
$message  = "<html><body>";
$message .= "<h1>Envío de datos del formulario</h1>";
$message .= "<table style='border-collapse: collapse; width: 100%;'>";
$message .= "<tr style='background-color: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>Etiqueta</th><th style='border: 1px solid #ddd; padding: 8px;'>Valor</th></tr>";

foreach ($formData as $data) {
    $message .= "<tr>";
    $message .= "<td style='border: 1px solid #ddd; padding: 8px;'>{$data[0]}</td>";
    $message .= "<td style='border: 1px solid #ddd; padding: 8px;'>{$data[1]}</td>";
    $message .= "</tr>";
}

$message .= "</table>";
$message .= "<style>
    body { font-family: Arial, sans-serif; }
    h1 { color: #333; }
    table { margin: 20px 0; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    td, th { text-align: left; }
</style>";
$message .= "</body></html>";

// Configurar las cabeceras del correo
$headers  = "From: $smtpUser\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Determinar si se debe enviar el correo basándose en el referer HTTP
$shouldSendEmail = false;
if (isset($_SERVER['HTTP_REFERER']) && stripos($_SERVER['HTTP_REFERER'], 'jocarsa.com') !== false) {
    $shouldSendEmail = true;
}

// ----- FILTRO DE SPAM -----
// Por defecto, no es spam
$isSpam = false;
$spamFilterFile = 'spamfilter.txt';
if (file_exists($spamFilterFile)) {
    // Leer palabras clave de spam del archivo (una por línea)
    $spamWords = file($spamFilterFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($spamWords as $spamWord) {
        // Comprobar si algún dato del formulario contiene la palabra clave de spam (sin distinguir mayúsculas/minúsculas)
        foreach ($dataForJson as $value) {
            if (stripos($value, $spamWord) !== false) {
                $isSpam = true;
                break 2; // Salir si se encuentra alguna palabra de spam
            }
        }
    }
}

// ----- NUEVO FILTRO: VALIDACIÓN DE EMAIL -----
// Verificar si algún campo es un email y, en ese caso, comprobar su dominio y TLD
foreach ($dataForJson as $value) {
    // Verifica si el valor es exactamente un email
    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
        // Obtener el dominio del email
        $emailDomain = substr(strrchr($value, "@"), 1);
        // Asegurarse de que el dominio contenga un punto para extraer la TLD
        if (strpos($emailDomain, ".") === false) {
            $isSpam = true;
            break;
        }
        // Extraer la TLD (parte después del último punto) y convertir a mayúsculas
        $tld = strtoupper(substr(strrchr($emailDomain, "."), 1));
        
        // Obtener la lista de TLD válidas desde IANA
        $tldListRaw = file_get_contents("https://data.iana.org/TLD/tlds-alpha-by-domain.txt");
        if ($tldListRaw !== false) {
            $tldList = explode("\n", $tldListRaw);
            $tldList = array_map('trim', $tldList);
            // Comprobar si la TLD extraída se encuentra en la lista
            if (!in_array($tld, $tldList)) {
                $isSpam = true;
                break;
            }
        } else {
            // Si no se pudo obtener la lista, considerar el email inválido
            $isSpam = true;
            break;
        }
    }
}

// ----- ESTRUCTURA DE CARPETAS Y ALMACENAMIENTO JSON -----
$mailFolder     = 'mail';
$incomingFolder = $mailFolder . '/incoming';
$spamFolder     = $mailFolder . '/spam';

// Crear directorios si no existen
if (!is_dir($incomingFolder)) {
    mkdir($incomingFolder, 0777, true);
}
if (!is_dir($spamFolder)) {
    mkdir($spamFolder, 0777, true);
}

// Guardar los datos del formulario como JSON
$jsonData     = json_encode($dataForJson, JSON_PRETTY_PRINT);
$filename     = uniqid('mail_', true) . '.json';
$targetFolder = $isSpam ? $spamFolder : $incomingFolder;
file_put_contents($targetFolder . '/' . $filename, $jsonData);

// ----- ENVÍO DEL CORREO ELECTRÓNICO VIA SMTP -----
// Solo enviar correo si no se marca como spam y el referer HTTP es válido
if (!$isSpam && $shouldSendEmail) {
    // Conectar al servidor SMTP vía SSL
    $connection = fsockopen($smtpServer, $smtpPort, $errno, $errstr, 30);
    if (!$connection) {
        echo "Fallo al conectar con el servidor SMTP: $errstr ($errno)\n";
        exit;
    }

    // Leer la respuesta inicial del servidor
    $response = '';
    while ($line = fgets($connection, 1024)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    if (substr($response, 0, 3) != "220") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Enviar comando EHLO
    fputs($connection, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($connection, 1024)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    if (substr($response, 0, 3) != "250") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Autenticación con AUTH LOGIN
    fputs($connection, "AUTH LOGIN\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "334") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Enviar usuario (codificado en base64)
    fputs($connection, base64_encode($smtpUser) . "\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "334") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Enviar contraseña (codificada en base64)
    fputs($connection, base64_encode($smtpPass) . "\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "235") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Comando MAIL FROM
    fputs($connection, "MAIL FROM: <$smtpUser>\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Comando RCPT TO (enviando a uno mismo en este ejemplo)
    fputs($connection, "RCPT TO: <$smtpUser>\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Comando DATA
    fputs($connection, "DATA\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "354") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Enviar cabeceras y cuerpo del correo
    fputs($connection, "Subject: $subject\r\n");
    fputs($connection, "$headers\r\n");
    fputs($connection, "$message\r\n");
    fputs($connection, ".\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }

    // Comando QUIT
    fputs($connection, "QUIT\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "221") {
        echo "Respuesta inesperada del servidor: $response\n";
        exit;
    }
    fclose($connection);
}

// ----- MENSAJE DE ÉXITO CON REDIRECCIÓN -----
// Determinar el protocolo para la redirección (HTTP o HTTPS)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
             || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Mensaje Enviado</title>
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            font-family: Arial, sans-serif; 
            background-color: #f7f7f7; 
        }
        .message-box { 
            background-color: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
            text-align: center; 
        }
        .message-box h1 { color: #4CAF50; }
        .message-box p { color: #555; }
    </style>
</head>
<body>
    <div class='message-box'>
        <h1>¡Mensaje Enviado!</h1>
        <p>Gracias por enviar tu mensaje. Serás redirigido en breve.</p>
    </div>
    <script>
        setTimeout(function() {
            // Redirigir a la raíz del dominio (por ejemplo, https://xxxxx.com)
            window.location.href = '<?php echo $protocol . $_SERVER['HTTP_HOST']; ?>';
        }, 5000);
    </script>
</body>
</html>

