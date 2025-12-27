# Especificação Funcional – Sistema Loggr

## 1. Visão Geral

O Loggr é um sistema centralizado de registro e consulta de logs de aplicações. Seu objetivo é receber eventos de múltiplos serviços (microserviços, APIs, jobs, etc.), armazená‑los em MongoDB e disponibilizar uma API para consulta estruturada, mantendo rastreabilidade via `trace_id` e garantindo alta performance através de persistência assíncrona.

## 2. Escopo

- Receber logs via API REST.
- Processar e persistir logs de forma assíncrona em MongoDB.
- Disponibilizar endpoints para consulta paginada e filtrada de logs.
- Permitir correlação de eventos por `trace_id`.
- Oferecer documentação e artefatos para testes funcionais e de carga.

## 3. Atores

- **Serviços Clientes (Client Apps)**: aplicações/microserviços que enviam logs para o Loggr.
- **Desenvolvedores / Observabilidade**: consomem a API de consulta para debug, monitoramento e análise.
- **Operações / DevOps**: monitoram filas, performance, armazenamento e integridade do sistema.

## 4. Entidade Principal

### 4.1. Log

Atributos funcionais da entidade `Log`:

- `id` (`_id` no MongoDB): UUID, identificador único do log.
- `trace_id`: UUID, identificador para correlação de eventos de uma mesma requisição/fluxo.
- `message`: texto descritivo do evento.
- `level`: nível de severidade (debug, info, notice, warning, error, critical, alert, emergency).
- `service_name`: nome lógico do serviço emissor (ex: `auth-service`, `payment-service`).
- `timestamp`: data/hora do evento no sistema que gerou o log.
- `created_at` / `updated_at`: timestamps de persistência no Loggr.

Regras:

- `id` e `trace_id` são UUIDs (v4) e, se não enviados, devem ser gerados pelo sistema.
- `message`, `level` e `service_name` são obrigatórios na criação.
- `timestamp` é opcional; se não informado, o sistema utiliza a data/hora atual.

## 5. Módulo de Ingestão de Logs (API de Escrita)

### 5.1. Endpoint – Criar Log

- **Método/rota**: `POST /api/logs`
- **Autenticação**: inicialmente não obrigatória (ambiente interno); para produção, recomenda‑se proteger via token (Sanctum ou gateway).

#### 5.1.1. Entrada

Body JSON:

- `message` (string, obrigatório)
- `level` (string, obrigatório – um dos: debug, info, notice, warning, error, critical, alert, emergency)
- `service_name` (string, obrigatório, máx. 255)
- `trace_id` (string UUID, opcional – se omitido, o sistema gera)
- `timestamp` (string ISO 8601, opcional – se omitido, o sistema usa `now()`)

Validações funcionais:

- Rejeitar requisições sem `message`, `level` ou `service_name`.
- Rejeitar `level` fora da lista permitida.
- Validar formato de `trace_id` quando enviado (UUID).
- Validar formato de `timestamp` quando enviado (data/hora válida).

#### 5.1.2. Comportamento

- Validar os dados com `StoreLogRequest`.
- Gerar `id` e `trace_id` (quando ausentes) antes de enfileirar o job.
- Despachar um job (`ProcessLogEntry`) para processamento assíncrono.
- Não bloquear o cliente pela escrita no banco; retornar resposta imediatamente após enfileiramento.

#### 5.1.3. Saída

- **HTTP 202 – Accepted**:
  - Corpo JSON com:
    - `message`: texto informativo ("Log será processado em breve.").
    - `id`: UUID gerado para o log.
    - `trace_id`: UUID do fluxo (enviado ou gerado).
- **HTTP 422 – Unprocessable Entity**:
  - Lista de erros de validação por campo.

## 6. Módulo de Consulta de Logs (API de Leitura)

### 6.1. Endpoint – Listar/Buscar Logs

- **Método/rota**: `GET /api/logs`

#### 6.1.1. Parâmetros de Query

- `trace_id` (string UUID, opcional): filtra logs por identificador de rastreamento.
- `level` (string, opcional): filtra por nível de log.
- `service_name` (string, opcional): filtra por serviço emissor.
- `date_from` (string ISO 8601, opcional): filtro de data/hora inicial (`timestamp >=`).
- `date_to` (string ISO 8601, opcional): filtro de data/hora final (`timestamp <=`).
- `per_page` (inteiro, opcional, padrão 15, máximo 100): tamanho da página.
- `page` (inteiro, opcional, padrão 1): número da página.

