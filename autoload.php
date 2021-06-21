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
	$path = __DIR__ . str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, "/" . $class );
	$path = strtolower( dirname( $path ) ) . DIRECTORY_SEPARATOR . basename( $path );

	if( file_exists( $path . ".class.php" ) )
		require_once( $path . ".class.php" );
	elseif( file_exists( $path . ".interface.php" ) )
		require_once( $path . ".interface.php" );
	else
		throw new Exception( "Required class {$class} does not exist. Please contact your system administrator." );
});