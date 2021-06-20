<?php
/**
 * Verificar requisição autenticada.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

class Auth {
	/**
	 * Verifica se as credenciais são válidas.
	 * @param  arrau   $request Objeto da requisição.
	 * @return boolean
	 */
	public function isAuth( $request ) {
		$clientSecret = $clientId = "";

		if( isset( $request[ "client_id" ] ) )
			$clientId = $request[ "client_id" ];

		if( isset( $request[ "client_secret" ] ) )
			$clientSecret = $request[ "client_secret" ];

		$scope = OKTASCOPE;
		$issuer = OKTAISSUER;

		$uri = $issuer . "/v1/token";
		$token = base64_encode( "$clientId:$clientSecret" );
		$payload = http_build_query([ "grant_type" => "client_credentials", "scope" => $scope ]);

		$curCh = curl_init();
		curl_setopt( $curCh, CURLOPT_URL, $uri );
		curl_setopt( $curCh, CURLOPT_HTTPHEADER, [
			"Content-Type: application/x-www-form-urlencoded",
			"Authorization: Basic $token"
		]);
		curl_setopt( $curCh, CURLOPT_POST, 1 );
		curl_setopt( $curCh, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $curCh, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec($curCh);
		$response = json_decode($response, true);

		if( !isset($response[ "access_token" ] ) || !isset( $response[ "token_type" ] ) )
			return false;

    	return $response[ "token_type" ] . " " . $response[ "access_token" ];
	}
}