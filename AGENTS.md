# PsicoAgenda / PsicoControl

## Core Rules

- PsicoAgenda is a practice-management SPA for psychologists.
- The app handles sensitive clinical and personal data. Keep privacy, least disclosure, and conservative security behavior as defaults.
- If a task changes user-visible behavior, security, data handling, workflow, or validation, follow RuFlo before editing files.
- Keep the scope minimal and do not expand beyond the request.
- Update `docs/features.md` in the same change for new features and for bug fixes or refactors that change user-visible behavior, domain rules, integrations, or workflow.

## RuFlo Workflow

1. Make a technical plan.
2. Split work across the relevant fronts: `frontend-vue`, `backend-node`, `database`, `security`, `qa-tests`, `code-review`.
3. Implement in small steps with validation after each step.
4. Record what changed in each relevant front when behavior, security, data, or tests change.

## Standard Task Format

Use this short brief for new tasks:

```text
Objective: [...]
Type: [new feature | bugfix | internal refactor | text/UI tweak]
Scope: [...]
Constraints:
- follow AGENTS.md
- do not expand beyond the request
- do not repeat fixed context
- keep changes minimal
Validation: [...]
Output: findings, files, risk, next step
```

## Safety Baseline

- Never log or expose `google_calendar_token`, password reset tokens, Sanctum tokens, WhatsApp tokens, or email verification hashes.
- Password reset must not reveal whether an email exists.
- Scope patient, record, appointment, and finance queries to the authenticated psychologist.
- Do not show real-looking fabricated patient data. Empty/sample states must read clearly as empty/sample.
- Treat clinical records and exports as sensitive.

## Working Style

- Classify the task before applying full RuFlo.
- Use subagents only when there is real parallelism.
- Keep responses short unless the user asks for detail.
- Follow existing Laravel controller/request/service patterns.
- Follow existing Vue Composition API style.
- Prefer structured APIs and model relationships over ad hoc string manipulation.
- Prefer clear Portuguese UI copy.

## Validation Baseline

- `npm run build`
- `php -l path/to/file.php`
- `php artisan test`
- `php artisan route:list`
- `php artisan serve --host=127.0.0.1 --port=8000`

The app is normally available at `http://127.0.0.1:8000`.

## Reference

Consult the themed files in `docs/agent-reference/` when you need deeper context on stack, domain rules, UI direction, or local conventions.

- `docs/agent-reference/workflow.md`
- `docs/agent-reference/stack.md`
- `docs/agent-reference/domain-map.md`
- `docs/agent-reference/backend.md`
- `docs/agent-reference/frontend.md`
- `docs/agent-reference/scheduling.md`
- `docs/agent-reference/finance.md`
- `docs/agent-reference/security.md`
- `docs/agent-reference/validation.md`
- `docs/agent-reference/visual-direction.md`
