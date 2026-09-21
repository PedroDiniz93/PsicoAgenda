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

### 2026-09-21 - Widget e validação de data na modal de agendamento

- Status: Implementada
- Objetivo: corrigir o erro de data inválida e facilitar a escolha de datas e horários no agendamento.
- Escopo: `resources/js/components/base/LocalizedDateInput.vue` e `resources/js/views/ScheduleView.vue`.
- Comportamento: os campos exibem ícone de calendário, abrem o seletor nativo de data/hora, aceitam o formato brasileiro e rejeitam datas ou horários impossíveis antes do envio; a data final da recorrência usa o mesmo padrão.
- Validação: build do frontend e verificação manual dos fluxos de criação, edição, data inválida e recorrência.

### 2026-09-21 - Confirmação persistente da autorização do paciente

- Status: Implementada
- Objetivo: garantir que o paciente saia da espera mesmo quando o evento de aprovação do WebSocket não for recebido.
- Escopo: nova coluna de autorização em `online_sessions`, `OnlineSessionController`, `OnlineSession`, `useOnlineSession.js` e testes da sala online.
- Comportamento: a aprovação é registrada somente pela rota autenticada do psicólogo; o paciente recebe pelo WebSocket e consulta o estado público da sala como fallback.
- Segurança: o estado público expõe apenas um booleano de aprovação, condicionado a uma conexão recente do paciente; o paciente não consegue gravar ou alterar a aprovação.
- Validação: teste da API de aprovação, teste da sala online, build e `git diff --check`.

### 2026-09-21 - Fallback de solicitação de entrada da videoconferência

- Status: Implementada
- Objetivo: evitar que o psicólogo deixe de ver a solicitação do paciente quando um evento WebSocket for perdido.
- Escopo: `app/Http/Controllers/Api/OnlineSessionController.php`, `resources/js/composables/useOnlineSession.js` e `tests/Feature/OnlineSessionApiTest.php`.
- Comportamento: enquanto o psicólogo aguarda na sala, o frontend confirma periodicamente no backend se existe um paciente aguardando autorização; o WebSocket continua sendo o caminho imediato.
- Segurança: a confirmação usa a mesma sessão autenticada do psicólogo e só considera uma conexão de paciente válida nos últimos 45 segundos.
- Validação: teste da API de estado da solicitação, build de produção e `git diff --check`.

### 2026-09-20 - HTTPS e WebSocket seguro da videoconferência em produção

- Status: Implementada
- Objetivo: permitir que a sala online funcione em produção com HTTPS e sinalização Reverb via `wss://`.
- Escopo: configuração de produção no Hetzner, Nginx, serviço systemd do Reverb e variáveis públicas do frontend; o certificado é gerenciado pelo Certbot.
- Comportamento: `http://psicocontrolpro.com.br` redireciona para HTTPS, o Reverb permanece interno em `127.0.0.1:8080` e o Nginx encaminha `/app/` com upgrade WebSocket.
- Segurança: chaves do Reverb foram geradas no servidor e não são expostas neste registro; credenciais privadas continuam fora do frontend.
- Validação: Nginx configurado com sucesso, serviços Reverb/worker/PHP-FPM ativos, portas 443/8080 verificadas, HTTPS retornando 200 e build de produção concluído.

### 2026-09-20 - Estrutura Twilio para STUN/TURN da videoconferência

- Status: Implementada
- Objetivo: preparar a sala para obter servidores ICE temporários sem expor credenciais Twilio no frontend.
- Escopo: `.env.example`, `config/services.php`, `routes/api.php`, `app/Http/Controllers/Api/OnlineSessionController.php`, `resources/js/composables/useOnlineSession.js` e `tests/Feature/OnlineSessionApiTest.php`.
- Comportamento: o backend solicita tokens TURN temporários quando as variáveis Twilio estão preenchidas; a sala usa o `ice_servers` retornado e mantém fallback local para `VITE_WEBRTC_ICE_SERVERS`.
- Segurança: `TWILIO_API_SECRET` fica somente no backend; o frontend recebe apenas a lista temporária de servidores ICE.
- Validação: `npm run build`, 8 testes da sala online (48 assertions), `php -l` e `git diff --check`.

### 2026-09-20 - Auditoria e limite de conexão da videoconferência

