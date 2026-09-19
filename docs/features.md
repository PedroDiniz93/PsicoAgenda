# Feature Registry

Registro curto de features do PsicoAgenda / PsicoControl para recuperar historico sem depender da conversa.

Sempre adicione a nova entrada no topo de `## Features`.

## Formato

```md
### YYYY-MM-DD - Nome da feature

- Status: Implementada | Parcial | Planejada
- Objetivo: problema resolvido.
- Escopo: arquivos ou areas alteradas.
- Comportamento: efeito para o usuario.
- Validacao: comandos ou criterio de aceite.
- Notas: riscos, restricoes ou observacoes.
```

Regras:

- Registre no topo.
- Seja factual e breve.
- Nao repita contexto ja fixo em `AGENTS.md`.

## Features

### 2026-09-19 - Ícones semânticos nos pares do Jogo da Memória

- Status: Implementada
- Objetivo: tornar a combinação dos pares mais clara e memorável para o paciente.
- Escopo: migration de ícone, `GameKitMemoryPair`, `GameKitMemoryController`, `GameKitAiService` e `GameKitMemoryPlayerView.vue`.
- Comportamento: cada par recebe um ícone semântico validado; a IA escolhe apenas ícones permitidos, e o player destaca pares encontrados com ícone grande, cor compartilhada, borda reforçada e selo de confirmação.
- Validação: `npm run build`, PHP lint, `php artisan migrate --force` e rotas do GameKit.
- Notas: cartas fechadas continuam neutras para não revelar a combinação antes da jogada.

### 2026-09-19 - Fluxo unificado do Jogo da Memória

- Status: Implementada
- Objetivo: alinhar a criação do Jogo da Memória ao mesmo fluxo do primeiro jogo do GameKit.
- Escopo: `resources/js/views/GameKitMemoryView.vue`.
- Comportamento: o psicólogo configura os parâmetros em um único formulário, escolhe modelo padrão ou IA, revisa os pares, salva o modelo e depois inicia uma sessão a partir da biblioteca.
- Validação: `npm run build`.

### 2026-09-19 - Geração por IA no Jogo da Memória

- Status: Implementada
- Objetivo: permitir que o psicólogo gere pares terapêuticos personalizados para o jogo.
- Escopo: `GameKitAiService`, `GameKitMemoryController`, migration de idade exata, `GameKitMemoryView.vue` e rotas de memória.
- Comportamento: a IA recebe tema, faixa etária, dificuldade e quantidade de pares; gera lados, conceito e feedback para cada par, que permanece editável antes do salvamento.
- Validação: `npm run build`, `php -l` nos arquivos PHP alterados, `php artisan route:list --path=gamekit` e `php artisan migrate --force`.
- Notas: a IA não recebe dados de pacientes e não salva o jogo automaticamente antes da revisão do psicólogo.

### 2026-09-19 - Jogo da Memória terapêutico do GameKit

- Status: Implementada
- Objetivo: adicionar um segundo jogo ao catálogo do GameKit Psi, com pares visuais e feedback terapêutico.
- Escopo: migration `2026_09_19_000005_create_gamekit_memory_tables.php`, modelos e `GameKitMemoryController`, rotas de memória, `GameKitMemoryView.vue`, `GameKitMemoryPlayerView.vue` e router.
- Comportamento: o psicólogo cria, edita, duplica, exclui e reutiliza jogos com 6, 8 ou 12 pares; o paciente recebe um link temporário, encontra os pares em uma grade responsiva e envia somente o resultado agregado da sessão.
- Validação: `npm run build`, `php -l` nos arquivos PHP novos, `php artisan route:list --path=gamekit` e `php artisan migrate --force`.
- Notas: a funcionalidade usa tabelas próprias e não altera o jogo Associação e Memória.

### 2026-09-19 - Catálogo inicial de jogos do GameKit

