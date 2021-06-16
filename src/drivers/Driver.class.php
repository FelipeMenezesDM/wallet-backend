<?php
/**
 * Classe abstrata para driver de conexões SGBD.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Drivers;

abstract class Driver {
	/**
	 * Versão da base de dados.
	 * @access private
	 * @var    string
	 */
	private static $version = "0";

	/**
	 * Definir a versão da base de dados.
	 * @param  string $version Número da versão da base de dados.
	 * @return void
	 */
	public function setDBVersion( $version ) {
		if( self::$version == "0" )
			self::$version = $version;
	}

	/**
	 * Obter versão da base de dados.
	 * @return tring
	 */
	public function getDBVersion() {
		return self::$version;
	}

	/**
	 * Obter string do DNS de conexão para o PDO.
	 * @param  string $host     Nome do host de conexão para o servidor da base de dados.
	 * @param  string $port     Porta do servidor de base de dados.
	 * @param  string $database Nome da base de dados. Obs: no caso do Postgre, o nome da base é case sensitive.
	 * @return string
	 */
	public abstract function getDNS( $host, $port, $database );

	/**
	 * Método para definir configurações primárias da conexão.
	 * @param  object $connection Objeto de conexão do PDO para configuração.
	 * @return void
	 */
	public function connectionSettings( $connection = null ) {
		return;
	}

	/**
	 * Obter scape para nomes e alias de tabelas e colunas.
	 * @return string
	 */
	public function getScapeChar() {
		return '"';
	}
}