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

	/**
	 * Mesclar dois arrays.
	 * @param  array   $array1        Array base.
	 * @param  array   $array2        Array definitivo.
	 * @param  boolean $caseSensitive Habilitar mesclagem sem case-sensitive para as chaves.
	 * @return array
	 */
	public static function arrayMerge( $array1, $array2, $caseSensitive = false ) {
		if( !$caseSensitive ) {
			$array1 = self::arrayKeyHandler( $array1 );
			$array2 = self::arrayKeyHandler( $array2 );
		}

		return array_merge( $array1, $array2 );
	}
}