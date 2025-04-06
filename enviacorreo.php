<?php
// Include the configuration file for SMTP settings
require_once 'config.php';

// Determine domain and subject
$domain  = $_SERVER['HTTP_HOST'];
$subject = "Mensaje from $domain";

// Capture form data from GET or POST
$formData    = [];
$dataForJson = []; // For saving as JSON

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

// Add referrer URL to data
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct access or referrer not set';
$formData[]                  = ["Referrer URL", $referrer];
$dataForJson['Referrer URL'] = $referrer;

// ---- Additional Visitor Data ----
// Visitor IP address
$visitorIP = $_SERVER['REMOTE_ADDR'];
$formData[]                  = ["Visitor IP", $visitorIP];
$dataForJson['Visitor IP']   = $visitorIP;

// Visitor User Agent
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided';
$formData[]                  = ["Visitor User Agent", $userAgent];
$dataForJson['Visitor User Agent'] = $userAgent;

// Full date in human readable form
$submissionDate = date('l, F j, Y g:i:s A');
$formData[]                  = ["Submission Date", $submissionDate];
$dataForJson['Submission Date'] = $submissionDate;

// Generate HTML table for email body
$message  = "<html><body>";
$message .= "<h1>Form Data Submission</h1>";
$message .= "<table style='border-collapse: collapse; width: 100%;'>";
$message .= "<tr style='background-color: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>Label</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";

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

// Set email headers
$headers  = "From: $smtpUser\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Determine if the email should be sent based on HTTP referrer
$shouldSendEmail = false;
if (isset($_SERVER['HTTP_REFERER']) && stripos($_SERVER['HTTP_REFERER'], 'jocarsa.com') !== false) {
    $shouldSendEmail = true;
}

// ----- SPAM FILTERING -----
// Default to not spam
$isSpam = false;
$spamFilterFile = 'spamfilter.txt';
if (file_exists($spamFilterFile)) {
    // Read spam keywords from the file (one per line)
    $spamWords = file($spamFilterFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($spamWords as $spamWord) {
        // Check if any form input contains a spam keyword (case-insensitive)
        foreach ($dataForJson as $value) {
            if (stripos($value, $spamWord) !== false) {
                $isSpam = true;
                break 2; // Exit if any spam keyword is found
            }
        }
    }
}

// ----- FOLDER STRUCTURE & JSON STORAGE -----
$mailFolder     = 'mail';
$incomingFolder = $mailFolder . '/incoming';
$spamFolder     = $mailFolder . '/spam';

// Create directories if they do not exist
if (!is_dir($incomingFolder)) {
    mkdir($incomingFolder, 0777, true);
}
if (!is_dir($spamFolder)) {
    mkdir($spamFolder, 0777, true);
}

// Save the form data as JSON
$jsonData     = json_encode($dataForJson, JSON_PRETTY_PRINT);
$filename     = uniqid('mail_', true) . '.json';
$targetFolder = $isSpam ? $spamFolder : $incomingFolder;
file_put_contents($targetFolder . '/' . $filename, $jsonData);

// ----- SMTP EMAIL SENDING -----
// Only send email if not flagged as spam and if the HTTP referrer is valid
if (!$isSpam && $shouldSendEmail) {
    // Connect to the SMTP server over SSL
    $connection = fsockopen($smtpServer, $smtpPort, $errno, $errstr, 30);
    if (!$connection) {
        echo "Failed to connect to the SMTP server: $errstr ($errno)\n";
        exit;
    }

    // Read initial server response
    $response = '';
    while ($line = fgets($connection, 1024)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    if (substr($response, 0, 3) != "220") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // Send EHLO command
    fputs($connection, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($connection, 1024)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    if (substr($response, 0, 3) != "250") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // Authenticate using AUTH LOGIN
    fputs($connection, "AUTH LOGIN\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "334") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // Send username (base64-encoded)
    fputs($connection, base64_encode($smtpUser) . "\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "334") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // Send password (base64-encoded)
    fputs($connection, base64_encode($smtpPass) . "\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "235") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // MAIL FROM command
    fputs($connection, "MAIL FROM: <$smtpUser>\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // RCPT TO command (sending to self in this example)
    fputs($connection, "RCPT TO: <$smtpUser>\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // DATA command
    fputs($connection, "DATA\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "354") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // Send email headers and message body
    fputs($connection, "Subject: $subject\r\n");
    fputs($connection, "$headers\r\n");
    fputs($connection, "$message\r\n");
    fputs($connection, ".\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "250") {
        echo "Unexpected server response: $response\n";
        exit;
    }

    // QUIT command
    fputs($connection, "QUIT\r\n");
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) != "221") {
        echo "Unexpected server response: $response\n";
        exit;
    }
    fclose($connection);
}

// ----- SUCCESS MESSAGE with Redirection -----
// Determine protocol for redirection (HTTP or HTTPS)
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
            // Redirect to the main domain root (e.g., https://xxxxx.com)
            window.location.href = '<?php echo $protocol . $_SERVER['HTTP_HOST']; ?>';
        }, 5000);
    </script>
</body>
</html>

