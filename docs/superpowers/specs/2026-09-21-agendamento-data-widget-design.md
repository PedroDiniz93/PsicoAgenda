# Widget de data na modal de agendamento

## Objetivo

Corrigir o erro de “data inválida” ao criar agendamentos e facilitar a escolha de data e horário na modal de atendimento, sem alterar o contrato da API nem as regras de agenda.

## Escopo

- Ajustar o componente `LocalizedDateInput` para aceitar digitação em `dd/mm/aaaa hh:mm` e oferecer um ícone clicável com seletor nativo de data/hora.
- Validar datas reais no frontend antes de emitir o valor interno.
- Manter o valor interno no formato `YYYY-MM-DDTHH:mm` e a conversão para ISO no `ScheduleView`.
- Aplicar o mesmo tratamento visual à data de pagamento e à data final da recorrência.
- Preservar acessibilidade, foco por teclado e comportamento responsivo.

Fora do escopo: trocar a biblioteca de calendário, mudar regras de disponibilidade/recorrência ou alterar o contrato dos endpoints.

## Fluxo e arquitetura

`LocalizedDateInput` continuará sendo o componente único usado nos campos de data da agenda. O campo de texto exibirá o formato brasileiro. Um botão com ícone `CalendarDays` abrirá um controle nativo auxiliar; sua seleção será convertida para o formato interno e refletida no texto.

O parser rejeitará datas inexistentes, como `31/02/2026`, horários fora do intervalo e valores incompletos. O componente não emitirá valor inválido. O `ScheduleView` continuará convertendo valores completos para ISO antes do envio. A submissão exibirá as mensagens já existentes junto aos campos e não enviará uma data inválida como `null` silenciosamente.

## Interface

- Campo de início e fim: entrada brasileira com ícone de calendário e seletor de data/hora.
- Campo “Pago em”: mesmo padrão, opcional.
- Campo “Repetir até”: seletor visual de data com ícone, mantendo `type="date"` como valor nativo.
- O ícone será registrado no `AppIcon` antes de ser usado.

## Erros e segurança

- Mensagens de validação permanecem em português e próximas ao campo.
- Nenhum dado clínico ou pessoal será incluído em logs.
- As validações de propriedade, disponibilidade, sobreposição e regras do backend permanecem inalteradas.

## Validação

- Testar manualmente criação e edição com seleção pelo widget.
- Testar digitação válida, data inexistente, horário inválido, término anterior ao início e recorrência.
- Executar `npm run build`.
- Executar `php -l` nos arquivos PHP tocados, se houver alteração backend.
- Executar testes relacionados a agendamento disponíveis no projeto.

