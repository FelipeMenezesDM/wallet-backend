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
	 * Obter instrução de consulta.
	 * @param  string $table        Nome da tabela principal de consulta.
	 * @param  string $columns      Colunas para retorno.
	 * @param  string $joins        Estrutura completa dos joins.
	 * @param  string $queries      Cláusulas "quando" para retorno de registros.
	 * @param  string $groupBy      Estrutura de agrupamento de registros.
	 * @param  string $orderBy      Cláusulas de ordenação.
	 * @param  string $reverseOrder Indicar ordenação insersa. Usado exclusivamente para ordenação a partir do número de linha.
	 * @param  string $perPage      Número de registros por página.
	 * @param  string $offset       Número de registros para deslocar.
	 * @return string
	 */
	public function getSelectStatement( $table, $columns, $joins, $queries, $groupBy, $orderBy, $reverseOrder, $perPage, $offset ) {
		$query = "SELECT";
		$defaultOrderBy = $appends = $orderBy;

		# Definir ORDER BY padrão.
		if( empty( $defaultOrderBy ) )
			$defaultOrderBy = " ORDER BY (SELECT NULL)";

		if( $perPage >= 1 ) {
			$query = "SELECT TAB.* FROM (${query}";
			$perPageOld = $offset + $perPage;

			if( $reverseOrder ) {
				$perPageOld = "(FOUNDROWS - ${offset})";
				$offset = "(FOUNDROWS - " . ( $offset + $perPage ) . ")";
			}

			$appends .= ") TAB WHERE ROWNUMBER > ${offset} AND ROWNUMBER <= ${perPageOld}";
		}

		# Inversão de ordem a partir do número de linha.
		if( $reverseOrder ) {
			if( $perPage >= 1 || empty( $appends ) )
				$appends .= " ORDER BY ROWNUMBER DESC";
			else
				$appends = preg_replace( "/ORDER BY/i", "ORDER BY ROWNUMBER DESC, ", $appends, 1 );
		}

		$totalRows = "(SELECT COUNT(*) FROM ${table}{$joins}{$queries}) AS FOUNDROWS";

		# Obter o número das linhas.
		$rowNumber = "ROW_NUMBER() OVER(${defaultOrderBy} ) AS ROWNUMBER";

		return "${query} ${totalRows}, ${rowNumber}, ${columns} FROM ${table}${joins}${queries}${groupBy}${appends}";
	}

	/**
	 * Obter statement de inserção de registros.
	 * @param  string  $table              Nome da tabela para inserção.
	 * @param  string  $columns            Definição de colunas para inserção.
	 * @param  array   $records            Lista de registros para inserção.
	 * @param  boolean $updateDuplicateKey Definir se o registro deve ser atualizado caso já exista na base.
	 * @param  string  $primaryKey         Nome da coluna de chave primária.
	 * @return string
	 */
	abstract public function getInsertStatement( $table, $columns, $records, $updateDuplicateKey, $primaryKey = null );

	/**
	 * Obter statement de atualização de registros.
	 * @param  string $table      Nome da tabela para atualização.
	 * @param  string $columns    Colunas da tabela para atualização.
	 * @param  string $joins      Estrutura completa dos joins.
	 * @param  string $queries    Cláusulas "quando" para atualização de registros.
	 * @param  string $primaryKey Chave primária da tabela principal.
	 * @return string
	 */
	abstract public function getUpdateStatement( $table, $columns, $joins, $queries, $primaryKey = null );

	/**
	 * Obter statement de deleção de registros.
	 * @param  string $table   Nome da tabela para deleção.
	 * @param  string $joins   Estrutura completa dos joins.
	 * @param  string $queries Cláusulas "quando" para deleção de registros.
	 * @return string
	 */
	abstract public function getDeleteStatement( $table, $joins, $queries );

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
	abstract public function getDNS( $host, $port, $database );

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

	/**
	 * Converter tipo de coluna.
	 * @param  string $column Nome da coluna.
	 * @param  string $type   Tipo para conversão.
	 * @param  int    $length Tamanho máximo do campo.
	 * @return string
	 */
	public function cast( $column, $type, $length = null ) {
		return "CAST(${column} AS ${type}" . ( is_null( $length ) ? "" : "(${length})" ) . ")";
	}

	/**
	 * Retornar operador de concatenação do gerenciador.
	 * @return string
	 */
	abstract public function getConcatOperator();

	/**
	 * Concatenação de valores com tratamento de nulos.
	 * @return string
	 */
	public function concatNoCoalesce() {
		$args = func_get_args();

		if( empty( $args ) )
			return "";

		return implode( " " . $this->getConcatOperator() . " ", $args );
	}

	/**
	 * Obter comparação ILIKE a partir de comparator.
	 * @param  string $comparator Comparador original.
	 * @return string
	 */
	public function getILikeComparator( $comparator ) {
		return $comparator;
	}

	/**
	 * Obter lista de itens para uso do ILIKE em substituição ao IN ou NOT IN.
	 * @param  string $items Lista de itens do ILIKE.
	 * @return string
	 */
	public function getILikeArray( $items = array() ) {
		return "(" . implode( ", ", (array) $items ) . ")";
	}

	/**
	 * Função para tratamento de elementos para ignorar acentuação de palavras para comparação.
	 * @param  string $schema    Schema da funçao de tratamento de elementos.
	 * @param  string $reference Elemento de referência para tratamento.
	 * @return string
	 */
	public function ignoreAccents( $schema, $reference ) {
		return $reference;
	}
}