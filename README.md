# Wallet (Backend)

Código do backend para a API de consumo da Wallet.

## Pré-requisitos
Para usar este projeto, é necessário ter instalado em sua máquina:

- PHP 5.3.0 ou superior
- PostgreSQL 8 ou superior

## Conteúdo
- [Instalação](#instalação)
- [API/REST](apirest)
  - [Autenticação](#autenticação)
  - [Composição](#composição)
  - [Resposta](#resposta)
  - [CURL](#curl)
- [Objetos](#objetos)
  - [Controladores](#controladores)
  - [Entidades](#entidades)
  - [Serviços](#serviços)
- [Parâmetros](#parâmetros)
  - [Get](#get)
  - [Post](#post)
  - [Put](#put)
  - [Delete](#delete)
- [Atributos condicionais](#atributos-condicionais)

## Instalação
- [Back](#conteúdo)
- [Instalação](#instalação)

A instalação deste projeto é feita de forma automatizada durante a instalação do [Wallet (Docker)](https://github.com/FelipeMenezesDM/wallet-docker). Para executar a instalação, siga as etapas abaixo.

1. Antes de iniciar a instalação do projeto, acesse este link para obter uma cópia do backup da base de dados Postgre.
2. Faça o clone deste projeto executando o comando abaixo no diretório de aplicações do seu servidor php:
  ```bash
  git clone https://github.com/FelipeMenezesDM/wallet-backend.git
  ```
3. Acesse o diretório raíz do projeto e crie um arquivo `init.php` que irá conter as credenciais de acesso à base de dados e as chaves do OKTA, para autenticação da API. Você pode copiar o conteúdo de `init.example.php`, que também está na raíz.

## API/REST
- [Back](#conteúdo)
- [API/REST](apirest)
  - [Autenticação](#autenticação)
  - [Composição](#composição)
  - [Resposta](#resposta)
  - [CURL](#curl)

Este projeto pode ser usado com ou sem API/REST. Caso a constante `ACT_API` esteja com o valor `true` no `init.php`, o acesso à API estará ativo.

### Autenticação
Toas as conexões com a API necessitam de autenticação. As chaves de acesso são fornecidas pelo framework da OKTA e, por hora, são geradas sob demanda.

### Composição
Para enviar as chamadas à API, é necessário usar a estrura HTTP abaixo:

```bash
${host}:${port}/api/v1.0/${method}/${object}${parameters}
```

Caso o modo rewrite não esteja ativo, o acesso deve ser feito da seguinte forma:

```bash
${host}:${port}/index.php/api/v1.0/${method}/${object}${parameters}
```

Lembrando que nessas requisições (POST, GET, PUT, DELETE, SERVICE), o método também deve ser enviado no cabeçalho.
A documentação dos parâmetros disponíveis para o uso na API será visto mais adiante.

```
Exemplo: obter as pessoas da base cujo nome contenha "Eduardo".

SERVER: localhost
PORT: 80
METHOD: GET
OBJECT: Person
PARAMETERS: fullname LIKE eduardo
URL: http://localhost/index.php/api/v1.0/get/person?meta_query[0][key]=fullname&meta_query[0][value]=eduardo&meta_query[0][compare]=LIKE?client_id=OKTACLIENTID&client_secret=OKTASECRET
```

### Resposta
Por padrão, os retornos das chamadas da API possuem a seguinte estrutura:

```
{
  status: "success" | "error",
  message: ""
  results: *
}
```
Porém, alguns campos adicionais podem ser retornados dependendo do tipo de requisição. Por exemplo, para uma requisição GET, há também a quantidade de itens retornados, a quantidade total de registros e campos do objeto.

### CURL
Abaixo, um exemplo de requisição GET usando o PHP_CURL:

```php
$type = "GET";
$url = "http://localhost/index.php/api/v1.0/get/person?client_id=" . OKTACLIENTID . "&client_secret=" . OKTASECRET;
$params = array(
  "per_page"    => 10,
  "paged"       => 2,
  "meta_query"  => array( array( "key" => "fullname", "value" => "eduardo", "compare" => "LIKE" ) )
);

if( $type === "GET" ) {
  $url = addQueryArgs( $params, $url );
  $CURLConn = curl_init();
  curl_setopt( $CURLConn, CURLOPT_URL, $url );
}else{
  $CURLConn = curl_init( $url );
  curl_setopt( $CURLConn, CURLOPT_POSTFIELDS, $params );
}

curl_setopt( $CURLConn, CURLOPT_RETURNTRANSFER, true );
$result = curl_exec( $CURLConn );
curl_close( $CURLConn );
```

## Objetos
- [Back](#conteúdo)
- [Objetos](#objetos)
  - [Controladores](#controladores)
  - [Entidades](#entidades)
  - [Serviços](#serviços)
 
O projeto é constituído de vários objetos que serão apresentados nos tópicos seguintes.

### Controladores
Os controladores fazem parte da camada intermediária entre a base de dados e a API. Essa estruturação abstrata permite controlar rotinas da base a partir de métodos pré-definidos.

Atualmente o projeto possui quatro controladores principais. São eles: `Select`, `Insert`, `Update` e `Delete`.

Abaixo, um exemplo de uso de um controlador de consultas:

```php
$setts = array(
  "table"       => "person",
  "meta_query"  => array(
    array(
      "key"     => "fullname",
      "value"   => "eduardo",
      "compare" => "LIKE"
    )
  )
);

$select = new \Src\Db\Query\Select( $setts );
while( $person = $select->getResults() ) {
  echo $person[ "fullname" ] . "\n";
}
```

Os controladores estão dispoíveis no pacote `\Src\Db\Query`;

### Entidades
São controladores que podem ser usados para manipular as entidades da base de dados, mapeando rotinas básicas como get(), post(), put() e delete(), mas também possibilitando o acréscimo de rotinas específicas.

Estas entidades devem, necessariamente, estender a classe abstrata `Entity`, pois irão herdar dela as rotinas básicas.

Abaixo, um exemplo da construção de uma entidade básica em uma tabela chamada `Teste`, que possue os campos `id` e `texto_livre`:

```php
class Teste extends Entity {
  CONST KEY_NAME = "id";
  
  var $id;
  var $texto_livre;
  
  public function setId( $id ) {
    $this->id = $id;
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function setTextoLivre( $texto_livre ) {
    $this->texto_livre = $texto_livre;
  }
  
  public function getTextoLivre() {
    return $this->texto_livre;
  }
}  
```

Com esta estrutura, é possível manipular a tabela `Testes` usando:

```php
$teste = new \Src\Entities\Teste();
$teste->getById(24); # Método padrão da classe entity
$teste->setTextoLivre( "teste de texto" );
$teste->put(); # Atualizando entidade.

$insertTeste = new \Src\Entities\Teste();
$insertTeste->setId(50);
$insertTeste->setTextoLivre( "teste 50" );
$insertTeste->post();
```

Atualmente, todas as tabelas do projeto Wallet estão mapeadas para entidades e disponíveis no pacote `\Src\Entities`.

### Serviços
Os serviços são controladores que podem ser usados para adicionar funcionalidades específicas à API, apesar de poderem ser usados para o todo o projeto, mesmo sem o uso da API.

Abaixo, o exemplo de construção de um serviço:

```php
class FirstService implements Service {
  public function getUsers( $request ) {
    $users = new Select( array( "table" => "users" ) );
    
    if( $users->hasError() || $users->getRowsCount() === 0 ) {
      return return array(
        "status"  => "error",
        "message" => "Não foram localizados usuários na base de dados.",
        "results" => array()
      );
    }
    
    return array(
      "status"  => "success",
      "message" => "",
      "results" => $users->getAllResults()
    );
  }
}
```
Para o pacote, o serviço pode ser usado integralmente como uma classe, que pode ser instanciada. Já para a API, o serviço pode ser utilizado com a requisição `SERVICE`:

```
Variables: $servico = "FirstService"; $feature = "getUsers";
URL: http://localhost/index.php/api/v1.0/service/${servico}/${feature}
```

Atualmente o projeto conta com alguns serviços como cadastro de usuários, validação de transação financeira e autenticação de acesso, disponíveis em `\Src\Services`.

### Parâmetros
- [Back](#conteúdo)
- [Parâmetros](#parâmetros)
  - [Get](#get)
  - [Post](#post)
  - [Put](#put)
  - [Delete](#delete)

Os parãmetros definidos a seguir são usados na construção das requisições da API e também como parâmetros dos controladores `Select`, `Insert`, `Update` e `Delete`.

### Get
- **table:** Tabela principal para consulta de dados.
	- Type: string
	- Note: também pode ser usada como lista de configurações, com definição do alias e schema da tabela.
	- Example: `"table" => "teste"` ou `"table" => array( "name" => "teste", "alias" => "TAB01", "schema" => "schema01" )`
- **fields**: Campos para retorno na consulta.
	- Type: array
	- Note: Caso não seja definido, a consulta irá retornar todos os campos de todas as tabelas da consulta.
- **joins:** Definição das tabelas relacionadas à tabela principal ou entre si.
	- Type: array
	- Params:
		- *table:* Tabela para JOIN.
			- Type: string
			- Note: também pode ser usada como lista de configurações, com definição do alias e schema da tabela.
      - Example: `"table" => "teste"` ou `"table" => array( "name" => "teste", "alias" => "TAB01", "schema" => "schema01" )`
		- *type:* Tipo do JOIN.
			- Type: string
			- Default: INNER
			- Support: INNER, LEFT, RIGHT e FULL
		- *meta_query:* Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
		- *meta_queries:* Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **per_page:** Item de paginação, determina o número de registros que devem ser retornados em cada página.
	- Type: integer
	- Default: false
	- Note: Quando definido como "false", todos os registros da consulta serão retornados.
- **paged:** Item de paginação, determina qual página deve ser retornada na consulta.
	- Type: integer
	- Default: 1
- **order:** Método de ordenação padrão.
	- Type: string
	- Default: ASC
	- Support: DESC ou ASC
- **order_by:** Item de ordenação, determina quais campos serão considerados para a ordenação dos resultados, bem como a ordem crescente ou decrescente de forma individual.
	- Type: array, string
	- Notes:
		1. Pode ser informado como string ou lista.
		1. Caso seja uma lista, a chave do item será o campo e o valor, o método de ordenação.
		1. Caso seja uma lista e o método de ordenação não seja informado, será usado o método padrão definido pelo atributo "order".
		1. É possível usar o campo "rownumber" parâmetro de ordenação. Ele representa um valor sequencial de cada registro retornado, levando em consideração o total de registros da consulta.
	- Example: `COLUMN01 DESC, COLUMN02 ASC` ou `array( "column01", "column02" => "desc", "column03" => "ASC" )` 
- **group_by:** Item de agrupamento, determina as colunas que serão os parâmetros para agrupamento.
	- Type: array, string
	- Notes:
		1. Pode ser informado como uma string livre ou como uma lista.
		1. Quando este atributo for informado, o atributo "fields" pode ser deixado em branco para que a API identifique automaticamente quais são os campos referenciados no agrupamento.
	- Example: `TAB01.COLUMN01, TAB01.COLUMN02` ou `array( "TAB01.COLUMN01", "TAB01.COLUMN02" )`
- **meta_query:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **meta_queries:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **unaccent:** Difinir se a comparação de strings deve ou não ignorar acentuação.
	- Type: boolean
	- Default: true

### Post
- **table:** Tabela principal para consulta de dados.
	- Type: string
	- Note: também pode ser usada como lista de configurações, com definição do alias e schema da tabela.
	- Example: `"table" => "teste"` ou `"table" => array( "name" => "teste", "alias" => "TAB01", "schema" => "schema01" )`
- **item:** Registro único para inserção.
	- Type: array
	- Notes:
		1. Neste atributo, as chaves da lista representam as colunas das tabelas.
		1. Este atributo pode ser usado juntamente com o atributo "items", porém apenas quando os dois forem compatíveis, isto é, quando as colunas de inserção definidas em um for exatamente igual a do outro.
	- Example: `"item" => array( "column_01" => 20, "column_02" => "TEXT", "file_01" => "C:/image01.jpg" )`
- **items:** Lista de registros para inserção.
	- Type: array
	- Notes:
		1. O atributo "item" não será incluído na lista de registros do atributo "items".
		1. Inserção de identidade será tratada de forma automática.
	- Example:
```php
"items" => array(
  "columns"	=> array( "column_01", "column_02" ),
  "records"	=> array(
    array( 20, "TEXT" ),
    array( 30, "TEXT" )
  )
)
```
- **key:** Informar a chave primária da tabela principal.
	- Type: string
- **update_duplicate_key:** Definir se deve atualizar o registro, caso este já exista na base.
	- Type: boolean
	- Default: true

### Put
- **table:** Tabela onde os dados serão atualizados.
	- Type: string
	- Note: também pode ser usada como lista de configurações, com definição do alias e schema da tabela.
	- Example: `"table" => "teste"` ou `"table" => array( "name" => "teste", "alias" => "TAB01", "schema" => "schema01" )`
- **sets:** Lista de colunas e valores que serão atualizadas.
	- Type: array
	- Notes:
		1. Pode ser definida como uma lista simples ou detalhada.
		1. Na lista simples, o conjunto será formado por chave e valor, onde a chave será o nome da coluna e o valor, o que será atribuído a esta. Neste caso, o valor será sempre tratado como uma string pelo statement, por isso, caso a atribuição seja de uma coluna de uma tabela relacionada, deve ser usado a lista detalhada.
	- Example:
```php
# Lista simples:
"sets" => array( "column01" => 100, "column02" => "text" )

# Lista detalhada:
"sets" => array(
  array(
    "set" => "column01",
    "value" => 100
  ),
  array(
    "set" => "column02",
    "column" => "TAB02.col01"
  )
)
```
- **key:** Informar a chave primária da tabela principal.
	- Type: string
- **meta_query:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **meta_queries:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **unaccent:** Difinir se a comparação de strings deve ou não ignorar acentuação.
	- Type: boolean
	- Default: true

### Delete
- **table:** Tabela da qual os dados serão removidos.
	- Type: string
	- Note: também pode ser usada como lista de configurações, com definição do alias e schema da tabela.
	- Example: `"table" => "teste"` ou `"table" => array( "name" => "teste", "alias" => "TAB01", "schema" => "schema01" )`
- **meta_query:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **meta_queries:** Ver a seção especial dos [atributos condicionais](#atributos-condicionais) para entender o funcionamento deste atributo.
- **unaccent:** Difinir se a comparação de strings deve ou não ignorar acentuação.
	- Type: boolean
	- Default: true

## Atributos condicionais
- [Back](#conteúdo)
- [Atributos condicionais](#atributos-condicionais)

Na lista de configurações, estes atributos representam a lista de condições para que a instrução seja executada. Em prática, representam as cláusulas do **WHERE** no SQL.
Assim como no SQL, estes atributos podem ser usados para instruções de atualização, deleção e consulta, ou seja, pelos controladores Select, Delete e Update.
É possível definir as condições da instrução de duas formas: a primeira, como o uso do atributo "meta_query", deve ser informada como uma lista de arrays que serão interpretados como um grupo de condições; e a segunda, com o uso do atributo "meta_queries", que pode ser entendida como uma equivalência à "meta_query", porém com um nível a mais, como será explicado a seguir:

- **meta_query:** Grupo de condições para execução de uma instrução.
	- Type: array
	- Params:
		- *key:*
			- Type: string
			- Notes:
				1. Chave de comparação da condição.
				1. Todo valor informado neste atributo não será tratado como uma string no statement da instrução, pois será subtentido como uma coluna.
		- *column:*
			- Type: string
			- Notes:
				1. Coluna de comparação em relação ao atributo "key", ideal para instruções que utilizam mais de uma tabela em sua composição, como os JOINs nas instruções de consulta.
				1. Todo valor informado neste atributo não será tratado como uma string no statement da instrução, pois será subtentido como uma coluna.
				1. Caso este atributo não seja informado, a API irá usar o atributo "value" para interpretação.
		- *value:*
			- Type: ...
			- Notes:
				1. Valor de comparação em relação ao atributo "key".
				1. Quando o atributo "column" for definido, este não será levado em consideração na interpretação.
		- *compare:*
			- Type: string
			- Default: =
			- Support: =, !=, <>, >, <, >=, <=, IN, NOT IN, IS NULL, IS NOT NULL, EXISTS, NOT EXISTS, BETWEEN, LIKE, LEFT LIKE, RIGHT LIKE, NOT LIKE, NOT LEFT LIKE, NOT RIGHT LIKE,  MATCH_PERCENTAGE e FUZZYSEARCH.
			- Notes:
				1. Operador de comparação entre a chave e o valor (ou coluna) da condição.
				1. Quando os comparadores LIKE, LEFT LIKE, RIGHT LIKE, NOT LIKE, NOT LEFT LIKE e NOT RIGHT LIKE forem utilizados, não é necessário adicionar o "%" no atributo "value" ou "column".
				1. Quando o atributo BETWEEN for utilizado, o atributo "value" ou "column" deverá ser informado como um array da seguinte forma: `array( "min" => MIN_VALUE, "max" => MAX_VALUE )`, inclusive para datas.
		- *percentage:*
			- Type: int
			- Default: 100
			- Notes:
				1. Parâmetro usado exclusivamente pelo tipo de comparação `MATCH_PERCENTAGE`, que determina o percentual de correspondência que uma coluna deve ter quando comparada com uma string ou com outra coluna obtido através da função `FUZZYSEARCH`.
				1. Quando não definido, o percentual padrão é 100%.
				1. A comparação sempre será feita por percentuais "maiores ou iguais", nunca por "menores", "iguais" ou "menores ou iguais", já que o comparador padrão para o *MATCH_PERCENTAGE* é o `>=`.
		- *relation:*
			- Type: string
			- Default: AND
			- Support: AND e OR
			- Notes:
				1. Tipo de relação entre as demais condições do grupo.
				1. A ordem das cláusulas após a interpretação será a mesma que a definida nesta lista.
	    - Example:
```php
array(
  "meta_query" => array(
    array(
      "key" => "column01",
      "value" => 1
    ),
    array(
      "key" => "column01",
      "value" => 2,
      "relation" => "OR"
    )
  )
)
```
- **meta_queries:** Lista de grupos de condições para execução de uma instrução.
	- Type: array
	- Notes:
		1. É equivalente ao "meta_query", porém com um nível a mais, para cláusulas que requerem vários grupos de condições.
		1. Pode ser utilizado juntamente com o "meta_query", que será agregado a este atributo como um grupo com relação "AND".
	- Example:
```php
array(
  "meta_queries" => array(
    array(
      array(
        "key" => "column01",
        "value" => 1
      ),
      array(
        "key" => "column01",
        "value" => 2,
        "relation" => "OR"
      )
    ),
    array(
      array(
        "key" => "column04",
        "value" => array(
          "min" => "2019-12-01",
          "max" => "2019-12-31"
        ),
        "compare" => "BETWEEN"
      )
    )
  )
) # Irá retornar: (column01 = 1 OR column01 = 2) AND column04 BETWEEN '2019-12-01' AND '2019-12-31'
```
