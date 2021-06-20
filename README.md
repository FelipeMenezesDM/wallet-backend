# Wallet (Backend)

Código do backend para a API de consumo da Wallet.

## Pré-requisitos
Para usar este projeto, é necessário ter instalado em sua máquina:

- PHP 5.3.0 ou superior
- PostgreSQL 8 ou superior

## Instalação
A instalação deste projeto é feita de forma automatizada durante a instalação do [Wallet (Docker)](https://github.com/FelipeMenezesDM/wallet-docker). Para executar a instalação, siga as etapas abaixo.

1. Antes de iniciar a instalação do projeto, acesse este link para obter uma cópia do backup da base de dados Postgre.
2. Faça o clone deste projeto executando o comando abaixo no diretório de aplicações do seu servidor php:
  ```bash
  git clone https://github.com/FelipeMenezesDM/wallet-backend.git
  ```
3. Acesse o diretório raíz do projeto e crie um arquivo `init.php` que irá conter as credenciais de acesso à base de dados e as chaves do OKTA, para autenticação da API. Você pode copiar o conteúdo de `init.example.php`, que também está na raíz.

## API/REST
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
