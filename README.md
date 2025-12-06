![Loggr Logo](./Loggr%20logo.png)

# Loggr - Setup e Instalação

## Sobre o Projeto

Loggr é uma aplicação Laravel utilizando MongoDB como banco de dados, desenvolvida seguindo os princípios SOLID e boas práticas de desenvolvimento.

## Stack Tecnológica

- **PHP**: 8.1+
- **Framework**: Laravel 10.x
- **Banco de Dados**: MongoDB
- **Autenticação**: Laravel Sanctum
- **Frontend**: Vite + JavaScript

## Pré-requisitos

Antes de começar, certifique-se de ter instalado em sua máquina:

- PHP 8.1 ou superior
- Composer
- MongoDB 5.0 ou superior
- Node.js 18+ e NPM
- Git

### Extensões PHP Necessárias

```bash
# Ubuntu/Debian
sudo apt-get install -y php8.1-cli php8.1-fpm php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-mongodb

# Verificar extensões instaladas
php -m
```

## Instalação

### 1. Clonar o Repositório

```bash
git clone <url-do-repositorio>
cd Loggr
```

### 2. Instalar Dependências PHP

```bash
composer install
```

### 3. Instalar Dependências JavaScript

```bash
npm install
```

### 4. Configurar Variáveis de Ambiente

Copie o arquivo de exemplo e configure as variáveis:

```bash
cp .env.example .env
```

Edite o arquivo `.env` e configure as seguintes variáveis:

```env
APP_NAME=Loggr
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# MongoDB Configuration
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=loggr
DB_USERNAME=
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
```

### 5. Gerar Application Key

```bash
php artisan key:generate
```

### 6. Configurar MongoDB

#### Opção A: MongoDB Local

Instale o MongoDB:

```bash
# Ubuntu/Debian
sudo apt-get install -y mongodb-org

# Iniciar serviço
sudo systemctl start mongod
sudo systemctl enable mongod

# Verificar status
sudo systemctl status mongod
```

#### Opção B: MongoDB via Docker

```bash
docker run -d \
  --name mongodb \
  -p 27017:27017 \
  -e MONGO_INITDB_DATABASE=loggr \
  -v mongodb_data:/data/db \
  mongo:latest
```

#### Opção C: MongoDB Atlas (Cloud)

1. Crie uma conta em [MongoDB Atlas](https://www.mongodb.com/cloud/atlas)
2. Crie um cluster gratuito
3. Configure o usuário e senha
4. Adicione seu IP à whitelist
5. Obtenha a connection string e atualize no `.env`:

```env
DB_CONNECTION=mongodb
DB_DSN=mongodb+srv://username:password@cluster.mongodb.net/loggr?retryWrites=true&w=majority
```

### 7. Executar Migrations e Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 8. Criar Link de Storage (se necessário)

```bash
php artisan storage:link
```

## Executando a Aplicação

### Desenvolvimento

Abra dois terminais:

**Terminal 1 - Servidor PHP:**
```bash
php artisan serve
```

**Terminal 2 - Build Assets:**
```bash
npm run dev
```

A aplicação estará disponível em: `http://localhost:8000`

### Usando Docker (Opcional)

Se preferir usar Docker Compose:

```bash
docker-compose up -d
```

## Testes

### Executar Todos os Testes

```bash
php artisan test
```

### Executar Testes com Coverage

```bash
php artisan test --coverage
```

### Executar Testes Específicos

```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit

# Teste específico
php artisan test --filter=UserTest
```

## Comandos Úteis

### Limpar Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Otimizar para Produção

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### Tinker (Console Interativo)

```bash
php artisan tinker
```

### Listar Rotas

```bash
php artisan route:list
```

## Estrutura do Projeto

```
app/
├── Console/           # Comandos Artisan
├── Exceptions/        # Tratamento de exceções
├── Foundation/        # Classes base customizadas
├── Http/
│   ├── Controllers/   # Controllers da aplicação
│   ├── Middleware/    # Middlewares
│   └── Requests/      # Form Requests
├── Models/            # Eloquent Models (MongoDB)
├── Repositories/      # Repositories (padrão Repository)
├── Services/          # Camada de serviços (lógica de negócio)
└── Providers/         # Service Providers

config/                # Arquivos de configuração
database/
├── factories/         # Model Factories
├── migrations/        # Migrations
└── seeders/          # Seeders

resources/
├── css/              # Arquivos CSS
├── js/               # Arquivos JavaScript
└── views/            # Blade Templates

routes/
├── api.php           # Rotas da API
├── web.php           # Rotas Web
└── channels.php      # Broadcasting channels

tests/
├── Feature/          # Testes de Feature
└── Unit/             # Testes Unitários
```

## Boas Práticas

Consulte o arquivo `BEST_PRACTICES.md` para conhecer os padrões e práticas recomendadas neste projeto.

## Troubleshooting

### Erro: "Class 'MongoDB\Laravel\MongoDBServiceProvider' not found"

```bash
composer require mongodb/laravel-mongodb
```

### Erro: "ext-mongodb is missing"

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-mongodb
sudo systemctl restart php8.1-fpm
```

### Erro: "Connection refused" ao conectar no MongoDB

Verifique se o MongoDB está rodando:

```bash
sudo systemctl status mongod
```

### Erro de permissão em storage/

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Contribuindo

1. Crie uma branch para sua feature: `git checkout -b feat/nova-feature`
2. Commit suas mudanças: `git commit -m 'feat: adicionar nova feature'`
3. Push para a branch: `git push origin feat/nova-feature`
4. Abra um Pull Request

## Suporte

Para dúvidas ou problemas:
- Verifique a documentação em `BEST_PRACTICES.md`
- Consulte a [documentação do Laravel](https://laravel.com/docs)
- Consulte a [documentação do MongoDB Laravel](https://www.mongodb.com/docs/drivers/php/laravel-mongodb/)

## Licença

Este projeto está sob a licença MIT.
