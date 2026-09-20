import { expect, test } from '@playwright/test';

const outputDir = 'storage/app/audits/ux-ui-2026-09-20-actions';
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

async function capture(page, name) {
    await page.screenshot({ path: `${outputDir}/${name}.png`, fullPage: true });
}

test('audita visualmente as ações principais da interface', async ({ page }) => {
    await login(page);

    await page.goto('/patients');
    await page.getByRole('button', { name: 'Cadastrar paciente' }).click();
    await capture(page, '01-pacientes-formulario-vazio');
    await page.getByLabel('Nome completo').fill(`Paciente auditoria ${Date.now()}`);
    await page.getByRole('button', { name: 'Adicionar contato' }).click();
    await capture(page, '02-pacientes-contato-emergencia');
    await page.getByRole('button', { name: 'Remover' }).click();
    await page.getByRole('button', { name: 'Cadastrar paciente', exact: true }).last().click();
    await capture(page, '03-pacientes-lista-apos-cadastro');

    await page.goto('/patients/1');
    await page.getByRole('button', { name: 'Nova anotação' }).first().click();
    await capture(page, '04-prontuario-modal-nova-anotacao');
    await page.getByLabel('Título').fill(`Auditoria UX ${Date.now()}`);
    await page.getByLabel('Notas clínicas').fill('Registro de auditoria visual.');
    await page.getByRole('button', { name: 'Adicionar ao prontuário' }).click();
    await capture(page, '05-prontuario-anotacao-salva');

    await page.goto('/schedule');
    await page.getByRole('button', { name: 'Novo agendamento' }).click();
    await page.locator('select').filter({ has: page.locator('option', { hasText: 'Paciente E2E' }) }).selectOption('1');
    await page.locator('#appointment-start').fill(localDateTime(17, 0));
    await page.locator('#appointment-end').fill(localDateTime(17, 50));
    await capture(page, '06-agenda-formulario-preenchido');
    await page.getByRole('button', { name: 'Agendar' }).click();

    await page.goto('/finance');
    await page.getByRole('tab', { name: 'Cobranças' }).click();
    await page.getByRole('button', { name: 'Abrir cobrança' }).first().click();
    await capture(page, '07-financeiro-modal-cobranca');
    await page.getByRole('button', { name: 'Salvar cobrança' }).click();

    await page.goto('/settings');
    await page.getByLabel('Dias antes').fill('3');
    await page.getByRole('checkbox', { name: 'E-mail' }).check();
    await page.getByRole('button', { name: 'Salvar preferências' }).click();
    await expect(page.getByText('Preferências de lembrete atualizadas com sucesso.')).toBeVisible();
    await capture(page, '08-configuracoes-feedback-sucesso');

    await page.goto('/exports');
    await page.getByRole('checkbox', { name: 'Selecionar paciente Paciente E2E' }).check();
    await capture(page, '09-exportacoes-selecao-pronta');

    await page.goto('/gamekit/routine');
    await page.getByRole('button', { name: 'Adicionar bloco' }).click();
    await capture(page, '10-gamekit-modal-edicao-bloco');
    await page.getByLabel('Atividade').fill('Pausa consciente');
    await page.getByRole('button', { name: 'Concluir edição' }).click();
    await page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Excluir bloco' }).click();
    await capture(page, '11-gamekit-estado-vazio');
});
