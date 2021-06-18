<?php
/**
 * Serviço de cadastro do usuário.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Services;

class Signup extends Signin {
	/**
	 * Método do construtor.
	 * @param array $request Objeto da requisição.
	 */
	public function __construct( $request ) {
		try{
			$this->register( $request );
			$this->results = $this->checkLogin( $request );
		}catch(\Exception $e) {
			$this->results = false;
		}
	}

	/**
	 * Executar inserção do registro.
	 * @param  array   $request Objeto da requisoção.
	 * @return boolean
	 */
	private function register( $request ) {
		$person = new \Src\Entities\Person();
		$person->setPersonId( $this->getUuid() );
		$person->setFullname( trim( $request[ "fullname" ] ) );
		$person->setEmail( trim( $request[ "email" ] ) );
		$person->setCpfCnpj( trim( $request[ "cpf_cnpj" ] ) );

		if( strlen( $person->getCpfCnpj() ) > 11 )
			$person->setType( "J" );
		else
			$person->setType( "F" );

		$person->post();

		$user = new \Src\Entities\User();
		$user->setUserId( $this->getUuid() );
		$user->setUsername( trim( $request[ "username" ] ) );
		$user->setUserPersonId( $person->getPersonId() );
		$user->setPassword( password_hash( trim( $request[ "password" ] ), PASSWORD_DEFAULT ) );
		$user->post();

		$wallet = new \Src\Entities\Wallet();
		$wallet->setWalletId( $this->getUuid() );
		$wallet->setWalletPersonId( $person->getPersonId() );
		$wallet->setBalance(0);
		$wallet->post();

		return !( $person->hasError() || $user->hasError() || $wallet->hasError() );
	}

	/**
	 * Gerar UUID do registro.
	 * @return string
	 */
	private function getUuid() {
		return \Src\Controllers\Utils::getUuid();
	}
}