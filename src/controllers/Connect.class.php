<?php
/**
 * Controlador de conexões com bases de dados.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

class Connect {
	/**
	 * Lista constante de SGBDs compatíveis.
	 */
	const DRIVERS = array( "postgres" );

	/**
	 * Gerenciador da base de dados.
	 * @access private
	 * @var    string
	 */
	private static $manager;

	/**
	 * Definição do driver da conexão.
	 * @access private
	 * @var    object
	 */
	private static $driver;

	/**
	 * Usuário da base de dados.
	 * @access private
	 * @var    string
	 */
	private $user;

	/**
	 * Senha de usuário da base de dados.
	 * @access private
	 * @var    string
	 */
	private $password;

	/**
	 * Endereço ou IP do servidor da base de dados.
	 * @access private
	 * @var    string
	 */
	private $host;

	/**
	 * Porta do servidor da base de dados.
	 * @access private
	 * @var    int
	 */
	private $port;

	/**
	 * Base de dados padrão da conexão.
	 * @access private
	 * @var    string
	 */
	private $database;

	/**
	 * Schema padrão da base de dados.
	 * @access private
	 * @var    string
	 */
	private $schema;

	/**
	 * Objeto de conexão com a base de dados.
	 * @access private
	 * @var    object
	 */
	private $connection;

	/**
	 * Guardar a última instrução executada na conexão.
	 * @access private
	 * @var    string
	 */
	private static $lastQuery = "";

	/**
	 * Guardar as últimas instruções executadas, separadas por tipo.
	 * @access private
	 * @var    array
	 */
	private static $lastQueries = array();

	/**
	 * Guardar erro na execução de instruções.
	 * @access private
	 * @var    array
	 */
	private $error = false;

	/**
	 * Ligar ou desligar o modo debug de queries executadas na base.
	 * @access private
	 * @var    boolean
	 */
	private static $debug = false;

	/**
	 * Método construtor para objeto de conexão com bases de dados.
	 * @param  string $database Banco de dados padrão da conexão.
	 * @param  string $user     Usuário da base de dados/instância.
	 * @param  string $password Senha do usuário da base de dados/instância.
	 * @param  string $host     Servidor da base de dados.
	 * @param  int    $port     Porta do servidor da base de dados.
	 * @return void
	 */
	public function __construct( $database = null, $user = null, $password = null, $host = null, $port = null ) {
		# Definir base de dados a partir de constante global.
		if( is_null( $database ) && defined( "DB_NAME" ) )
			$database = DB_NAME;

		# Definir usuário da base de dados a partir de constante global.
		if( is_null( $user ) && defined( "DB_USER" ) )
			$user = DB_USER;

		# Definir senha de usuário da base de dados a partir de constante global.
		if( is_null( $password ) && defined( "DB_PASSWORD" ) && !empty( trim( DB_PASSWORD ) ) )
			$password = DB_PASSWORD;
		elseif( is_null( $password ) )
			$password  = "";

		# Definir endereço do servidor de base de dados a partir de constante global.
		if( is_null( $host ) && defined( "DB_HOST" ) && !empty( trim( DB_HOST ) ) )
			$host = DB_HOST;
		elseif( is_null( $host ) || empty( trim( $host ) ) )
			$host = "localhost";

		# Definir porta do servidor de base de dados a partir de constante global.
		if( is_null( $port ) && defined( "DB_PORT" ) && !empty( trim( DB_PORT ) ) )
			$port = DB_PORT;
		elseif( empty( trim( $port ) ) )
			$port = NULL;

		# Definir gerenciador da base de dados a partir de constante global.
		if( is_null( self::$manager ) && defined( "DB_MANAGER" ) )
			$this->setManager( DB_MANAGER );

		# Definir schema.
		$this->schema = $database;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * Definir gerenciador global da base de dados.
	 * @param  string $manager Gerenciador ao qual pertence a base de dados.
	 * @return void
	 */
	public function setManager( $manager = null ) {
		# Definir gerenciador apenas quando este não houver sido definido anteriormente.
		if( is_null( self::$manager ) ) {
			$manager = strtolower( trim( $manager ) );
			$driver = "Src\Drivers\Driver${manager}";

			# Verificar se o gerenciador foi definido.
			if( empty( $manager ) )
				Logger::setDisplayMessage( gettext( "O gerenciador de base de dados precisa ser definido." ) );

			# Definir driver do gerenciador.
			if( !in_array( $manager, self::DRIVERS ) || !class_exists( $driver ) )
				logger::setDisplayMessage( gettext( "O gerenciador de base de dados definido não é suportado." ) );
			else
				self::$driver = new $driver();

			self::$manager = $manager;
		}else{
			Logger::setDisplayMessage( gettext( "Um gerenciador de base de dados já está em uso." ) );
		}
	}

	/**
	 * Obter o driver do gerenciador.
	 * @return object
	 */
	public function getDBDriver() {
		return self::$driver;
	}

	/**
	 * Obter ID gerenciador da base de dados.
	 * @return string
	 */
	public function getManager() {
		return self::$manager;
	}

	/**
	 * Definir schema padrão para a conexão.
	 * @param  string $schema Nome do schema padrão.
	 * @return void
	 */
	public function setSchema( $schema = "" ) {
		if( empty( trim( $schema ) ) )
			$schema = $this->database;

		$this->schema = trim( $schema );
	}

	/**
	 * Obter schema padrão.
	 * @return string
	 */
	public function getSchema() {
		return $this->schema;
	}

	/**
	 * Obter base de dados padrão.
	 * @return string
	 */
	public function getDB() {
		return $this->database;
	}

	/**
	 * Obter o tipo de instrução da DML (SELECT, UPDATE, DELETE OU INSERT).
	 * @param  string $sql Intrução para verificação.
	 * @return string
	 */
	public static function getStatementType( $sql ) {
		if( is_array( $sql ) )
			$sql = array_pop( $sql );

		$sql = strtoupper( $sql );
		$dml = explode( " ", trim( trim( trim( trim( $sql ), "(" ), ")" ) ) )[0];

		if( $dml == "WITH" ) {
			return "SELECT";
		}elseif( $dml == "MERGE" ) {
			if( strrpos( $sql, "INSERT" ) )
				return "INSERT";
			else
				return "UPDATE";
		}

		return $dml;
	}

	/**
	 * Iniciar transação.
	 */
	public function beginTransaction() {
		if( !is_null( $this->connection ) )
			$this->connection->beginTransaction();
	}

	/**
	 * Comitar transação.
	 * @return void
	 */
	public function commit() {
		if( !is_null( $this->connection ) )
			$this->connection->commit();
	}

	/**
	 * Finalizar transação sem efetivar alterações.
	 * @return void
	 */
	public function rollBack() {
		if( !is_null( $this->connection ) )
			$this->connection->rollBack();
	}

	/**
	 * Realizar conexão com a base de dados.
	 */
	public function connect() {
		# Verificar atributos obrigatórios para conexão.
		if( is_null( self::$manager ) )
			Logger::setDisplayMessage( gettext( "O gerenciador de base de dados precisa ser definido." ) );
		elseif( is_null( $this->user ) || is_null( $this->password ) )
			Logger::setDisplayMessage( gettext( "As credenciais de conexão com a base de dados precisam ser definidas." ) );

		try {
			$this->connection = new \PDO( $this->getDBDriver()->getDNS( $this->host, $this->port, $this->database ), $this->user, $this->password );
			$this->connection->setAttribute( \PDO::ATTR_CASE, \PDO::CASE_LOWER );
			$this->getDBDriver()->connectionSettings( $this->connection );
			$this->getDBDriver()->setDBVersion( $this->connection->getAttribute( \PDO::ATTR_SERVER_VERSION ) );
		}catch( Exception $e ) {
			$this->connection = null;
			Logger::setDisplayMessage( gettext( "Não foi possível estabelecer conexão com a base de dados. Por favor, entre em contato com o administrador do sistema." ), $e->getMessage() );
		}
	}

	/**
	 * Encerrar conexão com a base de dados.
	 * @return void
	 */
	public function close() {
		$this->connection = null;
	}

	/**
	 * Executar comando SQL e retornar número de linhas afetadas.
	 * @param  string  $sql       Script SQL para execução.
	 * @param  boolean $saveQuery Salvar como última instrução executada.
	 * @return object
	 */
	public function exec( $sql = "", $saveQuery = true ) {
		return $this->run( $sql, "exec", array(), $saveQuery );
	}

	/**
	 * Executar comando SQL e retornar objeto com os resultados.
	 * @param  string  $sql       Script SQL para execução.
	 * @param  boolean $saveQuery Salvar como última instrução executada.
	 * @return string
	 */
	public function query( $sql = "", $saveQuery = true ) {
		return $this->run( $sql, "query", array(), $saveQuery );
	}

	/**
	 * Preparar statement
	 * @param  string  $sql       Script SQL para execução.
	 * @param  array   $params    Parâmetros do statement.
	 * @param  boolean $saveQuery Salvar como última instrução executada.
	 * @return object
	 */
	public function prepare( $sql = "", $params, $saveQuery = true ) {
		return $this->run( $sql, "prepare", $params, $saveQuery );
	}

	/**
	 * Execução de comandos SQL por tipo.
	 * @access private
	 * @param  string  $sql       Script SQL para execução.
	 * @param  string  $type      Tipo de execução.
	 * @param  array   $params    Parâmetros do statement.
	 * @param  boolean $saveQuery Salvar como última instrução executada.
	 * @return object
	 */
	private function run( $sql, $type, $params = array(), $saveQuery = true ) {
		if( is_null( $this->connection ) ) {
			Logger::setLogMessage( gettext( "A conexão com a base de dados não foi estabelecida." ) );
			return false;
		}

		# Armazena a última instrução executada.
		if( $saveQuery )
			self::$lastQuery = $sql;

		$start = ( time() + (double) microtime() );
		$resultSet = false;
		$this->error = false;

		try {
			switch( $type ) {
				case "exec" :
					$resultSet = $this->connection->exec( $sql );
				break;
				case "prepare" :
					# Tratamento de parâmetros repetidos em statements do PDO.
					# Eliminar as repetições, agregando o mesmo valor ao novo parâmetro.
					foreach( $params as $param => $value ) {
						if( is_int( $param ) )
							break;

						if( ( $countParam = preg_match_all( "/${param}[^_a-zA-Z]*/", $sql ) ) > 1 ) {
							for( $i = 1; $i < $countParam; $i++ ) {
								$newParam = "${param}_${i}";
								$params[ ( $newParam ) ] = $value;
								$sql = preg_replace( "/${param}([^_a-zA-Z]*)/", "${newParam}$1", $sql, 1 );
							}
						}
					}

					$values = array();
					$resultSet = $this->connection->prepare( $sql );

					# Mapear tipo de dados da coluna.
					foreach( $params as $param => $value ) {
						if( is_int( $param ) && $param >= 0 )
							$param += 1;

						$values[ ( $param ) ] = $value;

						# Tratamento de arquivos binários.
						if( is_resource( $value ) )
							$resultSet->bindParam( $param, $values[ ( $param ) ], PDO::PARAM_NULL|PDO::PARAM_LOB, 0 );
						else
							$resultSet->bindParam( $param, $values[ ( $param ) ] );
					}

					# Guardar última instrução executada.
					if( $saveQuery ) {
						$stmtType = strtolower( $this->getStatementType( $sql ) );
						self::$lastQuery = $this->handlerStatementParams( $sql, $params );
						self::$lastQueries[ ( $stmtType ) ] = self::$lastQuery;
					}

					# Executar instrução.
					try{
						$resultSet->execute();

						if( $resultSet->errorCode() !== "00000" )
							$this->error = $resultSet->errorInfo();
					}catch( Exception $ex ) {
						$this->error = $resultSet->errorInfo();
						$resultSet = false;
					}
				break;
				default:
					$resultSet = $this->connection->query( $sql );
				break;
			}

			# Imprimir última query executada, caso o modo debug esteja ligado.
			if( self::$debug && !empty( $this->getLastQuery() ) )
				Logger::setLogMessage( $this->getLastQuery(), self::LOG_INFO );
		}catch( Exception $e ) {
			# Imprimir última query executada, caso o modo debug esteja ligado.
			if( self::$debug )
				Logger::setLogMessage( $this->getLastQuery() );

			Logger::setLogMessage( gettext( "Falha na execução da instrução." ), $e->getMessage() );
		}

		Logger::setExecTime( ( time() + (double) microtime() ) - $start );
		return $resultSet;
	}

	/**
	 * Tratamento dos parâmetros do statement, traduzindo os valores para retorno da
	 * instrução final.
	 * @param  string $sql    Instrução do statement.
	 * @param  array  $params Parâmetros do statement.
	 * @return string
	 */
	private function handlerStatementParams( $sql, $params ) {
		foreach( $params as $var => $param ) {
			if( is_int( $var ) )
				$var = "\?";

			if( is_null( $param ) )
				$param = "NULL";
			elseif( is_string( $param ) )
				$param = "'${param}'";
			
			$sql = preg_replace( "/({$var}(?!\w))(?=(?:[^\"']|[\"'][^\"']*[\"'])*$)/", $param, $sql );
		}

		return $sql;
	}

	/**
	 * Obter objeto de conexão do PDO.
	 * @return object
	 */
	public function getPDOConnection() {
		return $this->connection;
	}

	/**
	 * Obter status de conexão com a base de dados.
	 * @return object
	 */
	public function getConnectionStatus() {
		return !is_null( $this->connection );
	}

	/**
	 * Obter erro no objeto de conexão.
	 * @return string
	 */
	public function getError() {
		if( is_null( $this->connection ) )
			return null;

		if( !( $error = $this->error ) )
			$error = $this->connection->errorInfo();

		return $error[2];
	}

	/**
	 * Verificar se há error na conexão e, caso haja, retornar seu código no SGBD.
	 * @return boolean|string
	 */
	public function hasError() {
		if( is_null( $this->connection ) )
			return false;

		if( !( $error = $this->error ) )
			$error = $this->connection->errorInfo();

		# Tratamento de erros desconhecidos.
		if( $error[0] == "HY000" ) {
			if( preg_match( "/(unique)(.*)(violated)(.*)/", $error[2] ) )
				$error[0] = "23000";
			elseif( preg_match( "/(value)(.*)(too)(.*)(large)(.*)/", $error[2] ) )
				$error[0] = "22001";
		}

		return ( empty( $error[2] ) ? false : (string) $error[0] );
	}

	/**
	 * Obter última instrução executada na conexão.
	 * @param  string $type Tipo da instrução (SELECT, UPDATE, DELETE e INSERT).
	 * @return string
	 */
	public static function getLastQuery( $type = null ) {
		$type = strtolower( trim( $type ) );

		if( isset( self::$lastQueries[ ( $type ) ] ) )
			return self::$lastQueries[ ( $type ) ];

		return self::$lastQuery;
	}
}