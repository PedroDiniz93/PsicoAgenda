import { expect, test } from '@playwright/test';

const credentials = {
    email: 'e2e@psicoagenda.test',
    password: 'password',
};

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail Profissional').fill(credentials.email);
    await page.getByRole('textbox', { name: 'Senha' }).fill(credentials.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByText('Olá, Psicóloga E2E')).toBeVisible();
}

test.describe('fluxos principais do PsicoAgenda', () => {
    test('faz login e abre o dashboard', async ({ page }) => {
        await login(page);

        await expect(page.getByText('Próximas sessões')).toBeVisible();
        await expect(page.getByText('Atendimentos semanais')).toBeVisible();
    });

    test('exibe a agenda com a sessão do paciente', async ({ page }) => {
        await login(page);
        const appointmentsResponse = page.waitForResponse(
            (response) => response.url().includes('/api/appointments') && response.request().method() === 'GET'
        );
        await page.goto('/schedule');

        await expect(page.getByRole('heading', { name: 'Agenda clínica' })).toBeVisible();
        await expect((await appointmentsResponse).status()).toBe(200);
        await expect(page.getByTitle('Paciente E2E')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Novo agendamento' })).toBeVisible();
    });

    test('filtra pacientes pelo nome', async ({ page }) => {
        await login(page);
        await page.goto('/patients');

        await expect(page.getByRole('heading', { name: 'Pacientes' })).toBeVisible();
        await page.getByPlaceholder('Nome, e-mail, telefone ou CPF').fill('Paciente E2E');
        await page.getByRole('button', { name: 'Filtrar' }).click();

        await expect(page.getByRole('table').getByText('Paciente E2E')).toBeVisible();
    });

    test('exibe a cobrança pendente no financeiro', async ({ page }) => {
        await login(page);
        await page.goto('/finance');

        await expect(page.getByRole('heading', { name: 'Recebimentos e cobranças' })).toBeVisible();
        await expect(page.getByText('Cobranças e pagamentos')).toBeVisible();
        await expect(page.getByText('Paciente E2E')).toBeVisible();
    });
});
