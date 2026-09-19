# Redesign completo — Clínica Serena

## Objetivo

Refatorar a interface completa da PsicoAgenda para transmitir uma prática psicológica moderna, serena e profissional. O sistema deve priorizar o trabalho prolongado no computador e oferecer uma experiência móvel realmente adaptada, preservando regras de negócio, contratos de API, autenticação e escopo dos dados.

## Escopo

O redesign cobre o shell autenticado, a navegação e todas as telas públicas e privadas atuais: autenticação, dashboard, agenda, pacientes, prontuário, financeiro, relatórios, exportações, perfil e configurações.

Também fazem parte do escopo:

- reorganização da navegação sem alterar URLs públicas existentes;
- consolidação de padrões visuais reutilizáveis;
- responsividade para celular;
- estados de carregamento, vazio e erro;
- redução da exposição incidental de dados pessoais e clínicos;
- compatibilidade com os modos claro e escuro já presentes no projeto.

Não fazem parte do escopo novas regras clínicas, mudanças em integrações, alterações de contratos da API ou novas funcionalidades de negócio, exceto o modo de privacidade visual descrito abaixo.

## Direção visual aprovada

A direção escolhida é **Clínica Serena**:

- aparência limpa, calma e institucional, sem parecer fria;
- azul clínico como cor principal, com verde sálvia para estados positivos;
- superfícies claras, bordas discretas e bastante espaço em branco;
- Source Serif 4 em títulos e Manrope em controles e conteúdo;
- controles com raio de 8 px e painéis com raio de 16 px;
- contraste contido, sombras leves e ausência de gradientes chamativos;
- nenhum uso de pacientes fictícios realistas em estados vazios ou demonstrações.

Os tokens existentes em `resources/css/app.css` serão a fonte de verdade. Cores hard-coded e os tokens legados de `resources/js/utils/designTokens.ts` serão substituídos ou alinhados aos tokens semânticos apenas nas superfícies incluídas neste redesign.

## Arquitetura do shell

O shell aprovado é o **Shell Equilibrado**:

- menu lateral fixo e recolhível no desktop, com ícone e rótulo;
- barra superior enxuta com contexto, busca global e perfil;
- área de conteúdo previsível, com largura confortável e cabeçalho uniforme;
- painel lateral transformado em drawer no mobile;
- ações primárias acessíveis sem comprimir o conteúdo.

A navegação será agrupada por rotina:

- **Consultório:** visão geral, agenda e pacientes;
- **Gestão:** financeiro, relatórios e exportações;
- **Conta:** perfil e configurações;
- **Administração:** exibida somente quando a permissão existente autorizar.

As rotas atuais permanecem estáveis. O `AppShell` continuará sendo montado somente para usuário autenticado com e-mail verificado.

## Sistema de componentes

O redesign consolidará padrões com responsabilidades claras:

- `PageHeader`: contexto, título, descrição e ações da página;
- `SectionPanel`: superfície e espaçamento de uma seção;
- `MetricCard`: métrica resumida com hierarquia consistente;
- `StatusChip`: estado sem depender somente de cor;
- `EmptyState`: ausência de dados com orientação objetiva;
- `SensitiveInfo`: conteúdo pessoal recolhido e revelado conscientemente;
- componentes base existentes (`Button`, `Card`, `Modal`, `DataTable`, `Tabs`, `FormField`, `SelectInput`, `Badge` e `AppIcon`) serão preservados e normalizados.

Cabeçalhos e cartões duplicados nas views serão migrados para esses padrões. Componentes sem uso só serão removidos quando a ausência de consumidores for confirmada.

## Comportamento por área

### Dashboard

Apresentará próximos atendimentos, pendências e atalhos úteis com hierarquia simples. Não exibirá detalhes clínicos. Métricas secundárias não competirão com a agenda do dia.

### Agenda

Manterá visão ampla no desktop e usará sequência diária legível no celular. Categorias, filtros e ações terão o mesmo padrão das demais telas.

### Pacientes

No desktop, a tabela priorizará identificação e ações essenciais. No mobile, cada linha virará um cartão. CPF, contatos, cobrança, emergência e observações livres não aparecerão todos simultaneamente na listagem.

### Prontuário

