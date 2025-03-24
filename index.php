<?php
	include "inc/motorplantilla.php";
	$json = file_get_contents('json/home.json');
	$datos = json_decode($json, true);

	$templateUrl = 'https://jocarsa.github.io/htmlcssjs/home/home.html';
	echo renderTemplate($templateUrl, $datos);
?>
