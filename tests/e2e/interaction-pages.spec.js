import { expect, test } from '@playwright/test';

const credentials = { email: 'e2e@psicoagenda.test', password: 'password' };

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail Profissional').fill(credentials.email);
    await page.getByRole('textbox', { name: 'Senha' }).fill(credentials.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/$/);
}

function localDateTime(hours, minutes = 0) {
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);
    const pad = (value) => String(value).padStart(2, '0');
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

test.describe('ações das telas clínicas', () => {
    test('cadastra, edita, filtra e abre o prontuário de um paciente', async ({ page }) => {
        await login(page);
        await page.goto('/patients');
        const patientName = `Paciente interação ${Date.now()}`;

        await page.getByRole('button', { name: 'Cadastrar paciente' }).click();
        await expect(page.getByRole('heading', { name: 'Novo paciente' })).toBeVisible();
        await page.getByLabel('Nome completo').fill(patientName);
        await page.getByRole('button', { name: 'Adicionar contato' }).click();
        await page.getByPlaceholder('Nome', { exact: true }).fill('Contato E2E');
        await page.getByPlaceholder('Família, amigo...').fill('Família');
        await page.getByRole('button', { name: 'Remover' }).click();
        await page.getByRole('button', { name: 'Cadastrar paciente', exact: true }).last().click();

        await expect(page.getByRole('table').getByText(patientName, { exact: true })).toBeVisible();
        await page.getByPlaceholder('Nome, e-mail, telefone ou CPF').fill(patientName);
        await page.getByRole('button', { name: 'Filtrar' }).click();
        await expect(page.getByRole('table').getByText(patientName, { exact: true })).toBeVisible();

        const patientRow = page.getByRole('row').filter({ hasText: patientName });
        await patientRow.getByTitle('Editar dados').click();
        await expect(page.getByRole('heading', { name: 'Editar paciente' })).toBeVisible();
        await page.getByLabel('Telefone').fill('(11) 98888-7777');
        await page.getByRole('button', { name: 'Salvar alterações' }).click();
        await expect(page.getByRole('table').getByText(patientName, { exact: true })).toBeVisible();

        await patientRow.getByRole('link', { name: 'Prontuário' }).click();
        await expect(page).toHaveURL(/\/patients\/\d+$/);
        await expect(page.getByText('Linha do tempo clínica')).toBeVisible();
    });

    test('cria, edita e exclui uma anotação no prontuário', async ({ page }) => {
        await login(page);
        await page.goto('/patients/1');
        const title = `Evolução E2E ${Date.now()}`;

        await page.getByRole('button', { name: 'Nova anotação' }).first().click();
        await page.getByLabel('Título').fill(title);
        await page.getByLabel('Notas clínicas').fill('Registro criado pelo teste automatizado.');
        await page.getByPlaceholder('Ex.: Reduzir ansiedade').fill('Autonomia');
        await page.getByRole('button', { name: 'Adicionar' }).first().click();
        await page.getByPlaceholder('Ex.: Reestruturação').fill('Psicoeducação');
        await page.getByRole('button', { name: 'Adicionar' }).nth(1).click();
        await page.getByRole('button', { name: 'Adicionar ao prontuário' }).click();
        await expect(page.getByText(title, { exact: true })).toBeVisible();

        const record = page.locator('article').filter({ hasText: title });
        await record.getByRole('button', { name: 'Editar' }).click();
        await page.getByLabel('Título').fill(`${title} atualizada`);
        await page.getByRole('button', { name: 'Salvar alterações' }).click();
        await expect(page.getByText(`${title} atualizada`, { exact: true })).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await page.locator('article').filter({ hasText: `${title} atualizada` }).getByRole('button', { name: 'Excluir' }).click();
        await expect(page.getByText(`${title} atualizada`, { exact: true })).toHaveCount(0);
    });

    test('cria e exclui um agendamento pela agenda', async ({ page }) => {
        await login(page);
        await page.goto('/schedule');
        await page.getByRole('button', { name: 'Novo agendamento' }).click();
        await expect(page.getByRole('heading', { name: 'Novo agendamento' })).toBeVisible();
        await page.locator('select').filter({ has: page.locator('option', { hasText: 'Paciente E2E' }) }).selectOption('1');
        await page.locator('#appointment-start').fill(localDateTime(16, 0));
        await page.locator('#appointment-end').fill(localDateTime(16, 50));
        await page.getByRole('button', { name: 'Agendar' }).click();
        await expect(page.getByRole('dialog', { name: 'Novo agendamento' })).toHaveCount(0);
        await expect(page.getByText('Agendamento criado com sucesso.')).toBeVisible();
        const createdAppointment = page.getByTitle('Paciente E2E').last();
        await expect(createdAppointment).toBeVisible();
        await createdAppointment.click();
        await expect(page.getByRole('heading', { name: 'Editar agendamento' })).toBeVisible();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Excluir agendamento' }).click();
        await expect(page.getByRole('heading', { name: 'Editar agendamento' })).toHaveCount(0);
    });

    test('salva preferências, navega nas categorias e abre uma cobrança', async ({ page }) => {
        await login(page);
        await page.goto('/settings');
        await page.getByLabel('Dias antes').fill('3');
        await page.getByRole('checkbox', { name: 'E-mail' }).check();
        await page.getByRole('button', { name: 'Salvar preferências' }).click();
        await expect(page.getByText('Preferências de lembrete atualizadas com sucesso.')).toBeVisible();

        await page.goto('/finance');
        await page.getByRole('tab', { name: 'Carteira' }).click();
        await expect(page.getByText('Paciente E2E')).toBeVisible();
        await page.getByRole('tab', { name: 'Cobranças' }).click();
        await page.getByRole('button', { name: /Abrir cobrança|Cobrar|Marcar como recebido/ }).first().click();
        await expect(page.getByText('Mensagem pronta para cobrança')).toBeVisible();
        await page.getByPlaceholder('Ex.: combinado para pagar por Pix após a sessão.').fill('Cobrança revisada pelo E2E.');
        await page.getByRole('button', { name: 'Salvar cobrança' }).click();
        await expect(page.getByText(/Cobrança salva|atualizada/)).toBeVisible();
    });

    test('seleciona paciente e executa exportação', async ({ page }) => {
        await login(page);
        await page.goto('/exports');
        await page.getByRole('checkbox', { name: 'Selecionar paciente Paciente E2E' }).check();
        await expect(page.getByRole('button', { name: 'Exportar CSV' })).toBeEnabled();
        const download = page.waitForEvent('download');
        await page.getByRole('button', { name: 'Exportar CSV' }).click();
        await expect((await download).suggestedFilename()).toMatch(/exportacao-pacientes-.*\.zip/);
    });

    test('cria e remove um bloco na rotina do GameKit', async ({ page }) => {
        await login(page);
        await page.goto('/gamekit/routine');
        await page.getByRole('button', { name: 'Adicionar bloco' }).click();
        await expect(page.getByText('blocos no dia')).toBeVisible();
        await page.getByLabel('Atividade').fill('Pausa consciente');
        await page.getByRole('button', { name: 'Concluir edição' }).click();
        await expect(page.getByText('Pausa consciente', { exact: true })).toBeVisible();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Excluir bloco' }).click();
        await expect(page.getByText('Adicione o primeiro bloco ou gere uma sugestão com IA.')).toBeVisible();
    });
});
