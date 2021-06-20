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
	CONST KEY_NAME = "payment_id";

	var $payment_id;
	var $payer;
	var $payee;
	var $value;
	var $payment_creation;

	public function setPaymentId( $paymentId ) {
		$this->payment_id = $paymentId;
	}

	public function getPaymentId() {
		return $this->payment_id;
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
		$this->payment_creation = $paymentCreation;
	}

	public function getPaymentCreation() {
		return $this->payment_creation;
	}
}