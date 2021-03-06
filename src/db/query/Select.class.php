<?php
/**
 * Controlador de consultas, que poderá será usado para
 * montagem automática de instruções baseadas em uma lista de
 * configurações.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Db\Query;

class Select extends \Src\Db\Controller {
	/**
	 * Atributo de configurações do objeto.
	 * @access protected
	 * @var    array
	 */
	protected $setts = array(
		"table"			=> "",
		"fields"		=> array(),
		"joins"			=> array(),
		"per_page"		=> false,
		"paged"			=> 1,
		"order_by"		=> array(),
		"group_by"		=> array(),
		"order"			=> "DESC",
		"meta_query"	=> array(),
		"meta_queries"	=> array(),
		"unaccent"		=> true
	);

	/**
	 * Atributo de configurações para JOINs.
	 * @access private
	 * @var    array
	 */
	private $joinSetts = array(
		"table"			=> "",
		"type"			=> "INNER",
		"meta_query"	=> array(),
		"meta_queries"	=> array()
	);

	/**
	 * Total de linhas retornadas após a execução da instrução.
	 * @access private
	 * @var    integer
	 */
	private $totalRows = 0;

	/**
	 * Linhas retornadas após a execução da instrução.
	 * @access private
	 * @var    integer
	 */
	private $resultRows = 0;

	/**
	 * Formato de retorno dos dados de consulta da instrução.
	 * @access private
	 * @var    object
	 */
	private $dataType = "ARRAY";

	/**
	 * Listagem de colunas obtidas na execução da instrução.
	 * @access private
	 * @var    array
	 */
	private $columns = array();

	/**
	 * Definir formato de retorno de dado em consulta da instrução.
	 * @param string $dataType Tipo de dado para retorno.
	 */
	public function setDataType( $dataType = "ARRAY" ) {
		$dataType = strtoupper( trim( $dataType ) );

		if( in_array( $dataType, array( "JSON", "OBJECT", "ARRAY" ) ) )
			$this->dataType = $dataType;
	}

	/* Override */
	protected function getAutoQuery() {
		$conn = $this->queryConnection;
		$setts = $this->setts;
		$table = $this->setts[ "table" ];
		$joins = $this->getJoins();
		$groupBy = $this->getGroupBy();
		$columns = $this->getColumns();
		$orderBy = $this->getOrderBy();
		
		# Incluir meta query individual à lista de meta queries para facilitar montagem.
		if( !empty( $setts[ "meta_query" ] ) )
			array_unshift( $setts[ "meta_queries" ], $setts[ "meta_query" ] );

		# Obter estrutura condicional.
		$queries = $this->getMetaQueries( $setts[ "meta_queries" ] );

		if( !empty( $queries ) )
			$queries = " WHERE " . $queries;

		# Variáveis de paginação.
		$perPage = (int) $this->setts[ "per_page" ];
		$paged = max( (int) $this->setts[ "paged" ], 1 ) - 1;
		$offset = ( $paged * $perPage );
		$tableName = $table[ "schema" ] . "." . $table[ "name" ] . " AS " . $table[ "alias" ];

		# Tratamento para ordenação com número da linha.
		$orders = $this->utils->arrayKeyHandler( $this->setts[ "order_by" ] );
		$reverseOrder = ( array_key_exists( "rownumber", $orders ) && ( strtoupper( trim( $orders[ "rownumber" ] ) ) == "DESC" ) );

		return $conn->getDBDriver()->getSelectStatement( $tableName, $columns, $joins, $queries, $groupBy, $orderBy, $reverseOrder, $perPage, $offset );
	}

	/* Override */
	protected function getAllowedDML() {
		return "SELECT";
	}

	/**
	 * Obter estrutura de JOINS para a tabela, com mapeamento automático ou personalizado de relacionamento.
	 * @access protected
	 * @return string
	 */
	protected function getJoins() {
		$query = "";
		$joins = $this->setts[ "joins" ] ;

		if( is_string( $joins ) ) {
			try{
				$joins = json_decode( $joins, true );
			}catch( \Exception $e ) {
				$joins = array();
			}
		}

		# Listar JOINs da tabela.
		foreach( $joins as $join ) {
			# Converter joins passados como json.
			if( !is_array( $join ) )
				continue;

			$join = $this->utils->arrayMerge( $this->joinSetts, $join );
			$join[ "type" ] = ( strtoupper( trim( $join[ "type" ] ) ) );
			
			$table = $this->handlerTable( $join[ "table" ] );
			$tableName = $table[ "schema" ] . "." . $table[ "name" ] . " AS " . $table[ "alias" ];
			$queries = "ON ";

			# Verificando tipo de mesclagem.
			if( !in_array( $join[ "type" ], array( "LEFT", "RIGHT", "FULL" ) ) )
				$join[ "type" ] = "INNER";

			# Adicionar metaquery individual à lista de metaqueries.
			if( !empty( $join[ "meta_query" ] ) )
				array_unshift( $join[ "meta_queries" ], $join[ "meta_query" ] );

			# Obter estrutura condicional.
			if( !empty( $join[ "meta_queries" ] ) )
				$queries .= $this->getMetaQueries( $join[ "meta_queries" ] );

			$query .= " " . $join[ "type" ] . " JOIN ${tableName} ${queries}";
		}

		return $query;
	}

	/**
	 * Obter estrutura de agrupamento da instrução.
	 * @access protected
	 * @return string
	 */
	protected function getGroupBy() {
		$groupBy = "";
		$fields = array();

		# Tratamento de GROUP BY enviado como string.
		if( !is_array( $this->setts[ "group_by" ] ) )
			$this->setts[ "group_by" ] = explode( ",", $this->setts[ "group_by" ] );

		# Processar lista de parâmetros de agrupamento.
		foreach( array_values( $this->setts[ "group_by" ] ) as $param ) {
			$groupBy .= ( !empty( $groupBy ) ? ", " : "" ) . $param;

			# Campos para agrupamento, caso não tenham sido informados.
			if( empty( $this->setts[ "fields" ] ) )
				$fields[] = $param;
		}

		if( !empty( $groupBy ) )
			$groupBy = " GROUP BY ${groupBy}";

		if( !empty( $fields ) )
			$this->setts[ "fields" ] = $fields;

		return $groupBy;
	}

	/**
	 * Obter estrutura de ordenação da instrução.
	 * @access protected
	 * @return string
	 */
	protected function getOrderBy() {
		$defOrder = ( strtoupper( trim( $this->setts[ "order" ] ) ) == "ASC" ? "ASC" : "DESC" );

		# Tratamento de ORDER BY enviado como string.
		if( !is_array( $this->setts[ "order_by" ] ) ) {
			$matches = array();
			$orderByColumns = explode( ",", $this->setts[ "order_by" ] );
			$this->setts[ "order_by" ] = array();

			foreach( $orderByColumns as $column ) {
				preg_match_all( "/^(.+\s)+(ASC|DESC|NONE)$/ui", trim( $column ), $matches );

				if( empty( $matches[1] ) )
					$this->setts[ "order_by" ][ trim( $column ) ] = $defOrder;
				else
					$this->setts[ "order_by" ][ trim( $matches[1][0] ) ] = $matches[2][0];
			}
		}

		return $this->handlerOrderBy();
	}

	/**
	 * Tratamento para o order by.
	 * @access private
	 * @return string
	 */
	private function handlerOrderBy(  ) {
		$orderBy = "";
		$defOrder = ( strtoupper( trim( $this->setts[ "order" ] ) ) == "ASC" ? "ASC" : "DESC" );

		# Processar lista de parâmetros de ordenação.
		foreach( $this->setts[ "order_by" ] as $param => $order ) {
			# O campo de número de linha não entra para a instrução de ordenação.
			if( strtolower( trim( $param ) ) == "rownumber" )
				continue;

			$order = trim( $order );
			$upperOrder = strtoupper( $order );

			# Cada parâmetro pode ter seu tipo de ordenação.
			if( !in_array( $upperOrder, array( "ASC", "DESC", "NONE" ) ) )
				$order = $defOrder;

			if( $upperOrder != "NONE" )
				$orderBy .= ( !empty( $orderBy ) ? ", " : "" ) . $param . " " . $order;
		}

		if( !empty( $orderBy ) )
			$orderBy = " ORDER BY " . $orderBy;

		return $orderBy;
	}

	/**
	 * Obter colunas.
	 * @access protected
	 * @return string
	 */
	protected function getColumns() {
		$columns = array();
		$scapeChar = $this->queryConnection->getDBDriver()->getScapeChar();

		if( empty( $this->setts[ "fields" ] ) ) {
			$tables = array();

			foreach( $this->tables as $table ) {
				$table[ "name" ] = strtolower( trim( $table[ "name" ] ) );

				# Não incluir tabelas repetidas na consulta.
				if( in_array( $table[ "name" ], $tables ) )
					continue;

				$columns[] = $scapeChar . $table[ "alias" ] . $scapeChar . ".*";
				$tables[] = $table[ "name" ];
			}
		}else{
			$fields = $this->setts[ "fields" ];

			if( is_string( $fields ) )
				$fields = explode( ",", $fields );

			foreach( (array) $this->setts[ "fields" ] as $field )
				$columns[] = $field;
		}

		return implode( ", ", $columns );
	}

	/* Override */
	protected function execute() {
		$conn = $this->queryConnection;
		$stmt = $conn->prepare( $this->query, $this->fields );

		if( $conn->hasError() ) {
			$this->error = $conn->getError();
			$this->logger->setMessage( gettext( "Não foi possível finalizar a execução da instrução." ), $this->error );
			return;
		}

		$this->resultSet = $stmt->fetchAll( \PDO::FETCH_ASSOC );

		# Definir número total de resultados após execução da instução.
		if( isset( $this->resultSet[0][ "foundrows" ] ) )
			$this->totalRows = (int) $this->resultSet[0][ "foundrows" ];

		$this->resultRows = count( (array) $this->resultSet );

		for( $i = 0; $i < $stmt->columnCount(); $i++ ) {
			$meta = $stmt->getColumnMeta( $i );
			$type = $meta[ "native_type" ];
			$name = strtolower( $meta[ "name" ] );

			if( $name == "foundrows" )
				continue;

			$this->columns[ ( $name ) ] = strtoupper( $type );
		}
	}

	/**
	 * Verificar se a execução da instrução retornou resultados.
	 * @access private
	 * @return boolean
	 */
	public function hasResults() {
		return ( $this->totalRows > 0 );
	}

	/**
	 * Obter número total de registros retornados após a execução da instrução.
	 * @return integer
	 */
	public function getTotalRowsCount() {
		return $this->totalRows;
	}

	/**
	 * Obter contagem de registros retornados após a execução da instrução.
	 * @return integer
	 */
	public function getRowsCount() {
		return $this->resultRows;
	}

	/**
	 * Obter dados de colunas da instrução.
	 * @return array
	 */
	public function getColumnsMeta() {
		return $this->columns;
	}

	/**
	 * Buscar próxima linha.
	 * @return object
	 */
	public function getResults() {
		if( !empty( $this->resultSet ) ) {
			$element = array_shift( $this->resultSet );

			# Tratamento de dados binários.
			foreach( $element as $key => $value ) {
				$type = isset( $this->columns[ ( $key ) ] ) ? $this->columns[ ( $key ) ] : "";

				if( is_resource( $value ) )
					$element[ ( $key ) ] = $this->getBlob( $value );

				# Tratamento de binários.
				if( ( $this->isApi || $this->dataType == "JSON" ) && !empty( $value ) && preg_match( "/BYTEA/", $type ) ) {
					$mimeType = $this->getMimeType( $element[ ( $key ) ] );
					$element[ ( $key ) ] = "data:" . $mimeType . ";base64," . base64_encode( $element[ ( $key ) ] );
				}
			}

			if( isset( $element[ "foundrows" ] ) )
				unset( $element[ "foundrows" ] );

			return $this->getElement( $this->dataType, $element );
		}

		return false;
	}

	/**
	 * Obter elemento em um formato de acordo com a requisição.
	 * @param  string $dataType Tipo do arquivo.
	 * @param  array  $element  Elemento base.
	 * @return string
	 */
	private function getElement( $dataType, $element ) {
		# Converter dados para o formato definido em setDatType().
		if( $dataType == "OBJECT" )
			$element = (object) $element;
		elseif( $dataType == "JSON" )
			$element = json_encode( $element );

		return $element;
	}

	/**
	 * Obter Mimetype a partir de um objeto.
	 * @param  object $object Objeto de referência.
	 * @return string
	 */
	private function getMimeType( $object ) {
		try {
			$finfo = finfo_open();
			$mimeType = finfo_buffer( $finfo, $object, FILEINFO_MIME_TYPE );
			finfo_close( $finfo );
		}catch( \Exception $excep ) {
			$mimeType = "none";
		}

		return $mimeType;
	}

	/**
	 * Retornar todos os resultados da consulta.
	 * @return array
	 */
	public function getAllResults() {
		$results = array();

		while( $element = $this->getResults() )
			$results[] = $element;

		return $this->getElement( $this->dataType, $results );
	}
}