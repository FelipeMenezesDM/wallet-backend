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

	public function setPerson_Id( $person_id ) {
		$this->person_id = $person_id;
	}

	public function getPerson_Id() {
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

	public function setCpf_Cnpj( $cpf_cnpj ) {
		$this->cpf_cnpj = $cpf_cnpj;
	}

	public function getCpf_Cnpj() {
		return $this->cpf_cnpj;
	}

	public function setPerson_Creation( $person_creation ) {
		$this->person_creation = $person_creation;
	}

	public function getPerson_creation() {
		return $this->person_creation;
	}
}