Validações com `IndexLogRequest`:

- `trace_id` deve ser UUID quando presente.
- `level` deve estar entre os níveis permitidos.
- `date_to` deve ser maior ou igual a `date_from` quando ambos forem fornecidos.
- `per_page` deve estar no intervalo [1, 100].

#### 6.1.2. Comportamento

- Montar filtro dinâmico com base nos parâmetros recebidos.
- Consultar o repositório (`LogRepository::search`) que aplica filtros em MongoDB.
- Ordenar os resultados por `timestamp` desc.
- Retornar dados em formato paginado (estrutura padrão Laravel paginator).

#### 6.1.3. Saída

- **HTTP 200 – OK**:
  - Corpo JSON contendo:
    - `data`: lista de logs transformados por `LogResource` (campos: id, trace_id, message, level, service_name, timestamp, created_at, updated_at).
    - `links` e `meta`: informações de paginação (current_page, per_page, total, etc.).

### 6.2. Endpoint – Buscar Log por ID

- **Método/rota**: `GET /api/logs/{id}`

Regras:

- `id` deve ser um UUID.
- Quando o log existir:
  - Retornar **HTTP 200** com recurso único em `data` (via `LogResource`).
- Quando o log não existir:
  - Retornar **HTTP 404** com corpo `{ "message": "Log não encontrado." }`.

## 7. Processamento Assíncrono

### 7.1. Job `ProcessLogEntry`

Responsabilidades funcionais:

- Receber payload do log já com `id` e `trace_id` pré-preenchidos pelo service.
- Garantir novamente que `id`, `trace_id` e `timestamp` estejam definidos (fallback de segurança).
- Persistir o documento na collection `logs` via `Log` (Model MongoDB).
- Em caso de falha:
  - Tentar novamente até `tries` (3 tentativas padrão).
  - Registrar no log de aplicação detalhes do erro e dados do log não processado.

### 7.2. Filas

- O sistema deve utilizar o mecanismo de filas do Laravel (driver configurável: Redis, DB, etc.).
- Jobs enfileirados devem ser processados por `queue:work` em background.
- Deve ser possível listar jobs falhos (`queue:failed`) para análise operacional.

## 8. Repositório e Acesso a Dados

### 8.1. `LogRepositoryInterface`

Define as assinaturas:

- `create(array $data): Log`
- `findById(string $id): ?Log`
- `search(array $filters, int $perPage): LengthAwarePaginator`

### 8.2. `LogRepository`

Comportamento:

- `create` deve criar um documento na collection `logs`.
- `findById` deve procurar por `_id` (UUID) em MongoDB.
- `search` deve aplicar filtros:
  - Igualdade para `trace_id`, `level`, `service_name`.
  - Intervalo de data para `timestamp` (`date_from`, `date_to`).
  - Ordenação por `timestamp` desc.
  - Paginação de acordo com `per_page`.

## 9. Serviço de Domínio (`LogService`)

Responsabilidades funcionais:

- `createAsync(array $data): array`
  - Validar minimamente o payload recebido (já validado no Form Request).
  - Gerar `id` e `trace_id` (UUID) quando não presentes.
  - Garantir `timestamp` (ISO 8601) quando não presente.
  - Despachar `ProcessLogEntry` para a fila.
  - Retornar os identificadores gerados: `['id' => ..., 'trace_id' => ...]`.

- `findById(string $id): ?Log`
  - Delegar busca ao repositório.

- `search(array $filters, int $perPage): LengthAwarePaginator`
  - Delegar busca ao repositório.

## 10. Transformação de Resposta (`LogResource`)

Regras:

- Expor campos:
  - `id` (mapeando `_id` do MongoDB).
  - `trace_id`, `message`, `level`, `service_name`.
  - `timestamp`, `created_at`, `updated_at` em formato ISO 8601.
- Garantir que campos de data possam ser nulos e tratados com `?->toIso8601String()`.

## 11. Regras de Validação (Resumo)

