# Diagramas - Módulo de Logs

## Visão Geral da Arquitetura

```mermaid
C4Context
    title Visão Geral da Arquitetura - Sistema de Logs

    Person(client, "Cliente/Aplicação", "Microserviços que geram logs")
    
    System_Boundary(loggr, "Loggr - Sistema de Logs") {
        Container(api, "API REST", "Laravel/PHP", "Recebe e processa requisições de logs")
        Container(queue, "Queue System", "Redis/Database", "Processa logs assincronamente")
        ContainerDb(mongodb, "MongoDB", "NoSQL Database", "Armazena documentos de logs")
    }

    Rel(client, api, "Envia logs via HTTP", "JSON/REST")
    Rel(api, queue, "Enfileira jobs")
    Rel(queue, mongodb, "Persiste logs")
    Rel(api, mongodb, "Consulta logs")
```

### Arquitetura em Camadas (Detalhada)

```mermaid
graph TB
    subgraph "Cliente"
        Client[Aplicações/Microserviços]
    end

    subgraph "API Layer - Laravel"
        Routes[Routes<br/>api.php]
        Middleware[Middleware<br/>CORS, Rate Limit]
        Controller[LogController<br/>REST Endpoints]
    end

    subgraph "Request Validation"
        StoreReq[StoreLogRequest<br/>Validação de Criação]
        IndexReq[IndexLogRequest<br/>Validação de Busca]
    end

    subgraph "Business Logic"
        Service[LogService<br/>Regras de Negócio<br/>Geração de UUIDs]
    end

    subgraph "Async Processing"
        Queue[Queue System<br/>Redis/Database]
        Job[ProcessLogEntry Job<br/>Processamento Assíncrono]
    end

    subgraph "Data Access"
        RepoInterface[LogRepositoryInterface<br/>Contrato]
        Repository[LogRepository<br/>Queries MongoDB]
        Model[Log Model<br/>Eloquent MongoDB]
    end

    subgraph "Response Transformation"
        Resource[LogResource<br/>JSON Serialization]
    end

    subgraph "Infrastructure"
        MongoDB[(MongoDB<br/>Collection: logs<br/>Índices Otimizados)]
    end

    Client -->|HTTP Request| Routes
    Routes --> Middleware
    Middleware --> Controller
    
    Controller -->|Validate| StoreReq
    Controller -->|Validate| IndexReq
    Controller -->|Use| Service
    
    Service -->|Dispatch| Job
    Service -->|Depends| RepoInterface
    
    Job --> Queue
    Queue -->|Process| Job
    Job -->|Create| Model
    
    RepoInterface -.->|Implements| Repository
    Repository -->|CRUD| Model
    Model -->|Persist| MongoDB
    
    Repository -->|Returns| Service
    Service -->|Returns| Controller
    Controller -->|Transform| Resource
    Resource -->|JSON Response| Client

    style Client fill:#e3f2fd
    style Controller fill:#fff3e0
    style Service fill:#f3e5f5
    style Repository fill:#e8f5e9
    style MongoDB fill:#c8e6c9
    style Queue fill:#ffe0b2
    style Job fill:#ffccbc
```

### Fluxo de Dados Completo

```mermaid
flowchart TD
    Start([Cliente envia POST /api/logs])
    
    subgraph "API Gateway"
        A[Routes/Middleware]
        B[LogController::store]
    end
    
    subgraph "Validação"
        C[StoreLogRequest::validate]
        D{Válido?}
    end
    
    subgraph "Processamento"
        E[LogService::createAsync]
        F[Gera UUIDs<br/>id + trace_id]
        G[ProcessLogEntry::dispatch]
    end
    
    subgraph "Response"
        H[Retorna 202 Accepted<br/>com IDs gerados]
    end
    
    subgraph "Background Processing"
        I[Queue System]
        J[ProcessLogEntry::handle]
        K[Log::create]
        L[(MongoDB<br/>logs collection)]
    end
    
    Start --> A
    A --> B
    B --> C
    C --> D
    D -->|Sim| E
    D -->|Não| Error[Retorna 422<br/>Validation Error]
    E --> F
    F --> G
    G --> H
    H --> End([Cliente recebe resposta])
    
    G -.->|Enfileira| I
    I -.->|Processa| J
    J --> K
    K --> L
    L -.->|Persistido| Success([Log salvo])
    
    style Start fill:#e3f2fd
    style End fill:#c8e6c9
    style Error fill:#ffcdd2
    style Success fill:#c8e6c9
    style L fill:#a5d6a7
```

### Arquitetura de Microserviços (Contexto)

