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

namespace Src\Controllers\Query;

class Select extends \Src\Controllers\Controller {
	/**
	 * Atributo de configurações do objeto.
	 * @access protected
	 * @var    array
	 */
	protected $mainSetts = array(
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
		"alias"			=> "",
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

		# Tratamento para ordenação com número da linha.
		$orders = Utils::arrayKeyHandler( $this->setts[ "order_by" ] );
		$reverseOrder = ( array_key_exists( "rownumber", $orders ) && ( strtoupper( trim( $orders[ "rownumber" ] ) ) == "DESC" ) );

		return $conn->getDBDriver()->getSelectStatement( $table, $columns, $joins, $queries, $groupBy, $orderBy, $reverseOrder, $perPage, $offset );
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

		# Listar JOINs da tabela.
		foreach( $this->setts[ "joins" ] as $join ) {
			if( !is_array( $join ) )
				continue;

			$join = Utils::arrayMerge( $this->joinSetts, $join );
			$join[ "type" ] = ( strtoupper( trim( $join[ "type" ] ) ) );
			
			$table = $this->handlerTable( $join[ "table" ] );
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

			$query .= " " . $join[ "type" ] . " JOIN ${table} ${queries}";
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
		$orderBy = "";
		$defOrder = ( strtoupper( trim( $this->setts[ "order" ] ) ) == "ASC" ? "ASC" : "DESC" );

		# Tratamento de ORDER BY enviado como string.
		if( !is_array( $this->setts[ "order_by" ] ) ) {
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
			$orderBy = " ORDER BY ${orderBy}";

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

			foreach( $this->tables as $alias => $name ) {
				$name = strtolower( trim( $name ) );

				# Não incluir tabelas repetidas na consulta.
				if( in_array( $name, $tables ) )
					continue;

				$columns[] = $scapeChar . $alias . $scapeChar . ".*";
				$tables[] = $name;
			}
		}else{
			foreach( (array) $this->setts[ "fields" ] as $alias => $field )
				$columns[] = $field;
		}

		return implode( ", ", $columns );
	}

	/* Override */
	protected function execute() {
		$conn = $this->queryConnection;
		$stmt = $conn->prepare( $this->query, $this->fields );

		if( !$conn->hasError() ) {
			$this->resultSet = $stmt->fetchAll( PDO::FETCH_ASSOC );

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
		}else{
			$this->error = $conn->getError();
			$conn->setMessage( gettext( "Não foi possível finalizar a execução da instrução." ), $this->error );
		}
	}

	/**
	 * Verificar se a execução da instrução retornou resultados.
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
	 * @param  boolean $isApiRequest Identificar requisições para API.
	 * @return object
	 */
	public function getResults( $isApiRequest = false ) {
		if( !empty( $this->resultSet ) ) {
			$element = array_shift( $this->resultSet );

			# Tratamento de dados binários.
			foreach( $element as $key => $value ) {
				$type = isset( $this->columns[ ( $key ) ] ) ? $this->columns[ ( $key ) ] : "";

				if( is_resource( $value ) )
					$element[ ( $key ) ] = $this->getBlob( $value );

				# Tratamento de binários.
				if( ( $isApiRequest || $this->dataType == "JSON" ) && !empty( $value ) && preg_match( "/BYTEA/", $type ) ) {
					# Obter mime-type do binário.
					try {
						$finfo = finfo_open();
						$mimeType = finfo_buffer( $finfo, $element[ ( $key ) ], FILEINFO_MIME_TYPE );
						finfo_close( $finfo );
					}catch( Exception $e ) {
						$mimeType = "none";
					}

					$element[ ( $key ) ] = "data:${mimeType};base64," . base64_encode( $element[ ( $key ) ] );
				}
			}

			if( isset( $element[ "foundrows" ] ) )
				unset( $element[ "foundrows" ] );

			# Converter dados para o formato definido em setDatType().
			if( $this->dataType == "OBJECT" )
				$element = (object) $element;
			elseif( $this->dataType == "JSON" )
				$element = json_encode( $element );

			return $element;
		}

		return false;
	}

	/**
	 * Retornar todos os resultados da consulta.
	 * @param  boolean $isApiRequest Identificar requisições para API.
	 * @return array
	 */
	public function getAllResults( $isApiRequest = false ) {
		$results = array();

		while( $element = $this->getResults( $isApiRequest ) )
			$results[] = $element;

		# Converter dados para o formato definido em setDataType().
		if( $this->dataType == "OBJECT" )
			$results = (object) $results;
		elseif( $this->dataType == "JSON" )
			$results = json_encode( $results );

		return $results;
	}
}