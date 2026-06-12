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
