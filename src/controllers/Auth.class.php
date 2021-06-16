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
	 * @return boolean
	 */
	public static function isAuth() {
		$clientId = getenv( "OKTACLIENTID" );
		$clientSecret = getenv( "OKTASECRET" );
		$scope = getenv( "OKTASCOPE" );
		$issuer = getenv( "OKTAISSUER" );

		$uri = $issuer . "/v1/token";
		$token = base64_encode( "$clientId:$clientSecret" );
		$payload = http_build_query([ "grant_type" => "client_credentials", "scope" => $scope ]);

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $uri );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/x-www-form-urlencoded",
			"Authorization: Basic $token"
		]);
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec($ch);
		$response = json_decode($response, true);

		if( !isset($response[ "access_token" ] ) || !isset( $response[ "token_type" ] ) )
			return false;

    	return $response[ "token_type" ] . " " . $response[ "access_token" ];
	}
}