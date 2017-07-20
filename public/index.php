<?php

//header('Content-Type: text/html; charset=utf-8');
header("Acess-Control-Allow-origin: *");
header("Acess-Control-Allow-methods: GET, POST, PUT, DELETE, OPTIONS");

$di = new Phalcon\DI\FactoryDefault();

//conexao com o banco de dados
$di->set('db', function(){
	return new Phalcon\Db\Adapter\Pdo\Mysql(array(
		"host" => "mariadb",
		"username" => "root",
		"password" => "123456",
		"dbname" => "operand_iscool"));
});

//inicializacao para poder usar os metodos e carregamento do banco
$app = new Phalcon\Mvc\Micro($di);

//rota de carregamento de dados
$app->get('/v1/bankaccounts', function() use($app){
	//select dos dados que serao carregados
	$sql = "SELECT id,name,balance FROM bank_account ORDER BY name";
	//execucao da query injetando o sql
	$result = $app->db->query($sql);
	//faz o carregamento dos dados no banco
	/*
		Modo de retorno que pode ser buscada no banco
		de dados
		sendo que os :: e para dizer que e um metodo statico
		FECTCH_OBJ - sera trazido em um objeto
	*/
	$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
	//inicializado uma variavel array vazia
	$data = array();
	//percorre o result para saber ser existe dados
	//fetch() cursor no banco de dados
	while($bankAccount = $result->fetch()){
		//montagem dos dados para serem enviados
		//referente as colunas que foram selecionadas
		//no select
		$data[] = array(
			'id' => $bankAccount->id,
			'name' => $bankAccount->name,
			'balance' =>$bankAccount->balance,
		);
	}
	//carregamento da api de resposta
	$response = new Phalcon\Http\Response();
	//verifica se contem dados ou nao
	//caso nao contenha sera retornado um erro
	if($data == false){
		$response->setStatuscode(404, "Not found");
		$response->SetJsonContent(array('status' => 'NOT-FOUND'));
	} else{
		$response->SetJsonContent(array(
			'status' => 'FOUND',
			'data' => $data
		));
	}

	return $response;
});

$app->post('/v1/bankaccounts', function() use ($app) {
	//criando uma variavel com o metodo de insercao
	//quer foram envaidos via url
    $bankAccount = $app->request->getPost();

    //somente para nao dar erro no cabeçalho
    if(!$bankaccounts){
    	$bankaccounts = (array) $app->request->getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    try {
    	//insere os dados enviados via url
        $result = $app->db->insert("bank_account",
            array($bankAccount['name'], $bankAccount['balance']),
            array("name", "balance")
        );

        $response->setStatusCode(201, "Created");
        //obtem o ultimo id inserdo no banco de dados
        $bankAccount['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccount));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

$app->options('/v1/bankaccounts', function() use ($app) {
	$app->response->setHeader('Acess-Control-Allow-Origin', '*');
});

//Updates bank account based on primary key
$app->put('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {

    $bankAccount = $app->request->getPut();
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->update("bank_account",
            array("name", "balance"),
            array($bankAccount['name'], $bankAccount['balance']),
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

//Deletes bank account based on primary key
$app->delete('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->delete("bank_account",
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});

//Retrieves bank account based on primary key
$app->get('/v1/bankaccounts/search/{id:[0-9]+}', function($id) use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account WHERE id = ?";
    $result = $app->db->query($sql, array($id));
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    $data = array();
    $bankAccount = $result->fetch();

    $response = new Phalcon\Http\Response();

    if ($bankAccount == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
    	//sql das operacoes
    	$sqlOperations = "SELECT id, operation, bank_account_id, date,
    					value 
    					FROM bank_account_operations
    					where bank_account_id =". $id . "
    					ORDER BY date";

//carrega as operacoes
    	$resultoperations = $app->db->query($sqlOperations);
    	$resultoperations->setFetchMode(Phalcon\Db::FETCH_OBJ);
    	$bankAccountOperations = $resultoperations->fetchAll();

//retorna os dados e as operacoes
        $response->setJsonContent(array(
                'id' => $bankAccount->id,
                'name' => $bankAccount->name,
                'balance' => $bankAccount->balance,
                'operations' => $bankAccountOperations,
        ));
    }

    return $response;

});
/*
//Searches for bank account with $name in their name
$app->get('/v1/bankaccounts/search/{name}', function($name) use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account WHERE name LIKE ? ORDER BY name";
    $result = $app->db->query($sql, array("%".$name."%"));
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    $data = array();
    while ($bankAccount = $result->fetch()) {
        $data[] = array(
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'balance' => $bankAccount->balance,
        );
    }

    $response = new Phalcon\Http\Response();

    if ($data == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $response->setJsonContent(array(
            'status' => 'FOUND',
            'data' => $data
        ));
    }

    return $response;

});
*/

//tratamento de erros que podem ocorrer ao tentar executar uma pagina
//nao existente
$app->notFound(function() use ($app){
	$app->response->setStatuscode(404, "Not Found")->sendHeaders();
	echo 'This is crazy, but this page was not found!';
});


$app->handle();
/*

//funcao anonima
//com json
$app->get('/diga/ola/{nome}', function ($nome){
	echo json_encode(array($nome, "uma", "informação", "importante"));
});


$app->handle();

*/

