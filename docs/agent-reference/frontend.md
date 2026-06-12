# Frontend Reference

- SPA routes live in `resources/js/router/index.js`.
- Views: `/login`, `/forgot-password`, `/reset-password`, `/email-verification`, `/`, `/patients`, `/patients/:id`, `/schedule`, `/exports`, `/reports`, `/finance`.
- State lives in `resources/js/stores/auth.js` and `resources/js/stores/alerts.js`.
- Reuse base components from `resources/js/components/base`.
- Use `AppIcon` for Lucide icons.
- `auth.initialize()` reads session before routing.
- Remember-me on: token in `localStorage` with 30-day backend expiry.
- Remember-me off: token in `sessionStorage`.
- Unauthenticated users go to `/login` with `redirect`.
- Verified psychologist email is required for main routes.
