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
	const KEY_NAME = "user_id";

	const JOINS = array(
		array(
			"table"			=> "person",
			"type"			=> "inner",
			"meta_query"	=> array( array( "key" => "user_person_id", "column" => "person_id" ) )
		)
	);

	var $user_id;
	var $username;
	var $password;
	var $user_person_id;
	var $user_creation;

	public function setUserId( $user_id ) {
		$this->user_id = $user_id;
	}

	public function getUserId() {
		return $this->user_id;
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

	public function setUserPersonId( $user_person_id ) {
		$this->user_person_id = $user_person_id;
	}

	public function getUserPersonId() {
		return $this->user_person_id;
	}

	public function setUserCreation( $user_creation ) {
		$this->user_creation = $user_creation;
	}

	public function getUserCreation() {
		return $this->user_creation;
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
}