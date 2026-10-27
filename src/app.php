
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Inicializa o Slim
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Registros iniciais
$missoesIniciais = [
    [
        "id" => 1,
        "nome" => "Apollo 11",
        "ano" => 1969,
        "agencia" => "NASA",
        "status" => "Concluída"
    ],
    [
        "id" => 2,
        "nome" => "Voyager 1",
        "ano" => 1977,
        "agencia" => "NASA",
        "status" => "Em operação"
    ],
    [
        "id" => 3,
        "nome" => "Artemis II",
        "ano" => 2026,
        "agencia" => "NASA",
        "status" => "Planejada"
    ],
    [
        "id" => 4,
        "nome" => "Chandrayaan-3",
        "ano" => 2023,
        "agencia" => "ISRO",
        "status" => "Concluída"
    ],
    [
        "id" => 5,
        "nome" => "Rosetta",
        "ano" => 2004,
        "agencia" => "ESA",
        "status" => "Concluída"
    ],
];

// Caminho do arquivo JSON
$diretorioDados = __DIR__ . '/data';
$arquivo = $diretorioDados . '/missoes.json';

// Disponibiliza o caminho para as funções globais
$GLOBALS['arquivo'] = $arquivo;

// Cria a pasta de dados se não existir
if (!is_dir($diretorioDados)) {
    mkdir($diretorioDados, 0777, true);
}

// Cria o arquivo JSON se não existir
if (!file_exists($arquivo)) {
    file_put_contents(
        $arquivo,
        json_encode(
            $missoesIniciais,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );
}

// Carrega as missões
if (!function_exists('carregar')) {
    function carregar(array $missoesIniciais): array
    {
        $arquivo = $GLOBALS['arquivo'] ?? null;

        if (
            !is_string($arquivo) ||
            $arquivo === '' ||
            !is_file($arquivo) ||
            !is_readable($arquivo)
        ) {
            return $missoesIniciais;
        }

        $dados = file_get_contents($arquivo);

        if ($dados === false) {
            return $missoesIniciais;
        }

        $missoes = json_decode($dados, true);

        return is_array($missoes)
            ? $missoes
            : $missoesIniciais;
    }
}

// Salva as missões
if (!function_exists('salvar')) {
    function salvar(array $missoes): void
    {
        $arquivo = $GLOBALS['arquivo'] ?? null;

        if (!is_string($arquivo) || $arquivo === '') {
            throw new RuntimeException(
                'O caminho do arquivo JSON não foi definido.'
            );
        }

        $conteudo = json_encode(
            $missoes,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_THROW_ON_ERROR
        );

        if (file_put_contents($arquivo, $conteudo) === false) {
            throw new RuntimeException(
                'Não foi possível salvar as missões.'
            );
        }
    }
}

// Busca o índice de uma missão pelo ID
if (!function_exists('buscarIndice')) {
    function buscarIndice(array $missoes, int $id): ?int
    {
        foreach ($missoes as $indice => $missao) {
            if ((int) $missao['id'] === $id) {
                return $indice;
            }
        }

        return null;
    }
}

// Verifica os campos obrigatórios
if (!function_exists('camposFaltando')) {
    function camposFaltando(?array $body): array
    {
        $campos = ['nome', 'ano', 'agencia', 'status'];
        $faltando = [];

        foreach ($campos as $campo) {
            if (
                !isset($body[$campo]) ||
                $body[$campo] === ''
            ) {
                $faltando[] = $campo;
            }
        }

        return $faltando;
    }
}

// Retorna uma resposta JSON
if (!function_exists('json')) {
    function json(
        Response $response,
        $dados,
        int $status
    ): Response {
        $response->getBody()->write(
            json_encode(
                $dados,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

// GET /missoes - Lista todas as missões
$app->get(
    '/missoes',
    function (
        Request $request,
        Response $response
    ) use ($missoesIniciais) {
        return json(
            $response,
            carregar($missoesIniciais),
            200
        );
    }
);

// GET /missoes/{id} - Busca uma missão pelo ID
$app->get(
    '/missoes/{id}',
    function (
        Request $request,
        Response $response,
        array $args
    ) use ($missoesIniciais) {
        $missoes = carregar($missoesIniciais);

        $indice = buscarIndice(
            $missoes,
            (int) $args['id']
        );

        if ($indice === null) {
            return json(
                $response,
                ["erro" => "Missão não encontrada"],
                404
            );
        }

        return json(
            $response,
            $missoes[$indice],
            200
        );
    }
);

// POST /missoes - Cadastra uma missão
$app->post(
    '/missoes',
    function (
        Request $request,
        Response $response
    ) use ($missoesIniciais) {
        $body = $request->getParsedBody();

        $faltando = camposFaltando(
            is_array($body) ? $body : null
        );

        if ($faltando) {
            return json(
                $response,
                [
                    "erro" => "Campos obrigatórios ausentes",
                    "campos" => $faltando
                ],
                400
            );
        }

        $missoes = carregar($missoesIniciais);

        $proximoId = $missoes
            ? max(array_column($missoes, 'id')) + 1
            : 1;

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
    }
);

// PUT /missoes/{id} - Atualiza uma missão
$app->put(
    '/missoes/{id}',
    function (
        Request $request,
        Response $response,
        array $args
    ) use ($missoesIniciais) {
        $missoes = carregar($missoesIniciais);
        $id = (int) $args['id'];

        $indice = buscarIndice($missoes, $id);

        if ($indice === null) {
            return json(
                $response,
                ["erro" => "Missão não encontrada"],
                404
            );
        }

        $body = $request->getParsedBody();

        $faltando = camposFaltando(
            is_array($body) ? $body : null
        );

        if ($faltando) {
            return json(
                $response,
                [
                    "erro" => "Campos obrigatórios ausentes",
                    "campos" => $faltando
                ],
                400
            );
        }

        $missoes[$indice] = [
            "id" => $id,
            "nome" => $body['nome'],
            "ano" => (int) $body['ano'],
            "agencia" => $body['agencia'],
            "status" => $body['status']
        ];

        salvar($missoes);

        return json(
            $response,
            $missoes[$indice],
            200
        );
    }
);

// DELETE /missoes/{id} - Exclui uma missão
$app->delete(
    '/missoes/{id}',
    function (
        Request $request,
        Response $response,
        array $args
    ) use ($missoesIniciais) {
        $missoes = carregar($missoesIniciais);

        $indice = buscarIndice(
            $missoes,
            (int) $args['id']
        );

        if ($indice === null) {
            return json(
                $response,
                ["erro" => "Missão não encontrada"],
                404
            );
        }

        unset($missoes[$indice]);

        $missoes = array_values($missoes);
        salvar($missoes);

        return $response->withStatus(204);
    }
);

// Retorna a aplicação para os testes automatizados
return $app;