- Status: Implementada
- Objetivo: preparar o GameKit para crescer como um pack de jogos terapêuticos.
- Escopo: `resources/js/views/GameKitView.vue`.
- Comportamento: ao abrir o GameKit, o psicólogo vê o catálogo e escolhe o jogo disponível; a tela de configuração, modelos salvos e histórico aparecem somente após essa escolha. O catálogo inicial contém Associação e memória.
- Validação: `npm run build`.
- Notas: novos jogos podem ser adicionados ao catálogo sem alterar o fluxo de geração e reutilização de modelos.

### 2026-09-19 - Gerador padrão como modelo reutilizável

- Status: Implementada
- Objetivo: separar a criação de um modelo padrão da abertura de uma sessão.
- Escopo: GameKitService, GameKitAiController, routes/api.php e GameKitView.vue.
- Comportamento: “Gerar modelo padrão” cria seis cartas com contexto, pergunta e três opções coerentes, abre a revisão e permite salvar o resultado como novo modelo reutilizável.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Modal e mini grid do histórico do GameKit

- Status: Implementada
- Objetivo: facilitar a navegação entre sessões respondidas e reduzir a área ocupada pelo histórico.
- Escopo: GameKitController, GameKitAiController e GameKitView.vue.
- Comportamento: o histórico aparece em mini grid com até seis sessões; clicar em uma sessão abre uma modal com respostas e vínculo ao paciente.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Retorno do editor de sessão do GameKit

- Status: Implementada
- Objetivo: permitir voltar da revisão de cartas para o configurador.
- Escopo: GameKitView.vue.
- Comportamento: a tela da sessão possui botão “Voltar”, que limpa o link temporário e retorna ao estado de criação/modelos.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Finalização automática das atividades do GameKit

- Status: Implementada
- Objetivo: eliminar a necessidade de finalizar manualmente uma sessão após a última resposta.
- Escopo: GameKitController, GameKitPlayerView, GameKitView e PatientRecordView.
- Comportamento: ao registrar a resposta da última carta, o backend marca a sessão como finalizada; os status são exibidos em português e o botão manual foi removido.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Atividades terapêuticas no prontuário do paciente

- Status: Implementada
- Objetivo: tornar visíveis as sessões GameKit vinculadas ao paciente.
- Escopo: Patient, PatientController e PatientRecordView.
- Comportamento: abaixo de “Dados pessoais” há uma seção “Atividades terapêuticas”; cada sessão pode ser expandida para consultar respostas, data, status e quantidade de respostas.
- Segurança: as atividades vêm da consulta do paciente autenticado e permanecem restritas ao psicólogo proprietário.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Histórico somente com sessões respondidas

- Status: Implementada
- Objetivo: evitar que rascunhos e sessões sem respostas poluam o histórico.
- Escopo: GameKitController e GameKitView.vue.
- Comportamento: o histórico consulta `with_responses=1` e exibe somente sessões que possuem ao menos uma resposta registrada.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Histórico e vínculo opcional das respostas do GameKit

- Status: Parcial
- Objetivo: permitir consultar respostas de sessões e associá-las a um paciente.
- Escopo: migration de `patient_id` em gamekit_responses, GameKitResponse, GameKitController e GameKitView.
- Comportamento: o psicólogo consulta sessões anteriores, visualiza resposta por carta e escolhe opcionalmente um paciente do próprio consultório uma única vez para toda a sessão; não há lançamento automático no prontuário.
- Segurança: paciente e sessão são validados no escopo do psicólogo autenticado.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Remoção da duração da configuração do GameKit

- Status: Implementada
- Objetivo: simplificar a criação de atividades removendo um parâmetro que não participa da dinâmica do jogo.
- Escopo: GameKitView, GameKitController e GameKitAiController.
- Comportamento: a duração não aparece mais no formulário nem é exigida nas requisições; sessões antigas continuam usando seu valor e novas sessões usam 45 minutos como padrão interno.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Atualização imediata de título e cartas do modelo

