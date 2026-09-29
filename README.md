# API REST de Missões Espaciais

## 1. Identificação

- **Nome completo:** Julia Rafaela da Silva
- **Curso:** Informática para Internet 
- **Unidade Curricular:** Desenvolver Serviços Web

## 2. Descrição do Projeto

API REST para cadastro, consulta, atualização e remoção de missões espaciais históricas e futuras. Os dados trafegam em JSON e a aplicação já inicia com 5 missões de exemplo. Os dados são persistidos no arquivo `data/missoes.json` (criado automaticamente na primeira requisição).

## 3. Tecnologias Utilizadas

- PHP
- Slim Framework 4
- Composer
- JSON

## 4. Como Clonar o Projeto

```bash
git clone URL_DO_REPOSITORIO
cd NOME_DA_PASTA
```

## 5. Como Instalar as Dependências

```bash
composer install
```

## 6. Como Executar o Projeto

```bash
php -S localhost:8080 index.php
```

A API ficará disponível em `http://localhost:8080`.

## 7. Documentação dos Endpoints

### GET /status
- **Objetivo:** verificar o funcionamento da API.
- **Requisição:** `GET http://localhost:8080/status`
- **Resposta (200 OK):**
```json
{ "status": "ok" }
```

### GET /missoes
- **Objetivo:** listar todas as missões.
- **Requisição:** `GET http://localhost:8080/missoes`
- **Resposta (200 OK):**
```json
[
  { "id": 1, "nome": "Apollo 11", "ano": 1969, "agencia": "NASA", "status": "Concluída" }
]
```

### GET /missoes/{id}
- **Objetivo:** buscar uma missão pelo ID.
- **Requisição:** `GET http://localhost:8080/missoes/1`
- **Resposta (200 OK):**
```json
{ "id": 1, "nome": "Apollo 11", "ano": 1969, "agencia": "NASA", "status": "Concluída" }
```
- **Resposta (404 Not Found):**
```json
{ "erro": "Missão não encontrada" }
```

### POST /missoes
- **Objetivo:** cadastrar uma nova missão.
- **Requisição:** `POST http://localhost:8080/missoes`
```json
{ "nome": "Artemis III", "ano": 2027, "agencia": "NASA", "status": "Planejada" }
```
- **Resposta (201 Created):**
```json
{ "id": 6, "nome": "Artemis III", "ano": 2027, "agencia": "NASA", "status": "Planejada" }
```
- **Resposta (400 Bad Request)** se faltar algum campo obrigatório.

### PUT /missoes/{id}
- **Objetivo:** atualizar uma missão existente.
- **Requisição:** `PUT http://localhost:8080/missoes/6`
```json
{ "nome": "Artemis III", "ano": 2027, "agencia": "NASA", "status": "Em preparação" }
```
- **Resposta (200 OK):**
```json
{ "id": 6, "nome": "Artemis III", "ano": 2027, "agencia": "NASA", "status": "Em preparação" }
```
- **Resposta (404 Not Found):**
```json
{ "erro": "Missão não encontrada" }
```

### DELETE /missoes/{id}
- **Objetivo:** remover uma missão.
- **Requisição:** `DELETE http://localhost:8080/missoes/6`
- **Resposta:** `204 No Content` (sem corpo) ou `404 Not Found` se não existir.

