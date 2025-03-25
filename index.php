<?php
	include "inc/motorplantilla.php";
	if(isset($_GET['producto'])){
		$json = file_get_contents('json/'.str_replace(" | ","_",$_GET['producto']).'.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/landing/landing.html';
		echo renderTemplate($templateUrl, $datos);
	}else if(isset($_GET['pagina'])){
		$json = file_get_contents('json/'.$_GET['pagina'].'.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/pagina/pagina.html';
		echo renderTemplate($templateUrl, $datos);	
	}else{
		$json = file_get_contents('json/home.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/home/home.html';
		echo renderTemplate($templateUrl, $datos);
	}

?>
