<?php
include "inc/motorplantilla.php";

// Create an instance of the TemplateEngine
$engine = new TemplateEngine();

// Render the header part
$json = file_get_contents('json/partes/cabeza.json');
$datos = json_decode($json, true);
$templateUrl = 'https://jocarsa.github.io/htmlcssjs/cabeza/cabeza.html';
echo $engine->render($templateUrl, $datos);

// Determine which template to render based on the query parameters
if (isset($_GET['producto'])) {
    $json = file_get_contents('json/productos/' . str_replace(" | ", "_", $_GET['producto']) . '.json');
    $datos = json_decode($json, true);
    $templateUrl = 'https://jocarsa.github.io/htmlcssjs/landing2/landing2.html';

    // Define the attributes for the form tag
    $attributes = [
        'form' => [
            'method' => 'POST',
            'action' => 'https://email.jocarsa.com/enviacorreo.php'
        ]
    ];

    // Render the product template with the specified attributes
    echo $engine->render($templateUrl, $datos, $attributes);
} elseif (isset($_GET['pagina'])) {
    $json = file_get_contents('json/paginas/' . $_GET['pagina'] . '.json');
    $datos = json_decode($json, true);
    $templateUrl = 'https://jocarsa.github.io/htmlcssjs/pagina2/pagina2.html';
    echo $engine->render($templateUrl, $datos);
}elseif (isset($_GET['contacto'])) {
    $templateUrl = 'https://jocarsa.github.io/htmlcssjs/contacto2/contacto.html';
    $attributes = [
        'form' => [
            'method' => 'POST',
            'action' => 'https://email.jocarsa.com/enviacorreo.php'
        ]
    ];

    // Render the product template with the specified attributes
    echo $engine->render($templateUrl, [], $attributes);
} elseif (isset($_GET['categoria'])) {
    $json = file_get_contents('json/categorias/' . $_GET['categoria'] . '.json');
    $datos = json_decode($json, true);
    $templateUrl = 'https://jocarsa.github.io/htmlcssjs/rejilla2/rejilla2.html';
    echo $engine->render($templateUrl, $datos);
} else {
    $json = file_get_contents('json/home.json');
    $datos = json_decode($json, true);
    $templateUrl = 'https://jocarsa.github.io/htmlcssjs/home2/home2.html';
    echo $engine->render($templateUrl, $datos);
}

// Render the footer part
$json = file_get_contents('json/partes/piedepagina.json');
$datos = json_decode($json, true);
$templateUrl = 'https://jocarsa.github.io/htmlcssjs/piedepagina/piedepagina.html';
echo $engine->render($templateUrl, $datos);
?>

<script src="https://ghostwhite.jocarsa.com/analytics.js?user=jocarsa.com"></script>

