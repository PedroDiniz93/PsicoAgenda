import { ref } from 'vue';

const STORAGE_KEY = 'psico-theme';
const isDark = ref(false);
let initialized = false;

const apply = (dark) => {
    isDark.value = dark;
    const root = document.documentElement;
    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';
};

export function initTheme() {
    if (initialized) return;
    initialized = true;

    let stored = null;
    try {
        stored = localStorage.getItem(STORAGE_KEY);
    } catch (error) {
        stored = null;
    }

    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
    apply(stored ? stored === 'dark' : prefersDark);
}

export function useTheme() {
    const setTheme = (dark) => {
        apply(dark);
        try {
            localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
        } catch (error) {
            /* ignore persistence errors */
        }
    };

    const toggleTheme = () => setTheme(!isDark.value);

    return { isDark, toggleTheme, setTheme };
}
