# Feature Registry

Registro curto de features do PsicoAgenda / PsicoControl para recuperar historico sem depender da conversa.

Sempre adicione a nova entrada no topo de `## Features`.

## Formato

```md
### YYYY-MM-DD - Nome da feature

- Status: Implementada | Parcial | Planejada
- Objetivo: problema resolvido.
- Escopo: arquivos ou areas alteradas.
- Comportamento: efeito para o usuario.
- Validacao: comandos ou criterio de aceite.
- Notas: riscos, restricoes ou observacoes.
```

Regras:

- Registre no topo.
- Seja factual e breve.
- Nao repita contexto ja fixo em `AGENTS.md`.

## Features

### 2026-09-19 - Modal para eventos externos

- Status: Implementada
- Objetivo: permitir consultar e excluir um evento externo sem ação imediata ao clicar no card.
- Escopo: ScheduleView.vue e fluxo de exclusão do Google Calendar.
- Comportamento: o clique abre uma modal somente leitura com título, início e fim; a modal oferece “Excluir no Google” com confirmação e estado de carregamento.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Loader da sincronização externa

- Status: Implementada
- Objetivo: sinalizar o carregamento dos eventos do Google antes de atualizar a grade.
- Escopo: ScheduleView.vue.
- Comportamento: a grade recebe uma camada translúcida com blur e spinner “Sincronizando eventos do Google...” enquanto a consulta está em andamento, preservando os agendamentos internos ao fundo.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Exclusão de eventos internos e externos

- Status: Implementada
- Objetivo: permitir remover um agendamento do PsicoAgenda ou um evento importado do Google Calendar.
- Escopo: endpoints de exclusão, serviço GoogleCalendarService e ações da ScheduleView.
- Comportamento: agendamentos internos podem ser excluídos com confirmação e removem o vínculo correspondente no Google; eventos externos são excluídos no Google após confirmação.
- Segurança: consultas e exclusões ficam restritas ao psicólogo autenticado; eventos sincronizados pelo PsicoAgenda não podem ser removidos pelo endpoint de eventos externos.
- Validação: npm run build, php -l dos controllers/serviço, php artisan route:list e git diff --check.

### 2026-09-19 - Indicadores de pacientes em uma linha

- Status: Implementada
- Objetivo: deixar os cards de resumo mais compactos e escaneáveis.
- Escopo: PatientsView.vue.
- Comportamento: ícone, rótulo, valor e indicador mensal ficam alinhados em uma única linha no desktop, com truncamento seguro para telas estreitas.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cards compactos de pacientes

- Status: Implementada
- Objetivo: reduzir o peso visual dos indicadores de pacientes.
- Escopo: PatientsView.vue.
- Comportamento: cards, ícones, espaçamentos e números passam a usar uma escala mais compacta, mantendo os quatro indicadores legíveis.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cabeçalho financeiro alinhado

- Status: Implementada
- Objetivo: manter o botão Dashboard dentro do enquadramento do cabeçalho financeiro.
- Escopo: FinanceView.vue.
- Comportamento: título, descrição e ação usam as regiões semânticas do cabeçalho, com alinhamento consistente em desktop e mobile.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cores das abas financeiras

- Status: Implementada
- Objetivo: alinhar as abas Resumo, Cobranças, Carteira e Configurações à paleta global do aplicativo.
- Escopo: FinanceView.vue.
- Comportamento: o estado ativo usa o azul de ação da interface e o estado inativo usa superfícies e bordas semânticas, com contraste consistente nos temas claro e escuro.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Campo de antecedência dos lembretes

- Status: Implementada
- Objetivo: ajustar a proporção do campo numérico de dias antes do lembrete.
- Escopo: HomeSettingsPanel.vue.
- Comportamento: o campo passa a ter largura compacta, adequada ao valor numérico, sem ocupar toda a linha.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Eventos externos do Google na agenda

- Status: Implementada
- Objetivo: mostrar na agenda os eventos existentes no Google Calendar conectado.
- Escopo: endpoint autenticado de leitura, GoogleCalendarService e grade/lista responsiva da ScheduleView.
- Comportamento: eventos externos são consultados apenas no intervalo visível, exibem o título do evento, não bloqueiam horários, não viram agendamentos locais e dividem a coluna quando há sobreposição.
- Privacidade: apenas o título é exposto; descrições, convidados, locais, links e tokens do Google permanecem ocultos; eventos criados pelo PsicoAgenda não são duplicados.
- Validação: npm run build, php -l dos arquivos PHP alterados, php artisan route:list e git diff --check.
- Notas: a leitura é sob demanda e não persiste eventos externos; webhooks, múltiplos calendários e sincronização bidirecional permanecem fora do escopo.

