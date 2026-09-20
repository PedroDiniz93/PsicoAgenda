# Plano de implementação — Sala de Atendimento Online

## Brief

- Objetivo: adicionar atendimento 1:1 ao vivo com vídeo, voz e chat, mantendo o prontuário disponível apenas para o psicólogo.
- Tipo: nova feature.
- Escopo: sala vinculada a compromisso, autenticação temporária do paciente, mídia WebRTC, sinalização/presença, chat não persistido e painel integrado do psicólogo.
- Restrições: sem gravação, transcrição, compartilhamento de tela, chamadas em grupo ou histórico persistido do chat; seguir isolamento por psicólogo.
- Validação: testes de autorização/isolamento, testes de expiração, build frontend, testes Laravel, lint e verificação manual com permissões/reconexão.

## Decisões técnicas

- Criar uma entidade de sala vinculada a `Appointment`, com estado, expiração e hash do token público.
- Usar WebRTC para mídia 1:1.
- Usar Laravel Reverb/WebSockets para sinalização, presença e mensagens efêmeras.
- Configurar TURN como dependência de infraestrutura; a aplicação não armazenará mídia.
- Separar claramente API autenticada do psicólogo e API temporária do paciente.
- Não persistir o conteúdo do chat nem incluir prontuário na resposta pública.

## Frentes de trabalho

### 1. Banco e domínio

1. Criar migration para salas de atendimento, vinculada a psicólogo, compromisso e paciente, com status, `expires_at`, `started_at`, `ended_at` e hash do token.
2. Criar modelo e relações mínimas com `Appointment`, `Patient` e `Psychologist`.
3. Definir estados permitidos: aguardando, ativa, encerrada e expirada.
4. Adicionar expiração e invalidação idempotente.
5. Não criar tabela de mensagens no MVP.

### 2. Backend Laravel

1. Criar requests/services para criar sala, gerar token temporário, entrar, encerrar e consultar estado.
2. Reutilizar o padrão de ownership de `AppointmentController` para impedir acesso cruzado entre psicólogos.
3. Criar endpoints autenticados para o psicólogo e endpoints temporários mínimos para o paciente.
4. Implementar eventos/canais privados para sinalização WebRTC, presença e chat efêmero.
5. Aplicar rate limit, validação de origem e respostas genéricas para tokens inválidos/expirados.
6. Garantir que logs e exceções não contenham tokens, conteúdo de chat ou dados clínicos.

### 3. Frontend Vue

1. Adicionar rota autenticada da sala do psicólogo e rota pública/temporária do paciente.
2. Criar composable isolado para ciclo WebRTC: permissões, `RTCPeerConnection`, ICE candidates, conexão, reconexão e limpeza de tracks.
3. Criar componentes de mídia, controles, chat efêmero, estado da sala e mensagens de erro.
4. Criar layout do psicólogo com chamada/chat e painel do paciente/prontuário.
5. Criar layout do paciente sem qualquer chamada de API de prontuário.
6. Integrar “Iniciar atendimento online” ao compromisso elegível e permitir copiar link temporário.
7. Cobrir loading, vazio, erro, foco de teclado, câmera/microfone bloqueados e viewport estreito.
8. Verificar ícones no `AppIcon.vue` antes de usar novos ícones.

### 4. Segurança

1. Testar ownership do compromisso, paciente e prontuário em toda operação.
2. Armazenar apenas hash do token público; nunca devolver o token em logs ou respostas desnecessárias.
3. Garantir expiração, uso limitado e invalidação após encerramento.
4. Revisar payloads públicos com allowlist explícita.
5. Não persistir áudio, vídeo ou chat.
6. Documentar configuração de HTTPS, origem permitida, TURN e credenciais fora do repositório.

### 5. QA e testes

1. Testes de criação somente para compromisso do psicólogo autenticado.
2. Testes de entrada com token válido, inválido, expirado e encerrado.
3. Testes de isolamento: paciente não recebe prontuário, financeiro, lista de pacientes ou Sanctum token.
4. Testes de encerramento idempotente e reconexão de participante.
5. Testes frontend para estados da sala e limpeza de recursos WebRTC.
6. Verificação manual em dois navegadores/dispositivos, com permissão concedida e bloqueada.

## Ordem de execução

1. Confirmar disponibilidade/configuração de Reverb e TURN no ambiente local e de produção.
2. Implementar domínio, migration e autorização sem interface.
3. Implementar ciclo de vida da sala e endpoints.
4. Implementar canais/eventos de sinalização e chat efêmero.
5. Implementar tela do psicólogo.
6. Implementar tela do paciente.
7. Integrar à agenda e ao registro pós-atendimento.
8. Executar validações incrementais e revisar segurança antes do aceite.

## Riscos e mitigação

- WebRTC pode falhar em redes restritas: usar TURN e apresentar estado de reconexão.
- Reverb/TURN exigem configuração operacional: documentar variáveis e health checks antes do lançamento.
- Prontuário na mesma tela aumenta risco de exposição: separar rotas, payloads e layouts por papel.
- Navegadores podem bloquear mídia: explicar permissões e oferecer reentrada sem expor dados sensíveis.
- Sem persistência do chat, mensagens perdidas não serão recuperáveis; isso é uma decisão explícita do MVP.

## Critério de pronto

A feature só será considerada pronta quando uma sala puder ser iniciada a partir de um compromisso, dois participantes puderem conversar por vídeo, áudio e chat, o psicólogo puder consultar o prontuário correto, o paciente não puder acessar dados internos, e os testes de autorização, expiração e isolamento passarem.
