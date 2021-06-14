<?php
/**
 * Controlador de logs do sistema.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

class Logger {
	/**
	 * Constantes de identificação de tipos de mensagem no console.
	 */
	const LOG_ERROR = 1;
	const LOG_WARNING = 2;
	const LOG_INFO = 3;
	const LOG_TYPES = array( 1 => "ERROR", 2 => "WARNING", 3 => "INFO" );

	/**
	 * Contador de tempo de execução das instruções.
	 * @access private
	 * @var    integer
	 */
	private $execTimeCounter = 0;

	/**
	 * Criar e exibir mensagem de erro no log do servidor.
	 * @param string $msg       Mensagem para exibição.
	 * @param string $serverMsg Mensagem reservada do servidor.
	 */
	public static function setDisplayMessage( $msg = "", $serverMsg = null ) {
		$backTrace = array_reverse( debug_backtrace() )[0];
		
		self::setLogMessage( $msg, $serverMsg );
		throw new Exception( $msg . " " . sprintf( gettext( "in %s on line %s" ), $backTrace[ "file" ], $backTrace[ "line" ] ) );
	}

	/**
	 * Criar registro de log simples em método estático.
	 * @param  string  $arg1 ... $argN-1 Mensagem para impressão no log.
	 * @param  integer $argN             Tipo de mensagem: 1 ERROR, 2 WARNING, 3 INFO.
	 * @return void
	 */
	public static function setLogMessage() {
		error_log( implode( " ", call_user_func_array( "self::getLogLine", func_get_args() ) ) );
	}

	/**
	 * Criar registro de log com contagem de tempo após execução de instruções.
	 * @param  string  $arg1 ... $argN-1 Mensagem para impressão no log.
	 * @param  integer $argN             Tipo de mensagem: 1 ERROR, 2 WARNING, 3 INFO.
	 * @return void
	 */
	public function setMessage() {
		$time = $this->execTimeCounter;
		$hours = str_pad( floor( $time / 3600 ), 2, "0", STR_PAD_LEFT );
		$minutes = str_pad( floor( ( $time - ( $hours * 3600 ) ) / 60 ), 2, "0", STR_PAD_LEFT );
		$seconds = str_pad( floor( $time % 60 ), 2, "0" ) . substr( ( $time - (int) $time ), 1, 5 );

		$logLine = call_user_func_array( "self::getLogLine", func_get_args() );
		array_splice( $logLine, 2, 0, array( "[C=${hours}:${minutes}:${seconds}]" ) );
		error_log( implode( " ", $logLine ) );
	}

	/**
	 * Criar linha de mensagem para o log do servidor.
	 * @access private
	 * @param  string  $arg1 ... $argN-1 Mensagem para impressão no log.
	 * @param  integer $argN             Tipo de mensagem: 1 ERROR, 2 WARNING, 3 INFO.
	 * @return string
	 */
	private static function getLogLine() {
		$args = func_get_args();
		$types = self::LOG_TYPES;
		$type = $types[ ( self::LOG_ERROR ) ];

		# Validar tipo de mensagem de log.
		if( !empty( $args ) && is_int( $args[ ( func_num_args() - 1 ) ] ) )
			$type = $types[ min( max( array_pop( $args ), min( array_keys( $types ) ) ), max( array_keys( $types ) ) ) ];

		$date = date( "Y-m-d H:i:s" );
		$logLine = array( "[T=${type}]", "[D=${date}]" );

		# Listar mensagens de log.
		foreach( $args as $msg ) {
			if( !empty( trim( $msg ) ) )
				$logLine[] = "[I=" . trim( preg_replace( "/[\[\]\r\n]+/", " ", $msg ) ) . "]";
		}

		$backTrace = array_reverse( debug_backtrace() )[0];
		$logLine[] = "[F=" . $backTrace[ "file" ] . "]";
		$logLine[] = "[L=" . $backTrace[ "line" ] . "]";

		# Informações adicionais do log.
		if( !empty( $backTrace[ "function" ] ) ) {
			$logLine[] = "[M=" . $backTrace[ "function" ] . "()]";

			if( !empty( $backTrace[ "class" ] ) )
				$logLine[] = "[E=" . $backTrace[ "class" ] . "]";
		}

		return $logLine;
	}
}