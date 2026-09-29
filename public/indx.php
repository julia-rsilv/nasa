<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

/*
 * Registros iniciais (array). Como o servidor embutido do PHP não guarda
 * memória entre requisições, os dados são persistidos em data/missoes.json.
 * Se o arquivo não existir, ele é criado a partir deste array.
 */
$missoesIniciais = [
    ["id" => 1, "nome" => "Apollo 11",  "ano" => 1969, "agencia" => "NASA",  "status" => "Concluída"],
    ["id" => 2, "nome" => "Voyager 1",  "ano" => 1977, "agencia" => "NASA",  "status" => "Em operação"],
    ["id" => 3, "nome" => "Artemis II", "ano" => 2026, "agencia" => "NASA",  "status" => "Planejada"],
    ["id" => 4, "nome" => "Chandrayaan-3", "ano" => 2023, "agencia" => "ISRO", "status" => "Concluída"],
    ["id" => 5, "nome" => "Rosetta",    "ano" => 2004, "agencia" => "ESA",   "status" => "Concluída"],
];

const ARQUIVO = __DIR__ . '/data/missoes.json';

function carregar(array $iniciais): array
{
    if (!file_exists(ARQUIVO)) {
        salvar($iniciais);
        return $iniciais;
    }
    $dados = json_decode(file_get_contents(ARQUIVO), true);
    return is_array($dados) ? $dados : $iniciais;
}

function salvar(array $missoes): void
{
    if (!is_dir(dirname(ARQUIVO))) {
        mkdir(dirname(ARQUIVO), 0777, true);
    }
    file_put_contents(
        ARQUIVO,
        json_encode(array_values($missoes), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

function json(Response $response, $dados, int $status = 200): Response
{
    $response->getBody()->write(json_encode($dados, JSON_UNESCAPED_UNICODE));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function buscarIndice(array $missoes, int $id): ?int
{
    foreach ($missoes as $i => $m) {
        if ($m['id'] === $id) {
            return $i;
        }
    }
    return null;
}

function camposFaltando($body): array
{
    $obrigatorios = ['nome', 'ano', 'agencia', 'status'];
    if (!is_array($body)) {
        return $obrigatorios;
    }
    return array_values(array_filter(
        $obrigatorios,
        fn($c) => !isset($body[$c]) || $body[$c] === ''
    ));
}

$app = AppFactory::create();
$app->addBodyParsingMiddleware();   // permite usar getParsedBody() com JSON
$app->addRoutingMiddleware();

// Tratamento de erros em JSON (rota inexistente, etc.)
$errorMiddleware = $app->addErrorMiddleware(true, false, false);
$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function (Request $request, Throwable $e) use ($app) {
        return json($app->getResponseFactory()->createResponse(), ["erro" => "Recurso não encontrado"], 404);
    }
);

// GET /status
$app->get('/status', function (Request $request, Response $response) {
    return json($response, ["status" => "ok"], 200);
});

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
    $faltando = camposFaltando($body);
    if ($faltando) {
        return json($response, ["erro" => "Campos obrigatórios ausentes", "campos" => $faltando], 400);
    }

    $missoes = carregar($missoesIniciais);
    $proximoId = $missoes ? max(array_column($missoes, 'id')) + 1 : 1;

    $nova = [
        "id"      => $proximoId,
        "nome"    => $body['nome'],
        "ano"     => (int) $body['ano'],
        "agencia" => $body['agencia'],
        "status"  => $body['status'],
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
    $faltando = camposFaltando($body);
    if ($faltando) {
        return json($response, ["erro" => "Campos obrigatórios ausentes", "campos" => $faltando], 400);
    }

    $missoes[$i] = [
        "id"      => $id,
        "nome"    => $body['nome'],
        "ano"     => (int) $body['ano'],
        "agencia" => $body['agencia'],
        "status"  => $body['status'],
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
    salvar($missoes);

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(204);
});

$app->run();