- Status: Implementada
- Objetivo: impedir uso simultâneo do mesmo link por dois pacientes e revisar a segurança do fluxo completo da videoconferência.
- Escopo: `database/migrations/2026_09_20_000004_add_patient_connection_lock_to_online_sessions_table.php`, `app/Models/OnlineSession.php`, `app/Http/Controllers/Api/OnlineSessionController.php`, `resources/js/composables/useOnlineSession.js`, `resources/js/views/OnlineSessionView.vue`, `tests/Feature/OnlineSessionApiTest.php` e `docs/audits/2026-09-20-videoconferencia.md`.
- Comportamento: a primeira conexão do paciente ocupa a sala; outra aba recebe estado de sala em uso; sinais públicos também ficam vinculados à conexão autorizada e expiram sem heartbeat.
- Auditoria: autorização, privacidade, WebRTC, sinalização, reconexão, controles, chat, tela cheia e prontuário foram revisados; a configuração de STUN/TURN permanece como risco de produção documentado.
- Validação: `npm run build`, 7 testes da sala online (35 assertions), `php -l` e `git diff --check`.

### 2026-09-20 - Notificações sonoras do chat da sala online

- Status: Implementada
- Objetivo: avisar novas mensagens e eventos de presença sem ocupar espaço visual no chat.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: novas mensagens e entrada ou saída do participante reproduzem um toque curto; o indicador textual “Nova mensagem” foi removido.
- Privacidade: o som é gerado localmente pelo navegador, sem arquivo externo ou envio de conteúdo.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Autorização do paciente na sala online

- Status: Implementada
- Objetivo: impedir que o paciente entre na videochamada antes da autorização do psicólogo.
- Escopo: `resources/js/composables/useOnlineSession.js`, `resources/js/views/OnlineSessionView.vue`, `app/Http/Controllers/Api/OnlineSessionController.php` e `tests/Feature/OnlineSessionApiTest.php`.
- Comportamento: o paciente aguarda em uma tela dedicada sem acessar câmera, microfone ou WebRTC; o psicólogo recebe a solicitação e libera a entrada pelo botão “Aceitar paciente”; a tela “Prepare sua sala” foi removida.
- Segurança: solicitações não ativam a sessão, o sinal de aprovação só pode ser enviado pelo psicólogo autenticado e o canal público só aceita o papel de paciente.
- Validação: `npm run build`, `php artisan test tests/Feature/OnlineSessionApiTest.php`, `php -l app/Http/Controllers/Api/OnlineSessionController.php` e `git diff --check`.

### 2026-09-20 - Correção do modo tela cheia da sala online

- Status: Implementada
- Objetivo: permitir entrar e sair da tela cheia sem depender da tecla Esc.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o painel ocupa a viewport, o vídeo se adapta ao espaço disponível e um botão explícito “Voltar ao tamanho normal” aparece no cabeçalho durante a tela cheia.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Simplificação do status visual da sala

- Status: Implementada
- Objetivo: evitar a repetição do estado de conexão na tela da chamada.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o status “Aguardando paciente” ou equivalente fica somente no indicador superior direito do painel de vídeo; o cronômetro permanece na barra inferior quando ativo.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Refatoração visual da sala online

- Status: Implementada
- Objetivo: tornar a sala de atendimento mais clara, moderna e confortável para uso prolongado.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: vídeo principal, controles e estado da sessão ficam agrupados em um painel de foco; chat e acesso ao prontuário ficam em uma lateral persistente, com melhor hierarquia visual e adaptação para telas menores.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Controles de mídia da sala online

- Status: Implementada
- Objetivo: permitir que psicólogo e paciente controlem áudio, vídeo, dispositivos e saída da sala durante o atendimento.
- Escopo: `resources/js/composables/useOnlineSession.js`, `resources/js/views/OnlineSessionView.vue` e `resources/js/components/base/AppIcon.vue`.
- Comportamento: microfone e câmera são alternados sem desconectar; dispositivos podem ser trocados via `replaceTrack`; o psicólogo encerra o atendimento e o paciente sai da sala com confirmação; estados desligados têm indicação visual.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Estados de estabilidade e pré-teste da sala online

- Status: Implementada
- Objetivo: diferenciar espera normal, conexão, reconexão e falha antes e durante o atendimento.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: a sala exibe teste de câmera e microfone antes da entrada, estados contextuais para paciente e psicólogo, aviso de internet instável e retry somente em falha técnica.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Destaque alternável dos vídeos da sala

