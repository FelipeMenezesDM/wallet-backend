<?php
/**
 * Controlador de inserções, que poderá será usado para
 * montagem automática de instruções baseadas em uma lista de
 * configurações.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Db\Query;

class Insert extends \Src\Db\Controller {
	/**
	 * Atributo de configurações do objeto.
	 * @access protected
	 * @var    array
	 */
	protected $setts = array(
		"table"					=> "",
		"item"					=> array(),
		"items"					=> array(),
		"key"					=> "",
		"update_duplicate_key"	=> true
	);

	/**
	 * Obter o ID do último item inserido.
	 * @access private
	 * @var    integer
	 */
	private $lastInsertID = null;

	/* Override */
	protected function getAutoQuery() {
		$conn = $this->queryConnection;
		$table = $this->handlerTable( $this->setts[ "table" ] );
		$items = &$this->setts[ "items" ];
		$key = &$this->setts[ "key" ];
		$fields = &$this->fields;
		$items = $this->utils->arrayMerge( array( "columns" => array(), "records" => array() ), $items );

		# Adicionar item de inserção individual à lista de itens.
		if( !empty( $this->setts[ "item" ] ) && is_array( $this->setts[ "item" ] ) ) {
			if( empty( $items[ "columns" ] ) )
				$items[ "columns" ] = array_keys( $this->setts[ "item" ] );

			# O item deverá ser adicionado somente quando compatível.
			if( count( $this->setts[ "item" ] ) == count( $items[ "columns" ] ) )
				$items[ "records" ][] = array_values( $this->setts[ "item" ] );
		}

		# Tratar definições de itens para inserção e chave da tabela.
		$key = trim( $key );
		$items[ "columns" ] = array_values( array_map( "trim", $items[ "columns" ] ) );
		$items[ "records" ] = array_values( $items[ "records" ] );
		$fields = $records = array( "is_identity" => array(), "is_not_identity" => array() );

		# Verificar se deve atualizar o registro caso a chave já exista na base de dados.
		$keyIndex = array_search( strtolower( $key ), array_map( "strtolower", $items[ "columns" ] ) );
		$isIdentity = $keyIndex !== false;
		$updateDuplicateKey = ( $this->setts[ "update_duplicate_key" ] && $isIdentity );

		# Listar registros para inserção.
		foreach( $items[ "records" ] as $recordID => $record ) {
			# Remover item inconsistente da lista de registros.
			if( !is_array( $record ) || count( $record ) != count( $items[ "columns" ] ) ) {
				unset( $items[ "records" ][ ( $recordID ) ] );
				continue;
			}

			# Tratamento para registros definidos para inserção.
			$record = array_values( $record );
			$items[ "records" ][ ( $recordID ) ] = $record;

			# Listar campos dos registros.
			foreach( $record as $id => $field ) {
				$value = ":VAL_SQ_${recordID}_${id}";
				$column = $items[ "columns" ][ ( $id ) ];

				if( !is_array( $field ) && strtoupper( trim( $field ) ) === "NULL" )
					$field = null;

				# Separar conteúdo com e sem inserção de identidade.
				if( $isIdentity ) {
					$fields[ "is_identity" ][ ( $value ) ] = $field;
					$records[ "is_identity" ][ ( $recordID ) ][] = $value;
				}elseif( $id !== $keyIndex ) {
					$fields[ "is_not_identity" ][ ( $value ) ] = $field;
					$records[ "is_not_identity" ][ ( $recordID ) ][] = $value;
				}
			}

			# Em caso de inserção de identidade, armazenar o ID do último registro.
			if( $isIdentity )
				$this->lastInsertID = $record[ ( $keyIndex ) ];
		}

		# Remover chave primária da lista de colunas.
		$columnsNoPK = $items[ "columns" ];
		$tableName = $table[ "schema" ] . "." . $table[ "name" ];

		if( $isIdentity )
			unset( $columnsNoPK[ ( $keyIndex ) ] );

		return array(
			"is_identity" => $conn->getDBDriver()->getInsertStatement( $tableName, $items[ "columns" ], $records[ "is_identity" ], $updateDuplicateKey, $key ),
			"is_not_identity" => $conn->getDBDriver()->getInsertStatement( $tableName, $columnsNoPK, $records[ "is_not_identity" ], false )
		);
	}

	/* Override */
	protected function execute() {
		$conn = $this->queryConnection;

		if( !$conn->getPDOConnection()->inTransaction() )
			$conn->beginTransaction();
		
		$lastInsertID = null;

		# Listar e executar instruções com e sem inserção de identidade.
		foreach( $this->fields as $type => $fields ) {
			if( empty( $fields ) )
				continue;

			$stmt = $conn->prepare( $this->query[ ( $type ) ], $fields );

			if( $conn->hasError() ) {
				$this->error = $conn->getError();
			}else{
				if( $conn->isAutocommit() )
					$conn->commit();

				// $lastInsertID = $conn->getPDOConnection()->lastInsertID();

				# Obter chave do último registro inserido, em caso de inserção de identidade.
				if( !$lastInsertID )
					$lastInsertID = $this->lastInsertID;
			}

			# Parar a execução da após falha.
			if( $this->error )
				break;
		}

		if( !$this->error ) {
			$this->lastInsertID = $lastInsertID;
		}else{
			\Src\Controllers\Logger::setMessage( gettext( "Não foi possível inserir o registro na base de dados." ), $this->error );

			if( $conn->isAutocommit() )
				$conn->rollback();

			$this->lastInsertID = null;
		}
	}

	/* Override */
	protected function getAllowedDML() {
		return "INSERT";
	}

	/**
	 * Obter o ID do último item inserido.
	 * @return integer
	 */
	public function getLastInsertID() {
		return $this->lastInsertID;
	}
}