# GameKit Psi — Organizador de Rotina

## Objetivo

Adicionar ao GameKit Psi uma ferramenta para montar, junto com o paciente, uma rotina diária visual e equilibrada. A rotina poderá ser vinculada a um paciente do PsicoAgenda e consultada posteriormente no prontuário.

## Experiência

O psicólogo escolhe o organizador no catálogo do GameKit e abre uma linha do tempo diária. Cada bloco representa uma atividade com horário inicial, duração, título, descrição opcional e categoria:

- Estudo ou trabalho
- Descanso ou sono
- Lazer
- Autocuidado

O layout usa uma linha vertical de horários, cartões coloridos por categoria, ícones semânticos e uma visualização responsiva para notebook e tablet. O paciente participa da montagem durante a sessão; a interface não classifica a rotina como certa ou errada.

## Fluxo do psicólogo

1. Criar uma nova rotina com nome e data de referência opcional.
2. Adicionar, editar, mover e excluir blocos da linha do tempo.
3. Ver um resumo visual da distribuição entre estudo, descanso, lazer e autocuidado.
4. Salvar a rotina como uma versão.
5. Vincular a rotina a um paciente selecionado entre os pacientes do psicólogo autenticado.
6. Consultar, editar ou criar uma nova versão a partir do histórico de rotinas.
7. Opcionalmente gerar um link temporário de consulta para o paciente.

O vínculo com o paciente será feito na rotina, não em cada bloco. O paciente poderá ter várias rotinas, e cada rotina terá seu próprio histórico de versões.

## Dados e privacidade

Serão criadas tabelas próprias:

- `gamekit_routines`: psicólogo proprietário, paciente opcional, nome, data de referência, status e timestamps.
- `gamekit_routine_versions`: rotina, número da versão, resumo e timestamps.
- `gamekit_routine_blocks`: versão, posição, horário inicial, duração, título, descrição, categoria e ícone.
- `gamekit_routine_links`: rotina, hash do token, expiração e status.

Consultas autenticadas serão limitadas ao psicólogo atual. O link público mostrará apenas a rotina compartilhada; não exibirá dados clínicos, histórico do paciente, anotações do prontuário ou tokens internos.

## Vínculo no prontuário

No prontuário do paciente haverá uma seção “Rotinas terapêuticas”. Ela mostrará nome, data, status e versão atual. Ao expandir uma rotina, o psicólogo verá a linha do tempo e poderá consultar versões anteriores.

## Link do paciente

O link será opcional, temporário e armazenado somente como hash. O paciente verá a rotina em modo de leitura, otimizada para celular. A primeira versão não permitirá editar a rotina pelo link; alterações continuam sendo feitas com o psicólogo durante a sessão.

## Regras de interação

- Blocos não podem ter duração menor que 5 minutos.
- O sistema permite sobreposição, mas mostra um alerta visual para o psicólogo revisar.
- O horário é exibido em formato de 24 horas.
- Mover um bloco atualiza o horário, sem apagar os demais.
- A exclusão exige confirmação quando o bloco já estiver salvo.
- Salvar uma alteração cria uma nova versão; versões anteriores permanecem somente para consulta.

## Critérios de aceite

- O catálogo mostra o Organizador de Rotina como nova ferramenta.
- É possível criar uma rotina diária com blocos das quatro categorias.
- A linha do tempo funciona em desktop, tablet e mobile.
- Uma rotina pode ser vinculada a um paciente do psicólogo autenticado.
- A rotina vinculada aparece no prontuário do paciente.
- É possível consultar versões anteriores sem perder a versão atual.
- O link temporário abre uma visualização pública somente leitura.
- Consultas e vínculos respeitam o psicólogo autenticado.
- `npm run build`, PHP lint, `php artisan route:list --path=gamekit` e testes Laravel relacionados passam.

## Fora da primeira versão

- Sincronização com Google Calendar.
- Lembretes ou notificações automáticas.
- Edição da rotina pelo link público.
- Recorrência semanal automática.
- Geração automática da rotina por IA.
