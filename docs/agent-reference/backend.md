# Backend Reference

- API routes live in `routes/api.php`.
- Public auth endpoints: `POST /api/auth/login`, `POST /api/auth/forgot-password`, `POST /api/auth/reset-password`.
- Authenticated routes include `/api/me`, email verification verify/resend, and logout.
- Most product APIs require `auth:sanctum` and `EnsurePsychologistEmailIsVerified`.
- Main controllers: `AuthController`, `AdminPsychologistController`, `PsychologistController`, `PatientController`, `PatientRecordController`, `AppointmentController`, `AvailabilityController`, `FinanceController`, `ReportController`, `GoogleOAuthController`, `WhatsAppWebhookController`.
- Use Form Request validation when the pattern already exists.
- Enforce psychologist ownership on every patient, appointment, record, and finance operation.
