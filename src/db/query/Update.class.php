<?php
/**
 * Controlador de atualizações, que poderá será usado para
 * montagem automática de instruções baseadas em uma lista de
 * configurações.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Db\Query;

class Update extends \Src\Db\Controller {
	/**
	 * Atributo de configurações do objeto.
	 * @access protected
	 * @var    array
	 */
	protected $setts = array(
		"table"			=> "",
		"sets"			=> array(),
		"using"			=> array(),
		"key"			=> "",
		"meta_query"	=> array(),
		"meta_queries"	=> array(),
		"unaccent"		=> true
	);

	/**
	 * Lista de dados do tipo BLOB para atualização após execução de instrução tratada.
	 * @access protected
	 * @var    array
	 */
	protected $updateFiles = array();

	/* Override */
	protected function getAutoQuery() {
		$table = $this->handlerTable( $this->setts[ "table"] );
		$joins = $this->getRelatedTables();
		$setts = $this->setts;
		$key = &$this->setts[ "key" ];

		# Incluir meta query individual à lista de meta queries para facilitar montagem.
		if( !empty( $setts[ "meta_query" ] ) )
			array_unshift( $setts[ "meta_queries" ], $setts[ "meta_query" ] );

		$key = trim( $key );
		$columns = $this->getColumns( $table[ "name" ] );

		# Quando um update é feito com JOINs, é necessário informar a coluna chave da tabela principal.
		if( !empty( $joins ) && empty( $key ) ) {
			$this->logger->setLogMessage( gettext( "Para executar uma atualização com JOIN entre tabelas, é necessário informar a chave primária da tabela principal." ), $this->logger->LOG_WARNING );
			return null;
		}

		# Obter estrutura condicional.
		$queries = $this->getMetaQueries( $setts[ "meta_queries" ] );
		$tableName = $table[ "schema" ] . "." . $table[ "name" ];

		if( !empty( $queries ) )
			$queries = " WHERE " . $queries;

		return $this->queryConnection->getDBDriver()->getUpdateStatement( $tableName, $columns, $joins, $queries, $key );
	}

	/**
	 * Obter colunas.
	 * @access protected
	 * @param  string    $table Nome da tabela.
	 * @return array
	 */
	protected function getColumns() {
		$sets = &$this->setts[ "sets" ];
		$setts = array( "set" => "", "column" => "", "value" => "" );
		$columns = array();
		$simpleSets = array_filter( $sets, function( $item ) { return !is_array( $item ); });
		$sets = array_values( array_filter( $sets, "is_array" ) );

		# Tratar configuração de campos simples.
		foreach( $simpleSets as $i => $set )
			$sets[] = array( "set" => $i, "column" => "", "value" => $set );

		# Listar todos os campos para atualização.
		foreach( $sets as $i => $set ) {
			if( is_array( $set ) ) {
				$setItem = $this->utils->arrayKeyHandler( $set );

				# Tratamento para colunas simples informadas como chave/valor: array( "column" => "valor" ).
				if( !isset( $setItem[ "set" ] ) && count( $setItem ) === 1 )
					$setItem = array( "set" => array_keys( $set )[0], "value" => array_values( $set )[0], "column" => "" );

				$set = array_merge( $setts, $setItem );

				# Colunas do número de linha não devem ser atualizadas e remover chave primária da lista de colunas para atualização.
				if( in_array( strtolower( trim( $set[ "set" ] ) ), array( "rownumber", strtolower( $this->setts[ "key" ] ) ) ) )
					continue;

				if( !empty( $set[ "column" ] ) ) {
					$value = $set[ "column" ];
				}else{
					$value = ":SET_SQ_" . ( (int) $i + 1 );

					if( !is_array( $set[ "value" ] ) && strtoupper( trim( $set[ "value" ] ) ) === "NULL" )
						$set[ "value" ] = null;
					
					$this->fields[ ( $value ) ] = $set[ "value" ];
				}

				$columns[] = ( $set[ "set" ] . " = " . $value );
			}
		}

		return $columns;
	}

	/* Override */
	protected function execute() {
		$conn = $this->queryConnection;
		$conn->prepare( $this->query, $this->fields );

		if( $conn->hasError() ) {
			$this->error = $conn->getError();
			$this->logger->setMessage( gettext( "Não foi possível concluir a rotina de atualização." ), $this->error );
		}
	}

	/* Override */
	protected function getAllowedDML() {
		return "UPDATE";
	}
}