Será separado em Evolução, Consultas, Documentos e Cadastro. A leitura clínica seguirá ordem cronológica. Dados pessoais e de emergência ficarão em uma seção recolhida por padrão, independente do tamanho de tela.

### Financeiro, relatórios e exportações

Usarão os mesmos cabeçalhos, métricas, filtros, tabelas e estados. Valores poderão ser ocultados pelo modo de privacidade. Impressões e exportações manterão o tratamento de conteúdo sensível já existente.

### Perfil, configurações e autenticação

Formulários adotarão agrupamentos, espaçamento, ajuda e feedback consistentes. Telas públicas continuarão fora do shell autenticado.

## Privacidade e segurança visual

A refatoração não altera autorização no backend nem amplia persistência de dados no navegador.

- A busca global exibirá somente o nome e a ação de abrir o paciente; telefone, e-mail e CPF não aparecerão no autocomplete.
- Notificações e badges não receberão detalhes clínicos.
- Um modo de privacidade visual ocultará dados pessoais e valores financeiros até nova ação do usuário.
- O título da página e a URL não incluirão nome de paciente.
- Respostas de erro de API não serão gravadas no console pelo novo código.
- Dados sensíveis permanecerão acessíveis apenas dentro das rotas e fluxos autenticados existentes.

O modo de privacidade é uma proteção contra exposição casual, não um mecanismo de autorização. O backend permanece responsável pelo isolamento entre psicólogos.

## Responsividade e acessibilidade

O desktop é o contexto principal. O mobile terá composição própria, não apenas redução proporcional:

- navegação em drawer;
- conteúdo em uma coluna;
- tabelas convertidas em cartões quando necessário;
- ações primárias visíveis e alvos de toque adequados;
- modais ajustados à viewport sem overflow horizontal.

Todos os controles terão foco visível, rótulo acessível e ordem de teclado coerente. Informação de status usará texto ou ícone além da cor. Contraste será verificado nos modos claro e escuro.

## Dados, carregamento e erros

As stores, composables, endpoints e eventos atuais serão preservados. A refatoração mudará a apresentação, não o fluxo de dados.

- carregamentos usarão skeletons discretos que preservam a estrutura da tela;
- estados vazios explicarão a ausência e oferecerão uma próxima ação quando aplicável;
- erros recuperáveis aparecerão junto da ação ou seção afetada;
- alertas globais serão reservados para eventos que realmente afetam toda a tela;
- falha em uma seção não apagará conteúdo válido de outra seção.

## Sequência de implementação

1. **Base visual e navegação:** tokens, shell, cabeçalhos, painéis, cartões, estados e responsividade.
2. **Rotina clínica:** dashboard, agenda, pacientes e prontuário.
3. **Gestão e acesso:** financeiro, relatórios, exportações, perfil, configurações e autenticação.

Cada bloco será validado antes de avançar. `docs/features.md` será atualizado junto das mudanças visíveis, de workflow e privacidade.

## Validação

- `npm run build`;
- `php artisan test`;
- `php artisan route:list` para confirmar estabilidade das rotas;
- smoke test manual das rotas públicas e privadas em desktop e mobile;
- menu aberto, recolhido e drawer mobile;
- busca, formulários, modais, tabelas e agenda sem overflow;
- modos claro e escuro;
- navegação por teclado, foco, labels, contraste e fechamento de modais;
- revisão de estados vazios, erros e exposição de dados pessoais;
- confirmação de que autenticação e verificação de e-mail continuam protegendo o shell.

Não há suíte E2E ou baseline de regressão visual no projeto. A primeira entrega usará build, testes Laravel e smoke visual documentado; a introdução de uma nova ferramenta de testes fica fora deste escopo.

## Riscos e controles

- **Amplitude da mudança:** implementação em três blocos e validação incremental.
- **Regressão de fluxo:** preservação de rotas, APIs, stores e eventos existentes.
- **Inconsistência entre telas:** componentes e tokens semânticos compartilhados.
- **Conflito com mudanças locais de tema:** alterações existentes serão preservadas e integradas, sem sobrescrita ampla.
- **Exposição incidental de dados:** redução de PII no shell e nas listagens, além do modo de privacidade.
