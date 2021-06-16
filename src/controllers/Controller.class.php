<?php
/**
 * Classe abstrata para as classes de controle de rotinas DML
 * do sistema.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

abstract class Controller {
	/**
	 * Constante de comparadores.
	 */
	const COMPARES = array( "=", "!=", "<>", ">", "<", ">=", "<=", "IN", "NOT IN", "IS NULL", "IS NOT NULL", "EXISTS", "NOT EXISTS", "BETWEEN", "LIKE", "LEFT LIKE", "RIGHT LIKE", "NOT LIKE", "NOT LEFT LIKE", "NOT RIGHT LIKE", "MATCH_PERCENTAGE" );
	const COMPARES_IGNORED_VALUES = array( "IS NULL", "IS NOT NULL", "EXISTS", "NOT EXISTS" );
	const COMPARES_LIKE = array( "LIKE", "LEFT LIKE", "RIGHT LIKE", "NOT LIKE", "NOT LEFT LIKE", "NOT RIGHT LIKE" );

	/**
	 * Configurações de metaqueries.
	 * @access private
	 * @var    array
	 */
	private $metaQuerySetts = array(
		"key"		=> "",
		"value"		=> "",
		"column"	=> "",
		"compare"	=> "=",
		"relation"	=> "AND"
	);

	/**
	 * Objeto de conexão estático e global.
	 * @access protected
	 * @var    object
	 */
	protected static $connection = null;

	/**
	 * Objeto de conexão local, usado para execução de rotinas.
	 * @access protected
	 * @var    object
	 */
	protected $queryConnection = null;

	/**
	 * Instrução de execução.
	 * @access protected
	 * @var    string
	 */
	protected $query;

	/**
	 * Lista de valores para substituição no statement.
	 * @access private
	 * @var    array
	 */
	protected $fields = array();

	/**
	 * Lista de tabelas usadas pela instrução automática.
	 * @access protected
	 * @var    array
	 */
	protected $tables = array();

	/**
	 * Resultados a partir do statement da instrução.
	 * @access protected
	 * @var    object
	 */
	protected $resultSet = null;

	/**
	 * Mensagem de erro do sistema.
	 * @access protected
	 * @var    boolean
	 */
	protected $error = false;

	/**
	 * Identificar requisição a partir da API.
	 * @access protected
	 * @var    boolean
	 */
	protected $isApi = false;

	/**
	 * Contrução do objeto.
	 * @param  array|string $settsOrQuery Definições do objeto ou instrução livre.
	 * @param  array        $params       Parâmetros da instrução para criação do statement.
	 * @param  object       $connection   Objeto opcional de conexão.
	 * @return void
	 */
	public function __construct( $settsOrQuery, $params = array(), $connection = null ) {
		# Identificar requisição da API.
		if( defined( "REQUEST_FROM_API" ) && REQUEST_FROM_API == 1 )
			$this->isApi = true;
		
		# Definir objeto de conexão global, caso não exista.
		if( !is_null( $connection ) ) {
			$this->queryConnection = $connection;
		}elseif( !is_null( self::$connection ) ) {
			$this->queryConnection = self::$connection;
		}else{
			$connection = new Connect();
			$connection->connect();

			$this->queryConnection = self::$connection = $connection;
		}

		# Objeto inútil se não houver o objeto de conexão.
		if( is_null( $this->queryConnection ) || !$this->queryConnection->getConnectionStatus() )
			Logger::setDisplayMessage( gettext( "Não foi possível estabelecer conexão com a base de dados. Por favor, entre em contato com o administrador do sistema." ) );

		# Tratamento de atributos de configuração.
		if( is_array( $settsOrQuery ) ) {
			$this->setts = Utils::arrayMerge( $this->setts, $settsOrQuery );
			$this->setts[ "table" ] = $this->handlerTable( $this->setts[ "table" ] );
			$this->query = $this->getAutoQuery();
		}else{
			$this->query = $settsOrQuery;
		}

		# Definir tipo de instrução.
		$DMLType = Connect::getStatementType( $this->query );

		# Obter e executar rotinas.
		if( empty( $this->query ) )
			Logger::setLogMessage( gettext( "Não é possível executar instruções em branco." ) );
		elseif( $DMLType != $this->getAllowedDML() )
			Logger::setLogMessage( sprintf( gettext( "O controlador não suporta a instrução \"%s\"." ), $DMLType ) );
		else
			$this->execute();
	}

	/**
	 * Tratamento de informações da tabela.
	 * @access private
	 * @param  string  $table Tabela para tratamento.
	 * @return array
	 */
	protected function handlerTable( $table ) {
		if( !is_array( $table ) )
			$table = array( "name" => $table );

		$schema = $this->queryConnection->getSchema();
		$alias = "TAB" . str_pad( ( count( $this->tables ) + 1 ), 2, "0", STR_PAD_LEFT );
		$setts = array( "name" => "", "alias" => $alias, "schema" => $schema );
		$table = Utils::arrayMerge( $setts, $table );

		# Adicionar à lista de tabelas usadas pelo controlador.
		$this->tables[] = $table;

		return $table;
	}

	/**
	 * Obter tipo de instrução permitida no controlador.
	 * @access protected
	 * @return string
	 */
	abstract protected function getAllowedDML();

	/**
	 * Obter instrução automática para o objeto.
	 * @access protected
	 * @return string
	 */
	abstract protected function getAutoQuery();

	/**
	 * Executar instrução.
	 * @access protected
	 * @return void
	 */
	abstract protected function execute();

	/**
	 * Obter mensagem de erro do sistema.
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Verificar se existe erro.
	 * @return boolean
	 */
	public function hasError() {
		return (bool) $this->error;
	}

	/**
	 * Obter a última instrução executada.
	 * @param  string $type Tipo da instrução (SELECT, UPDATE, DELETE e INSERT).
	 * @return string
	 */
	public static function getLastQuery( $type = null ) {
		return self::$connection->getLastQuery( $type );
	}

	/**
	 * Obter estrutura condicional da consultas (cláusula WHERE).
	 * @access protected
	 * @param  array     $metaQueries Lista das propriedades condicionais de consulta.
	 * @return string
	 */
	protected function getMetaQueries( $metaQueries = array() ) {
		# Definições de comparação.
		$query = "";
		$schema = $this->queryConnection->getSchema();
		$driver = $this->queryConnection->getDBDriver();
		$item = 1;

		# Listar meta queries multiplas.
		foreach( (array) $metaQueries as $metaQuery ) {
			if( !is_array( $metaQuery ) )
				continue;

			$query .= ( !empty( $query ) ? " AND " : "" ) . "(";
			$shortQuery = "";
			$metaQuery = array_values( (array) $metaQuery );
			$subitem = 1;

			# Listar meta queries individuais.
			foreach( $metaQuery as $meta ) {
				# Nome do parâmetro do statement.
				$param = ":WH_SQ_" . $item . "_" . $subitem;

				if( !is_array( $meta ) )
					continue;

				# Aplicar método trim() nos parâmetros.
				$meta = array_map( function( $val ) {
						if( !is_array( $val ) && !is_null( $val ) )
							$val = trim( $val );

						return $val;
					}, Utils::arrayMerge( $this->metaQuerySetts, $meta )
				);
				$meta[ "compare" ] = ( in_array( strtoupper( $meta[ "compare" ] ), self::COMPARES ) ? strtoupper( $meta[ "compare" ] ) : "=" );
				$meta[ "relation" ] = ( strtoupper( $meta[ "relation" ] ) == "OR" ? " OR " : " AND " );

				# Comparadores que ignoram o valor do campo.
				if( in_array( $meta[ "compare" ], self::COMPARES_IGNORED_VALUES ) ) {
					$meta[ "value" ] = "";

					# Para utilizar o comparador EXISTS, é necessário configurar a query como um atributo "key".
					if( substr_count( $meta[ "compare" ], "EXISTS" ) > 0 ) {
						$meta[ "value" ] = "(" . $meta[ "key" ] . ")";
						$meta[ "key" ] = "";
					}
				}
				# Percentual de correspondência.
				elseif( $meta[ "compare" ] == "MATCH_PERCENTAGE" ) {
					if( ( !empty( $meta[ "column" ] ) ) ) {
						$meta[ "value" ] = $meta[ "column" ];
					}else{
						$this->fields[ ( $param ) ] = $meta[ "value" ];
						$meta[ "value" ] = $param;
					}

					$meta[ "key" ] = $schema . ".FUZZYSEARCH((" . $driver->cast( $meta[ "key" ], "VARCHAR", 200 ) . "), (" . $driver->cast( $meta[ "value" ], "VARCHAR", 200 ) . "))";
					$meta[ "value" ] = isset( $meta[ "percentage" ] ) ? (int) $meta[ "percentage" ] : 100;
					$meta[ "compare" ] = ">=";
				}else{
					$value = null;
					$isColumn = ( !empty( $meta[ "column" ] ) );
					$realCompare = $meta[ "compare" ];

					if( !is_array( $meta[ "value" ] ) && strtoupper( trim( $meta[ "value" ] ) ) == "NULL" )
						$meta[ "value" ] = null;

					# Comparadores LIKE.
					if( in_array( $meta[ "compare" ], self::COMPARES_LIKE ) ) {
						# Definir estrutura do LIKE.
						if( $isColumn )
							$value = $meta[ "column" ];
						else
							$value = $meta[ "value" ];

						# Definir posicionamento do percent.
						if( is_null( $value ) ) {
							$val = null;
						}elseif( substr_count( $meta[ "compare" ], "LEFT" ) > 0 ) {
							$val = "%" . $value;

							if( $isColumn )
								$val = $driver->concatNoCoalesce( "'%'", "COALESCE(" . $value . ", '')" );
						}elseif( substr_count( $meta[ "compare" ], "RIGHT" ) > 0 ) {
							$val = $value . "%";

							if( $isColumn )
								$val = $driver->concatNoCoalesce( "COALESCE(" . $value . ", '')", "'%'" );
						}else{
							$val = "%" . $value . "%";

							if( $isColumn )
								$val = $driver->concatNoCoalesce( "'%'", "COALESCE(" . $value . ", '')", "'%'" );
						}

						$meta[ "value" ] = $param;
						$meta[ "compare" ] = ( substr_count( $meta[ "compare" ], "NOT" ) > 0 ? "NOT " : "" ) . "LIKE";

						if( $isColumn )
							$meta[ "value" ] = $val;
						else
							$value = $val;
					}
					# Comparador de intervalos.
					elseif( $meta[ "compare" ] == "BETWEEN" ) {
						$meta[ "value" ] = Utils::arrayMerge( array( "min" => "", "max" => "" ), (array) $meta[ "value" ] );
						$meta[ "column" ] = Utils::arrayMerge( array( "min" => "", "max" => "" ), (array) $meta[ "column" ] );

						if( $isColumn ) {
							$meta[ "value" ] = $meta[ "column" ][ "min" ] . " AND " . $meta[ "column" ][ "max" ];
						}else{
							if( strtoupper( trim( $meta[ "value" ][ "min" ] ) ) == "NULL" )
								$meta[ "value" ][ "min" ] = null;

							if( strtoupper( trim( $meta[ "value" ][ "max" ] ) ) == "NULL" )
								$meta[ "value" ][ "max" ] = null;

							$value = array(
								"${param}_min" => $meta[ "value" ][ "min" ],
								"${param}_max" => $meta[ "value" ][ "max" ]
							);
							$meta[ "value" ] = "${param}_min AND ${param}_max";
						}
					}
					# Comparadores para verificar se valor existe em uma lista.
					elseif( in_array( $meta[ "compare" ], array( "IN", "NOT IN" ) ) ) {
						if( !is_array( $meta[ "value" ] ) )
							$meta[ "value" ] = explode( ",", $meta[ "value" ] );

						if( $isColumn ) {
							$valueColumns = (array) $meta[ "column" ];

							# Remover caracteres especiais.
							if( $this->setts[ "unaccent" ] )
								$valueColumns = $driver->ignoreAccents( $schema, $valueColumns );

							$meta[ "value" ] = "(" . implode( ", ", $valueColumns ) . ")";
						}else{
							$value = array();

							foreach( (array) $meta[ "value" ] as $j => $val ) {
								if( strtolower( trim( $val ) ) == "NULL" )
									$val = null;

								$value[ ( "${param}_" . ( $j + 1 ) ) ] = trim( $val );
							}

							$valueKeys = array_keys( $value );

							# Remover caracteres especiais.
							if( $this->setts[ "unaccent" ] )
								$valueKeys = $driver->ignoreAccents( $schema, $valueKeys );

							$meta[ "value" ] = $driver->getILikeArray( $valueKeys );
						}
					}else{
						if( $isColumn ) {
							$meta[ "value" ] = $meta[ "column" ];
						}else{
							$value = $meta[ "value" ];
							$meta[ "value" ] = $param;
						}

						$meta[ "value" ] = "COALESCE((" . $driver->cast( $meta[ "value" ], "VARCHAR", 200 ) . "), '')";
					}

					# Caso necessário, obter comparador ILIKE.
					$meta[ "compare" ] = $driver->getILikeComparator( $meta[ "compare" ] );
					$meta[ "key" ] = "COALESCE((" . $driver->cast( $meta[ "key" ], "VARCHAR", 200 ) . "), '')";

					# Aplicar função para ignorar acentuação de caracteres.
					if( $this->setts[ "unaccent" ] ) {
						$meta[ "key" ] = $driver->ignoreAccents( $schema, $meta[ "key" ] );

						if( !in_array( $realCompare, array( "IN", "NOT IN" ) ) )
							$meta[ "value" ] = $driver->ignoreAccents( $schema, $meta[ "value" ] );
					}

					# Adicionar elementos na lista de substituição do statement.
					if( !$isColumn ) {
						if( is_array( $value ) ) {
							foreach( $value as $i => $val )
								$this->fields[ ( $i ) ] = $val;
						}else{
							$this->fields[ ( $param ) ] = $value;
						}
					}
				}

				$shortQuery .= ( ( !empty( $shortQuery ) ? $meta[ "relation" ] : "" ) . $meta[ "key" ] . " " . $meta[ "compare" ] . " " . $meta[ "value" ] );

				$subitem++;
			} # Fim da lista de meta queries individuais.

			$query .= $shortQuery . ")";

			$item++;
		} # Fim da lista de meta queries multiplas.

		return $query;
	}
}