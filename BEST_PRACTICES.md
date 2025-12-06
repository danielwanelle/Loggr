# Boas Práticas - Loggr Application

## Visão Geral
Esta aplicação utiliza Laravel com MongoDB seguindo os princípios SOLID e padrões de desenvolvimento modernos.

## Stack Tecnológica
- **Framework**: Laravel
- **Database**: MongoDB (usando `mongodb/laravel-mongodb`)
- **PHP Version**: 8.1+
- **Autenticação**: Laravel Sanctum

## Princípios SOLID

### Single Responsibility Principle (SRP)
- Cada classe deve ter uma única responsabilidade
- Controllers devem apenas coordenar requests e responses
- Lógica de negócio deve estar em Services
- Validações devem estar em Form Requests

### Open/Closed Principle (OCP)
- Classes abertas para extensão, fechadas para modificação
- Use interfaces e abstrações
- Prefira composição sobre herança

### Liskov Substitution Principle (LSP)
- Subclasses devem ser substituíveis por suas classes base
- Mantenha contratos de interface consistentes

### Interface Segregation Principle (ISP)
- Interfaces específicas são melhores que interfaces gerais
- Clientes não devem depender de métodos que não usam

### Dependency Inversion Principle (DIP)
- Dependa de abstrações, não de implementações concretas
- Use Dependency Injection via constructor

## Estrutura de Código

### Models (MongoDB)

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;
    
    // Especificar a conexão MongoDB
    protected $connection = 'mongodb';
    
    // Especificar a collection
    protected $collection = 'users';
    
    // Fillable attributes
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    
    // Hidden attributes
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    // Casts
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

### Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}
    
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        
        return response()->json($user, 201);
    }
}
```

### Services

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}
    
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        
        return $this->userRepository->create($data);
    }
}
```

### Repositories

```php
<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    public function findById(string $id): ?User;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
}
```

```php
<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    public function findById(string $id): ?User
    {
        return User::find($id);
    }
    
    public function update(string $id, array $data): bool
    {
        return User::where('_id', $id)->update($data);
    }
    
    public function delete(string $id): bool
    {
        return User::destroy($id);
    }
}
```

### Form Requests

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

## MongoDB Específico

### Conexão
```php
// config/database.php
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 27017),
    'database' => env('DB_DATABASE', 'loggr'),
    'username' => env('DB_USERNAME', ''),
    'password' => env('DB_PASSWORD', ''),
    'options' => [
        'appName' => 'Loggr',
    ],
],
```

### Relacionamentos
```php
// Um para muitos
public function posts()
{
    return $this->hasMany(Post::class);
}

// Muitos para muitos (embedded)
public function roles()
{
    return $this->embedsMany(Role::class);
}

// Relação inversa
public function user()
{
    return $this->belongsTo(User::class);
}
```

### Queries Específicas do MongoDB
```php
// Operadores MongoDB
User::where('age', '>', 18)->get();
User::whereBetween('created_at', [$start, $end])->get();
User::whereIn('status', ['active', 'pending'])->get();

// Arrays
User::where('tags', 'laravel')->get();
User::whereAll('tags', ['laravel', 'mongodb'])->get();

// Embedded documents
User::where('address.city', 'São Paulo')->get();

// Agregações
User::raw(function($collection) {
    return $collection->aggregate([
        ['$match' => ['status' => 'active']],
        ['$group' => ['_id' => '$city', 'count' => ['$sum' => 1]]],
    ]);
});
```

## Padrões de Código

### Naming Conventions
- **Classes**: PascalCase (ex: `UserService`)
- **Methods**: camelCase (ex: `createUser`)
- **Variables**: camelCase (ex: `$userData`)
- **Constants**: UPPER_SNAKE_CASE (ex: `MAX_ATTEMPTS`)
- **Collections**: plural snake_case (ex: `users`, `blog_posts`)

### Type Hints
- Sempre use type hints em parâmetros e retornos
- Use tipos nativos do PHP 8.1+
- Use union types quando apropriado

```php
public function process(string|int $id): User|null
{
    // implementation
}
```

### Readonly Properties
- Use `readonly` para dependências injetadas
```php
public function __construct(
    private readonly UserService $userService,
    private readonly LogService $logService
) {}
```

## Testes

### Estrutura
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_can_create_a_user(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];
        
        $response = $this->postJson('/api/users', $data);
        
        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'email']);
            
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }
}
```

## Service Providers

### Binding de Repositórios
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\UserRepositoryInterface::class,
        \App\Repositories\UserRepository::class
    );
}
```

## Segurança

### Validação
- Sempre valide input do usuário
- Use Form Requests para validações complexas
- Sanitize dados antes de armazenar

### Autenticação
- Use Laravel Sanctum para API tokens
- Sempre hash passwords com `Hash::make()`
- Implemente rate limiting

### Autorização
- Use Policies para lógica de autorização
- Implemente Gates quando necessário
- Valide permissões em todos os endpoints

## Performance

### Eager Loading
```php
// Evite N+1 queries
$users = User::with('posts', 'roles')->get();
```

### Indexação MongoDB
```php
// Em migrations ou comandos artisan
Schema::collection('users', function ($collection) {
    $collection->index('email');
    $collection->index('created_at');
});
```

### Cache
```php
use Illuminate\Support\Facades\Cache;

$users = Cache::remember('users.active', 3600, function () {
    return User::where('status', 'active')->get();
});
```

## Logging

### Estrutura de Logs
```php
use Illuminate\Support\Facades\Log;

// Contexto estruturado
Log::info('User created', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => request()->ip(),
]);

// Diferentes níveis
Log::debug('Debug information');
Log::error('Error occurred', ['exception' => $e]);
```

## API Resources

### Transformação de Dados
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->_id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

## Comandos Artisan Úteis

```bash
# Criar model com migration e factory
php artisan make:model Post -mf

# Criar controller com resource
php artisan make:controller PostController --resource

# Criar service
php artisan make:class Services/PostService

# Criar repository interface
php artisan make:interface Repositories/PostRepositoryInterface

# Criar form request
php artisan make:request StorePostRequest

# Executar testes
php artisan test

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Git Workflow

### Commits
- Use mensagens descritivas em português
- Formato: `tipo: descrição`
- Tipos: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`

```
feat: adicionar autenticação de usuários
fix: corrigir validação de email
refactor: melhorar estrutura do UserService
docs: atualizar documentação da API
test: adicionar testes para UserController
```

## Referências
- [Laravel Documentation](https://laravel.com/docs)
- [MongoDB Laravel Driver](https://www.mongodb.com/docs/drivers/php/laravel-mongodb/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
