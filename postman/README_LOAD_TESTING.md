# Guia de Testes de Carga - API de Logs

## Visão Geral

Este guia explica como executar testes de carga na API de Logs usando Postman e Newman.

## Arquivos

- `Loggr_Load_Testing.postman_collection.json` - Collection completa com todos os testes
- `Loggr_Environment.postman_environment.json` - Variáveis de ambiente

## Importar Collection no Postman

1. Abra o Postman
2. Clique em **Import**
3. Selecione o arquivo `Loggr_Load_Testing.postman_collection.json`
4. Importe também o arquivo `Loggr_Environment.postman_environment.json`

## Estrutura da Collection

### 1. Health Check
- Verifica se a API está respondendo
- Testa tempo de resposta básico

### 2. Create Log
- **Create Single Log - Info**: Cria um log de nível info
- **Create Log - Error**: Cria um log de erro
- **Bulk Create - Load Test**: Para testes de carga massivos

### 3. Search Logs
- **List All Logs**: Busca paginada
- **Search by trace_id**: Busca por rastreamento
- **Search by Level**: Filtra por nível
- **Search by Service Name**: Filtra por serviço
- **Complex Search - Load Test**: Teste com múltiplos filtros

### 4. Get Single Log
- **Get Log by ID**: Busca específica
- **Get Non-existent Log**: Teste de erro 404

### 5. Validation Tests
- Testes de validação de campos
- Testes de regras de negócio

## Executar Testes Manualmente

### Teste Individual
1. Selecione uma requisição
2. Clique em **Send**
3. Verifique os resultados dos testes na aba **Test Results**

### Executar Collection Completa
1. Clique com botão direito na collection
2. Selecione **Run collection**
3. Configure as iterações e delay
4. Clique em **Run Loggr - API de Logs - Load Testing**

## Testes de Carga com Newman (CLI)

### Instalação do Newman

```bash
npm install -g newman
npm install -g newman-reporter-htmlextra
```

### Executar Testes Básicos

```bash
# Teste simples
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json

# Com relatório HTML
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/test-report.html
```

### Teste de Carga Leve (100 requisições)

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 100 \
  --delay-request 50 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/load-test-100.html
```

### Teste de Carga Moderado (1.000 requisições)

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 1000 \
  --delay-request 10 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/load-test-1000.html
```

### Teste de Carga Pesado (10.000 requisições)

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 10000 \
  --delay-request 0 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/load-test-10000.html
```

### Teste de Stress (Sem Delay)

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 5000 \
  --delay-request 0 \
  --timeout-request 30000 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/stress-test.html
```

## Teste Focado em Endpoint Específico

### Apenas Criação de Logs

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --folder "Create Log" \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 1000 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/create-log-test.html
```

### Apenas Busca de Logs

```bash
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --folder "Search Logs" \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 500 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/search-log-test.html
```

## Métricas Importantes

### Tempo de Resposta
- **Criação de Log**: < 200ms (202 Accepted)
- **Busca Simples**: < 500ms
- **Busca Complexa**: < 2000ms
- **Busca por ID**: < 300ms

### Taxa de Sucesso
- **Taxa esperada**: > 99%
- **Erros aceitáveis**: < 1%

### Throughput
- **Criação**: > 100 req/s
- **Leitura**: > 200 req/s

## Monitoramento Durante Testes

### 1. Verificar Queue Worker

```bash
# Via Sail
./vendor/bin/sail artisan queue:work --verbose

# Verificar jobs falhados
./vendor/bin/sail artisan queue:failed
```

### 2. Monitorar MongoDB

```bash
# Conectar ao MongoDB
./vendor/bin/sail mongo

# Ver estatísticas
use loggr
db.stats()

# Contar logs
db.logs.countDocuments()

# Ver operações em andamento
db.currentOp()
```

### 3. Monitorar Recursos do Sistema

```bash
# CPU e Memória (Docker)
docker stats

