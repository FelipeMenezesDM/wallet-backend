<?php
/**
 * Autoload de classes do projeto.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

spl_autoload_register( function( $class ) {
	# Definir pacote da classe.
	$path = __DIR__ . str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, "/" . $class ) . ".class.php";

	if( file_exists( $path ) )
		require_once( $path );
	else
		throw new Exception( "Required class {$class} does not exist. Please contact your system administrator." );
});