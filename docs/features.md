# Feature Registry

Este arquivo registra features implementadas no PsicoAgenda / PsicoControl para que futuras sessoes do Codex entendam o historico do produto sem depender de memoria da conversa.

Sempre que uma feature nova for implementada, adicione uma entrada no topo da lista em "Features".

## Formato

```md
### YYYY-MM-DD - Nome da feature

- Status: Implementada | Parcial | Planejada
- Objetivo: problema ou necessidade atendida.
- Escopo: principais telas, endpoints, modelos, services ou comandos alterados.
- Comportamento: como a feature funciona para o usuario.
```

## Features

### 2026-06-12 - Dashboard consolidada com proximos pacientes e atendimentos semanais

- Status: Implementada
- Objetivo: substituir os blocos de leitura de periodo e acoes operacionais da dashboard por uma visao direta dos proximos pacientes e do volume semanal de atendimentos.
- Escopo: `app/Http/Controllers/Api/HomeDashboardController.php`, `routes/api.php`, `resources/js/composables/useHomeDashboardModel.js`, `resources/js/components/home/HomeOverviewPanel.vue`, `resources/js/views/HomeView.vue`.
- Comportamento: a Home passa a consumir `GET /api/home/dashboard` e exibe a lista dos proximos atendimentos agendados, barras de atendimentos realizados na semana atual e as metricas principais da dashboard.
- Validacao: build frontend via `npm run build` e teste de integracao do endpoint consolidado.
- Notas: a listagem usa dados reais do psicologo autenticado e nao exibe pacientes ficticios.

### 2026-06-12 - Autocomplete global de pacientes

- Status: Implementada
- Objetivo: permitir busca rapida de pacientes pelo header global sem sair do contexto atual.
- Escopo: `AppShell.vue`, `HomeTopBar.vue` e endpoint existente `GET /api/patients` com `q` e `per_page=5`.
- Comportamento: ao digitar 3 ou mais caracteres, o header mostra ate 5 pacientes encontrados com acoes para editar na tela Pacientes filtrada e abrir diretamente o prontuario.
- Validacao: build frontend via `npm run build`.
- Notas: o autocomplete usa dados reais do psicologo autenticado e respeita o escopo do endpoint existente.

### 2026-06-12 - Dashboard MCP TherapyFlow

- Status: Implementada
- Objetivo: reorganizar a visao geral da Home com separacao entre modelo, contexto e protocolo inspirada na tela Dashboard - TherapyFlow do Stitch.
- Escopo: artefatos em `storage/app/stitch/11237689929679384621/dashboard/`, modelo em `resources/js/composables/useHomeDashboardModel.js`, shell em `HomeAppSidebar.vue` e `HomeTopBar.vue`, `HomeView.vue`, `HomeOverviewPanel.vue` e busca inicial em `PatientsView.vue`.
- Comportamento: as paginas autenticadas usam shell inspirado no Stitch com sidebar global recolhivel, drawer mobile, header com busca de pacientes e sino de alertas de pacientes inativos, metricas, leitura do periodo e acoes operacionais usando dados reais de relatorios, sem pacientes ficticios.
- Validacao: build frontend via `npm run build`.
- Notas: `AppShell.vue` controla o shell global; `HomeView.vue` permanece como contexto/orquestrador da dashboard; `HomeAppSidebar.vue`, `HomeTopBar.vue` e `HomeOverviewPanel.vue` recebem props e emitem apenas eventos de UI.

### 2026-06-12 - Design system Serene Practice do Stitch

- Status: Implementada
- Objetivo: alinhar a aplicacao ao design system do Stitch para o projeto PsychoCare Practice Manager.
- Escopo: tokens globais em `resources/css/app.css`, componentes base em `resources/js/components/base`, assets em `storage/app/stitch/11237689929679384621/` e imagem publica em `public/images/stitch/`.
- Comportamento: interface clara, clinica e calma com fundo `#f9f9f8`, superficies brancas, azul primario `#415f76`, sage `#4c6455`, Source Serif 4 para titulos e Manrope para interface.

### 2026-06-12 - Login inspirado no Stitch

- Status: Implementada
- Objetivo: reproduzir a tela Login - TherapyFlow do Stitch no login real da aplicacao.
- Escopo: `resources/js/views/LoginView.vue`, imagem publica `public/images/stitch/login-therapy-room.jpg`, referencias baixadas em `storage/app/stitch/11237689929679384621/login/`.
- Comportamento: login em duas colunas no desktop, imagem atmosferica, marca PsicoControl, formulario com icones, mostrar/ocultar senha, lembrar de mim e link de recuperacao.

### 2026-06-12 - Recuperacao e redefinicao de senha

- Status: Implementada
- Objetivo: tornar o link "Esqueci minha senha" funcional.
- Escopo: `AuthController`, `routes/api.php`, `PasswordResetMail`, `resources/views/emails/password_reset.blade.php`, `ForgotPasswordView.vue`, `ResetPasswordView.vue`, `resources/js/router/index.js`.
- Comportamento: usuario solicita recuperacao por e-mail; se o e-mail existir, recebe link com token para `/reset-password`; nova senha invalida tokens existentes e remove o token de reset.

### 2026-06-12 - Lembrar de mim por 30 dias

- Status: Implementada
- Objetivo: fazer o checkbox "Lembrar de mim por 30 dias" persistir a sessao de forma real.
- Escopo: `AuthController`, `resources/js/stores/auth.js`, `LoginView.vue`.
- Comportamento: quando marcado, login envia `remember: true`, backend cria token Sanctum com `expires_at` em 30 dias e frontend salva em `localStorage`; quando desmarcado, frontend usa `sessionStorage`.
