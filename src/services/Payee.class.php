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
use \Src\Db\Query as Query;

class Payee {
 	/**
	 * Obter listagem de usários.
	 * @param  array $request Objeto da requisição.
	 * @return array
	 */
	public function getPayees( $request ) {
		try{
			$query = new Query\Select( array(
				"table"			=> "person",
				"key"			=> "personId",
				"fields"		=> array( "personId", "fullname", "username", "type" ),
				"joins"			=> array(
					array( "table" => "user", "meta_query" => array( array( "key" => "userPersonId", "column" => "personId" ) ) )
				),
				"meta_query"	=> array( array( "key" => "personId", "compare" => "!=", "value" => $request[ "personid" ] ) ),
				"order_by" 		=> "fullname desc"
			));

			if( $query->hasError() )
				$list = array();
			else
				$list = $query->getAllResults();
		}catch( \Exception $e ) {
			$list = array();
		}

		return array( "status" => "success", "message" => "", "results" => $list );
	}
}