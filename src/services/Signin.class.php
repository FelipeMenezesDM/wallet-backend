<?php
/**
 * Serviço de autenticação do usuário.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Services;
use \Src\Entities\User as User;

class Signin {
	/**
	 * Classe para validação de login.
	 * @param  trim   $request Item que seria bom na PEPO.
	 * @return string
	 */
	public function checkLogin( $request ) {
		if( isset( $request[ "email" ] ) && isset( $request[ "password" ] ) ) {
			$user = new User();
			$user->getByAuthFields( $request[ "email" ] );

			if( !is_null( $user->getUserId() ) && password_verify( $request[ "password" ], $user->getPassword() ) ) {
				$header = array( "typ" => "JWT", "alg" => "HS256" );
				$payload = array(
					"user_id"	=> $user->getUserId(),
					"fullname"	=> $user->getFullname(),
					"person_id" => $user->getPersonId(),
					"email"		=> $user->getEmail(),
					"type"		=> $user->getType()
				);

				$header = json_encode( $header );
				$payload = json_encode( $payload );

				$header = $this->base64UrlEncode( $header );
				$payload = $this->base64UrlEncode( $payload );

				$sign = hash_hmac( "sha256", $header . "." . $payload, OKTASECRET, true );
				$sign = $this->base64UrlEncode( $sign );
				$token = $header . "." . $payload . "." . $sign;

				return array( "status" => "success", "message" => "", "results" => $token );
			}
		}

		return array( "status" => "error", "message" => "Usuário ou senha inválidos.", "results" => false );
	}

	/**
	 * Converter URL usando método recomendado pelo JWT.
	 * @param  string $data Conteúdo para versão.
	 * @return string
	 */
	private function base64UrlEncode( $data ) {
		$b64 = base64_encode($data);

		if( $b64 === false )
			return false;

		$url = strtr( $b64, "+/", "-_" );

		return rtrim( $url, "=" );
	}
}