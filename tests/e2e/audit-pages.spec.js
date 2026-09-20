import { expect, test } from '@playwright/test';

test.describe('auditoria visual das páginas do PsicoAgenda', () => {
    test('captura páginas públicas, autenticadas e jogadores', async ({ page }) => {
        test.setTimeout(180000);

        const auditDir = 'storage/app/audits/ux-ui-2026-09-20-all-pages';
        const pages = [
            ['/login', 'login'],
            ['/forgot-password', 'forgot-password'],
            ['/reset-password?token=demo&email=e2e%40psicoagenda.test', 'reset-password'],
        ];

        for (const [index, [route, name]] of pages.entries()) {
            await page.goto(route);
            await page.waitForTimeout(700);
            await page.screenshot({ path: `${auditDir}/${String(index + 1).padStart(2, '0')}-${name}.png`, fullPage: true });
        }

        await page.goto('/login');
        await page.getByLabel('E-mail Profissional').fill('e2e@psicoagenda.test');
        await page.getByRole('textbox', { name: 'Senha' }).fill('password');
        await page.getByRole('button', { name: 'Entrar' }).click();
        await expect(page).toHaveURL(/\/$/);

        const authenticatedPages = [
            ['/', 'home'],
            ['/patients', 'patients'],
            ['/patients/1', 'patient-record'],
            ['/schedule', 'schedule'],
            ['/exports', 'exports'],
            ['/reports', 'reports'],
            ['/profile', 'profile'],
            ['/finance', 'finance'],
            ['/settings', 'settings'],
            ['/email-verification', 'email-verification'],
            ['/gamekit', 'gamekit'],
            ['/gamekit/memory', 'gamekit-memory'],
            ['/gamekit/routine', 'gamekit-routine'],
            ['/gamekit/tictactoe', 'gamekit-tictactoe'],
            ['/gamekit/hangman', 'gamekit-hangman'],
            ['/gamekit/play/demo-token', 'gamekit-player'],
            ['/gamekit/memory/play/demo-token', 'gamekit-memory-player'],
            ['/gamekit/routine/play/demo-token', 'gamekit-routine-player'],
            ['/gamekit/tictactoe/play/demo-token', 'gamekit-tictactoe-player'],
            ['/gamekit/hangman/play/demo-token', 'gamekit-hangman-player'],
        ];

        for (const [route, name] of authenticatedPages) {
            await page.goto(route);
            await page.waitForTimeout(900);
            await page.screenshot({ path: `${auditDir}/${name}.png`, fullPage: true });
        }

        for (const [route, name] of [
            ['/', 'home-mobile'],
            ['/patients', 'patients-mobile'],
            ['/schedule', 'schedule-mobile'],
            ['/finance', 'finance-mobile'],
        ]) {
            await page.setViewportSize({ width: 390, height: 844 });
            await page.goto(route);
            await page.waitForTimeout(900);
            await page.screenshot({ path: `${auditDir}/${name}.png`, fullPage: true });
        }
    });
});