### 11.1. Criação de Log (`StoreLogRequest`)

- `trace_id`: nullable, string, UUID.
- `message`: required, string.
- `level`: required, string, in (debug, info, notice, warning, error, critical, alert, emergency).
- `service_name`: required, string, max:255.
- `timestamp`: nullable, date.

### 11.2. Busca de Logs (`IndexLogRequest`)

- `trace_id`: nullable, string, UUID.
- `level`: nullable, string, in (níveis válidos).
- `service_name`: nullable, string, max:255.
- `date_from`: nullable, date.
- `date_to`: nullable, date, after_or_equal:date_from.
- `per_page`: nullable, integer, min:1, max:100.

## 12. Requisitos Não Funcionais (Resumo)

> Embora o foco seja funcional, estes requisitos impactam diretamente o comportamento esperado.

- **Desempenho**:
  - Criação de log: resposta em < 200ms (sem considerar processamento da fila).
  - Listagem simples: resposta em < 500ms para consultas típicas.
  - Consultas complexas: resposta em < 2000ms em cenários de carga moderada.
- **Escalabilidade**:
  - Uso de filas para desacoplar ingestão de persistência.
  - Uso de MongoDB com índices específicos para campos de filtro.
- **Confiabilidade**:
  - Retentativas de jobs de persistência (3 tentativas).
  - Registro de erros de jobs falhos em log de aplicação.
- **Observabilidade**:
  - Logs estruturados no próprio Loggr para falhas internas.
  - Artefatos de teste de carga Postman/Newman para benchmark.

## 13. Integrações e Artefatos de Suporte

- **Documentação de API**: `API_LOGS_DOCUMENTATION.md` – descreve endpoints, exemplos de uso e detalhes técnicos.
- **Diagramas de Arquitetura e ER**: `DIAGRAMA_LOGS.md` – visão em alto nível, entidades e fluxos.
- **Boas Práticas de Código**: `BEST_PRACTICES.md` – convenções de código, SOLID, uso de MongoDB.
- **Testes de Carga**:
  - Collection Postman: `postman/Loggr_Load_Testing.postman_collection.json`.
  - Environment Postman: `postman/Loggr_Environment.postman_environment.json`.
  - Guia de execução: `postman/README_LOAD_TESTING.md`.

## 14. Casos de Uso Principais

1. **UC01 – Registrar Log de Evento**
   - Ator: Serviço Cliente.
   - Fluxo principal:
     1. Serviço envia `POST /api/logs` com mensagem, nível e service_name.
     2. Sistema valida dados.
     3. Sistema gera `id` e `trace_id` (se necessário).
     4. Sistema enfileira job de persistência.
     5. Sistema retorna `202 Accepted` com `id` e `trace_id`.

2. **UC02 – Consultar Logs por trace_id**
   - Ator: Desenvolvedor/Observabilidade.
   - Fluxo principal:
     1. Usuário chama `GET /api/logs?trace_id={uuid}`.
     2. Sistema valida `trace_id`.
     3. Sistema aplica filtro e paginação.
     4. Sistema retorna lista de logs daquele fluxo.

3. **UC03 – Consultar Logs por nível e serviço**
   - Ator: Desenvolvedor/Observabilidade.
   - Fluxo principal:
     1. Usuário chama `GET /api/logs?level=error&service_name=payment-service`.
     2. Sistema valida parâmetros.
     3. Sistema filtra por `level` e `service_name`.
     4. Sistema retorna lista paginada de erros daquele serviço.

4. **UC04 – Analisar um Log Específico**
   - Ator: Desenvolvedor/Observabilidade.
   - Fluxo principal:
     1. Usuário obtém `id` de um log relevante.
     2. Usuário chama `GET /api/logs/{id}`.
     3. Sistema retorna detalhes completos do log.

5. **UC05 – Executar Testes de Carga da API de Logs**
   - Ator: DevOps/QA.
   - Fluxo principal:
     1. Executor utiliza coleção Postman/Newman indicada.
     2. Sistema recebe alto volume de requisições de escrita/leitura.
     3. Métricas de performance são coletadas e analisadas.

---

Este documento descreve o comportamento esperado do sistema Loggr do ponto de vista funcional, servindo como referência para desenvolvimento, testes e evolução da solução.