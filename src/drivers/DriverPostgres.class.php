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
            $port = "1521";

        return "pgsql:host=${host};port=${port};dbname=${database};options='--client_encoding=UTF8'";
    }
}