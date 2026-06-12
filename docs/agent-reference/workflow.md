# Workflow Reference

## RuFlo

- Make a technical plan first.
- Split work across the relevant fronts: `frontend-vue`, `backend-node`, `database`, `security`, `qa-tests`, `code-review`.
- Implement in small steps and validate after each step.
- Record changes in each relevant front when behavior, security, data, or tests change.

## Task Framing

Use the standard brief before working:

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

## Operating Rules

- Classify the task before expanding into full RuFlo.
- Use subagents only when there is real parallelism.
- Keep answers short unless detail is requested.
- Do not repeat structural project context that already lives elsewhere.
