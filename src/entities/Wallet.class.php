<?php
/**
 * Entidade para a tabela de carteiras.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Entities;

class Wallet extends Entity {
	CONST KEY_NAME = "wallet_id";

	var $wallet_id;
	var $wallet_person_id;
	var $balance;
	var $wallet_creation;

	public function setWalletId( $wallet_id ) {
		$this->wallet_id = $wallet_id;
	}

	public function getWalletId() {
		return $this->wallet_id;
	}

	public function setWalletPersonId( $wallet_person_id ) {
		$this->wallet_person_id = $wallet_person_id;
	}

	public function getWalletPersonId() {
		return $this->wallet_person_id;
	}

	public function setBalance( $balance ) {
		$this->balance = $balance;
	}

	public function getBalance() {
		return $this->balance;
	}

	public function setWalletCreation( $wallet_creation ) {
		$this->wallet_creation = $wallet_creation;
	}

	public function getWalletCreation() {
		return $this->wallet_creation;
	}
}