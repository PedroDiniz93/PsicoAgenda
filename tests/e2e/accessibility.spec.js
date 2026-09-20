import { expect, test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const credentials = { email: 'e2e@psicoagenda.test', password: 'password' };
const auditDir = 'storage/app/audits/accessibility-2026-09-20';

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail Profissional').fill(credentials.email);
    await page.getByRole('textbox', { name: 'Senha' }).fill(credentials.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/$/);
}

test.describe('acessibilidade real do PsicoAgenda', () => {
    test('executa axe-core nas páginas autenticadas principais', async ({ page }, testInfo) => {
        await login(page);
        const routes = ['/', '/patients', '/schedule', '/finance', '/settings', '/exports', '/reports', '/gamekit/visual-activities'];
        const results = [];

        for (const route of routes) {
            await page.goto(route);
            await expect(page.locator('body')).toBeVisible();
            const axe = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa'])
                .analyze();
            results.push({ route, violations: axe.violations });
        }

        await testInfo.attach('axe-results.json', {
            body: JSON.stringify(results, null, 2),
            contentType: 'application/json',
        });

        const serious = results.flatMap(({ route, violations }) =>
            violations
                .filter((violation) => ['serious', 'critical'].includes(violation.impact))
                .map((violation) => ({ route, id: violation.id, impact: violation.impact, nodes: violation.nodes.length }))
        );
        console.log(`axe-core serious/critical findings: ${JSON.stringify(serious)}`);
        const contrastNodes = results.flatMap(({ route, violations }) => violations
            .filter((violation) => violation.id === 'color-contrast')
            .flatMap((violation) => violation.nodes.slice(0, 6).map((node) => ({
                route,
                target: node.target,
                html: node.html,
                summary: node.failureSummary,
            }))));
        console.log(JSON.stringify(contrastNodes));
        expect(serious, JSON.stringify(serious, null, 2)).toEqual([]);
    });

    test('mantém foco inicial e fecha modais com Escape', async ({ page }) => {
        await login(page);

        await page.goto('/patients');
        await page.getByRole('button', { name: 'Cadastrar paciente' }).click();
        await expect(page.getByRole('dialog', { name: 'Novo paciente' })).toBeVisible();
        await expect(page.getByLabel('Nome completo')).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog', { name: 'Novo paciente' })).toHaveCount(0);

        await page.goto('/patients/1');
        await page.getByRole('button', { name: 'Nova anotação' }).first().click();
        await expect(page.getByRole('dialog', { name: 'Nova anotação' })).toBeVisible();
        await expect(page.getByLabel('Título')).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog', { name: 'Nova anotação' })).toHaveCount(0);

        await page.goto('/schedule');
        await page.getByRole('button', { name: 'Novo agendamento' }).click();
        await expect(page.getByRole('dialog', { name: 'Novo agendamento' })).toBeVisible();
        await expect(page.locator('#appointment-patient')).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog', { name: 'Novo agendamento' })).toHaveCount(0);
    });

    test('captura telas principais em zoom de 200%', async ({ page }) => {
        await login(page);
        // A 640px CSS viewport is the effective layout width of a 1280px screen at 200% zoom.
        await page.setViewportSize({ width: 640, height: 720 });

        await page.goto('/patients');
        await page.screenshot({ path: `${auditDir}/01-pacientes-zoom-200.png`, fullPage: true });

        await page.goto('/schedule');
        await page.screenshot({ path: `${auditDir}/02-agenda-zoom-200.png`, fullPage: true });

        await page.goto('/finance');
        await page.screenshot({ path: `${auditDir}/03-financeiro-zoom-200.png`, fullPage: true });
    });
});
