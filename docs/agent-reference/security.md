# Security Reference

- Never log or expose `google_calendar_token`, password reset tokens, Sanctum tokens, WhatsApp tokens, or email verification hashes.
- Password reset must not reveal whether an email exists.
- Scope patient, record, appointment, and finance queries to the authenticated psychologist.
- Do not show real-looking fabricated patient data. Empty/sample states must read clearly as empty/sample.
- Treat clinical records and exports as sensitive.
