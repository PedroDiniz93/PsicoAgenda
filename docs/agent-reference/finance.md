# Finance Reference

- Chargeable statuses: `scheduled`, `done`, `missed`.
- Canceled appointments are excluded from receivables.
- Payment settings live on `Psychologist`.
- Receipts require a paid appointment and a positive price.
- When changing finance behavior, check `FinanceController`, `FinancePaymentUpdateRequest`, `FinanceSettingsUpdateRequest`, `FinanceView.vue`, and `resources/js/utils/formatters.js`.