- Status: Implementada
- Objetivo: manter a tela sincronizada logo após salvar uma edição.
- Escopo: GameKitAiController.
- Comportamento: alterações de título, contexto, pergunta e opções são gravadas na nova versão e a resposta retorna essa versão como `cards`, atualizando a lista sem recarregar a página.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Modal de edição dos modelos do GameKit

- Status: Implementada
- Objetivo: melhorar o foco da edição de um modelo salvo.
- Escopo: GameKitView.vue.
- Comportamento: clicar no modelo abre uma modal centralizada com título, cartas, contexto, pergunta e opções; a modal possui rolagem própria e botão para fechar sem alterar.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Correção do uso de modelos em sessões

- Status: Implementada
- Objetivo: garantir que um modelo salvo carregue suas cartas na nova sessão.
- Escopo: GameKitAiController.
- Comportamento: a versão atual das cartas é filtrada explicitamente, sem depender da relação Eloquent dinâmica que retornava coleção vazia; a sessão passa a iniciar com contexto, pergunta e opções.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Player do GameKit com contexto e pergunta separados

- Status: Implementada
- Objetivo: evitar que a pergunta do paciente pareça uma opção de resposta.
- Escopo: GameKitController e GameKitPlayerView.vue.
- Comportamento: o link público exibe Contexto, Pergunta e, em seguida, as três opções selecionáveis.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Unificação do formato de cartas do GameKit

- Status: Implementada
- Objetivo: alinhar geração padrão, IA, edição e link do paciente.
- Escopo: GameKitService, GameKitController e GameKitView.vue.
- Comportamento: toda carta possui contexto, pergunta e exatamente três opções; a edição da sessão usa esses campos e o link recupera as cartas do servidor quando necessário.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Correção da contagem de cartas nos modelos salvos

- Status: Implementada
- Objetivo: exibir e editar as cartas da versão atual de um modelo salvo.
- Escopo: GameKitAiController e GameKitView.vue.
- Comportamento: a API retorna explicitamente as cartas da versão vigente no campo `cards`; a tela usa esse campo para contar e abrir as cartas para edição.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Edição do título de modelos vazios

- Status: Implementada
- Objetivo: permitir renomear um modelo mesmo quando ele ainda não possui cartas.
- Escopo: GameKitAiController, rotas de templates e GameKitView.vue.
- Comportamento: ao clicar no modelo, o campo de título aparece; a alteração pode ser salva separadamente das cartas.
- Validação: npm run build, php -l e git diff --check.
- Observação: a atualização somente do título usa o endpoint PUT principal e, após sucesso, o editor retorna ao estado inicial.

### 2026-09-19 - Edição e exclusão de modelos do GameKit

- Status: Implementada
- Objetivo: permitir administrar cartas e modelos já salvos.
- Escopo: GameKitAiController e GameKitView.vue.
- Comportamento: modelos exibem a quantidade atual de cartas, podem ser abertos para edição, atualizados, ter cartas removidas ou ser excluídos; a exclusão exige confirmação.
- Validação: npm run build, php -l e git diff --check.

### 2026-09-19 - Correção do carregamento inicial do GameKit

- Status: Implementada
- Objetivo: evitar erro de renderização ao abrir o GameKit sem uma sessão criada.
- Escopo: GameKitView.vue.
- Comportamento: o painel da sessão só é renderizado quando `session` existe; a configuração permanece visível enquanto a sessão não foi criada.
- Validação: npm run build e git diff --check.

### 2026-09-19 - GameKit: configuração unificada de atividade

- Status: Implementada
- Objetivo: remover a duplicação dos formulários de geração padrão e por IA.
- Escopo: GameKitView.vue.
- Comportamento: uma única configuração alimenta os botões “Gerar jogo padrão” e “Gerar com IA”; modelos salvos continuam disponíveis para reutilização.
- Validação: npm run build e git diff --check.

### 2026-09-19 - GameKit Psi com geração assistida por IA

