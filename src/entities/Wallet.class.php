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

class Wallet extends Person {
	CONST KEY_NAME = "walletId";
	CONST JOINS = array(
		array(
			"table" 		=> "person",
			"meta_query" 	=> array(
				array(
					"key"		=> "walletPersonId",
					"column"	=> "personId"
				)
			)
		)
	);

	var $walletId;
	var $walletPersonId;
	var $balance;
	var $walletCreation;

	public function setWalletId( $walletId ) {
		$this->walletId = $walletId;
	}

	public function getWalletId() {
		return $this->walletId;
	}

	public function setWalletPersonId( $walletPersonId ) {
		$this->walletPersonId = $walletPersonId;
	}

	public function getWalletPersonId() {
		return $this->walletPersonId;
	}

	public function setBalance( $balance ) {
		$this->balance = $balance;
	}

	public function getBalance() {
		return $this->balance;
	}

	public function setWalletCreation( $walletCreation ) {
		$this->walletCreation = $walletCreation;
	}

	public function getWalletCreation() {
		return $this->walletCreation;
	}

	/**
	 * Obter pessoa a partir do seu ID.
	 * @param  string  $personId ID da pessoa.
	 * @return boolean
	 */
	public function getByPersonId( $personId ) {
		return $this->get( array( "meta_query" => array( array( "key" => "walletPersonId", "value" => $personId ) ) ) );
	}
}