<?php
// SMTP server configuration
$smtpServer = "ssl://smtp.ionos.es"; // Use SSL
$smtpPort = 465; // Port for SSL
$smtpUser = "info@jocarsa.com";
$smtpPass = "Lielolilo123$";

// Email details
$to = "info@jocarsa.com";
$domain = $_SERVER['HTTP_HOST']; // Get the domain of the current URL
$subject = "Mensaje from $domain";

// Capture form data from GET or POST
$formData = [];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $formData[] = [$key, $value];
    }
}
// Check if the request method is GET
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach ($_GET as $key => $value) {
        $formData[] = [$key, $value];
    }
}

// Generate HTML table with CSS
$message = "<html><body>";
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

// Headers
$headers = "From: info@jocarsa.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Connect to the SMTP server over SSL
$connection = fsockopen($smtpServer, $smtpPort, $errno, $errstr, 30);

if (!$connection) {
    echo "Failed to connect to the SMTP server: $errstr ($errno)\n";
    exit;
}

// Read the server response
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

// Authenticate
fputs($connection, "AUTH LOGIN\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "334") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send username
fputs($connection, base64_encode($smtpUser) . "\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "334") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send password
fputs($connection, base64_encode($smtpPass) . "\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "235") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send MAIL FROM command
fputs($connection, "MAIL FROM: <$smtpUser>\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "250") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send RCPT TO command
fputs($connection, "RCPT TO: <$to>\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "250") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send DATA command
fputs($connection, "DATA\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "354") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send email headers and body
fputs($connection, "Subject: $subject\r\n");
fputs($connection, "$headers\r\n");
fputs($connection, "$message\r\n");
fputs($connection, ".\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "250") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Send QUIT command
fputs($connection, "QUIT\r\n");
$response = fgets($connection, 1024);
if (substr($response, 0, 3) != "221") {
    echo "Unexpected server response: $response\n";
    exit;
}

// Close the connection
fclose($connection);

// Display success message
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Mensaje Enviado</title>";
echo "<style>";
echo "body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: Arial, sans-serif; background-color: #f7f7f7; }";
echo ".message-box { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }";
echo ".message-box h1 { color: #4CAF50; }";
echo ".message-box p { color: #555; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='message-box'>";
echo "<h1>¡Mensaje Enviado!</h1>";
echo "<p>Gracias por enviar tu mensaje. Serás redirigido en breve.</p>";
echo "</div>";
echo "<script>";
echo "setTimeout(function() {";
echo "  window.location.href = 'http://$domain';";
echo "}, 5000);";
echo "</script>";
echo "</body>";
echo "</html>";
?>