- Status: Parcial
- Objetivo: gerar cartas terapêuticas editáveis e salvar modelos reutilizáveis.
- Escopo: GameKitAiService, GameKitAiController, modelos/migration de templates, GameKitView e configuração OpenAI.
- Comportamento: o psicólogo informa parâmetros da atividade, recebe cartas com contexto, pergunta e três opções, revisa/edita e salva um modelo versionado; nenhum dado de paciente é enviado à IA.
- Segurança: chave somente no backend, `store:false`, schema estrito e validação local da resposta; prompts e respostas não são registrados.
- Validação: npm run build, php -l dos arquivos PHP alterados, php artisan route:list e git diff --check.
- Notas: requer `OPENAI_API_KEY`; a geração por IA não substitui revisão clínica.

### 2026-09-19 - Correção da primeira resposta do GameKit

- Status: Implementada
- Objetivo: permitir que a primeira resposta pública seja enviada sem identificador prévio.
- Escopo: GameKitController.
- Comportamento: o sistema gera o participant_key anônimo quando ele ainda não foi enviado pelo player.
- Validação: php -l e git diff --check.

### 2026-09-19 - Correção da chave das cartas do GameKit

- Status: Implementada
- Objetivo: alinhar a relação entre sessões, cartas e respostas com as colunas reais da migration.
- Escopo: modelo GameKitSession.
- Comportamento: a criação de cartas passa a gravar em gamekit_session_id, sem depender da convenção automática que gerava game_kit_session_id.
- Validação: php -l e git diff --check.

### 2026-09-19 - Correção das tabelas do GameKit

- Status: Implementada
- Objetivo: corrigir o nome de tabela inferido pelo Eloquent nas sessões, cartas e respostas do GameKit.
- Escopo: modelos GameKit.
- Comportamento: os modelos passam a usar explicitamente as tabelas criadas pela migration, evitando erro de tabela inexistente no MySQL.
- Validação: php -l dos modelos e git diff --check.

### 2026-09-19 - GameKit Psi (MVP)

- Status: Parcial
- Objetivo: oferecer uma atividade terapêutica de Associação e Memória para uso presencial ou por link anônimo temporário.
- Escopo: migrations e modelos GameKit, serviço de templates, API autenticada/pública, telas GameKit Psi e player público.
- Comportamento: o psicólogo configura um jogo, revisa cartas, gera um link sem login para o paciente e finaliza a sessão; respostas ficam vinculadas à sessão e não são lançadas automaticamente no prontuário.
- Segurança: token público com hash e expiração, rate limit público, isolamento por psicólogo e ausência de dados identificáveis no player.
- Validação: npm run build, php -l dos arquivos PHP alterados, php artisan route:list e git diff --check.
- Notas: o catálogo inicial é determinístico; IA, impressão e integração efetiva de respostas selecionadas ao prontuário permanecem etapas posteriores.

### 2026-09-19 - Modal para eventos externos

- Status: Implementada
- Objetivo: permitir consultar e excluir um evento externo sem ação imediata ao clicar no card.
- Escopo: ScheduleView.vue e fluxo de exclusão do Google Calendar.
- Comportamento: o clique abre uma modal somente leitura com título, início e fim; a modal oferece “Excluir no Google” com confirmação e estado de carregamento.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Loader da sincronização externa

- Status: Implementada
- Objetivo: sinalizar o carregamento dos eventos do Google antes de atualizar a grade.
- Escopo: ScheduleView.vue.
- Comportamento: a grade recebe uma camada translúcida com blur e spinner “Sincronizando eventos do Google...” enquanto a consulta está em andamento, preservando os agendamentos internos ao fundo.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Exclusão de eventos internos e externos

