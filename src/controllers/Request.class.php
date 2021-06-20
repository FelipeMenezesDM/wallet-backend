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
use \Src\Db\Query as Query;
use \Src\Db as Db;

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
	 * Armazenar objeto da requisição.
	 * @access private
	 * @var    object
	 */
	private $request;

	/**
	 * Método construtor.
	 * @param array $requestParams Parâmetros de requsição da API.
	 */
	public function __construct( $requestParams = array() ) {
		$this->setRequest();
		$this->addHeaders();
		$response = & $this->response;
		$params = & $this->requestParams;
		$params = array_intersect_key( Utils::arrayKeyHandler( $requestParams ), array_flip( array( "version", "type", "object", "feature" ) ) );
		$params = array_merge( array( "version" => "", "type" => "", "object" => "", "feature" => "" ), $params );
		$params = array_map( "strtolower", array_map( "trim", $params ) );
		$fileName = "error";
		$isPretty = ( ( isset( $this->request[ "pretty" ] ) && (bool) $this->request[ "pretty" ] ) ? JSON_PRETTY_PRINT : 0 );

		# Adicionar atributos adicionais para requisições GET.
		if( $params[ "type" ] === "get" ) {
			$response[ "fields" ] = array();
			$response[ "total" ] = 0;
			$response[ "items" ] = 0;
		}

		# Validar requisição.
		if( $message = $this->validateRequest( $params ) )
			$response[ "message" ] = $message;

		# Tentativa de conversão em JSON para testar resposta.
		try{
			json_encode( $response, $isPretty );
		}catch( \Exception $e ){
			$jsonError = true;
		}

		# Verificar se há erro de json.
		$jsonError = $this->getJsonError();

		if( $jsonError ) {
			$response[ "message" ] = $jsonError;
			$response[ "status" ] = "error";
		}

		header( "Content-Disposition: inline; filename=\"" . $fileName . ".json\"" );
		echo json_encode( $response, $isPretty );
	}

	/**
	 * Definir objeto de requisição.
	 * @access private
	 */
	private function setRequest() {
		$this->request = ( $_SERVER[ "REQUEST_METHOD" ] === "GET" ? $_GET : $_POST );
	}

	/**
	 * Verificar erro na conversão de objeto em JSON.
	 * @access private
	 * @return string
	 */
	private function getJsonError() {
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

		return $jsonError;
	}

	/**
	 * Adicionar cabeçalhos.
	 * @access private
	 */
	private function addHeaders() {
		# Limpar buffer com conteúdo desnecessário.
		while( ob_get_level() )
			ob_end_clean();

		header( "Access-Control-Allow-Origin: *" ); 
		header( "Access-Control-Allow-Credentials: true");
		header( "Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS" );
		header( "Access-Control-Max-Age: 1000" );
		header( "Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization" );
		header( "Content-Type: application/json" );
	}

	/**
	 * Validar requisição.
	 * @access private
	 * @param  array   $params Parâmetros da requisição.
	 * @return string
	 */
	private function validateRequest( $params ) {
		$auth = new Auth();

		# Verificar se a versão está preenchida.
		if( empty( $params[ "version" ] ) ) {
			return gettext( "Versão da API não encontrada." );
		}
		# Verificar se a versão é válida.
		elseif( !in_array( $params[ "version" ], self::VERSIONS ) ) {
			return gettext( "A versão da API informada é inválida." );
		}
		# Verificar se o tipo de requisição está preenchido.
		elseif( empty( $params[ "type" ] ) ) {
			return gettext( "Tipo de requisição não encontrado." );
		}
		# Verificar se o tipo de requisição informado é válido.
		elseif( !in_array( $params[ "type" ], self::RESQUEST_TYPES ) ) {
			return gettext( "O tipo da requisição informado é inválido." );
		}
		# Verificar se o tipo de requisição está preenchido.
		elseif( empty( $params[ "object" ] ) ){
			return gettext( "O objeto da requisição não foi informado." );
		}
		# Verificar autenticação da requisição.
		elseif( !$auth->isAuth( $this->request ) ) {
			return gettext( "Requisição não autorizada." );
		}

		$this->processRequest();
		return false;
	}

	/**
	 * Processar e validar requisição da API.
	 * @access private
	 */
	private function processRequest() {
		$request = $this->request;
		$request[ "table" ] = $this->requestParams[ "object" ];
		$object = null;

		define( "REQUEST_FROM_API", 1 );
		Db\Connect::setDebugMode( ( isset( $this->request[ "debug" ] ) && (bool) $this->request[ "debug" ] ) );

		# Obter objeto de acordo com o tipo de resquisição.
		switch( $this->requestParams[ "type" ] ) {
			case "get" : # Requisição de consulta.
				# Limite de registros padrão, caso nao seja informado.
				if( !isset( $request[ "per_page" ] ) )
					$request[ "per_page" ] = 100;

				$object = new Query\Select( $request );

				# Obter totalizadores e resultados.
				$this->response[ "fields" ] = array_keys( $object->getColumnsMeta() );
				$this->response[ "total" ] = $object->getTotalRowsCount();
				$this->response[ "items" ] = $object->getRowsCount();
				$this->response[ "results" ] = $object->getAllResults( true );
			break;
			case "delete" : # Requisição de remoção.
				$object = new Query\Delete( $request );
			break;
			case "put" : # Requisição de atualização.
				$object = new Query\Update( $request );
			break;
			case "post" : # Requisição de inserção.
				$object = new Query\Insert( $request );
				$this->response[ "last_insert_id" ] = $object->getLastInsertID();
			break;
			case "service" : # Requisição de serviços.
				try{ 
					$service = "Src\Services\\" . $this->requestParams[ "object" ];
					$service = new $service();
					$feature = $this->requestParams[ "feature" ];
					$this->response = call_user_func_array( array( $service, $feature ), array( $request ) );
				}catch(\Exception $e) {
					$object = false;
				}
			break;
		}

		# Validação do objeto.
		if( $this->requestParams[ "type" ] !== "service" ) {
			if( is_null( $object ) )
				$this->response[ "message" ] = gettext( "Erro desconhecido." );
			elseif( is_object( $object ) && $object->hasError() )
				$this->response[ "message" ] = gettext( $object->getError() );
			else
				$this->response[ "status" ] = "success";
		}
	}
}