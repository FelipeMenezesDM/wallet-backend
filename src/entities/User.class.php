<?php
/**
 * Entidade para a tabela de usuários.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Entities;

class User extends Person {
	const KEY_NAME = "userId";

	const JOINS = array(
		array(
			"table"			=> "person",
			"type"			=> "inner",
			"meta_query"	=> array( array( "key" => "userPersonId", "column" => "personId" ) )
		)
	);

	var $userId;
	var $username;
	var $password;
	var $userPersonId;
	var $userCreation;

	public function setUserId( $userId ) {
		$this->userId = $userId;
	}

	public function getUserId() {
		return $this->userId;
	}

	public function setUsername( $username ) {
		$this->username = $username;
	}

	public function getUsername() {
		return $this->username;
	}

	public function setPassword( $password ) {
		$this->password = $password;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setUserPersonId( $userIersonId ) {
		$this->userPersonId = $userIersonId;
	}

	public function getUserPersonId() {
		return $this->userPersonId;
	}

	public function setUserCreation( $userCreation ) {
		$this->userCreation = $userCreation;
	}

	public function getUserCreation() {
		return $this->userCreation;
	}

	/**
	 * Obter usuário a partir dos campos de autenticação: e-mail e nome de usuário.
	 * @param  string  $value Valor para comparação.
	 * @return boolean
	 */
	public function getByAuthFields( $value ) {
		$metaQuery = array( array(
			"key"	=> "username",
			"value"	=> $value
		), array(
			"key"		=> "email",
			"value"		=> $value,
			"relation"	=> "OR"
		) );

		return $this->get( array( "meta_query" => $metaQuery ) );
	}

	/**
	 * Obter usuário a partir do seu nome de usuário.
	 * @param  string  $cpfCnpj Número de CPF ou CNPJ para busca.
	 * @return boolean
	 */
	public function getByUsername( $username ) {
		return $this->get( array( "meta_query" => array( array( "key" => "username", "value" => $username ) ) ) );
	}
}