- Status: Implementada
- Objetivo: permitir remover um agendamento do PsicoAgenda ou um evento importado do Google Calendar.
- Escopo: endpoints de exclusão, serviço GoogleCalendarService e ações da ScheduleView.
- Comportamento: agendamentos internos podem ser excluídos com confirmação e removem o vínculo correspondente no Google; eventos externos são excluídos no Google após confirmação.
- Segurança: consultas e exclusões ficam restritas ao psicólogo autenticado; eventos sincronizados pelo PsicoAgenda não podem ser removidos pelo endpoint de eventos externos.
- Validação: npm run build, php -l dos controllers/serviço, php artisan route:list e git diff --check.

### 2026-09-19 - Indicadores de pacientes em uma linha

- Status: Implementada
- Objetivo: deixar os cards de resumo mais compactos e escaneáveis.
- Escopo: PatientsView.vue.
- Comportamento: ícone, rótulo, valor e indicador mensal ficam alinhados em uma única linha no desktop, com truncamento seguro para telas estreitas.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cards compactos de pacientes

- Status: Implementada
- Objetivo: reduzir o peso visual dos indicadores de pacientes.
- Escopo: PatientsView.vue.
- Comportamento: cards, ícones, espaçamentos e números passam a usar uma escala mais compacta, mantendo os quatro indicadores legíveis.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cabeçalho financeiro alinhado

- Status: Implementada
- Objetivo: manter o botão Dashboard dentro do enquadramento do cabeçalho financeiro.
- Escopo: FinanceView.vue.
- Comportamento: título, descrição e ação usam as regiões semânticas do cabeçalho, com alinhamento consistente em desktop e mobile.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Cores das abas financeiras

- Status: Implementada
- Objetivo: alinhar as abas Resumo, Cobranças, Carteira e Configurações à paleta global do aplicativo.
- Escopo: FinanceView.vue.
- Comportamento: o estado ativo usa o azul de ação da interface e o estado inativo usa superfícies e bordas semânticas, com contraste consistente nos temas claro e escuro.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Campo de antecedência dos lembretes

- Status: Implementada
- Objetivo: ajustar a proporção do campo numérico de dias antes do lembrete.
- Escopo: HomeSettingsPanel.vue.
- Comportamento: o campo passa a ter largura compacta, adequada ao valor numérico, sem ocupar toda a linha.
- Validação: npm run build e git diff --check.

### 2026-09-19 - Eventos externos do Google na agenda

- Status: Implementada
- Objetivo: mostrar na agenda os eventos existentes no Google Calendar conectado.
- Escopo: endpoint autenticado de leitura, GoogleCalendarService e grade/lista responsiva da ScheduleView.
- Comportamento: eventos externos são consultados apenas no intervalo visível, exibem o título do evento, não bloqueiam horários, não viram agendamentos locais e dividem a coluna quando há sobreposição.
- Privacidade: apenas o título é exposto; descrições, convidados, locais, links e tokens do Google permanecem ocultos; eventos criados pelo PsicoAgenda não são duplicados.
- Validação: npm run build, php -l dos arquivos PHP alterados, php artisan route:list e git diff --check.
- Notas: a leitura é sob demanda e não persiste eventos externos; webhooks, múltiplos calendários e sincronização bidirecional permanecem fora do escopo.

### 2026-09-19 - Redesign Clínica Serena

- Status: Implementada
- Objetivo: unificar toda a interface em uma experiência moderna, calma e adequada à rotina de psicólogos.
- Escopo: shell autenticado, navegação, componentes base, dashboard, pacientes, prontuário, agenda, relatórios, financeiro, exportações, perfil, configurações e telas públicas de acesso.
- Comportamento: a navegação passa a ser agrupada por rotina, o conteúdo acompanha a largura da sidebar, cabeçalhos e superfícies seguem tokens semânticos, e as telas mantêm composição responsiva para desktop e mobile.
- Privacidade: a busca global deixa de exibir contatos, a listagem de pacientes mostra apenas informações essenciais, dados cadastrais do prontuário ficam recolhidos e um modo transitório oculta busca, alertas, prontuário e financeiro.
- Validação: `npm run build` e `php artisan route:list` concluídos; `php artisan test` executado, com os testes de banco bloqueados pela ausência do driver `pdo_sqlite` no ambiente.
- Notas: APIs, autenticação, payloads clínicos e regras de escopo por psicólogo foram preservados; o redesign integra o modo claro/escuro já existente.

