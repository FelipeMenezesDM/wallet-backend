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
	const KEY_NAME = "person_id";

	var $person_id;
	var $fullname;
	var $email;
	var $type;
	var $cpf_cnpj;
	var $person_creation;

	public function setPersonId( $person_id ) {
		$this->person_id = $person_id;
	}

	public function getPersonId() {
		return $this->person_id;
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

	public function setCpfCnpj( $cpf_cnpj ) {
		$this->cpf_cnpj = $cpf_cnpj;
	}

	public function getCpfCnpj() {
		return $this->cpf_cnpj;
	}

	public function setPersonCreation( $person_creation ) {
		$this->person_creation = $person_creation;
	}

	public function getPersonCreation() {
		return $this->person_creation;
	}
}