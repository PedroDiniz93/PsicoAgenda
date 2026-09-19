# Integração visual com Google Calendar

## Objetivo

Exibir na agenda semanal os eventos existentes no calendário primário do Google conectado ao psicólogo. Esses eventos serão referências visuais, não serão convertidos em agendamentos do PsicoAgenda e não bloquearão a criação de sessões locais.

## Decisões de produto

- A sincronização será somente de leitura, sob demanda, para o intervalo visível da agenda.
- Eventos externos exibirão apenas o título original; quando o título estiver vazio, será usado o rótulo neutro Evento do Google. Descrição e demais metadados não serão devolvidos à interface.
- Eventos externos poderão ocupar o mesmo horário de sessões locais e de outros eventos externos.
- Sessões cadastradas no PsicoAgenda continuam obedecendo às regras atuais de disponibilidade e conflito entre sessões locais.
- Eventos externos serão somente leitura: clicar poderá abrir uma indicação de origem, mas não editará nem cancelará o evento no Google.
- Eventos que o próprio PsicoAgenda criou no Google serão excluídos da camada externa usando `google_event_id`, evitando duplicação visual.

## Contrato backend

Adicionar `GET /api/google/calendar/events` autenticado, com `from` e `to` obrigatórios em ISO 8601 e limite máximo de 31 dias. O controller deve obter o psicólogo autenticado e delegar ao `GoogleCalendarService`.

O serviço chamará `GET /calendars/primary/events` com `timeMin`, `timeMax`, `singleEvents=true`, `orderBy=startTime`, `showDeleted=false`, `maxResults` limitado e o fuso horário do psicólogo. A API oficial aceita essa consulta por intervalo e a expansão de recorrências via `singleEvents`; o escopo já usado pelo app (`calendar.events`) permite listar eventos. [Google Events.list](https://developers.google.com/calendar/api/v3/reference/events/list?hl=en)

Paginação será consumida no backend até o limite local. Respostas serão normalizadas para uma projeção mínima:

```json
{
  "id": "google-event-id",
  "source": "google",
  "title": "Título original do evento",
  "start_at": "2026-09-22T09:00:00-03:00",
  "end_at": "2026-09-22T10:00:00-03:00",
  "all_day": false,
  "read_only": true
}
```

Eventos cancelados, sem início/fim utilizáveis ou fora do intervalo serão descartados. Eventos de dia inteiro serão mantidos com `all_day=true`, sem acessar ou devolver título, descrição, convidados, local, links ou tokens.

Falhas de autenticação, escopo ou API não devem vazar detalhes do token. O endpoint retornará erro genérico tratável pela interface; logs conterão apenas psicólogo, status HTTP e contexto técnico sanitizado.

## Contrato frontend

`ScheduleView` fará uma chamada adicional ao carregar a semana e ao navegar entre semanas. O resultado será mantido em estado local, limpo no modo privacidade e combinado com os agendamentos locais apenas para renderização.

Cada evento receberá `source: local|google`. A agenda continuará usando o fluxo atual para abrir modal apenas em itens locais; eventos Google terão aparência verde tracejada, rótulo neutro e nenhuma ação de edição.

Para posicionamento, os eventos de cada dia serão ordenados por início e fim. Um algoritmo de varredura atribuirá a menor coluna livre entre os intervalos ativos e calculará o total de colunas do grupo de sobreposição. O card usará `left = coluna / total`, `width = 1 / total`, com pequena margem interna. Intervalos que não se cruzam voltarão a ocupar a largura disponível.

No mobile, a lista diária continuará exibindo eventos externos como itens somente leitura; não haverá tentativa de encaixar a grade horizontal em telas estreitas.

## Segurança e privacidade

- Nunca expor `google_calendar_token` ou payload bruto do Google; somente o campo de título passa pela projeção segura.
- Não persistir eventos externos em tabelas clínicas/financeiras.
- Não criar pacientes, sessões ou registros a partir de eventos Google.
- Restringir a consulta ao psicólogo autenticado e ao calendário primário da sua própria conexão.
- Respeitar o modo privacidade da sessão, removendo também eventos externos da memória da tela.
- Manter o escopo OAuth existente; uma reconexão não será exigida para tokens que já possuem `calendar.events`.

## Validação

- Testes do serviço para paginação, recorrência, dia inteiro, evento cancelado, erro 401/403 e sanitização da projeção.
- Teste do controller para autenticação, intervalo inválido, intervalo acima de 31 dias e escopo por psicólogo.
- Teste do algoritmo de colunas para dois, três e grupos parcialmente sobrepostos.
- `npm run build`, `php -l` dos arquivos alterados, `php artisan route:list`, `php artisan test` (dependente de `pdo_sqlite` disponível) e `git diff --check`.

## Fora de escopo

Persistência local, webhooks/push do Google, sincronização bidirecional de alterações externas, escolha de múltiplos calendários e bloqueio automático de horários.
