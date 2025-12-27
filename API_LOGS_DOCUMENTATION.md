# API de Logs - Documentação

## Visão Geral

A API de Logs permite gravar e consultar logs de aplicações no MongoDB de forma assíncrona. Todos os logs são processados em background através de filas, garantindo alta performance e escalabilidade.

## Base URL

```
http://localhost:8000/api
```

## Autenticação

Atualmente os endpoints de logs não requerem autenticação. Para ambientes de produção, recomenda-se implementar autenticação via Laravel Sanctum.

## Endpoints

### 1. Criar Log

Grava um novo log de forma assíncrona. O processamento é feito em background através de uma fila.

**Endpoint:** `POST /api/logs`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
  "message": "Usuário realizou login com sucesso",
  "level": "info",
  "service_name": "auth-service",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2024-12-07T10:30:00Z"
}
```

**Parâmetros:**

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `message` | string | Sim | Mensagem do log |
| `level` | string | Sim | Nível do log (debug, info, notice, warning, error, critical, alert, emergency) |
| `service_name` | string | Sim | Nome do serviço que gerou o log (máx. 255 caracteres) |
| `trace_id` | string (UUID) | Não | ID de rastreamento (UUID v4). Se não fornecido, será gerado automaticamente |
| `timestamp` | string (ISO 8601) | Não | Data/hora do log. Se não fornecido, usa a data/hora atual |

**Resposta de Sucesso: `202 Accepted`**

```json
{
  "message": "Log será processado em breve.",
  "id": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Resposta de Erro: `422 Unprocessable Entity`**

```json
{
  "message": "The message field is required. (and 1 more error)",
  "errors": {
    "message": [
      "O campo message é obrigatório."
    ],
    "level": [
      "O campo level é obrigatório."
    ]
  }
}
```

**Exemplo cURL:**
```bash
curl -X POST http://localhost:8000/api/logs \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Erro ao processar pagamento",
    "level": "error",
    "service_name": "payment-service",
    "trace_id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11"
  }'
```

---

### 2. Listar Logs

Consulta logs com filtros opcionais. Suporta paginação.

**Endpoint:** `GET /api/logs`

**Headers:**
```
Accept: application/json
```

**Query Parameters:**

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `trace_id` | string (UUID) | Não | Filtrar por trace_id específico |
| `level` | string | Não | Filtrar por nível (debug, info, notice, warning, error, critical, alert, emergency) |
| `service_name` | string | Não | Filtrar por nome do serviço |
| `date_from` | string (ISO 8601) | Não | Data inicial para filtro de timestamp |
| `date_to` | string (ISO 8601) | Não | Data final para filtro de timestamp |
| `per_page` | integer | Não | Número de registros por página (padrão: 15, máx: 100) |
| `page` | integer | Não | Número da página (padrão: 1) |

**Resposta de Sucesso: `200 OK`**

```json
{
  "data": [
    {
      "id": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
      "trace_id": "550e8400-e29b-41d4-a716-446655440000",
      "message": "Usuário realizou login com sucesso",
      "level": "info",
      "service_name": "auth-service",
      "timestamp": "2024-12-07T10:30:00.000000Z",
      "created_at": "2024-12-07T10:30:01.000000Z",
      "updated_at": "2024-12-07T10:30:01.000000Z"
    },
    {
      "id": "8d0f7780-8536-51ef-cc8e-f18gd2g01bf8",
      "trace_id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
      "message": "Erro ao processar pagamento",
      "level": "error",
      "service_name": "payment-service",
      "timestamp": "2024-12-07T10:29:45.000000Z",
      "created_at": "2024-12-07T10:29:46.000000Z",
      "updated_at": "2024-12-07T10:29:46.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/logs?page=1",
    "last": "http://localhost:8000/api/logs?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "links": [...],
    "path": "http://localhost:8000/api/logs",
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

**Exemplos de Uso:**

```bash
# Buscar todos os logs
curl -X GET "http://localhost:8000/api/logs" \
  -H "Accept: application/json"

# Buscar logs por trace_id
curl -X GET "http://localhost:8000/api/logs?trace_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json"

# Buscar logs de erro do serviço de pagamento
curl -X GET "http://localhost:8000/api/logs?level=error&service_name=payment-service" \
  -H "Accept: application/json"

# Buscar logs em um intervalo de datas
curl -X GET "http://localhost:8000/api/logs?date_from=2024-12-01T00:00:00Z&date_to=2024-12-07T23:59:59Z" \
  -H "Accept: application/json"

# Buscar com paginação customizada (25 registros por página)
curl -X GET "http://localhost:8000/api/logs?per_page=25&page=1" \
  -H "Accept: application/json"
```

---

### 3. Buscar Log por ID

Retorna um log específico pelo seu ID.

**Endpoint:** `GET /api/logs/{id}`

**Headers:**
```
Accept: application/json
```

**Path Parameters:**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | string (UUID) | ID do log |

**Resposta de Sucesso: `200 OK`**

```json
{
  "data": {
    "id": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
    "trace_id": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Usuário realizou login com sucesso",
    "level": "info",
    "service_name": "auth-service",
    "timestamp": "2024-12-07T10:30:00.000000Z",
    "created_at": "2024-12-07T10:30:01.000000Z",
    "updated_at": "2024-12-07T10:30:01.000000Z"
  }
}
```

**Resposta de Erro: `404 Not Found`**

```json
{
  "message": "Log não encontrado."
}
```

**Exemplo cURL:**
```bash
curl -X GET "http://localhost:8000/api/logs/7c9e6679-7425-40de-944b-e07fc1f90ae7" \
  -H "Accept: application/json"
```

---

## Níveis de Log

A API suporta os seguintes níveis de log (baseados no padrão PSR-3):

| Nível | Descrição |
|-------|-----------|
| `debug` | Informações detalhadas para debugging |
| `info` | Eventos informativos gerais |
| `notice` | Eventos normais mas significativos |
| `warning` | Avisos que não são erros |
| `error` | Erros em tempo de execução |
| `critical` | Condições críticas |
| `alert` | Ação deve ser tomada imediatamente |
| `emergency` | Sistema está inutilizável |

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| `200` | Requisição bem-sucedida |
| `202` | Requisição aceita para processamento (assíncrono) |
| `404` | Recurso não encontrado |
| `422` | Erro de validação |
| `500` | Erro interno do servidor |

## Processamento Assíncrono

A criação de logs é **assíncrona**. Quando você faz um POST para `/api/logs`:

1. A API valida os dados
2. Gera UUIDs para `id` e `trace_id` (se não fornecidos)
3. Retorna imediatamente com status `202 Accepted`
4. O log é enfileirado para processamento em background
5. Um worker processa a fila e persiste o log no MongoDB

### Configuração da Fila

Para processar logs em desenvolvimento:

```bash
# Via Sail (Docker)
./vendor/bin/sail artisan queue:work

# Localmente
php artisan queue:work
```

Para produção, configure supervisord ou similar para manter o worker ativo.

## Índices do MongoDB

A collection `logs` possui os seguintes índices para otimizar buscas:

- `trace_id` (simples)
- `level` (simples)
- `service_name` (simples)
- `timestamp` (simples)
- `trace_id + timestamp` (composto, descendente)
- `service_name + level + timestamp` (composto)

## Exemplos de Integração

### JavaScript/Node.js

```javascript
// Criar log
async function createLog(logData) {
  const response = await fetch('http://localhost:8000/api/logs', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(logData)
  });
  
  return await response.json();
}

