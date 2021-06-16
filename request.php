<?php
/**
 * Verificar se há requisição para uso da API.
 * @return array
 */
if( !function_exists( "apiRequest" ) ) :
function apiRequest() {
	$request = $_REQUEST;

	# Friendly URL sem rewrite.
	if( isset( $_SERVER[ "PATH_INFO" ] ) ) {
		$info = array_values( array_filter( explode( "/", $_SERVER[ "PATH_INFO" ] ) ) );
		$request = array( ( $info[0] ) => "" );

		if( isset( $info[1] ) )
			$request[ "version" ] = $info[1];

		if( isset( $info[2] ) )
			$request[ "type" ] = $info[2];

		if( isset( $info[3] ) )
			$request[ "object" ] = $info[3];
	}

	if( isset( $request[ "api" ] ) )
		return $request;

	return false;
}
endif;

if( $apiRequest = apiRequest() ) {
	new \Src\Controllers\Request( $apiRequest );
	exit;
}