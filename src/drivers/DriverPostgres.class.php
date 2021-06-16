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
    public function getInsertStatement( $table, $columns, $records, $updateDuplicateKey, $primaryKey = null ) {
        $insertColumns = implode( ", ", $columns );
        $insertRecords = implode( ", ", array_map( function( $value ) { return "( " . implode( ", ", (array) $value ) . " )"; }, $records ) );
        $insert = "INSERT INTO ${table} (${insertColumns}) VALUES ${insertRecords}";

        # Atualizar registros existentes.
        if( $updateDuplicateKey ) {
            $updateColumns = implode( ", ", array_map( function( $column ) { return "${column} = EXCLUDED.${column}"; }, array_diff( $columns, array( $primaryKey ) ) ) );
            $insert .= " ON CONFLICT ($primaryKey) DO UPDATE SET ${updateColumns}";
        }

        return $insert;
    }

    /* Override */
    public function getUpdateStatement( $table, $columns, $joins, $queries, $primaryKey = null ) {
        $columns = implode( ",", $columns );

        if( empty( $joins ) || !is_array( $joins ) )
            return "UPDATE ${table} SET ${columns}${queries}";

        return "UPDATE ${table} SET ${columns} FROM " . implode( ", ", $joins ) . "${queries}";
    }

    /* Override */
    public function getDeleteStatement( $table, $joins, $queries ) {
        if( empty( $joins ) || !is_array( $joins ) )
            return "DELETE FROM ${table}${queries}";
        
        return "DELETE FROM ${table} USING " . implode( ", ", $joins ) . "${queries}";
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