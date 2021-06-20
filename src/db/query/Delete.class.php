<?php
/**
 * Controlador de remoções, que poderá será usado para
 * montagem automática de instruções baseadas em uma lista de
 * configurações.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Db\Query;

class Delete extends \Src\Db\Controller {
	/**
	 * Atributo de configurações do objeto.
	 * @access protected
	 * @var    array
	 */
	protected $setts = array(
		"table"			=> "",
		"using"			=> array(),
		"meta_query"	=> array(),
		"meta_queries"	=> array(),
		"unaccent"		=> true
	);

	/* Override */
	protected function getAutoQuery() {
		$conn = $this->queryConnection;
		$table = $this->handlerTable( $this->setts[ "table" ] );
		$joins = $this->getRelatedTables();
		$setts = $this->setts;

		# Incluir meta query individual à lista de meta queries para facilitar montagem.
		if( !empty( $setts[ "meta_query" ] ) )
			array_unshift( $setts[ "meta_queries" ], $setts[ "meta_query" ] );

		# Obter estrutura condicional.
		$queries = $this->getMetaQueries( $setts[ "meta_queries" ] );
		$tableName = $table[ "schema" ] . "." . $table[ "name" ];

		if( !empty( $queries ) )
			$queries = " WHERE " . $queries;

		return $conn->getDBDriver()->getDeleteStatement( $tableName, $joins, $queries );
	}

	/* Override */
	protected function getAllowedDML() {
		return "DELETE";
	}

	/* Override */
	protected function execute() {
		$conn = $this->queryConnection;
		$conn->prepare( $this->query, $this->fields );

		if( $conn->hasError() ) {
			$this->error = $conn->getError();
			$this->logger->setMessage( gettext( "Failed to finish execution of statement due to assembly failure" ), $this->error );
		}
	}
}