### 2026-06-12 - Refinamento visual do modo escuro

- Status: Implementada
- Objetivo: corrigir superficies claras e elementos desconexos no tema escuro.
- Escopo: `resources/css/app.css`, `resources/js/components/AppShell.vue`.
- Comportamento: o fundo global, cards, topbar, sidebar, campos, bordas e textos com cores fixas passam a seguir os tokens escuros da interface.
- Validacao: `npm run build`.
- Notas: ajuste visual sem mudanca no contrato da API.

### 2026-06-12 - Modo escuro por usuario

- Status: Implementada
- Objetivo: permitir que cada psicologo habilite ou desabilite o modo escuro com preferencia salva na conta.
- Escopo: `database/migrations/2026_06_12_000001_add_theme_mode_to_psychologists_table.php`, `app/Http/Controllers/Api/PsychologistController.php`, `app/Http/Requests/PsychologistSettingsUpdateRequest.php`, `app/Models/Psychologist.php`, `resources/js/views/SettingsView.vue`, `resources/js/components/home/HomeSettingsPanel.vue`, `resources/js/components/AppShell.vue`, `resources/css/app.css`.
- Comportamento: a tela de configuracoes exibe um toggle de tema; a escolha fica salva por usuario, abre em claro por padrao e aplica o tema em toda a interface ao recarregar ou trocar de rota.
- Validacao: `npm run build`.
- Notas: a preferencia usa `light` como valor default para usuarios sem configuracao salva.

### 2026-06-12 - Editar paciente a partir da busca global

- Status: Implementada
- Objetivo: abrir a modal de edicao do paciente diretamente pelo autocomplete global.
- Escopo: `resources/js/components/AppShell.vue`, `resources/js/views/PatientsView.vue`.
- Comportamento: ao clicar em `Editar`, o sistema carrega os dados completos e abre a modal de edicao por cima da pagina atual, sem redirecionar.
- Validacao: `npm run build`.
- Notas: a acao usa o endpoint existente de detalhes do paciente e nao altera o contrato da API.

### 2026-06-12 - Data de nascimento com mascara digitavel

- Status: Implementada
- Objetivo: permitir digitar a data de nascimento com mascara `dd/mm/aaaa` sem depender apenas do calendario.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`.
- Comportamento: o campo visivel aceita digitação manual com mascara e continua abrindo o seletor nativo pelo botao de calendario; o valor enviado ao backend segue em `YYYY-MM-DD`.
- Validacao: `npm run build`.
- Notas: nao altera contrato da API.

### 2026-06-12 - Data de nascimento com calendario e formato BR

- Status: Implementada
- Objetivo: manter o calendario nativo no cadastro de paciente e exibir a data em `dd/mm/aaaa`.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`.
- Comportamento: o campo visual mostra data no padrao brasileiro e o botao de calendario abre o seletor nativo; o valor enviado ao backend continua em `YYYY-MM-DD`.
- Validacao: `npm run build`.
- Notas: nao altera contrato da API.

### 2026-06-12 - Modal de paciente alinhado ao Stitch

- Status: Implementada
- Objetivo: adaptar o cadastro e a edicao de paciente ao layout `Edicao de Paciente - TherapyFlow` do Stitch.
- Escopo: `resources/js/components/patients/PatientFormModal.vue`, `storage/app/stitch/11237689929679384621/patient-edit/`.
- Comportamento: o formulario abre em modal amplo com secoes visuais no padrao TherapyFlow, mantendo os campos e validacoes existentes.
- Validacao: `npm run build`.
- Notas: nenhum campo novo foi enviado ao backend; os artefatos do Stitch ficam em `storage/app/stitch/11237689929679384621/patient-edit/`.