// Buscar logs
async function getLogs(filters = {}) {
  const params = new URLSearchParams(filters);
  const response = await fetch(`http://localhost:8000/api/logs?${params}`, {
    headers: {
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Uso
await createLog({
  message: 'Erro ao conectar ao banco de dados',
  level: 'error',
  service_name: 'api-gateway'
});

const logs = await getLogs({
  level: 'error',
  service_name: 'api-gateway'
});
```

### Python

```python
import requests
import uuid
from datetime import datetime

# Criar log
def create_log(message, level, service_name, trace_id=None):
    url = 'http://localhost:8000/api/logs'
    
    payload = {
        'message': message,
        'level': level,
        'service_name': service_name,
        'trace_id': trace_id or str(uuid.uuid4()),
        'timestamp': datetime.utcnow().isoformat() + 'Z'
    }
    
    response = requests.post(url, json=payload, headers={
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    })
    
    return response.json()

# Buscar logs
def get_logs(trace_id=None, level=None, service_name=None):
    url = 'http://localhost:8000/api/logs'
    
    params = {}
    if trace_id:
        params['trace_id'] = trace_id
    if level:
        params['level'] = level
    if service_name:
        params['service_name'] = service_name
    
    response = requests.get(url, params=params, headers={
        'Accept': 'application/json'
    })
    
    return response.json()

# Uso
result = create_log(
    message='Usuário autenticado',
    level='info',
    service_name='auth-service'
)

logs = get_logs(level='error', service_name='payment-service')
```

### PHP/Laravel

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Criar log
function createLog(array $data): array
{
    $response = Http::withHeaders([
        'Accept' => 'application/json',
    ])->post('http://localhost:8000/api/logs', array_merge($data, [
        'trace_id' => $data['trace_id'] ?? (string) Str::uuid(),
    ]));
    
    return $response->json();
}

// Buscar logs
function getLogs(array $filters = []): array
{
    $response = Http::withHeaders([
        'Accept' => 'application/json',
    ])->get('http://localhost:8000/api/logs', $filters);
    
    return $response->json();
}

// Uso
$result = createLog([
    'message' => 'Pagamento processado',
    'level' => 'info',
    'service_name' => 'payment-service',
]);

$logs = getLogs([
    'level' => 'error',
    'service_name' => 'payment-service',
]);
```

## Boas Práticas

### 1. Use trace_id para correlacionar logs
```json
{
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Iniciando processamento de pedido #12345",
  "level": "info",
  "service_name": "order-service"
}

{
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Pedido #12345 processado com sucesso",
  "level": "info",
  "service_name": "order-service"
}
```

### 2. Use níveis apropriados
- **debug**: Apenas em desenvolvimento
- **info**: Fluxo normal da aplicação
- **warning**: Situações que merecem atenção
- **error**: Erros que precisam ser investigados

### 3. Inclua contexto relevante
```json
{
  "message": "Falha ao processar pagamento: Cartão recusado",
  "level": "error",
  "service_name": "payment-service",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 4. Use service_name consistente
- Padronize os nomes dos serviços
- Exemplos: `auth-service`, `payment-service`, `order-service`

## Troubleshooting

### Logs não aparecem após criação

1. Verifique se o queue worker está rodando:
```bash
./vendor/bin/sail artisan queue:work
```

2. Verifique os failed jobs:
```bash
./vendor/bin/sail artisan queue:failed
```

3. Verifique o log do Laravel:
```bash
tail -f storage/logs/laravel.log
```

### Erro de conexão com MongoDB

Verifique a configuração no arquivo `.env`:
```env
DB_CONNECTION=mongodb
DB_HOST=mongo
DB_PORT=27017
DB_DATABASE=loggr
```

## Suporte

Para questões ou problemas, consulte:
- Documentação do projeto: `BEST_PRACTICES.md`
- Setup inicial: `README.md`