- Status: Implementada
- Objetivo: priorizar a visualização do participante durante o atendimento.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o vídeo remoto aparece grande por padrão e a câmera local fica em uma janela sobreposta; “Trocar destaque” inverte os tamanhos sem reiniciar a chamada.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Indicadores compartilhados de áudio e vídeo

- Status: Implementada
- Objetivo: permitir que cada participante saiba quando o outro desligou câmera ou microfone.
- Escopo: `resources/js/composables/useOnlineSession.js`, `resources/js/views/OnlineSessionView.vue` e `app/Http/Controllers/Api/OnlineSessionController.php`.
- Comportamento: os estados de áudio e vídeo são enviados pelo canal da sala; os ícones `MicOff` e `VideoOff` aparecem sobre o quadro correspondente para todos os participantes.
- Validação: `npm run build`, `php -l app/Http/Controllers/Api/OnlineSessionController.php` e `git diff --check`.

### 2026-09-20 - Eventos de entrada e saída no chat da sala

- Status: Implementada
- Objetivo: manter no histórico do chat os eventos de presença do atendimento.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: a entrada ou saída do participante aparece como mensagem do sistema e ativa o indicador de nova mensagem.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Prontuário expansível durante a sala online

- Status: Implementada
- Objetivo: consultar o prontuário sem sair da chamada ou perder o foco do atendimento.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o psicólogo abre o prontuário em um painel abaixo da sala; os registros são carregados sob demanda e permanecem na mesma página.
- Segurança: o painel só é renderizado para o psicólogo e usa o endpoint autenticado já existente, mantendo a validação de propriedade no backend.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Inicialização automática do Reverb no ambiente local

- Status: Implementada
- Objetivo: impedir que a sala online falhe porque o servidor de sinalização não foi iniciado.
- Escopo: `composer.json`.
- Comportamento: `composer dev` passa a iniciar o Laravel Reverb junto com servidor HTTP, fila, logs e Vite.
- Validação: handshake WebSocket local, testes da API da sala online e validação do arquivo Composer.

### 2026-09-20 - Inicialização da mídia após renderização da sala online

- Status: Implementada
- Objetivo: fazer câmera e microfone funcionarem já na primeira abertura da sala.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: os elementos de vídeo são renderizados antes da inicialização da mídia e do WebRTC, evitando depender do botão “Tentar novamente”.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Identificação da etapa de conexão da sala online

- Status: Implementada
- Objetivo: tornar explícita a etapa que falha após câmera e áudio serem liberados.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: o erro informa somente a fase técnica (`mídia`, `WebRTC`, canal ou presença), sem tokens, URLs ou dados clínicos.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Estado de espera sem alerta de erro

- Status: Implementada
- Objetivo: não tratar a ausência temporária do outro participante como falha.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: a mensagem vermelha aparece somente quando a sala está no estado `error`; enquanto aguarda o outro participante, a tela mantém apenas “Aguardando conexão...”.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Preservação do estado ativo após o handshake

- Status: Implementada
- Objetivo: evitar que a resposta tardia do envio de presença sobrescreva uma conexão já estabelecida.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: o estado permanece `active` quando o WebRTC conecta antes da resposta HTTP da presença; somente conexões ainda não ativas entram em `waiting`.
- Validação: fluxo Laravel `presence → offer → answer → ICE` e `npm run build`.

### 2026-09-20 - Rastreamento das transições WebRTC

- Status: Implementada
- Objetivo: identificar sobrescritas de estado e falhas na negociação ICE.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: o console registra origem de cada transição de status e os estados de sinalização, ICE e coleta de candidatos, sem conteúdo de mídia ou dados sensíveis.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Renderização correta do estado da sala

- Status: Implementada
- Objetivo: exibir “Conexão ativa” quando o WebRTC realmente está conectado.
- Escopo: `resources/js/views/OnlineSessionView.vue`.
- Comportamento: refs de status, erro, mídia e mensagens são expostos como bindings do template, evitando comparar objetos `ref` com strings.
- Validação: logs `ICE connected` e `status active`, `npm run build` e `git diff --check`.

### 2026-09-20 - Captura parcial de mídia na sala online

- Status: Implementada
- Objetivo: permitir entrar na sala quando o navegador encontra somente câmera ou somente microfone.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: após `NotFoundError` na captura conjunta, câmera e microfone são testados separadamente; a sala continua com a mídia disponível e exibe um aviso claro.
- Validação: `npm run build`, `git diff --check` e diagnóstico sem registro de nomes ou IDs de dispositivos.