### 2026-06-12 - Sidebar sem deslocar conteudo

- Status: Implementada
- Objetivo: fazer o sidebar abrir e recolher sem empurrar o conteudo da pagina para a direita.
- Escopo: `resources/js/components/AppShell.vue`.
- Comportamento: o sidebar permanece fixo sobre a interface e o conteudo principal nao muda de largura ou posicao quando o menu abre.
- Validacao: `npm run build`.
- Notas: sem impacto em API ou permissao; altera apenas o shell global.

### 2026-06-12 - Metricas de pacientes pelo backend

- Status: Implementada
- Objetivo: calcular no backend os totais exibidos nos cards da base de pacientes.
- Escopo: `app/Http/Controllers/Api/PatientController.php`, `resources/js/views/PatientsView.vue`, `tests/Feature/PatientIndexTest.php`.
- Comportamento: `/api/patients` retorna `metrics` com total geral, criados no mes, ativos, pausados e encerrados do psicologo autenticado; os cards usam esses valores.
- Validacao: `php -l app/Http/Controllers/Api/PatientController.php`, `php -l tests/Feature/PatientIndexTest.php`, `npm run build`; `php artisan test --filter=PatientIndexTest` bloqueado localmente por driver SQLite ausente.
- Notas: as metricas nao sao filtradas pela busca/status da grid; apenas a lista paginada segue os filtros.

### 2026-06-12 - Grid de pacientes sem observacoes

- Status: Implementada
- Objetivo: simplificar a tabela de pacientes removendo a coluna de observacoes da grid desktop.
- Escopo: `resources/js/components/patients/PatientsList.vue`.
- Comportamento: a tabela passa a exibir paciente, status, cobranca, contato e acoes; as observacoes continuam acessiveis no card mobile.
- Validacao: `npm run build`.
- Notas: ajuste visual pequeno, sem mudancas de API.

### 2026-06-12 - Base de Pacientes alinhada ao Stitch

- Status: Implementada
- Objetivo: reformular a pagina de pacientes com base na tela `Base de Pacientes - TherapyFlow` do Stitch.
- Escopo: `resources/js/views/PatientsView.vue`, `resources/js/components/patients/PatientsHeader.vue`, `resources/js/components/patients/PatientsFilters.vue`, `resources/js/components/patients/PatientsList.vue`, `storage/app/stitch/11237689929679384621/patients/`.
- Comportamento: `/patients` exibe cabecalho, cards de status, filtros em pills, tabela desktop e cards mobile no visual TherapyFlow usando apenas dados reais da API.
- Validacao: `npm run build`.
- Notas: metricas de status usam a pagina atual; o total usa a paginacao retornada por `/api/patients`.

### 2026-06-12 - Perfil do psicologo em pagina propria

- Status: Implementada
- Objetivo: tirar a edicao de perfil da Home e disponibilizar uma pagina dedicada no sidebar.
- Escopo: `resources/js/views/ProfileView.vue`, `resources/js/router/index.js`, `resources/js/components/AppShell.vue`, `resources/js/composables/useHomeDashboardModel.js`, `resources/js/views/HomeView.vue`.
- Comportamento: `Perfil` abre `/profile` para editar os dados profissionais; a Home nao exibe mais a aba de perfil.
- Validacao: `npm run build`.
- Notas: reaproveita `HomeProfilePanel.vue` e os endpoints existentes de perfil.

### 2026-06-12 - Configuracoes em pagina independente

- Status: Implementada
- Objetivo: mover configuracoes da Home para rota propria.
- Escopo: `resources/js/views/SettingsView.vue`, `resources/js/router/index.js`, `resources/js/components/AppShell.vue`, `resources/js/views/HomeView.vue`, `app/Http/Controllers/Api/GoogleOAuthController.php`.
- Comportamento: `Configuracoes` abre `/settings`; Home nao usa mais `?tab=settings`; retorno do Google vai para a nova pagina.
- Validacao: `npm run build`.
- Notas: reutiliza `HomeSettingsPanel.vue`.

