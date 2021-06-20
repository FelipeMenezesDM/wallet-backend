<?php
/**
 * Entidade para a tabela de pessoas.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Entities;

class Person extends Entity {
	const KEY_NAME = "personId";

	var $personId;
	var $fullname;
	var $email;
	var $type;
	var $cpfCnpj;
	var $personCreation;

	public function setPersonId( $personId ) {
		$this->personId = $personId;
	}

	public function getPersonId() {
		return $this->personId;
	}

	public function setFullname( $fullname ) {
		$this->fullname = $fullname;
	}

	public function getFullname() {
		return $this->fullname;
	}

	public function setEmail( $email ) {
		$this->email = $email;
	}

	public function getEmail() {
		return $this->email;
	}

	public function setType( $type ) {
		$this->type = $type;
	}

	public function getType() {
		return $this->type;
	}

	public function setCpfCnpj( $cpfCnpj ) {
		$this->cpfCnpj = $cpfCnpj;
	}

	public function getCpfCnpj() {
		return $this->cpfCnpj;
	}

	public function setPersonCreation( $personCreation ) {
		$this->personCreation = $personCreation;
	}

	public function getPersonCreation() {
		return $this->personCreation;
	}

	/**
	 * Obter pessoa a partir do seu email.
	 * @param  string  $email Endereço de e-mail para busca.
	 * @return boolean
	 */
	public function getByEmail( $email ) {
		return $this->get( array( "meta_query" => array( array( "key" => "email", "value" => $email ) ) ) );
	}

	/**
	 * Obter pessoa a partir do seu CPF/CNPJ.
	 * @param  string  $cpfCnpj Número de CPF ou CNPJ para busca.
	 * @return boolean
	 */
	public function getByCpfCnpj( $cpfCnpj ) {
		return $this->get( array( "meta_query" => array( array( "key" => "cpf_cnpj", "value" => $cpfCnpj ) ) ) );
	}
}