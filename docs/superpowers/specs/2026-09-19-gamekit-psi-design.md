# GameKit Psi — jogos terapêuticos no navegador

## Objetivo

Adicionar ao PsicoAgenda uma área de jogos terapêuticos simples para uso em sessão, começando pelo formato Associação e Memória. O psicólogo poderá configurar e revisar o jogo, conduzi-lo no próprio dispositivo ou gerar um link temporário para o paciente responder anonimamente.

## Escopo da primeira versão

- Um formato: Associação e Memória.
- Catálogo determinístico de modelos revisados.
- Configuração por faixa etária, tema, dificuldade e duração.
- Edição, remoção e reordenação de cartas antes do início.
- Modo psicólogo no tablet/notebook.
- Link anônimo, temporário e sem login para o paciente.
- Acompanhamento de respostas durante a sessão.
- Revisão explícita antes de enviar conteúdo ao prontuário.
- Exportação visual para modo TV e impressão como etapa posterior, sem bloquear o MVP.

Ficam fora da primeira versão: geração por IA, editor totalmente livre, múltiplos jogadores, ranking/pontuação, marketplace de jogos, integração com videoconferência e sincronização automática com prontuário.

## Fluxo do psicólogo

1. Acessa /gamekit dentro da área autenticada.
2. Escolhe faixa etária, tema, dificuldade, duração e o formato Associação e Memória.
3. O backend gera cartas a partir de templates controlados.
4. O psicólogo revisa, edita, remove ou embaralha as cartas.
5. Inicia no mesmo dispositivo ou gera um link temporário.
6. Acompanha respostas e progresso.
7. Seleciona respostas relevantes, acrescenta uma observação opcional e decide se envia ao prontuário.
8. Finaliza a sessão; o link deixa de aceitar respostas.

## Experiência do paciente

O link seguirá o formato conceitual /gamekit/play/{token}. A tela pública:

- não exige autenticação;
- não exibe nome, e-mail, telefone ou dados do paciente;
- mostra uma carta/tarefa por vez;
- permite responder somente às opções válidas da carta atual;
- não permite editar o jogo, navegar para outras sessões ou acessar o PsicoAgenda;
- mostra uma tela neutra quando o token estiver inválido, expirado ou encerrado.

O token será aleatório, longo, armazenado de forma segura e terá validade curta. A sessão poderá ser encerrada manualmente pelo psicólogo.

## Modelo de dados

### GameKitSession

Pertence ao psicólogo e contém formato, faixa etária, tema, dificuldade, duração, status (draft, active, finished, expired), token público protegido, expiração, início e término.

### GameKitCard

Pertence à sessão e contém posição, tipo de associação, texto da carta A, texto da carta B, opções editadas, resposta esperada quando aplicável e metadados mínimos do template.

### GameKitResponse

Pertence à sessão e à carta. Contém identificador anônimo da participação, resposta escolhida, ordem, horário, revisão do psicólogo, inclusão no prontuário e observação clínica opcional.

Não haverá relação obrigatória com patient_id na primeira versão. Se o psicólogo quiser enviar conteúdo ao prontuário, a operação exigirá seleção explícita do paciente e confirmação.

## API autenticada

~~~
GET    /api/gamekit/templates
POST   /api/gamekit/sessions
GET    /api/gamekit/sessions/{id}
PUT    /api/gamekit/sessions/{id}
POST   /api/gamekit/sessions/{id}/start
POST   /api/gamekit/sessions/{id}/link
POST   /api/gamekit/sessions/{id}/finish
POST   /api/gamekit/sessions/{id}/review
~~~

Todas as consultas devem ser escopadas ao psicólogo autenticado. O endpoint de revisão deve aceitar somente respostas da própria sessão e registrar uma decisão explícita antes de criar conteúdo no prontuário.

## API pública limitada

~~~
GET  /api/gamekit/play/{token}
POST /api/gamekit/play/{token}/responses
POST /api/gamekit/play/{token}/finish
~~~

O token deve ser validado em cada requisição. A API pública nunca retorna o psicólogo, paciente, prontuário ou outras sessões. Respostas duplicadas, cartas inexistentes, sessão encerrada e payloads fora do contrato retornam erro genérico sem revelar detalhes internos.

## Frontend

- GameKitView: configuração, revisão e acompanhamento no espaço autenticado.
- GameKitPlayerView: experiência pública por token.
- Componentes para configuração, carta, progresso, resposta, resumo e geração de link.
- Rota autenticada /gamekit e rota pública /gamekit/play/:token.
- Estado de sessão separado do store clínico geral.
- Estados de carregamento, link copiado, token expirado, sessão finalizada e erro de rede.

O modo psicólogo usa a linguagem e os tokens visuais do PsicoAgenda. O modo paciente prioriza uma tarefa por vez, botões grandes, contraste e funcionamento em tablet/celular.

## Segurança e privacidade

- Nunca expor tokens internos, IDs de psicólogo ou dados de paciente no link.
- Nunca registrar respostas no prontuário sem revisão e confirmação.
- Não colocar conteúdo clínico em logs, URLs, analytics ou mensagens de erro.
- Aplicar rate limit nos endpoints públicos.
- Invalidar o token ao expirar ou finalizar a sessão.
- Impedir acesso cruzado entre psicólogos.
- Sanitizar textos editáveis antes de renderizar.
- Tratar respostas como dado clínico sensível, com retenção mínima e exclusão vinculada à sessão.

## Critérios de aceite

- O psicólogo cria um jogo de Associação e Memória em menos de dois minutos.
- O jogo pode ser revisado antes de iniciar.
- O mesmo jogo funciona no modo local e por link.
- Um link expirado não revela informações da sessão.
- O paciente responde sem login e sem ver dados pessoais.
- O psicólogo acompanha respostas e decide o que vai para o prontuário.
- O sistema impede respostas inválidas e acessos fora da sessão.
- A experiência é utilizável em notebook, tablet e celular.

## Validação

- Testes de autorização por psicólogo.
- Testes de expiração, encerramento e rate limit do token.
- Testes de geração determinística dos templates.
- Testes de respostas válidas, inválidas e duplicadas.
- Testes de revisão e envio seletivo ao prontuário.
- npm run build, php -l dos arquivos alterados, php artisan test, php artisan route:list e git diff --check.
