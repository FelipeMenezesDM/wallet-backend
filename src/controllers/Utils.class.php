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
	public function arrayKeyHandler( $array, $case = CASE_LOWER ) {
		$array = array_change_key_case( $array, $case );
		return array_combine( array_map( "trim", array_keys( $array ) ), $array );
	}

	/**
	 * Mesclar dois arrays.
	 * @param  array $array1 Array base.
	 * @param  array $array2 Array definitivo.
	 * @return array
	 */
	public function arrayMerge( $array1, $array2 ) {
		$array1 = $this->arrayKeyHandler( $array1 );
		$array2 = $this->arrayKeyHandler( $array2 );

		return array_merge( $array1, $array2 );
	}

	/**
	 * Mesclar dois arrays com case sentive para chaves.
	 * @param  array $array1 Array base.
	 * @param  array $array2 Array definitivo.
	 * @return array
	 */
	public function arrayMergeCaseSensitive( $array1, $array2 ) {
		return array_merge( $array1, $array2 );
	}

	/**
	 * Gerador de UUIDs.
	 * @return string
	 */
	public function getUuid() {
		return md5( uniqid( rand(), true ) );
	}
}