
<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class ApiMissoesTest extends TestCase
{
    private static $appCompartilhado = null;

    private $app;

    private string $arquivo;
    private ?string $dadosOriginais = null;
    private bool $arquivoExistia = false;

    private array $missoesIniciais = [
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
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$appCompartilhado === null) {
            self::$appCompartilhado =
                require __DIR__ . '/../src/app.php';
        }

        $this->app = self::$appCompartilhado;

        $this->arquivo = __DIR__ . '/../src/data/missoes.json';

        if (!is_dir(dirname($this->arquivo))) {
            mkdir(dirname($this->arquivo), 0777, true);
        }

        $this->arquivoExistia = file_exists($this->arquivo);

        if ($this->arquivoExistia) {
            $this->dadosOriginais = file_get_contents(
                $this->arquivo
            );
        }

        file_put_contents(
            $this->arquivo,
            json_encode(
                $this->missoesIniciais,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    protected function tearDown(): void
    {
        if ($this->arquivoExistia) {
            file_put_contents(
                $this->arquivo,
                $this->dadosOriginais ?? ''
            );
        } elseif (file_exists($this->arquivo)) {
            unlink($this->arquivo);
        }

        parent::tearDown();
    }

    private function requisicao(
        string $metodo,
        string $caminho,
        ?array $body = null
    ) {
        $factory = new ServerRequestFactory();

        $request = $factory->createServerRequest(
            strtoupper($metodo),
            'http://localhost' . $caminho,
            [
                'REQUEST_URI' => $caminho,
                'REQUEST_METHOD' => strtoupper($metodo),
                'HTTP_HOST' => 'localhost'
            ]
        );

        if ($body !== null) {
            $request = $request->withParsedBody($body);
        }

        return $this->app->handle($request);
    }

    public function testListarMissoes(): void
    {
        $response = $this->requisicao(
            'GET',
            '/missoes'
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($dados);
        $this->assertCount(5, $dados);
        $this->assertSame('Apollo 11', $dados[0]['nome']);
    }

    public function testBuscarMissaoPorId(): void
    {
        $response = $this->requisicao(
            'GET',
            '/missoes/1'
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Apollo 11', $dados['nome']);
        $this->assertSame(1969, $dados['ano']);
    }

    public function testBuscarMissaoInexistente(): void
    {
        $response = $this->requisicao(
            'GET',
            '/missoes/999'
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(404, $response->getStatusCode());

        $this->assertSame(
            'Missão não encontrada',
            $dados['erro']
        );
    }

    public function testCadastrarMissao(): void
    {
        $novaMissao = [
            'nome' => 'Mars Exploration',
            'ano' => 2027,
            'agencia' => 'NASA',
            'status' => 'Planejada'
        ];

        $response = $this->requisicao(
            'POST',
            '/missoes',
            $novaMissao
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Mars Exploration', $dados['nome']);
        $this->assertSame(6, $dados['id']);
    }

    public function testCadastrarMissaoSemCamposObrigatorios(): void
    {
        $response = $this->requisicao(
            'POST',
            '/missoes',
            ['nome' => 'Missão incompleta']
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame(
            'Campos obrigatórios ausentes',
            $dados['erro']
        );

        $this->assertContains('ano', $dados['campos']);
        $this->assertContains('agencia', $dados['campos']);
        $this->assertContains('status', $dados['campos']);
    }

    public function testAtualizarMissao(): void
    {
        $missaoAtualizada = [
            'nome' => 'Apollo 11 Atualizada',
            'ano' => 1969,
            'agencia' => 'NASA',
            'status' => 'Concluída'
        ];

        $response = $this->requisicao(
            'PUT',
            '/missoes/1',
            $missaoAtualizada
        );

        $dados = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            'Apollo 11 Atualizada',
            $dados['nome']
        );

        $this->assertSame(1, $dados['id']);
    }

    public function testAtualizarMissaoInexistente(): void
    {
        $response = $this->requisicao(
            'PUT',
            '/missoes/999',
            [
                'nome' => 'Teste',
                'ano' => 2027,
                'agencia' => 'NASA',
                'status' => 'Planejada'
            ]
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExcluirMissao(): void
    {
        $response = $this->requisicao(
            'DELETE',
            '/missoes/1'
        );

        $this->assertSame(204, $response->getStatusCode());

        $busca = $this->requisicao(
            'GET',
            '/missoes/1'
        );

        $this->assertSame(404, $busca->getStatusCode());
    }

    public function testExcluirMissaoInexistente(): void
    {
        $response = $this->requisicao(
            'DELETE',
            '/missoes/999'
        );

        $this->assertSame(404, $response->getStatusCode());
    }
}
