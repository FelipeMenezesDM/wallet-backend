<?php
/**
 * Classe de funções gerais.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Controllers;

class Utils {
	/**
	 * Método estático para tratamento de chaves de array.
	 * @param  array $array Lista para tratamento.
	 * @param  array $case  Transformação do texto da chave.
	 * @return array
	 */
	public static function arrayKeyHandler( $array, $case = CASE_LOWER ) {
		$array = array_change_key_case( $array, $case );
		return array_combine( array_map( "trim", array_keys( $array ) ), $array );
	}
}