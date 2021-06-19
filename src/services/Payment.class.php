<?php
/**
 * Serviço para validação de transação financeira.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Services;

class Payment {
	/**
	 * Constantes auxiliares.
	 */
	CONST MOCKY_URI_TRANSACTION = "https://run.mocky.io/v3/8fafdd68-a090-496f-8c9a-3442cf30dae6";
	CONST MOCKY_URI_NOTIFICATION = "http://o4d9z.mocklab.io/notify";

	/**
	 * Respostas de requisição da API.
	 * @access private
	 * @var    array
	 */
	private $response = array(
		"status"	=> "error",
		"message"	=> "",
		"results"	=> array()
	);

	/**
	 * Validar transação financeira.
	 * @param  $request Objeto da requisição.
	 * @return boolean
	 */
	public function validatePayment( $request ) {
		if( !isset( $request[ "payer" ] ) || !isset( $request[ "payee" ] ) || !isset( $request[ "value" ] ) ) {
			$this->response[ "message" ] = gettext( "Pagamento recusado, pois a requisição não possuí parâmetros suficientes." );
			return $this->response;
		}

		try{
			$value = (float) $this->handlerPaymentValue( $value );
		}catch( \Exception $e ) {
			$this->response[ "message" ] = gettext( "O valor informado está em um formato inválido." );;
			return $this->resonse;
		}

		$conn = new \Src\Controllers\Connect();
		$conn->connect();
		$conn->beginTransaction();

		$payer = new \Src\Entities\Wallet();
		$payer->setConnection( $connect );
		$payer->getByPersonId( $request[ "payer" ] );

		$payee = new \Src\Entities\Wallet();
		$payee->setConnection( $connect );
		$payee->getByPersonId( $request[ "payee" ] );

		if(
			! $this->validateUserData( $payer, $payee, $value ) &&
			! $this->validateTransaction( $payer, $payee, $value ) &&
			! $this->validateTransactionHistory( $payer, $payee, $value, $conn ) &&
			! $this->validateMocky()
		) {
			$conn->commit();
			$this->sendNotification();
			$this->response[ "status" ] = "success";
			return $this->response;
		}else{
			$conn->rollBack();
			return $this->response;
		}
	}

	/**
	 * Validar informações dos usuários envolvidos na transação.
	 * @access private
	 * @param  object  $payer Objeto do pagador.
	 * @param  object  $payee Objeto do receptor do pagamento.
	 * @param  float   $value Valor pago.
	 * @return string
	 */
	private function validateUserData( $payer, $payee, $value ) {
		$error = false;

		if( $value === 0 ) {
			$error = gettext( "O valor de pagamento informado é inválido." );
		}elseif( !$payer->getPersonId() ) {
			$error = gettext( "O usuário pagador é inválido." );
		}elseif( !$payee->getPersonId() ) {
			$error = gettext( "O usuário receber selecionado é inválido." );
		}elseif( $payer->getType() !== "F" ) {
			$error = gettext( "Essa ação não é permitida para o seu tipo de usuário." );
		}elseif( $payer->getBalance() < $value ) {
			$error = gettext( "Não há saldo sufiente para está operação." );
		}elseif( $payer->getPersonId() === $payee->getPersonId() ) {
			$error = gettext( "Este tipo de operação é inválida." );
		}

		$this->response[ "message" ] = $erro;
		return $error;
	}

	/**
	 * Validar persistência da transação.
	 * @access private
	 * @param  object  $payer Objeto do pagador.
	 * @param  object  $payee Objeto do receptor do pagamento.
	 * @param  float   $value Valor pago.
	 * @return string
	 */
	private function validateTransaction( $payer, $payee, $value ) {
		$error = false;
		$payer->setBalance( $payer->getBalance() - $value );
		$payee->setBalance( $payee->getBalance() + $value );

		if( !$payer->put() || !$payee->put() )
			$error = gettext( "Não foi possível concluir essa operação." );

		$this->response[ "message" ] = $erro;
		return $error;
	}

	/**
	 * Validar persistência do histórico de transações.
	 * @access private
	 * @param  object  $payer Objeto do pagador.
	 * @param  object  $payee Objeto do receptor do pagamento.
	 * @param  float   $value Valor pago.
	 * @param  object  $conn  Objeto da conexão com o transaction iniciado.
	 * @return string
	 */
	private function validateTransactionHistory( $payer, $payee, $value, $conn ) {
		$error = false;
		$payment = new \Src\Entities\Payment();
		$payment->setConnection( $conn );
		$payment->setPayer( $payer->getPersonId() );
		$payment->setPayee( $payee->getPersonId() );
		$payment->setValue( $value );

		if( !$payment->post() )
			$error = gettext( "Não foi possível finalizar a transação." );

		$this->response[ "message" ] = $erro;
		return $error;
	}

	/**
	 * Validar mocky externo.
	 * @access private
	 * @return boolean
	 */
	private function validateMocky() {
		$error = false;

		try{
			$response = json_decode( file_get_contents( MOCKY_URI_TRANSACTION ) );

			if( $response->message !== "Autorizado" )
				$error = gettext( "Esta transação não foi autorizada." );
		}catch( \Exception $e ) {
			$error = gettext( "Não foi possível acessar a autorização desta transação." );
		}

		$this->response[ "message" ] = $erro;
		return $error;
	}

	private function sendNotification() {
		$error = false;

		try{
			$response = json_decode( file_get_contents( MOCKY_URI_NOTIFICATION ) );

			if( $response->message !== "Sucesso" )
				$error = gettext( "Não foi possível enviar a notifacação." );
		}catch( \Exception $e ) {
			$error = gettext( "Não foi possível enviar a notifacação." );
		}

		$this->response[ "message" ] = $erro;
		return $error;
	}

	/**
	 * Tratamemto de valores com máscara.
	 * @access private
	 * @param  string  $value Valor para tratamento.
	 * @return float
	 */
	private function handlerPaymentValue( $value ) {
		return substr( ",", ".", substr( array( " ", "." ), "", $value ) );
	}
}