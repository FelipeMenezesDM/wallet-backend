<?php
/**
 * Controlador de conexões com bases de dados.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

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
	 * Guardar erro na execução de instruções.
	 * @access private
	 * @var    array
	 */
	private $error = false;

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
			$driver = "Driver${manager}";

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
			$this->connection = new PDO( $this->getDBDriver()->getDNS( $this->host, $this->port, $this->database ), $this->user, $this->password );
			$this->connection->setAttribute( PDO::ATTR_CASE, PDO::CASE_LOWER );
			$this->getDBDriver()->connectionSettings( $this->connection );
			$this->getDBDriver()->setDBVersion( $this->connection->getAttribute( PDO::ATTR_SERVER_VERSION ) );
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
}