# Otimização incremental de queries e relações

## Objetivo

Reduzir consultas repetidas, respostas excessivamente grandes e carregamento desnecessário de relações no backend Laravel, preservando os contratos atuais da API e as regras de isolamento por psicólogo.

## Escopo

- Consolidar métricas repetidas do dashboard e de pacientes em agregações SQL.
- Reutilizar dados já carregados no dashboard financeiro quando isso não alterar o resultado.
- Paginar a listagem de agendamentos e limitar `per_page` e intervalos excessivos.
- Selecionar somente colunas necessárias nas relações usadas pelos endpoints auditados.
- Carregar somente cartões da versão atual dos templates GameKit.
- Evitar materializar conjuntos desnecessários durante exportações e relatórios, mantendo o formato existente.
- Adicionar índices compostos somente para filtros recorrentes confirmados na auditoria.

## Restrições

- Não alterar regras de negócio nem permissões.
- Não adicionar cache nesta etapa.
- Não mudar o formato esperado pelo frontend; novos metadados de paginação serão compatíveis com o padrão Laravel.
- Não executar migrações contra banco remoto sem conexão e validação do ambiente.

## Componentes afetados

1. `HomeDashboardController`: reduzir contagens e somas sobre a mesma base de agendamentos.
2. `FinanceController`: reduzir buscas duplicadas e restringir colunas quando possível.
3. `PatientController`: consolidar métricas e controlar exportações/alertas.
4. `AppointmentController`: paginação compatível e proteção contra intervalos muito grandes.
5. `GameKitAiController`: restringir eager loading à versão atual dos cartões.
6. Nova migration de índices para pacientes, alertas e recibos de agendamento.

## Fluxo de dados

- Cada endpoint continua filtrando primeiro pelo `psychologist_id` autenticado.
- Agregações retornam apenas números e valores necessários.
- Listas continuam usando eager loading, mas com relações e colunas explicitamente limitadas.
- A agenda passa a retornar paginator Laravel, preservando os itens e adicionando metadados de paginação.

## Tratamento de limites e erros

- `per_page` será limitado a um teto seguro.
- Intervalos de agenda excessivos serão rejeitados com HTTP 422, evitando consultas acidentais de todo o histórico.
- Exportações continuam respondendo como download; quando o conjunto for grande, o processamento deverá evitar duplicação de coleções em memória.

## Validação

- Testes de API para agenda, pacientes, dashboard financeiro e permissões.
- Testes existentes completos.
- Pint, PHPStan/Larastan e build frontend.
- `composer audit`, `npm audit` e `git diff --check`.
- `EXPLAIN` será executado quando houver conexão com o MySQL local; sem conexão, a validação ficará limitada à análise estática e aos testes.

