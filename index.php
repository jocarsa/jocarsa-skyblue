<?php
	include "inc/motorplantilla.php";
	
	
	$json = file_get_contents('json/cabeza.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/cabeza/cabeza.html';
		echo renderTemplate($templateUrl, $datos);
	
	
	if(isset($_GET['producto'])){
		$json = file_get_contents('json/'.str_replace(" | ","_",$_GET['producto']).'.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/landing2/landing2.html';
		echo renderTemplate($templateUrl, $datos);
	}else if(isset($_GET['pagina'])){
		$json = file_get_contents('json/'.$_GET['pagina'].'.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/pagina2/pagina2.html';
		echo renderTemplate($templateUrl, $datos);	
	}else if(isset($_GET['categoria'])){
		$json = file_get_contents('json/'.$_GET['categoria'].'.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/rejilla2/rejilla2.html';
		echo renderTemplate($templateUrl, $datos);	
	}else{
		$json = file_get_contents('json/home.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/home2/home2.html';
		echo renderTemplate($templateUrl, $datos);
	}
	
	$json = file_get_contents('json/piedepagina.json');
		$datos = json_decode($json, true);
		$templateUrl = 'https://jocarsa.github.io/htmlcssjs/piedepagina/piedepagina.html';
		echo renderTemplate($templateUrl, $datos);

?>