### 2026-06-12 - Dashboard consolidada com proximos pacientes e atendimentos semanais

- Status: Implementada
- Objetivo: trocar blocos de leitura da dashboard por foco em proximos atendimentos e volume semanal.
- Escopo: `app/Http/Controllers/Api/HomeDashboardController.php`, `routes/api.php`, `resources/js/composables/useHomeDashboardModel.js`, `resources/js/components/home/HomeOverviewPanel.vue`, `resources/js/views/HomeView.vue`.
- Comportamento: Home consome `GET /api/home/dashboard` e mostra proximos atendimentos, barras da semana e metricas.
- Validacao: `npm run build` e teste de integracao do endpoint.
- Notas: usa dados reais do psicologo autenticado.

### 2026-06-12 - Autocomplete global de pacientes

- Status: Implementada
- Objetivo: permitir busca rapida de pacientes no header.
- Escopo: `AppShell.vue`, `HomeTopBar.vue`, `GET /api/patients?q=&per_page=5`.
- Comportamento: com 3+ caracteres, mostra ate 5 pacientes com acoes para editar ou abrir o prontuario.
- Validacao: `npm run build`.
- Notas: usa dados reais do psicologo autenticado.

### 2026-06-12 - Dashboard MCP TherapyFlow

- Status: Implementada
- Objetivo: reorganizar a Home com inspiracao na tela Dashboard - TherapyFlow do Stitch.
- Escopo: `storage/app/stitch/11237689929679384621/dashboard/`, `resources/js/composables/useHomeDashboardModel.js`, `HomeAppSidebar.vue`, `HomeTopBar.vue`, `HomeView.vue`, `HomeOverviewPanel.vue`, `PatientsView.vue`.
- Comportamento: shell com sidebar recolhivel, drawer mobile, header com busca, sino de alertas, metricas e acoes operacionais.
- Validacao: `npm run build`.
- Notas: sem pacientes ficticios; `AppShell.vue` orquestra o shell global.

### 2026-06-12 - Design system Serene Practice do Stitch

- Status: Implementada
- Objetivo: alinhar a aplicacao ao design system do Stitch.
- Escopo: `resources/css/app.css`, `resources/js/components/base`, `storage/app/stitch/11237689929679384621/`, `public/images/stitch/`.
- Comportamento: interface clara e clinica com fundo `#f9f9f8`, superficies brancas, azul `#415f76`, sage `#4c6455`, Source Serif 4 e Manrope.

### 2026-06-12 - Login inspirado no Stitch

- Status: Implementada
- Objetivo: reproduzir a tela Login - TherapyFlow no login real.
- Escopo: `resources/js/views/LoginView.vue`, `public/images/stitch/login-therapy-room.jpg`, `storage/app/stitch/11237689929679384621/login/`.
- Comportamento: login em duas colunas, imagem atmosferica, marca PsicoControl, senha visivel/oculta, lembrar de mim e recuperacao.

### 2026-06-12 - Recuperacao e redefinicao de senha

- Status: Implementada
- Objetivo: tornar o fluxo de esqueci minha senha funcional.
- Escopo: `AuthController`, `routes/api.php`, `PasswordResetMail`, `resources/views/emails/password_reset.blade.php`, `ForgotPasswordView.vue`, `ResetPasswordView.vue`, `resources/js/router/index.js`.
- Comportamento: usuario pede recuperacao; se existir, recebe link para `/reset-password`; nova senha invalida tokens anteriores.

### 2026-06-12 - Lembrar de mim por 30 dias

- Status: Implementada
- Objetivo: persistir a sessao quando o checkbox e marcado.
- Escopo: `AuthController`, `resources/js/stores/auth.js`, `LoginView.vue`.
- Comportamento: com `remember: true`, backend cria token Sanctum com `expires_at` em 30 dias e frontend usa `localStorage`; sem isso, usa `sessionStorage`.