```mermaid
graph TB
    subgraph "Microserviços da Empresa"
        MS1[Auth Service]
        MS2[Payment Service]
        MS3[Order Service]
        MS4[Notification Service]
        MS5[Other Services...]
    end

    subgraph "Loggr - Sistema Centralizado de Logs"
        API[API REST<br/>Laravel]
        Queue[Queue System]
        Mongo[(MongoDB)]
    end

    subgraph "Observabilidade"
        Dashboard[Dashboard<br/>Visualização de Logs]
        Alerts[Sistema de Alertas<br/>Logs Críticos]
        Analytics[Analytics<br/>Agregações e Métricas]
    end

    MS1 -->|Envia logs| API
    MS2 -->|Envia logs| API
    MS3 -->|Envia logs| API
    MS4 -->|Envia logs| API
    MS5 -->|Envia logs| API

    API --> Queue
    Queue --> Mongo
    
    Mongo --> Dashboard
    Mongo --> Alerts
    Mongo --> Analytics

    style API fill:#4caf50
    style Queue fill:#ff9800
    style Mongo fill:#2196f3
```

### Stack Tecnológica

```mermaid
mindmap
    root((Loggr System))
        Backend
            PHP 8.1+
            Laravel 10.x
            Composer
        Database
            MongoDB 5.0+
            Índices Otimizados
            Replicação
        Queue
            Redis
            Database Queue
            Supervisor
        API
            RESTful
            JSON
            HTTP/HTTPS
        DevOps
            Docker
            Laravel Sail
            Git
        Patterns
            Repository Pattern
            Service Layer
            Dependency Injection
            Job Queue
            SOLID Principles
```

## Diagrama Entidade-Relacionamento (ER)

```mermaid
erDiagram
    LOGS {
        string _id PK "UUID - Chave Primária"
        string trace_id "UUID - Rastreamento"
        string message "Mensagem do log"
        string level "Nível do log"
        string service_name "Nome do serviço"
        datetime timestamp "Data/hora do evento"
        datetime created_at "Data de criação"
        datetime updated_at "Data de atualização"
    }

    LOGS ||--o{ LOGS : "agrupa por trace_id"
```

### Descrição dos Atributos

| Campo | Tipo | Descrição | Constraints |
|-------|------|-----------|-------------|
| `_id` | UUID (string) | Identificador único do log | PRIMARY KEY, NOT NULL, AUTO-GENERATED |
| `trace_id` | UUID (string) | ID para correlacionar logs relacionados | NOT NULL, INDEXED, AUTO-GENERATED |
| `message` | String | Mensagem descritiva do log | NOT NULL |
| `level` | Enum(string) | Nível de severidade | NOT NULL, ENUM: debug, info, notice, warning, error, critical, alert, emergency |
| `service_name` | String | Nome do serviço que gerou o log | NOT NULL, MAX: 255, INDEXED |
| `timestamp` | DateTime | Data e hora do evento logado | NULLABLE, INDEXED, DEFAULT: NOW() |
| `created_at` | DateTime | Data de criação do registro | AUTO, INDEXED |
| `updated_at` | DateTime | Data da última atualização | AUTO |

### Índices MongoDB

```mermaid
graph TB
    subgraph "Índices Simples"
        A[trace_id]
        B[level]
        C[service_name]
        D[timestamp]
    end

    subgraph "Índices Compostos"
        E["trace_id + timestamp (desc)"]
        F["service_name + level + timestamp (desc)"]
    end

    G[(Collection: logs)]

    A -.-> G
    B -.-> G
    C -.-> G
    D -.-> G
    E -.-> G
    F -.-> G

    style G fill:#e8f5e9
```

### Relacionamento por trace_id

```mermaid
graph LR
    subgraph "Request Flow com mesmo trace_id"
        L1[Log 1: Request iniciada<br/>trace_id: abc-123]
        L2[Log 2: Validação OK<br/>trace_id: abc-123]
        L3[Log 3: Processando<br/>trace_id: abc-123]
        L4[Log 4: Concluído<br/>trace_id: abc-123]
    end

    L1 -.->|mesmo trace_id| L2
    L2 -.->|mesmo trace_id| L3
    L3 -.->|mesmo trace_id| L4

    style L1 fill:#e3f2fd
    style L2 fill:#e3f2fd
    style L3 fill:#e3f2fd
    style L4 fill:#c8e6c9
```

### Modelo de Dados MongoDB (Documento)

```json
{
  "_id": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Usuário realizou login com sucesso",
  "level": "info",
  "service_name": "auth-service",
  "timestamp": {
    "$date": "2024-12-07T10:30:00.000Z"
  },
  "created_at": {
    "$date": "2024-12-07T10:30:01.234Z"
  },
  "updated_at": {
    "$date": "2024-12-07T10:30:01.234Z"
  }
}
```

### Cardinalidade

- **1 trace_id** pode ter **N logs** (relação 1:N)
- Permite rastrear todo o fluxo de uma requisição ou operação
- Logs são independentes, mas podem ser correlacionados via `trace_id`

