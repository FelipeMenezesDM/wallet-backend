<?php
/**
 * Controlador para requisições da API.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

class Request {
	/**
	 * Lista de versões disponíveis da API.
	 */
	const VERSIONS = array( "v1.0" );

	/**
	 * Lista de tipos de requisições permitidas para a API.
	 */
	const RESQUEST_TYPES = array( "get", "post", "put", "delete", "service" );

	/**
	 * Parâmetros globais da requisição.
	 * @access private
	 * @var    array
	 */
	private $requestParams = null;

	/**
	 * Respostas de requisição da API.
	 * @access private
	 * @var    array
	 */
	private $response = array(
		"status"		=> "error",
		"message"		=> "",
		"results"		=> array()
	);


	/**
	 * Método construtor.
	 * @param array $requestParams Parâmetros de requsição da API.
	 */
	public function __construct( $requestParams = array() ) {
		$response = & $this->response;
		$params = & $this->requestParams;
		$params = array_intersect_key( \Src\Controllers\Utils::arrayKeyHandler( $requestParams ), array_flip( array( "version", "type", "object" ) ) );
		$params = array_merge( array( "version" => "", "type" => "", "object" => "" ), $params );
		$params = array_map( "strtolower", array_map( "trim", $params ) );
		$fileName = "error";
		$isPretty = ( ( isset( $_REQUEST[ "pretty" ] ) && (bool) $_REQUEST[ "pretty" ] ) ? JSON_PRETTY_PRINT : 0 );

		# Adicionar atributos adicionais para requisições GET.
		if( $params[ "type" ] === "get" ) {
			$response[ "fields" ] = array();
			$response[ "total" ] = 0;
			$response[ "items" ] = 0;
		}

		# Verificar se a versão está preenchida.
		if( empty( $params[ "version" ] ) ) 
			$response[ "message" ] = gettext( "Versão da API não encontrada." );
		# Verificar se a versão é válida.
		elseif( !in_array( $params[ "version" ], self::VERSIONS ) )
			$response[ "message" ] = gettext( "A versão da API informada é inválida." );
		# Verificar se o tipo de requisição está preenchido.
		elseif( empty( $params[ "type" ] ) )
			$response[ "message" ] = gettext( "Tipo de requisição não encontrado." );
		# Verificar se o tipo de requisição informado é válido.
		elseif( !in_array( $params[ "type" ], self::RESQUEST_TYPES ) )
			$response[ "message" ] = gettext( "O tipo da requisição informado é inválido." );
		# Verificar se o tipo de requisição está preenchido.
		elseif( empty( $params[ "object" ] ) )
			$response[ "message" ] = gettext( "O objeto da requisição não foi informado." );
		else {
			$fileName = $params[ "object" ];

			# Processar e validar requisição.
			$this->processRequest();
		}

		# Tentativa de conversão em JSON para testar resposta.
		try{
			json_encode( $response, $isPretty );
		}catch( Exception $e ){}

		# Verificar erro de conversão da resposta em JSON.
		switch( json_last_error() ) {
			case JSON_ERROR_NONE :
				$jsonError = false;
			break;
			case JSON_ERROR_DEPTH:
				$jsonError = gettext( "Maximum stack depth exceeded" );
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$jsonError = gettext( "Underflow or the modes mismatch" );
			break;
			case JSON_ERROR_CTRL_CHAR:
				$jsonError = gettext( "Unexpected control character found" );
			break;
			case JSON_ERROR_SYNTAX:
				$jsonError = gettext( "Syntax error, malformed JSON" );
			break;
			case JSON_ERROR_UTF8:
				$jsonError = gettext( "Malformed UTF-8 characters, possibly incorrectly encoded" );
			break;
			default:
				$jsonError = gettext( "Unknown error" );
			break;
		}

		if( $jsonError ) {
			$response[ "message" ] = $jsonError;
			$response[ "status" ] = "error";
		}

		# Limpar buffer com conteúdo desnecessário.
		while( ob_get_level() )
			ob_end_clean();

		header( "Content-Type: application/json" );
		header( "Content-Disposition: inline; filename=\"" . $fileName . ".json\"" );
		echo json_encode( $response, $isPretty );
	}

	/**
	 * Processar e validar requisição da API.
	 * @access private
	 */
	private function processRequest() {
		$request = $_REQUEST;
		$request[ "table" ] = $this->requestParams[ "object" ];
		$object = null;

		define( "REQUEST_FROM_API", 1 );
		\Src\Db\Connect::setDebugMode( ( isset( $_REQUEST[ "debug" ] ) && (bool) $_REQUEST[ "debug" ] ) );

		# Obter objeto de acordo com o tipo de resquisição.
		switch( $this->requestParams[ "type" ] ) {
			case "get" : # Requisição de consulta.
				# Limite de registros padrão, caso nao seja informado.
				if( !isset( $request[ "per_page" ] ) )
					$request[ "per_page" ] = 100;

				$object = new \Src\Db\Query\Select( $request );

				# Obter totalizadores e resultados.
				$this->response[ "fields" ] = array_keys( $object->getColumnsMeta() );
				$this->response[ "total" ] = $object->getTotalRowsCount();
				$this->response[ "items" ] = $object->getRowsCount();
				$this->response[ "results" ] = $object->getAllResults( true );
			break;
			case "delete" : # Requisição de remoção.
				$object = new \Src\Db\Query\Delete( $request );
			break;
			case "put" : # Requisição de atualização.
				$object = new \Src\Db\Query\Update( $request );
			break;
			case "post" : # Requisição de inserção.
				$object = new \Src\Db\Query\Insert( $request );
				$this->response[ "last_insert_id" ] = $object->getLastInsertID();
			break;
			case "service" : # Requisição de serviços.
				$service = "\Src\Services\\" . $this->requestParams[ "object" ];

				if( !class_exists( $service ) ) {
					$object = false;
				}else{
					$service = new $service( $request );
					$object = $service->getResults();
					$this->response[ "results" ] = $object;
				}
			break;
		}

		# Validação do objeto.
		if( is_null( $object ) )
			$this->response[ "message" ] = gettext( "Erro desconhecido." );
		elseif( $object === false )
			$this->response[ "message" ] = gettext( "Serviço não encontrado." );
		elseif( is_object( $object ) && $object->hasError() )
			$this->response[ "message" ] = gettext( $object->getError() );
		else
			$this->response[ "status" ] = "success";
	}
}