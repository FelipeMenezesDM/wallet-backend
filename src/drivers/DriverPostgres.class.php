<?php
/**
 * Driver padronizado para PostgreSQL, com funções, scripts e atributos
 * próprios do PostgreSQL.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Drivers;

class DriverPostgres extends Driver {
	/* Override */
	public function getDNS( $host, $port, $database ) {
		if( is_null( $port ) )
			$port = 5432;

		return "pgsql:host=${host};port=${port};dbname=${database};options='--client_encoding=UTF8'";
	}

    /* Override */
    public function getScapeChar() {
        return '';
    }

    /* Override */
    public function getConcatOperator() {
        return "||";
    }

    /* Override */
    public function getILikeArray( $items = array() ) {
        return "ANY(ARRAY[" . implode( ", ", (array) $items ) . "])";
    }

    /* Override */
    public function getILikeComparator( $comparator ) {
        $map = array(
            "IN"        => "LIKE",
            "NOT IN"    => "NOT LIKE",
            "="         => "LIKE",
            "!="        => "NOT LIKE",
            "<>"        => "NOT LIKE"
        );

        if( isset( $map[ ( $comparator ) ] ) )
            $comparator = $map[ ( $comparator ) ];

        return str_replace( "LIKE", "ILIKE", $comparator );
    }

    /* Override */
    public function ignoreAccents( $schema, $reference ) {
        if( is_array( $reference ) )
            return array_map( function( $value ) use ( $schema ) { return "${schema}.unaccent(${value})"; }, $reference );

        return "${schema}.unaccent(${reference})";
    }
}