### Queries Comuns

```javascript
// Buscar todos os logs de um trace específico
db.logs.find({ trace_id: "550e8400-e29b-41d4-a716-446655440000" })
  .sort({ timestamp: -1 })

// Buscar logs de erro de um serviço
db.logs.find({ 
  service_name: "payment-service",
  level: "error"
}).sort({ timestamp: -1 })

// Buscar logs em um período
db.logs.find({
  timestamp: {
    $gte: ISODate("2024-12-01T00:00:00Z"),
    $lte: ISODate("2024-12-07T23:59:59Z")
  }
}).sort({ timestamp: -1 })

// Agregação: contar logs por nível
db.logs.aggregate([
  { $group: { _id: "$level", count: { $sum: 1 } } },
  { $sort: { count: -1 } }
])

// Agregação: contar logs por serviço
db.logs.aggregate([
  { $group: { _id: "$service_name", count: { $sum: 1 } } },
  { $sort: { count: -1 } }
])
```

## Diagrama de Classes Completo

```mermaid
classDiagram
    %% Controllers
    class LogController {
        -LogService logService
        +__construct(LogService)
        +index(IndexLogRequest) AnonymousResourceCollection
        +store(StoreLogRequest) JsonResponse
        +show(string) JsonResponse
    }

    %% Services
    class LogService {
        -LogRepositoryInterface logRepository
        +__construct(LogRepositoryInterface)
        +createAsync(array) array
        +findById(string) Log|null
        +search(array, int) LengthAwarePaginator
    }

    %% Repositories
    class LogRepositoryInterface {
        <<interface>>
        +create(array) Log
        +findById(string) Log|null
        +search(array, int) LengthAwarePaginator
    }

    class LogRepository {
        +create(array) Log
        +findById(string) Log|null
        +search(array, int) LengthAwarePaginator
    }

    %% Models
    class Log {
        #string connection
        #string collection
        #string primaryKey
        #string keyType
        #bool incrementing
        #array fillable
        #array casts
        +boot() void
    }

    %% Jobs
    class ProcessLogEntry {
        -array logData
        +int tries
        +int timeout
        +__construct(array)
        +handle() void
        +failed(Throwable) void
    }

    %% Requests
    class StoreLogRequest {
        +authorize() bool
        +rules() array
        +messages() array
    }

    class IndexLogRequest {
        +authorize() bool
        +rules() array
        +messages() array
    }

    %% Resources
    class LogResource {
        +toArray(Request) array
    }

    %% Traits
    class ShouldQueue {
        <<interface>>
    }

    class Model {
        <<abstract>>
    }

    class FormRequest {
        <<abstract>>
    }

    class JsonResource {
        <<abstract>>
    }

    %% Relationships
    LogController --> LogService : usa
    LogController --> IndexLogRequest : recebe
    LogController --> StoreLogRequest : recebe
    LogController --> LogResource : retorna

    LogService --> LogRepositoryInterface : depende
    LogService --> ProcessLogEntry : despacha
    LogService --> Log : retorna

    LogRepository ..|> LogRepositoryInterface : implementa
    LogRepository --> Log : cria/busca

    ProcessLogEntry ..|> ShouldQueue : implementa
    ProcessLogEntry --> Log : cria

    StoreLogRequest --|> FormRequest : herda
    IndexLogRequest --|> FormRequest : herda

    LogResource --|> JsonResource : herda
    LogResource --> Log : transforma

    Log --|> Model : herda
```

## Diagrama por Camadas

```mermaid
graph TB
    subgraph "Camada de Apresentação"
        A[LogController]
        B[LogResource]
    end

    subgraph "Camada de Validação"
        C[StoreLogRequest]
        D[IndexLogRequest]
    end

    subgraph "Camada de Negócio"
        E[LogService]
        F[ProcessLogEntry Job]
    end

    subgraph "Camada de Dados"
        G[LogRepositoryInterface]
        H[LogRepository]
        I[Log Model]
    end

    subgraph "Infraestrutura"
        J[(MongoDB)]
        K[Queue System]
    end

    A -->|valida| C
    A -->|valida| D
    A -->|usa| E
    A -->|retorna| B

    E -->|depende| G
    E -->|despacha| F
    H -->|implementa| G
    H -->|CRUD| I
    F -->|cria| I

    I -->|persiste| J
    F -->|processa| K

    style A fill:#e1f5ff
    style E fill:#fff4e1
    style H fill:#e8f5e9
    style I fill:#f3e5f5
    style F fill:#ffe0b2
```

## Fluxo de Criação de Log (Assíncrono)

