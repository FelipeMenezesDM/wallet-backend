<?php
/**
 * Serviço para consulta de destinatários.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Services;

class Payee implements Service {
	private $results;

	/**
	 * Método construtor.
	 * @param string $request Objeto da requisição.
	 */
	public function __construct( $request ) {
		$this->results = $this->getPayees( $request );
	}

	/**
	 * Obter resultados da consulta.
	 * @return array
	 */
	public function getResults() {
		return $this->results;
	}

	/**
	 * Obter listagem de 
	 * @param  array $request Objeto da requisição.
	 * @return array
	 */
	private function getPayees( $request ) {
		try{
			$query = new \Src\Db\query\Select( array(
				"table"			=> "person",
				"key"			=> "person_id",
				"fields"		=> array( "person_id", "fullname", "username" ),
				"joins"			=> array(
					array( "table" => "user", "meta_query" => array( array( "key" => "user_person_id", "column" => "person_id" ) ) )
				)
			));

			if( $query->hasError() )
				return array();

			return $query->getAllResults();
		}catch(\Exception $e) {
			return array();
		}
	}
}