### 2026-09-19 - Redesign Clínica Serena

- Status: Implementada
- Objetivo: unificar toda a interface em uma experiência moderna, calma e adequada à rotina de psicólogos.
- Escopo: shell autenticado, navegação, componentes base, dashboard, pacientes, prontuário, agenda, relatórios, financeiro, exportações, perfil, configurações e telas públicas de acesso.
- Comportamento: a navegação passa a ser agrupada por rotina, o conteúdo acompanha a largura da sidebar, cabeçalhos e superfícies seguem tokens semânticos, e as telas mantêm composição responsiva para desktop e mobile.
- Privacidade: a busca global deixa de exibir contatos, a listagem de pacientes mostra apenas informações essenciais, dados cadastrais do prontuário ficam recolhidos e um modo transitório oculta busca, alertas, prontuário e financeiro.
- Validação: `npm run build` e `php artisan route:list` concluídos; `php artisan test` executado, com os testes de banco bloqueados pela ausência do driver `pdo_sqlite` no ambiente.
- Notas: APIs, autenticação, payloads clínicos e regras de escopo por psicólogo foram preservados; o redesign integra o modo claro/escuro já existente.

### 2026-06-12 - Refinamento visual do modo escuro

- Status: Implementada
- Objetivo: corrigir superficies claras e elementos desconexos no tema escuro.
- Escopo: `resources/css/app.css`, `resources/js/components/AppShell.vue`.
- Comportamento: o fundo global, cards, topbar, sidebar, campos, bordas e textos com cores fixas passam a seguir os tokens escuros da interface.
- Validacao: `npm run build`.
- Notas: ajuste visual sem mudanca no contrato da API.

### 2026-06-12 - Modo escuro por usuario

- Status: Implementada
- Objetivo: permitir que cada psicologo habilite ou desabilite o modo escuro com preferencia salva na conta.
- Escopo: `database/migrations/2026_06_12_000001_add_theme_mode_to_psychologists_table.php`, `app/Http/Controllers/Api/PsychologistController.php`, `app/Http/Requests/PsychologistSettingsUpdateRequest.php`, `app/Models/Psychologist.php`, `resources/js/views/SettingsView.vue`, `resources/js/components/home/HomeSettingsPanel.vue`, `resources/js/components/AppShell.vue`, `resources/css/app.css`.
- Comportamento: a tela de configuracoes exibe um toggle de tema; a escolha fica salva por usuario, abre em claro por padrao e aplica o tema em toda a interface ao recarregar ou trocar de rota.
- Validacao: `npm run build`.
- Notas: a preferencia usa `light` como valor default para usuarios sem configuracao salva.

### 2026-06-12 - Editar paciente a partir da busca global

- Status: Implementada
- Objetivo: abrir a modal de edicao do paciente diretamente pelo autocomplete global.
- Escopo: `resources/js/components/AppShell.vue`, `resources/js/views/PatientsView.vue`.
- Comportamento: ao clicar em `Editar`, o sistema carrega os dados completos e abre a modal de edicao por cima da pagina atual, sem redirecionar.
- Validacao: `npm run build`.
- Notas: a acao usa o endpoint existente de detalhes do paciente e nao altera o contrato da API.

### 2026-06-12 - Data de nascimento com mascara digitavel

- Status: Implementada
- Objetivo: permitir digitar a data de nascimento com mascara `dd/mm/aaaa` sem depender apenas do calendario.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`.
- Comportamento: o campo visivel aceita digitação manual com mascara e continua abrindo o seletor nativo pelo botao de calendario; o valor enviado ao backend segue em `YYYY-MM-DD`.
- Validacao: `npm run build`.
- Notas: nao altera contrato da API.

### 2026-06-12 - Data de nascimento com calendario e formato BR

- Status: Implementada
- Objetivo: manter o calendario nativo no cadastro de paciente e exibir a data em `dd/mm/aaaa`.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`.
- Comportamento: o campo visual mostra data no padrao brasileiro e o botao de calendario abre o seletor nativo; o valor enviado ao backend continua em `YYYY-MM-DD`.
- Validacao: `npm run build`.
- Notas: nao altera contrato da API.