# Logs do Laravel
tail -f storage/logs/laravel.log
```

## Interpretação de Resultados

### Newman CLI Output

```
┌─────────────────────────┬──────────┬──────────┐
│                         │ executed │   failed │
├─────────────────────────┼──────────┼──────────┤
│              iterations │     1000 │        0 │
├─────────────────────────┼──────────┼──────────┤
│                requests │     5000 │        0 │
├─────────────────────────┼──────────┼──────────┤
│            test-scripts │    10000 │        0 │
├─────────────────────────┼──────────┼──────────┤
│      prerequest-scripts │     2000 │        0 │
├─────────────────────────┼──────────┼──────────┤
│              assertions │    15000 │        0 │
├─────────────────────────┴──────────┴──────────┤
│ total run duration: 2m 30s                    │
├───────────────────────────────────────────────┤
│ total data received: 2.5MB (approx)           │
├───────────────────────────────────────────────┤
│ average response time: 145ms                  │
└───────────────────────────────────────────────┘
```

### Análise
- **0 failed**: Todos os testes passaram ✅
- **average response time: 145ms**: Excelente performance ✅
- **total run duration**: Tempo total de execução

## Scripts de Automação

### Script Bash para Múltiplos Testes

Crie um arquivo `run-load-tests.sh`:

```bash
#!/bin/bash

echo "🚀 Iniciando Testes de Carga - API de Logs"
echo "=========================================="

# Criar diretório de relatórios
mkdir -p reports

# Teste 1: Carga Leve
echo "📊 Teste 1: Carga Leve (100 requisições)"
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 100 \
  --delay-request 50 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/load-test-100-$(date +%Y%m%d-%H%M%S).html

# Aguardar processamento
echo "⏳ Aguardando 30 segundos para processar fila..."
sleep 30

# Teste 2: Carga Moderada
echo "📊 Teste 2: Carga Moderada (1.000 requisições)"
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 1000 \
  --delay-request 10 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/load-test-1000-$(date +%Y%m%d-%H%M%S).html

# Aguardar processamento
echo "⏳ Aguardando 60 segundos para processar fila..."
sleep 60

# Teste 3: Teste de Busca
echo "📊 Teste 3: Teste de Busca (500 requisições)"
newman run postman/Loggr_Load_Testing.postman_collection.json \
  --folder "Search Logs" \
  --environment postman/Loggr_Environment.postman_environment.json \
  --iteration-count 500 \
  --reporters cli,htmlextra \
  --reporter-htmlextra-export reports/search-test-$(date +%Y%m%d-%H%M%S).html

echo "✅ Testes Concluídos!"
echo "📁 Relatórios salvos em: reports/"
```

Execute:

```bash
chmod +x run-load-tests.sh
./run-load-tests.sh
```

## Boas Práticas

### Antes dos Testes

1. ✅ Certifique-se de que o queue worker está rodando
2. ✅ Limpe o banco de dados se necessário
3. ✅ Monitore recursos do sistema (CPU, memória, disco)
4. ✅ Configure timeouts adequados

### Durante os Testes

1. 📊 Monitore logs em tempo real
2. 📊 Observe o comportamento da fila
3. 📊 Verifique uso de recursos
4. 📊 Acompanhe taxa de erro

### Depois dos Testes

1. 📈 Analise relatórios HTML
2. 📈 Verifique logs de erro
3. 📈 Confirme que todos os jobs foram processados
4. 📈 Valide dados no MongoDB

## Troubleshooting

### Jobs não estão sendo processados

```bash
# Reiniciar queue worker
./vendor/bin/sail artisan queue:restart

# Verificar failed jobs
./vendor/bin/sail artisan queue:failed
```

### Erro de timeout

Aumente o timeout nas requisições:

```bash
newman run ... --timeout-request 60000
```

### MongoDB lento

Verifique os índices:

```javascript
db.logs.getIndexes()
```

## Exemplos de Resultados Esperados

### ✅ Teste Bem-Sucedido
- Taxa de sucesso: 100%
- Tempo médio: < 200ms
- Sem erros 5xx
- Queue processando normalmente

### ⚠️ Teste com Atenção
- Taxa de sucesso: 95-99%
- Tempo médio: 200-500ms
- Alguns timeouts ocasionais
- Queue com backlog pequeno

### ❌ Teste Problemático
- Taxa de sucesso: < 95%
- Tempo médio: > 1000ms
- Muitos erros 5xx
- Queue com backlog grande

## Próximos Passos

1. Executar testes em ambiente de staging
2. Configurar CI/CD para testes automáticos
3. Implementar dashboards de monitoramento
4. Configurar alertas de performance
5. Escalar workers conforme necessário
