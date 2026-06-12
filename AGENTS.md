# PsicoAgenda / PsicoControl

## Product Context

PsicoAgenda, currently branded in the UI as PsicoControl, is a practice-management SPA for psychologists. The app helps a psychologist or clinic operator manage patients, clinical records, appointments, recurring sessions, schedule availability, payment collection, receipts, reports, exports, reminders, and administrative psychologist accounts.

The product handles sensitive clinical and personal data. Treat privacy, least disclosure, and conservative security behavior as first-class requirements. Do not invent real patient data, do not expose secrets/tokens in logs or UI, and keep auth/password-reset messages generic when that avoids user enumeration.

## Stack

- Backend: Laravel 12 / PHP 8.2.
- Auth: Laravel Sanctum personal access tokens.
- Frontend: Vue 3, Pinia, Vue Router, Axios.
- Styling: Tailwind CSS v4, global tokens in `resources/css/app.css`.
- Icons: `@lucide/vue`, normally through `resources/js/components/base/AppIcon.vue`.
- Mail: Laravel Mail. Local development commonly uses `MAIL_MAILER=log`.
- Primary app route: Laravel serves `resources/views/welcome.blade.php`; Vue Router owns SPA routes.

## Visual Direction

The UI follows the Stitch project `PsychoCare Practice Manager` / design system `Serene Practice`.

Use a calm, clinical, professional style:

- Background: `#f9f9f8`.
- Main surface: white / `#ffffff`.
- Muted surface: `#f3f4f3` or `#eeeeed`.
- Primary action: soft blue `#415f76`, hover/container `#5a7890` or `#2b4a60`.
- Secondary/success accent: sage `#4c6455`, container `#cbe6d4`.
- Error: `#ba1a1a`, container `#ffdad6`.
- Typography: headings use Source Serif 4; interface/body uses Manrope.
- Shape: 8px radius for controls, 16px for larger cards/panels.
- Avoid loud gradients, dark themes, decorative clutter, fake data, or marketing-style landing pages inside the application.

Stitch reference assets are kept under `storage/app/stitch/11237689929679384621/`. Public UI images downloaded from Stitch live under `public/images/`.

## Domain Map

Core models:

- `User`: auth user, role, e-mail verification state, Sanctum tokens.
- `Psychologist`: practitioner profile, timezone, session duration, reminders, Google Calendar, WhatsApp sender, finance settings.
- `Patient`: patient identity/contact, emergency contacts, status, notes, default session fee.
- `Appointment`: scheduled session, status, type, payment fields, Google event id, reminder fields, recurrence link.
- `PatientRecord`: clinical record entries tied to a patient/psychologist.
- `RecurringAppointment`: weekly recurrence metadata; generated appointments point back via `recurrence_id`.
- `PatientAlert`: generated patient inactivity alerts.
- `PsychologistAvailabilityRule` and `PsychologistScheduleBlock`: availability windows and blocked times.

Important services:

- `AppointmentAvailabilityService`: validates same-day sessions, configured availability, blocks, and daily limits.
- `RecurringAppointmentService`: generates weekly occurrences up to 8 weeks ahead and cancels future scheduled occurrences.
- `GoogleCalendarService`: syncs/deletes appointment calendar events.
- `WhatsAppService`: sends WhatsApp confirmations and handles provider integration.
- `EmailVerificationService`: sends and verifies six-digit e-mail verification codes.

Scheduled commands:

- `whatsapp:send-confirmations`: sends WhatsApp/e-mail reminders for upcoming sessions.
- `alerts:generate-inactivity`: creates inactivity alerts.
- `appointments:sync-recurring`: keeps recurring sessions generated.


## Feature Registry

New features must be registered in `docs/features.md` in the same change that implements them. This is required so future Codex sessions can recover product history from the repository instead of relying on chat memory.

When implementing a feature:

- Add a new entry at the top of the `## Features` list in `docs/features.md`.
- Include date, status, objective, scope, user-facing behavior, validation, and important notes.
- Keep entries factual and concise; do not include secrets, tokens, patient data, or private clinical content.
- If the work is only a bug fix or internal refactor, register it only when it changes user-visible behavior, domain rules, integrations, or developer workflow.

## Backend Structure

API routes live in `routes/api.php`.

Public auth routes:

- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`

Authenticated but not necessarily verified routes:

- `/api/me`
- e-mail verification verify/resend
- logout

Most product APIs are behind both `auth:sanctum` and `EnsurePsychologistEmailIsVerified`.

Main controllers:

- `AuthController`: login, logout, password reset, e-mail verification.
- `AdminPsychologistController`: admin management of psychologists/users.
- `PsychologistController`: profile/settings.
- `PatientController`: patient CRUD, exports, inactivity alerts.
- `PatientRecordController`: clinical records.
- `AppointmentController`: appointments, status changes, weekly recurrence creation.
- `AvailabilityController`: availability rules and blocks.
- `FinanceController`: finance dashboard, payment updates, receipts, finance settings.
- `ReportController`: appointment reports.
- `GoogleOAuthController`: Google Calendar OAuth.
- `WhatsAppWebhookController`: WhatsApp webhook verification and inbound message handling.

When adding or changing backend behavior, prefer Form Request validation in `app/Http/Requests` where the pattern already exists. Enforce psychologist ownership on every patient/appointment/record/finance operation.

## Frontend Structure

SPA routes live in `resources/js/router/index.js`.

Views:

- `/login`: `LoginView.vue`
- `/forgot-password`: `ForgotPasswordView.vue`
- `/reset-password`: `ResetPasswordView.vue`
- `/email-verification`: `EmailVerificationView.vue`
- `/`: `HomeView.vue`
- `/patients`: `PatientsView.vue`
- `/patients/:id`: `PatientRecordView.vue`
- `/schedule`: `ScheduleView.vue`
- `/exports`: `ExportsView.vue`
- `/reports`: `ReportsView.vue`
- `/finance`: `FinanceView.vue`

State:

- `resources/js/stores/auth.js`: token, user, e-mail-verification state, remember-me persistence.
- `resources/js/stores/alerts.js`: patient alert bell state.

Base components live in `resources/js/components/base`. Reuse these before creating new primitive UI components. Use `AppIcon` for Lucide icons.

Authentication behavior:

- `auth.initialize()` reads session from storage before routing.
- Remember-me checked: token is stored in `localStorage`, backend token expires in 30 days.
- Remember-me unchecked: token is stored in `sessionStorage`.
- Unauthenticated users are redirected to `/login` with `redirect` query.
- Verified psychologist e-mail is required for main product routes.

## Scheduling Rules

When creating/updating appointments:

- Start must be before end.
- Appointment must belong to the authenticated psychologist.
- Non-canceled appointments cannot overlap.
- Availability rules, schedule blocks, and daily appointment limits must be respected through `AppointmentAvailabilityService`.
- Weekly recurrences create a `RecurringAppointment` and generate upcoming appointments through `RecurringAppointmentService`.
- Timezone-sensitive calculations should use the psychologist timezone when present, falling back to `config('app.timezone')`.

## Finance Rules

Finance is appointment-based. Chargeable statuses are `scheduled`, `done`, and `missed`; canceled appointments are excluded from receivables. Payment settings live on `Psychologist`. Receipts require a paid appointment and a positive price.

When changing finance behavior, check:

- `FinanceController`
- `FinancePaymentUpdateRequest`
- `FinanceSettingsUpdateRequest`
- `FinanceView.vue`
- `resources/js/utils/formatters.js`

## Security And Privacy Rules

- Never log or expose `google_calendar_token`, password reset tokens, Sanctum tokens, WhatsApp tokens, or email verification hashes.
- Password reset should not reveal whether an e-mail exists.
- Keep patient/record/appointment queries scoped by authenticated psychologist.
- Avoid showing real-looking fabricated patient data. Empty/sample states must read clearly as empty/sample.
- For clinical records and exports, treat content as sensitive.

## Validation Commands

Run these depending on what changed:

```bash
npm run build
```

```bash
php -l path/to/file.php
```

```bash
php artisan test
```

```bash
php artisan route:list
```

Useful local server command:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

The app is normally available at `http://127.0.0.1:8000`.

## Local Caveats

- The worktree may already contain user edits; never revert unrelated changes.
- `resources.zip` may appear as an unrelated untracked file.
- Local tests in this environment may be limited by available PHP extensions; report exact failures instead of masking them.
- Build output under `public/build` is generated by Vite and may change after `npm run build`.
- Use `curl -L` for Stitch-hosted downloads when reproducing Stitch screens.

## Preferred Implementation Style

- Keep changes scoped to the feature or bug requested.
- Follow existing Laravel controller/request/service patterns.
- Follow existing Vue Composition API style.
- Prefer structured APIs and model relationships over ad hoc string manipulation.
- Prefer clear Portuguese UI copy.
- Preserve the calm design system and avoid broad restyling unless explicitly requested.
