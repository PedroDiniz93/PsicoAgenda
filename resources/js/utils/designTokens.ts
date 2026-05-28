/**
 * Design Tokens - Constantes de Design da PsicoAgenda
 * Centraliza cores, espaçamentos, e outros tokens de design
 */

export const COLORS = {
  // Primary
  primary: {
    50: '#eff6ff',
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#3b82f6',
    600: '#2563eb',
    700: '#1d4ed8',
    800: '#1e40af',
    900: '#1e3a8a',
  },

  // Success
  success: {
    50: '#f0fdf4',
    100: '#dcfce7',
    200: '#bbf7d0',
    300: '#86efac',
    400: '#4ade80',
    500: '#22c55e',
    600: '#16a34a',
    700: '#15803d',
    800: '#166534',
    900: '#134e4a',
  },

  // Warning
  warning: {
    50: '#fffbeb',
    100: '#fef3c7',
    200: '#fde68a',
    300: '#fcd34d',
    400: '#fbbf24',
    500: '#f59e0b',
    600: '#d97706',
    700: '#b45309',
    800: '#92400e',
    900: '#78350f',
  },

  // Error
  error: {
    50: '#fef2f2',
    100: '#fee2e2',
    200: '#fecaca',
    300: '#fca5a5',
    400: '#f87171',
    500: '#ef4444',
    600: '#dc2626',
    700: '#b91c1c',
    800: '#991b1b',
    900: '#7f1d1d',
  },

  // Neutral
  neutral: {
    50: '#fafafa',
    100: '#f5f5f5',
    200: '#e5e5e5',
    300: '#d4d4d4',
    400: '#a3a3a3',
    500: '#737373',
    600: '#525252',
    700: '#404040',
    800: '#262626',
    900: '#171717',
  },
};

export const STATUS_COLORS = {
  active: 'success',
  paused: 'warning',
  closed: 'error',
  done: 'success',
  pending: 'warning',
  canceled: 'error',
  missed: 'error',
} as const;

export const SPACING = {
  xs: '0.25rem',
  sm: '0.5rem',
  md: '1rem',
  lg: '1.5rem',
  xl: '2rem',
  '2xl': '2.5rem',
  '3xl': '3rem',
};

export const BORDER_RADIUS = {
  sm: '0.5rem',
  md: '0.75rem',
  lg: '1rem',
  xl: '1.25rem',
  '2xl': '1.5rem',
  '3xl': '2rem',
};

export const SHADOWS = {
  xs: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
  sm: '0 1px 2px 0 rgba(0, 0, 0, 0.08)',
  base: '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
  md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
  lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
  xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
  '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
};

export const Z_INDEX = {
  dropdown: 10,
  sticky: 20,
  'modal-backdrop': 30,
  modal: 40,
  tooltip: 50,
  notification: 60,
};

export const TRANSITIONS = {
  fast: '150ms',
  base: '200ms',
  normal: '300ms',
  slow: '500ms',
};

// Componente Button variantes
export const BUTTON_VARIANTS = {
  primary: 'bg-primary-600 text-white hover:bg-primary-700 disabled:bg-primary-300',
  secondary: 'border border-neutral-200 text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50 disabled:opacity-60',
  danger: 'bg-error-600 text-white hover:bg-error-700 disabled:bg-error-300',
  ghost: 'text-neutral-700 hover:bg-neutral-100 disabled:opacity-60',
  success: 'bg-success-600 text-white hover:bg-success-700 disabled:bg-success-300',
  warning: 'bg-warning-600 text-white hover:bg-warning-700 disabled:bg-warning-300',
};

export const BUTTON_SIZES = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
};

// Grid responsivo padrão
export const GRID_CLASSES = {
  cols1: 'grid gap-4',
  cols2: 'grid gap-4 md:grid-cols-2',
  cols3: 'grid gap-4 md:grid-cols-3',
  cols4: 'grid gap-4 md:grid-cols-2 lg:grid-cols-4',
  responsive: 'grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
  responsiveLg: 'grid gap-6 lg:grid-cols-2',
};

// Ícones SVG comuns
export const ICONS = {
  check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  close: 'M6 18L18 6M6 6l12 12',
  warning: 'M12 9v2m0 4v2m7.07-10.07a10 10 0 11-14.14 0M12 2a10 10 0 1010 10',
  error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2',
  info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  filter: 'M4 6h16M4 12h16M4 18h16',
  refresh: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
  chevronDown: 'M19 14l-7 7m0 0l-7-7m7 7V3',
  chartBar: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
};

// Mensagens comuns
export const MESSAGES = {
  loading: 'Carregando...',
  empty: 'Nenhum registro encontrado',
  error: 'Ocorreu um erro',
  success: 'Sucesso!',
  confirm: 'Tem certeza?',
};
