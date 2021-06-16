<?php
/**
 * Interface para implementação de serviços.
 * 
 * @author    Felipe Menezes <contato@felipemenezes.com.br>
 * @copyright (c) 2021 Felipe Menezes
 * @package   Wallet_Backend
 * @version   1.0.0
 */

namespace Src\Services;

interface Service {
    /**
     * Método construtor padrão de serviços.
     * @param array $request Objeto da requisição.
     */
    public function __construct( $request );

    /**
     * Retorno de resultados obrigatrório.
     * @return *
     */
    public function getResults();
}