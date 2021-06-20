<?php
/**
 * Classe abstrata das entidades.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Entities;

abstract class Entity {
	/**
	 * Identificador da chave da tabela.
	 */
	const KEY_NAME = self::KEY_NAME;

	/* Override */
	const JOINS = array();

	/**
	 * Objeto de resultados de consultas.
	 * @access private
	 * @var    null
	 */
	private $object = null;

	/**
	 * Armazenar retorno com erro.
	 * @access private
	 * @var    null
	 */
	private $error = null;

	/**
	 * Objeto de conexão específico.
	 * @access private
	 * @var    null
	 */
	private $connection = null;

	/**
	 * Consultar registros com configurações livres.
	 * @param  array   $request Objeto da requisição.
	 * @return boolean
	 */
	public function get( $request = array() ) {
		$request[ "table" ] = $this->getTableName();
		$request[ "key" ] = static::KEY_NAME;

		if( !isset( $request[ "joins" ] ) )
			$request[ "joins" ] = static::JOINS;

		$query = new \Src\Db\Query\Select( $request, null, $this->connection );
		$this->error = $query->getError();

		if( !$query->hasError() && $query->getRowsCount() > 0 ) {
			$this->object = $query;
			$this->next();
			return true;
		}

		return false;
	}

	/**
	 * Obter registros a partir do ID.
	 * @param  string  $id ID para consulta.
	 * @return booleam
	 */
	public function getById( $id ) {
		return $this->get( array( "meta_query" => array( array( "key" => static::KEY_NAME, "value" => $id ) ) ) );
	}

	/**
	 * Inserir registros na base de dados.
	 * @param  array   $request Objeto da reqisição.
	 * @return boolean
	 */
	public function post( $request = array() ) {
		$request[ "table" ] = $this->getTableName();
		$request[ "key" ] = static::KEY_NAME;
		$props = $this->getProps();
		$item = array();

		foreach( $props as $prop ) {
			$value = $this->getPropValue( $prop );

			if( !is_null( $value ) )
				$item[ ( $prop ) ] = ( $value === "NULL" ? null : $value );
		}

		$request[ "item" ] = $item;
		$query = new \Src\Db\Query\Insert( $request, null, $this->connection );
		$this->error = $query->getError();

		if( !$query->hasError() ) {
			if( (boolean) $query->getLastInsertID() )
				$this->getById( $query->getLastInsertID() );

			return $query->getLastInsertID();
		}

		return false;
	}

	/**
	 * Atualização de registro na base.
	 * @param  array   $request Objeto da requisição.
	 * @return boolean
	 */
	public function put() {
		$request = array();
		$props = $this->getProps();
		$sets = array();
		$metaQuery = array();

		foreach( $props as $prop ) {
			if( strtolower( $prop ) === strtolower( static::KEY_NAME ) ) {
				$metaQuery[] = array( "key" => $prop, "value" => $this->getPropValue( $prop ) );
			}else{
				$value = $this->getPropValue( $prop );

				if( !is_null( $value ) )
					$sets[ ( $prop ) ] = ( $value === "NULL" ? null : $value );
			}
		}

		$request[ "table" ] = $this->getTableName();
		$request[ "key" ] = static::KEY_NAME;
		$request[ "sets" ] = $sets;
		$request[ "meta_query" ] = $metaQuery;
		$query = new \Src\Db\Query\Update( $request, null, $this->connection );
		$this->error = $query->getError();

		if( !$query->hasError() )
			return true;

		return false;
	}


	/**
	 * Remover registro da base.
	 * @return boolean
	 */
	public function delete() {
		$request = array(
			"table" => $this->getTableName(),
			"key" => static::KEY_NAME,
			"meta_query" => array( array( "key" => static::KEY_NAME, "value" => $this->getPropValue( static::KEY_NAME ) ) )
		);

		$query = new \Src\Db\Query\Delete( $request, null, $this->connection );
		$this->error = $query->getError();

		if( !$query->hasError() )
			return true;

		return false;
	}

	/**
	 * Carregar valor das propriedades da classe.
	 * @return void
	 */
	private function loadProps( $load ) {
		$props = $this->getProps( true );

		foreach( $props as $prop ) {
			$this->setPropValue( $prop, null );
		}

		foreach( $load as $name => $value ) {
			$this->setPropValue( $name, $value );
		}
	}

	/**
	 * Obter próximo registro da consulta.
	 * @return boolean
	 */
	public function next() {
		if( !is_null( $this->object ) && $load = $this->object->getResults() ) {
			$this->loadProps( $load );
			return true;
		}

		return false;
	}

	/**
	 * Obter as propriedades da entidade.
	 * @return array
	 */
	public function getProps( $includeParentProps = false ) {
		$vars = array_keys( get_class_vars( get_called_class() ) );
		$parent = get_parent_class( $this );
		$return = array();

		foreach( $vars as $var ) {
			if( property_exists( get_called_class(), $var ) && ( !$parent || $includeParentProps || !property_exists( $parent, $var ) ) ) {
				$return[] = $var;
			}
		}

		return $return;
	}

	/**
	 * Obter valor de uma propriedade da entidade.
	 * @param  string $prop Identificador da propriedade.
	 * @return string
	 */
	public function getPropValue( $prop ) {
		if( method_exists( $this, "get" . $prop ) ) {
			return call_user_func( array( $this, "get" . $prop ) );
		}

		return null;
	}

	/**
	 * Definir valor de uma propriedade da entidade.
	 * @param  string $prop  Identificador da propriedade.
	 * @param  string $value Novo valor para a propriedade.
	 * @return string
	 */
	private function setPropValue( $prop, $value ) {
		if( method_exists( $this, "set" . $prop ) ) {
			call_user_func_array( array( $this, "set" . $prop ), array( $value ) );
		}
	}

	/**
	 * Obter o nome da tabela a partir da classe.
	 * @return string
	 */
	public function getTableName() {
		return basename( str_replace( "\\", "/", get_called_class() ) );
	}

	/**
	 * Obter erro da entidade.
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Verificar se existe na entidade.
	 * @return boolean
	 */
	public function hasError() {
		return (boolean) $this->error;
	}

	/**
	 * Definir um objeto de conexão para as transações da entidade.
	 * @param object $connection Objeto de conexão do PDO.
	 */
	public function setConnection( $connection ) {
		$this->connection = $connection;
	}
}