```mermaid
sequenceDiagram
    participant Client
    participant Controller as LogController
    participant Request as StoreLogRequest
    participant Service as LogService
    participant Job as ProcessLogEntry
    participant Queue as Queue System
    participant Repo as LogRepository
    participant Model as Log
    participant DB as MongoDB

    Client->>Controller: POST /api/logs
    Controller->>Request: validate()
    Request-->>Controller: validated data
    Controller->>Service: createAsync(data)
    Service->>Service: gera UUIDs
    Service->>Job: dispatch(data)
    Job->>Queue: enfileira
    Service-->>Controller: {id, trace_id}
    Controller-->>Client: 202 Accepted

    Note over Queue,DB: Processamento Assíncrono

    Queue->>Job: executa
    Job->>Job: garante UUIDs
    Job->>Model: create(data)
    Model->>Model: boot() - gera UUIDs se necessário
    Model->>DB: insert document
    DB-->>Model: documento criado
    Model-->>Job: Log instance
```

## Fluxo de Busca de Logs

```mermaid
sequenceDiagram
    participant Client
    participant Controller as LogController
    participant Request as IndexLogRequest
    participant Service as LogService
    participant Repo as LogRepository
    participant Model as Log
    participant DB as MongoDB
    participant Resource as LogResource

    Client->>Controller: GET /api/logs?filters
    Controller->>Request: validate()
    Request-->>Controller: validated filters
    Controller->>Service: search(filters, perPage)
    Service->>Repo: search(filters, perPage)
    Repo->>Model: query builder
    Model->>DB: find with filters
    DB-->>Model: documents
    Model-->>Repo: Collection
    Repo-->>Service: LengthAwarePaginator
    Service-->>Controller: LengthAwarePaginator
    Controller->>Resource: collection(logs)
    Resource-->>Controller: AnonymousResourceCollection
    Controller-->>Client: 200 OK + JSON
```

## Padrões de Design Utilizados

```mermaid
mindmap
    root((Módulo de Logs))
        Repository Pattern
            Interface
            Implementação
            Abstração de Dados
        Dependency Injection
            Constructor Injection
            Service Container
            IoC
        Service Layer
            Lógica de Negócio
            Orquestração
            Isolamento
        Job Queue Pattern
            Processamento Assíncrono
            Retry Logic
            Fail Handling
        Resource Pattern
            Transformação de Dados
            API Response
            Serialização
        SOLID Principles
            Single Responsibility
            Dependency Inversion
            Interface Segregation
```

## Estrutura de Diretórios

```
app/
├── Http/
│   ├── Controllers/
│   │   └── LogController.php          # Controlador REST
│   ├── Requests/
│   │   ├── StoreLogRequest.php        # Validação de criação
│   │   └── IndexLogRequest.php        # Validação de busca
│   └── Resources/
│       └── LogResource.php            # Transformação de resposta
├── Services/
│   └── LogService.php                 # Lógica de negócio
├── Repositories/
│   ├── LogRepositoryInterface.php     # Contrato
│   └── LogRepository.php              # Implementação
├── Models/
│   └── Log.php                        # Model MongoDB
└── Jobs/
    └── ProcessLogEntry.php            # Job assíncrono
```

## Responsabilidades das Classes

### LogController
- Recebe requisições HTTP
- Valida entrada via Form Requests
- Delega operações ao Service
- Retorna respostas formatadas via Resources

### LogService
- Contém lógica de negócio
- Gera UUIDs antes de persistir
- Despacha jobs assíncronos
- Orquestra operações do Repository

### LogRepository
- Abstrai acesso aos dados
- Implementa queries no MongoDB
- Retorna Models ou Collections
- Aplica filtros de busca

### Log (Model)
- Representa entidade no MongoDB
- Define atributos e casts
- Gera UUIDs automaticamente (boot)
- Gerencia timestamps

### ProcessLogEntry (Job)
- Processa logs assincronamente
- Implementa retry logic (3 tentativas)
- Trata falhas
- Persiste no banco via Model

### Form Requests
- **StoreLogRequest**: Valida criação de logs
- **IndexLogRequest**: Valida filtros de busca

### LogResource
- Transforma Model em JSON
- Formata datas (ISO 8601)
- Expõe apenas campos necessários

## Injeção de Dependências

```mermaid
graph LR
    A[AppServiceProvider] -->|bind| B[LogRepositoryInterface]
    B -.->|resolve to| C[LogRepository]
    D[LogService] -->|depends on| B
    E[LogController] -->|depends on| D

    style A fill:#e1f5ff
    style B fill:#fff4e1
    style C fill:#e8f5e9
    style D fill:#f3e5f5
    style E fill:#ffe0b2
```

**Configuração no AppServiceProvider:**
```php
$this->app->bind(
    LogRepositoryInterface::class,
    LogRepository::class
);
```

Isso permite que o Laravel injete automaticamente a implementação correta quando `LogRepositoryInterface` for solicitada.
