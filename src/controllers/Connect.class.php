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
	 * Contador de tempo de execução das instruções.
	 * @access private
	 * @var    integer
	 */
	private $execTimeCounter = 0;

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
				self::setDisplayMessage( gettext( "O gerenciador de base de dados precisa ser definido." ) );

			# Definir driver do gerenciador.
			if( !in_array( $manager, self::DRIVERS ) || !class_exists( $driver ) )
				self::setDisplayMessage( gettext( "O gerenciador de base de dados definido não é suportado." ) );
			else
				self::$driver = new $driver();

			self::$manager = $manager;
		}else{
			self::setDisplayMessage( gettext( "Um gerenciador de base de dados já está em uso." ) );
		}
	}

	/**
	 * Obter ID gerenciador da base de dados.
	 * @return string
	 */
	public function getManager() {
		return self::$manager;
	}
}