
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

// Inicializa o Slim
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Registros iniciais
$missoesIniciais = [
    ["id" => 1, "nome" => "Apollo 11", "ano" => 1969, "agencia" => "NASA", "status" => "Concluída"],
    ["id" => 2, "nome" => "Voyager 1", "ano" => 1977, "agencia" => "NASA", "status" => "Em operação"],
    ["id" => 3, "nome" => "Artemis II", "ano" => 2026, "agencia" => "NASA", "status" => "Planejada"],
    ["id" => 4, "nome" => "Chandrayaan-3", "ano" => 2023, "agencia" => "ISRO", "status" => "Concluída"],
    ["id" => 5, "nome" => "Rosetta", "ano" => 2004, "agencia" => "ESA", "status" => "Concluída"],
];

// Caminho do arquivo JSON
$arquivo = __DIR__ . '/data/missoes.json';

// Cria a pasta e o arquivo se não existirem
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

if (!file_exists($arquivo)) {
    file_put_contents(
        $arquivo,
        json_encode($missoesIniciais, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// Função para carregar as missões
function carregar(array $missoesIniciais): array
{
    global $arquivo;

    $dados = file_get_contents($arquivo);
    $missoes = json_decode($dados, true);

    return is_array($missoes) ? $missoes : $missoesIniciais;
}

// Função para salvar as missões
function salvar(array $missoes): void
{
    global $arquivo;

    file_put_contents(
        $arquivo,
        json_encode($missoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// Função para buscar o índice de uma missão
function buscarIndice(array $missoes, int $id): ?int
{
    foreach ($missoes as $indice => $missao) {
        if ($missao['id'] === $id) {
            return $indice;
        }
    }

    return null;
}

// Função para verificar campos obrigatórios
function camposFaltando(?array $body): array
{
    $campos = ['nome', 'ano', 'agencia', 'status'];
    $faltando = [];

    foreach ($campos as $campo) {
        if (!isset($body[$campo]) || $body[$campo] === '') {
            $faltando[] = $campo;
        }
    }

    return $faltando;
}

// Função para retornar JSON
function json(Response $response, $dados, int $status): Response
{
    $response->getBody()->write(
        json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// GET /missoes
$app->get('/missoes', function (Request $request, Response $response) use ($missoesIniciais) {
    return json($response, carregar($missoesIniciais), 200);
});

// GET /missoes/{id}
$app->get('/missoes/{id}', function (Request $request, Response $response, array $args) use ($missoesIniciais) {
    $missoes = carregar($missoesIniciais);
    $i = buscarIndice($missoes, (int) $args['id']);

    if ($i === null) {
        return json($response, ["erro" => "Missão não encontrada"], 404);
    }

    return json($response, $missoes[$i], 200);
});

// POST /missoes
$app->post('/missoes', function (Request $request, Response $response) use ($missoesIniciais) {
    $body = $request->getParsedBody();

    $faltando = camposFaltando(is_array($body) ? $body : null);

    if ($faltando) {
        return json($response, [
            "erro" => "Campos obrigatórios ausentes",
            "campos" => $faltando
        ], 400);
    }

    $missoes = carregar($missoesIniciais);
    $proximoId = $missoes ? max(array_column($missoes, 'id')) + 1 : 1;

    $nova = [
        "id" => $proximoId,
        "nome" => $body['nome'],
        "ano" => (int) $body['ano'],
        "agencia" => $body['agencia'],
        "status" => $body['status']
    ];

    $missoes[] = $nova;
    salvar($missoes);

    return json($response, $nova, 201);
});

// PUT /missoes/{id}
$app->put('/missoes/{id}', function (Request $request, Response $response, array $args) use ($missoesIniciais) {
    $missoes = carregar($missoesIniciais);
    $id = (int) $args['id'];
    $i = buscarIndice($missoes, $id);

    if ($i === null) {
        return json($response, ["erro" => "Missão não encontrada"], 404);
    }

    $body = $request->getParsedBody();

    $faltando = camposFaltando(is_array($body) ? $body : null);

    if ($faltando) {
        return json($response, [
            "erro" => "Campos obrigatórios ausentes",
            "campos" => $faltando
        ], 400);
    }

    $missoes[$i] = [
        "id" => $id,
        "nome" => $body['nome'],
        "ano" => (int) $body['ano'],
        "agencia" => $body['agencia'],
        "status" => $body['status']
    ];

    salvar($missoes);

    return json($response, $missoes[$i], 200);
});

// DELETE /missoes/{id}
$app->delete('/missoes/{id}', function (Request $request, Response $response, array $args) use ($missoesIniciais) {
    $missoes = carregar($missoesIniciais);
    $i = buscarIndice($missoes, (int) $args['id']);

    if ($i === null) {
        return json($response, ["erro" => "Missão não encontrada"], 404);
    }

    unset($missoes[$i]);
    $missoes = array_values($missoes);
    salvar($missoes);

    return $response->withStatus(204);
});

$app->run();