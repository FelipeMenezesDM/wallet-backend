<?php
/**
 * Entidade para a tabela de histórico de pagamentos.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Entities;

class Payment extends Entity {
	CONST KEY_NAME = "paymentId";

	var $paymentId;
	var $payer;
	var $payee;
	var $value;
	var $paymentCreation;

	public function setPaymentId( $paymentId ) {
		$this->paymentId = $paymentId;
	}

	public function getPaymentId() {
		return $this->paymentId;
	}

	public function setPayer( $payer ) {
		$this->payer = $payer;
	}

	public function getPayer() {
		return $this->payer;
	}

	public function setPayee( $payee ) {
		$this->payee = $payee;
	}

	public function getPayee() {
		return $this->payee;
	}

	public function setValue( $value ) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

	public function setPaymentCreation( $paymentCreation ) {
		$this->paymentCreation = $paymentCreation;
	}

	public function getPaymentCreation() {
		return $this->paymentCreation;
	}
}