### 2026-09-20 - Vínculo dos vídeos da sala online

- Status: Implementada
- Objetivo: exibir os streams local e remoto após a conexão WebRTC.
- Escopo: `resources/js/views/OnlineSessionView.vue` e `resources/js/composables/useOnlineSession.js`.
- Comportamento: os elementos `<video>` ficam ligados aos refs do composable; falhas de vínculo são registradas sem conteúdo de mídia ou dados sensíveis.
- Validação: `npm run build`, `git diff --check` e confirmação de `connectionState: connected` nos dois participantes.

### 2026-09-20 - Handshake independente da ordem de entrada

- Status: Implementada
- Objetivo: iniciar a chamada mesmo quando o paciente abre a sala antes do psicólogo.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: o paciente confirma presença ao detectar o psicólogo, o psicólogo cria a oferta somente para a presença do paciente e ofertas concorrentes são bloqueadas.
- Validação: `npm run build`, `git diff --check` e fluxo `presence → offer → answer → connected`.

### 2026-09-20 - Correção do scroll do menu lateral

- Status: Implementada
- Objetivo: remover o erro do Vue ao navegar entre as telas autenticadas.
- Escopo: `resources/js/components/home/HomeAppSidebar.vue`.
- Comportamento: o menu converte a referência do `RouterLink` para o elemento DOM antes de chamar `scrollIntoView()`.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Alinhamento local do Reverb na sala online

- Status: Implementada
- Objetivo: permitir que a API publique a sinalização WebRTC no Reverb local.
- Escopo: `.env.example` e configuração local de Reverb.
- Comportamento: backend e frontend usam `127.0.0.1` para coincidir com o endereço de escuta do servidor Reverb, evitando falha de conexão via `localhost`.
- Validação: `php artisan config:clear` e `npm run build`.

### 2026-09-20 - Reprodução explícita da prévia de vídeo

- Status: Implementada
- Objetivo: exibir a câmera autorizada mesmo quando o navegador não inicia automaticamente o elemento de vídeo.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: streams local e remoto recebem `srcObject` e uma tentativa explícita de `play()` após a conexão dos elementos.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Diagnóstico seguro da sala online

- Status: Implementada
- Objetivo: identificar em qual etapa a sala falha quando o servidor não registra uma requisição.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o console registra cada condição de mídia, WebRTC, Echo, presença e sinal recebido, incluindo o papel somente em mensagens de presença, além de etapa, tipo, nome/código do erro e status HTTP; o backend registra somente id da sala, papel da presença, tipo do sinal, estado e presença do socket. Tokens, URLs completas, dados de pacientes e demais payloads não são registrados.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Ordenação de candidatos ICE na sala online

- Status: Implementada
- Objetivo: evitar falha WebRTC quando candidatos ICE chegam antes da descrição remota.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: candidatos recebidos antes de `setRemoteDescription()` ficam enfileirados e são adicionados após a descrição remota; a fila é limpa ao reiniciar ou sair da sala.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Normalização da descrição SDP na sala online

- Status: Implementada
- Objetivo: corrigir ofertas WebRTC rejeitadas pelo navegador como SDP inválido.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: ofertas e respostas são serializadas apenas com `type` e `sdp`, com normalização de quebras de linha e descarte de descrições duplicadas.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Rastreamento das fases de inicialização da sala

- Status: Implementada
- Objetivo: localizar estados de erro sem exceção no console.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: o console registra somente as fases `start`, `media ready`, `presence sent`, `ready` e `retry`, sem dados sensíveis.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Retry confiável da sala online

- Status: Implementada
- Objetivo: evitar faixa de erro vazia e tentativa de recuperação sem efeito na videochamada.
- Escopo: `resources/js/views/OnlineSessionView.vue` e `resources/js/composables/useOnlineSession.js`.
- Comportamento: a sala exibe mensagem padrão quando o erro não traz texto, recarrega a sala quando a falha ocorre antes de obter a sessão, reinicia recursos WebRTC e informa quando a nova tentativa está em andamento.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Limpeza do erro após recuperação da sala