### 2026-06-12 - Modal de paciente alinhado ao Stitch

- Status: Implementada
- Objetivo: adaptar o cadastro e a edicao de paciente ao layout `Edicao de Paciente - TherapyFlow` do Stitch.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`, `storage/app/stitch/11237689929679384621/patient-edit/`.
- Comportamento: o formulario abre em modal amplo com secoes visuais no padrao TherapyFlow, mantendo os campos e validacoes existentes.
- Validacao: `npm run build`.
- Notas: nenhum campo novo foi enviado ao backend; os artefatos do Stitch ficam em `storage/app/stitch/11237689929679384621/patient-edit/`.

### 2026-06-12 - Sidebar sem deslocar conteudo

- Status: Implementada
- Objetivo: fazer o sidebar abrir e recolher sem empurrar o conteudo da pagina para a direita.
- Escopo: `resources/js/components/AppShell.vue`.
- Comportamento: o sidebar permanece fixo sobre a interface e o conteudo principal nao muda de largura ou posicao quando o menu abre.
- Validacao: `npm run build`.
- Notas: sem impacto em API ou permissao; altera apenas o shell global.

### 2026-06-12 - Metricas de pacientes pelo backend

- Status: Implementada
- Objetivo: calcular no backend os totais exibidos nos cards da base de pacientes.
- Escopo: `app/Http/Controllers/Api/PatientController.php`, `resources/js/views/PatientsView.vue`, `tests/Feature/PatientIndexTest.php`.
- Comportamento: `/api/patients` retorna `metrics` com total geral, criados no mes, ativos, pausados e encerrados do psicologo autenticado; os cards usam esses valores.
- Validacao: `php -l app/Http/Controllers/Api/PatientController.php`, `php -l tests/Feature/PatientIndexTest.php`, `npm run build`; `php artisan test --filter=PatientIndexTest` bloqueado localmente por driver SQLite ausente.
- Notas: as metricas nao sao filtradas pela busca/status da grid; apenas a lista paginada segue os filtros.

### 2026-06-12 - Grid de pacientes sem observacoes

- Status: Implementada
- Objetivo: simplificar a tabela de pacientes removendo a coluna de observacoes da grid desktop.
- Escopo: `resources/js/components/patients/PatientsList.vue`.
- Comportamento: a tabela passa a exibir paciente, status, cobranca, contato e acoes; as observacoes continuam acessiveis no card mobile.
- Validacao: `npm run build`.
- Notas: ajuste visual pequeno, sem mudancas de API.

### 2026-06-12 - Base de Pacientes alinhada ao Stitch

- Status: Implementada
- Objetivo: reformular a pagina de pacientes com base na tela `Base de Pacientes - TherapyFlow` do Stitch.
- Escopo: `resources/js/views/PatientsView.vue`, `resources/js/components/patients/PatientsHeader.vue`, `resources/js/components/patients/PatientsFilters.vue`, `resources/js/components/patients/PatientsList.vue`, `storage/app/stitch/11237689929679384621/patients/`.
- Comportamento: `/patients` exibe cabecalho, cards de status, filtros em pills, tabela desktop e cards mobile no visual TherapyFlow usando apenas dados reais da API.
- Validacao: `npm run build`.
- Notas: metricas de status usam a pagina atual; o total usa a paginacao retornada por `/api/patients`.

### 2026-06-12 - Perfil do psicologo em pagina propria

- Status: Implementada
- Objetivo: tirar a edicao de perfil da Home e disponibilizar uma pagina dedicada no sidebar.
- Escopo: `resources/js/views/ProfileView.vue`, `resources/js/router/index.js`, `resources/js/components/AppShell.vue`, `resources/js/composables/useHomeDashboardModel.js`, `resources/js/views/HomeView.vue`.
- Comportamento: `Perfil` abre `/profile` para editar os dados profissionais; a Home nao exibe mais a aba de perfil.
- Validacao: `npm run build`.
- Notas: reaproveita `HomeProfilePanel.vue` e os endpoints existentes de perfil.

### 2026-06-12 - Configuracoes em pagina independente

- Status: Implementada
- Objetivo: mover configuracoes da Home para rota propria.
- Escopo: `resources/js/views/SettingsView.vue`, `resources/js/router/index.js`, `resources/js/components/AppShell.vue`, `resources/js/views/HomeView.vue`, `app/Http/Controllers/Api/GoogleOAuthController.php`.
- Comportamento: `Configuracoes` abre `/settings`; Home nao usa mais `?tab=settings`; retorno do Google vai para a nova pagina.
- Validacao: `npm run build`.
- Notas: reutiliza `HomeSettingsPanel.vue`.

### 2026-06-12 - Dashboard consolidada com proximos pacientes e atendimentos semanais

- Status: Implementada
- Objetivo: trocar blocos de leitura da dashboard por foco em proximos atendimentos e volume semanal.
- Escopo: `app/Http/Controllers/Api/HomeDashboardController.php`, `routes/api.php`, `resources/js/composables/useHomeDashboardModel.js`, `resources/js/components/home/HomeOverviewPanel.vue`, `resources/js/views/HomeView.vue`.
- Comportamento: Home consome `GET /api/home/dashboard` e mostra proximos atendimentos, barras da semana e metricas.
- Validacao: `npm run build` e teste de integracao do endpoint.
- Notas: usa dados reais do psicologo autenticado.

### 2026-06-12 - Autocomplete global de pacientes

- Status: Implementada
- Objetivo: permitir busca rapida de pacientes no header.
- Escopo: `AppShell.vue`, `HomeTopBar.vue`, `GET /api/patients?q=&per_page=5`.
- Comportamento: com 3+ caracteres, mostra ate 5 pacientes com acoes para editar ou abrir o prontuario.
- Validacao: `npm run build`.
- Notas: usa dados reais do psicologo autenticado.

### 2026-06-12 - Dashboard MCP TherapyFlow

- Status: Implementada
- Objetivo: reorganizar a Home com inspiracao na tela Dashboard - TherapyFlow do Stitch.
- Escopo: `storage/app/stitch/11237689929679384621/dashboard/`, `resources/js/composables/useHomeDashboardModel.js`, `HomeAppSidebar.vue`, `HomeTopBar.vue`, `HomeView.vue`, `HomeOverviewPanel.vue`, `PatientsView.vue`.
- Comportamento: shell com sidebar recolhivel, drawer mobile, header com busca, sino de alertas, metricas e acoes operacionais.
- Validacao: `npm run build`.
- Notas: sem pacientes ficticios; `AppShell.vue` orquestra o shell global.

### 2026-06-12 - Design system Serene Practice do Stitch

- Status: Implementada
- Objetivo: alinhar a aplicacao ao design system do Stitch.
- Escopo: `resources/css/app.css`, `resources/js/components/base`, `storage/app/stitch/11237689929679384621/`, `public/images/stitch/`.
- Comportamento: interface clara e clinica com fundo `#f9f9f8`, superficies brancas, azul `#415f76`, sage `#4c6455`, Source Serif 4 e Manrope.

