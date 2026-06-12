# Stack Reference

- Backend: Laravel 12 / PHP 8.2.
- Auth: Laravel Sanctum personal access tokens.
- Frontend: Vue 3, Pinia, Vue Router, Axios.
- Styling: Tailwind CSS v4, tokens in `resources/css/app.css`.
- Icons: `@lucide/vue` via `resources/js/components/base/AppIcon.vue`.
- Mail: Laravel Mail, usually `MAIL_MAILER=log` locally.
- Main route: Laravel serves `resources/views/welcome.blade.php`; Vue Router owns SPA routes.
