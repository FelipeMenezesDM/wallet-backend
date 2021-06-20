<?php
/**
 * Arquivo de inicialização da aplicação.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

# Credenciais da base de dados.
define( "DB_MANAGER", "postgres" );
define( "DB_NAME", "DB_NAME" );
define( "DB_USER", "DB_NAME" );
define( "DB_PASSWORD", "DB_PASSWORD" );
define( "DB_HOST", "DB_HOST" );
define( "DB_PORT", 5432 );

# Credenciais do OKTA.
define( "OKTAISSUER", "OKTAISSUER" );
define( "OKTACLIENTID", "OKTACLIENTID" );
define( "OKTASECRET", "OKTASECRET" );
define( "OKTASCOPE", "OKTASCOPE" );

# Definir se a API está ativa.
define( "ACT_API", true );