### 2026-06-12 - Login inspirado no Stitch

- Status: Implementada
- Objetivo: reproduzir a tela Login - TherapyFlow no login real.
- Escopo: `resources/js/views/LoginView.vue`, `public/images/stitch/login-therapy-room.jpg`, `storage/app/stitch/11237689929679384621/login/`.
- Comportamento: login em duas colunas, imagem atmosferica, marca PsicoControl, senha visivel/oculta, lembrar de mim e recuperacao.

### 2026-06-12 - Recuperacao e redefinicao de senha

- Status: Implementada
- Objetivo: tornar o fluxo de esqueci minha senha funcional.
- Escopo: `AuthController`, `routes/api.php`, `PasswordResetMail`, `resources/views/emails/password_reset.blade.php`, `ForgotPasswordView.vue`, `ResetPasswordView.vue`, `resources/js/router/index.js`.
- Comportamento: usuario pede recuperacao; se existir, recebe link para `/reset-password`; nova senha invalida tokens anteriores.

### 2026-06-12 - Lembrar de mim por 30 dias

- Status: Implementada
- Objetivo: persistir a sessao quando o checkbox e marcado.
- Escopo: `AuthController`, `resources/js/stores/auth.js`, `LoginView.vue`.
- Comportamento: com `remember: true`, backend cria token Sanctum com `expires_at` em 30 dias e frontend usa `localStorage`; sem isso, usa `sessionStorage`.
