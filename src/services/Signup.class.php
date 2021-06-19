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
	 * Executar inserção do registro.
	 * @param  array   $request Objeto da requisoção.
	 * @return boolean
	 */
	public function register( $request ) {
		$person = new \Src\Entities\Person();

		if( $person->getByEmail( $request[ "email" ] ) ) {
			return $this->error( "O e-mail informado já está em uso no sistema." );
		}elseif( $person->getByCpfCnpj( $request[ "cpf_cnpj" ] ) ) {
			return $this->error( "O CPF/CNPJ informado já está em uso no sistema." );
		}

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

		if( $user->getByUsername( $request[ "username" ] ) ) {
			return $this->error( "O nome de usuário informado já está em uso no sistema." );
		}

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

		if( !( $person->hasError() || $user->hasError() || $wallet->hasError() ) )
			return $this->success( "Usuário cadastrado com sucesso." );

		return $this->error( "Não foi possível finalizar o cadastro." );
	}

	/**
	 * Gerar UUID do registro.
	 * @return string
	 */
	private function getUuid() {
		return \Src\Controllers\Utils::getUuid();
	}

	/**
	 * Retornar informações de erro do sistema.
	 * @param  string $msg     Mensagem de erro.
	 * @param  array  $results Lista de retorno.
	 * @return arrau
	 */
	private function error( $msg = "", $results = array() ) {
		return array( "status" => "error", "message" => $msg, "results" => $results );
	}

	/**
	 * Retornar informações do sistema.
	 * @param  string $msg     Mensagem de retorno.
	 * @param  array  $results Lista de retorno.
	 * @return arrau
	 */
	private function success( $msg = "", $results = array() ) {
		return array( "status" => "success", "message" => $msg, "results" => $results );
	}
}