- Status: Implementada
- Objetivo: remover a mensagem de erro antiga quando a sala conclui a inicialização.
- Escopo: `resources/js/composables/useOnlineSession.js` e `resources/js/views/OnlineSessionView.vue`.
- Comportamento: a mensagem é limpa após o envio de presença bem-sucedido e só permanece visível durante estados reais de erro.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Limpeza do erro após conexão WebRTC

- Status: Implementada
- Objetivo: remover mensagens antigas quando a chamada efetivamente conecta.
- Escopo: `resources/js/composables/useOnlineSession.js`.
- Comportamento: ao atingir `connectionState: connected`, a sala muda para `active` e limpa qualquer erro residual.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-20 - Diagnóstico de câmera e microfone na sala online

- Status: Implementada
- Objetivo: explicar por que a sala não conseguiu acessar áudio e vídeo.
- Escopo: resources/js/composables/useOnlineSession.js.
- Comportamento: a sala identifica contexto inseguro, permissão bloqueada, dispositivo ausente, dispositivo ocupado e falhas específicas do navegador, exibindo uma orientação em português.
- Validação: npm run build e testes da sala online.

### 2026-09-20 - Tentativa novamente para mídia da sala online

- Status: Implementada
- Objetivo: permitir recuperar falhas temporárias de câmera e microfone sem sair da sala.
- Escopo: resources/js/views/OnlineSessionView.vue e resources/js/composables/useOnlineSession.js.
- Comportamento: a interface oferece “Tentar novamente” após falha de mídia e corrige a mensagem para contexto inseguro ou API de mídia indisponível.
- Validação: npm run build e testes da sala online.

### 2026-09-20 - Núcleo da sala de atendimento online

- Status: Parcial
- Objetivo: preparar uma sala temporária vinculada ao compromisso para atendimento ao vivo.
- Escopo: migration de salas online, modelo `OnlineSession`, `OnlineSessionController` e rotas autenticadas/públicas.
- Comportamento: o psicólogo pode criar, consultar e encerrar uma sala de compromisso online; o paciente recebe somente o estado mínimo da sala por token temporário.
- Validação: testes de autorização, expiração, isolamento do payload público, PHP lint, rotas e migration.
- Notas: vídeo, voz, chat em tempo real e telas ainda serão implementados na próxima etapa; não há gravação nem persistência de mensagens.

### 2026-09-19 - Cards de link temporário do GameKit

- Status: Implementada
- Objetivo: tornar os links dos jogos mais fáceis de copiar e abrir.
- Escopo: `resources/js/components/gamekit/TemporaryLinkCard.vue` e telas do GameKit.
- Comportamento: todos os jogos exibem o link em um card padronizado, com cópia ao clicar na URL, botão explícito de copiar, confirmação visual e abertura em nova aba.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-19 - Correções no Jogo da Velha Mutante

- Status: Implementada
- Objetivo: corrigir a alternância entre jogadores e o reinício da partida.
- Escopo: `resources/js/views/GameKitTicTacToePlayerView.vue`.
- Comportamento: no modo local, os toques alternam corretamente entre X e O; “Jogar novamente” limpa o tabuleiro sem recarregar ou invalidar a tela.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-19 - Boneco visual na Forca infantil

- Status: Implementada
- Objetivo: tornar os erros da Forca claros e acolhedores para crianças.
- Escopo: `resources/js/views/GameKitHangmanPlayerView.vue`.
- Comportamento: o boneco é desenhado progressivamente em doze erros, começando pela estrutura da forca e terminando nas pernas; ao completar o desenho, a palavra é revelada e o paciente pode avançar para a próxima.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-19 - Jogos infantis recreativos no GameKit

- Status: Implementada
- Objetivo: oferecer jogos simples e acolhedores para crianças, com link de participação no celular.
- Escopo: migration `2026_09_19_000009_create_gamekit_child_games_tables.php`, modelos e `GameKitChildGamesController`, `GameKitAiService`, rotas, catálogo GameKit e telas dos jogos da velha e da forca.
- Comportamento: o Jogo da Velha Mutante limita cada jogador a três peças e remove a mais antiga ao inserir a quarta; a Forca permite escolher tema/faixa etária, gerar palavras com IA, revisar o modelo e criar uma sessão com link temporário.
- Validação: PHP lint, migration, `npm run build`, `php artisan route:list --path=gamekit` e `git diff --check`.
- Notas: resultados recreativos ficam separados dos registros clínicos; tokens públicos são armazenados somente como hash.

### 2026-09-19 - Exclusão de rotina pelo prontuário

