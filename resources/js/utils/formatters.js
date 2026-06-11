export const currencyFormatter = new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
});

export const formatMoney = (value) => currencyFormatter.format(Number(value ?? 0));

const BRAZIL_TIME_ZONE = "America/Sao_Paulo";

const parseDate = (value) => {
    if (!value) return null;

    const parsed = value instanceof Date ? value : new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

export const formatDateOnly = (value, timeZone = "UTC") => {
    const parsed = parseDate(value);
    if (!parsed) return "—";

    return new Intl.DateTimeFormat("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        timeZone,
    }).format(parsed);
};

export const formatDateTime = (value) => {
    const parsed = parseDate(value);
    if (!parsed) return "—";

    const dateFormatter = new Intl.DateTimeFormat("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        timeZone: BRAZIL_TIME_ZONE,
    });
    const timeFormatter = new Intl.DateTimeFormat("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
        timeZone: BRAZIL_TIME_ZONE,
    });

    return dateFormatter.format(parsed) + " às " + timeFormatter.format(parsed);
};

export const onlyDigits = (value) => String(value ?? "").replace(/\D/g, "");

export const formatCpf = (value) => {
    const digits = onlyDigits(value).slice(0, 11);

    return digits
        .replace(/^(\d{3})(\d)/, "$1.$2")
        .replace(/^(\d{3})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, "$1.$2.$3-$4");
};

export const formatPhone = (value) => {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/^(\d{2})(\d)/, "($1) $2")
            .replace(/^(\(\d{2}\) \d{4})(\d)/, "$1-$2");
    }

    return digits
        .replace(/^(\d{2})(\d)/, "($1) $2")
        .replace(/^(\(\d{2}\) \d{5})(\d)/, "$1-$2");
};
