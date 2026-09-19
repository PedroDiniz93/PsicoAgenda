# Plano de implementação — Clínica Serena

## Brief

Objective: aplicar o redesign aprovado a toda a SPA.
Type: refatoração visual e de workflow.
Scope: shell, componentes base, rotas públicas e privadas, responsividade e privacidade visual.
Constraints: seguir AGENTS.md; preservar APIs, autenticação, alterações locais e regras clínicas; não ampliar o escopo.
Validation: build, testes Laravel, rotas e smoke responsivo/acessível.
Output: arquivos alterados, riscos, validação e próximo passo.

## Frentes RuFlo

- `frontend-vue`: tokens, componentes, shell e views.
- `security`: redução de PII no shell/listagens e modo privacidade transitório.
- `qa-tests`: build, testes existentes e checklist manual por rota.
- `code-review`: revisão final do diff, foco em eventos, payloads e responsividade.
- `backend-node` e `database`: sem mudanças; contratos e persistência permanecem intactos.

## Passos

1. Consolidar tokens e primitives em `resources/css/app.css`; modernizar componentes base sem alterar APIs.
2. Refatorar `AppShell`, sidebar e topbar; agrupar navegação; introduzir modo privacidade apenas em memória.
3. Normalizar dashboard, pacientes, agenda e prontuário.
4. Normalizar relatórios, financeiro, exportações, perfil e configurações.
5. Normalizar login, recuperação, redefinição e verificação de e-mail.
6. Atualizar `docs/features.md` e executar a validação completa.

## Controles de risco

- editar incrementalmente e executar build após fundação/shell e ao final;
- preservar props, emits, stores, endpoints, payloads e nomes de rota;
- integrar o diff local de tema sem reverter arquivos;
- não remover componentes aparentemente legados nesta rodada;
- não alterar o HTML de impressão financeira, salvo incompatibilidade visual comprovada;
- revisar manualmente sobreposição de sidebar, topbar, autocomplete, alertas e modais.
