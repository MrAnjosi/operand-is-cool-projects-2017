<?php

$db = new PDO('mysql:host=mariadb;dbname=operand_iscool', 'root', '123456') or die("ERRO");

/*
$sql = "INSERT INTO agenda (ddd, numero, excluido) VALUES (47, 99353786, 0)";

$db->query($sql);
*/

$sql = "INSERT INTO agenda (ddd, numero, excluido) VALUES ";

$countItens = 100000;

for ($i=0; $i < $countItens; $i++) { 
	$sql .= "(:ddd$i, :numero$i, :excluido$i), ";
}

$sql = substr($sql, 0, -2) . ";";

try {
	//prepara o sql para poder ser executado
	$stmt = $db->prepare($sql);

	for ($i=0; $i < $countItens; $i++) { 
		$ddd = rand(10, 99);
		$numero = rand(1000, 9999).rand(1000, 9999);
		$excluido = rand(0, 1);

		//$stmt->bindParam
		//adiciona os valores as suas determinadas variaveis
		$stmt->bindValue(":ddd$i", $ddd);
		$stmt->bindValue(":numero$i", $numero);
		$stmt->bindValue(":excluido$i", $excluido);
	}

	try {
		//tenta executar a operacao sql que foi adicionado os valores 
		//anteriomente
		$stmt->execute();
	} catch (Exception $e) {
		echo "Try execute 
		<pre>";
		//tag <pre> serve para poder mostrar os valores identados
		//comando para mostrar os valores das variaveis
		$stmt->debugDumpParamns();
		//imprimi tada a informacao na tela
		print_r($e);
		exit();
	}
} catch (Exception $e) {
	echo "Try Prepare 
	<pre>";
	print_r($e);
	exit();
}

echo '<br /> Script finalizado ! <br />';

exit();