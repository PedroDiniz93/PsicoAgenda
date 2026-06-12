# Scheduling Reference

- Start must be before end.
- Appointment must belong to the authenticated psychologist.
- Non-canceled appointments cannot overlap.
- Respect availability rules, schedule blocks, and daily limits through `AppointmentAvailabilityService`.
- Weekly recurrences create a `RecurringAppointment` and generate upcoming appointments through `RecurringAppointmentService`.
- Use the psychologist timezone when present, otherwise `config('app.timezone')`.