- Status: Implementada
- Objetivo: permitir remover uma rotina terapêutica diretamente do paciente vinculado.
- Escopo: `resources/js/views/PatientRecordView.vue`.
- Comportamento: o card da rotina ganhou um ícone de lixeira; a exclusão pede confirmação, remove a rotina e suas versões e atualiza a lista sem recarregar a página.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-19 - Resumo enxuto das atividades da rotina

- Status: Implementada
- Objetivo: deixar a leitura da rotina no prontuário mais limpa.
- Escopo: `resources/js/views/PatientRecordView.vue`.
- Comportamento: cada bloco mostra apenas horário e nome da atividade; duração e códigos internos de categoria não aparecem no resumo.
- Validação: `git diff --check`.

### 2026-09-19 - Formatação dos horários da rotina

- Status: Implementada
- Objetivo: deixar os horários legíveis no formato de horas e minutos.
- Escopo: `GameKitRoutineController.php` e `PatientRecordView.vue`.
- Comportamento: horários como `06:00:00` são exibidos como `06:00`, sem segundos.
- Validação: PHP lint, `npm run build` e `git diff --check`.

### 2026-09-19 - Correção do carregamento da versão atual da rotina

- Status: Implementada
- Objetivo: permitir listar e abrir rotinas sem erro SQL no carregamento dos blocos.
- Escopo: `app/Models/GameKitRoutine.php`.
- Comportamento: a versão atual é carregada como a versão incremental mais recente, com suporte a eager loading em listas, prontuário e link público.
- Validação: PHP lint, `php artisan route:list --path=gamekit/routines` e `git diff --check`.

### 2026-09-19 - Exibição amigável das rotinas no prontuário

- Status: Implementada
- Objetivo: evitar que o objeto completo da versão apareça no resumo da rotina.
- Escopo: `resources/js/views/PatientRecordView.vue` e `resources/js/views/GameKitRoutineView.vue`.
- Comportamento: os cards mostram o número da versão e a quantidade de blocos, mantendo os detalhes organizados ao expandir a rotina.
- Validação: `npm run build` e `git diff --check`.

### 2026-09-19 - Geração de rotina por IA

- Status: Implementada
- Objetivo: acelerar a criação de uma rotina diária personalizada sem retirar a revisão do psicólogo.
- Escopo: `GameKitAiService`, `GameKitRoutineController`, rotas de rotina e `GameKitRoutineView.vue`.
- Comportamento: o psicólogo informa tema, faixa etária e quantidade de momentos; a IA sugere horários, duração, atividade, categoria, ícone e descrição em português. Os blocos são carregados no mesmo editor, podem ser ajustados e só são persistidos ao clicar em salvar.
- Validação: PHP lint, `npm run build`, `php artisan route:list --path=gamekit` e `git diff --check`.
- Notas: a IA recebe apenas os parâmetros da atividade; nenhum dado identificável do paciente é enviado.

### 2026-09-19 - Organizador de Rotina vinculado ao paciente

- Status: Implementada
- Objetivo: montar uma rotina diária visual durante a sessão e reutilizá-la no acompanhamento do paciente.
- Escopo: migration `2026_09_19_000008_create_gamekit_routine_tables.php`, modelos e `GameKitRoutineController`, rotas, `GameKitRoutineView.vue`, `GameKitRoutinePlayerView.vue`, catálogo GameKit, `PatientController`, `Patient.php` e `PatientRecordView.vue`.
- Comportamento: o psicólogo cria blocos de estudo, descanso, lazer e autocuidado em uma linha do tempo, vincula a rotina a um paciente, cria novas versões, consulta a rotina no prontuário e pode gerar um link temporário somente leitura.
- Validação: `php artisan migrate --force`, PHP lint, `npm run build`, `php artisan route:list --path=gamekit` e `git diff --check`.
- Notas: a rotina não cria eventos na agenda e o link público não exibe dados clínicos nem informações adicionais do paciente.

### 2026-09-19 - Pausa antes do encerramento do Jogo da Memória

- Status: Implementada
- Objetivo: permitir que o paciente veja o último par antes do resumo final.
- Escopo: `resources/js/views/GameKitMemoryPlayerView.vue`.
- Comportamento: após encontrar o último par, o destaque e o feedback permanecem visíveis por 1,2 segundo antes de “Atividade concluída”.
- Validação: `npm run build` e `git diff --check`.

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
