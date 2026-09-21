# Autorização de entrada na sala online

## Objetivo

Impedir que o paciente entre na videochamada antes da autorização do psicólogo. A tela de preparação genérica será substituída por um fluxo de espera e aceite explícito.

## Fluxo aprovado

1. O psicólogo abre a sala e inicia seus dispositivos para ficar disponível.
2. O paciente abre o link, entra apenas no canal de sinalização e vê uma tela de espera.
3. O psicólogo recebe uma solicitação identificada como “Paciente aguardando entrada” e pode clicar em “Aceitar paciente”.
4. A autorização é transmitida pelo canal da sala.
5. Somente após a autorização o paciente solicita câmera e microfone, cria o WebRTC e entra na chamada.
6. O WebRTC segue os estados atuais até “Conexão ativa”.

## Arquitetura

- `useOnlineSession` terá uma etapa de sinalização sem mídia/peer para o paciente, além do início completo já usado pelo psicólogo.
- A solicitação do paciente usará presença com estado explícito; ela não iniciará oferta, resposta, ICE ou mídia.
- A autorização usará um sinal dedicado e validado (`entry-approved`). Ao recebê-lo, o paciente inicia o fluxo completo.
- O psicólogo só cria a oferta depois de receber a presença do paciente após a autorização.
- O backend não promoverá a sessão para `active` ao receber uma solicitação de entrada; somente sinais de participação autorizada poderão alterar esse estado.

## Estados de interface

- Paciente: “Aguardando autorização do psicólogo” enquanto aguarda; após autorização, “Conectando...” e estados já existentes.
- Psicólogo: aviso não bloqueante de solicitação e botão “Aceitar paciente”; após aceitar, estados já existentes.
- Falhas de sinalização exibem mensagem acionável sem expor tokens, payloads ou dados clínicos.

## Segurança e privacidade

- O paciente não captura nem publica câmera/microfone antes de receber `entry-approved`.
- O backend mantém autenticação e escopo do psicólogo nas rotas privadas; o link público só poderá solicitar entrada na própria sala.
- Logs registram somente tipo do sinal, papel e estados técnicos, sem token, conteúdo de chat ou dados clínicos.

## Validação

- Teste da API para garantir que solicitação não ativa a sala e que a aprovação é aceita apenas no canal correto.
- Fluxo manual psicólogo → paciente: paciente aguardando, aceite, captura de mídia, handshake e conexão.
- Fluxo de recusa/expiração ou falha de autorização com mensagem clara.
- `npm run build`, `php artisan test tests/Feature/OnlineSessionApiTest.php`, `php -l app/Http/Controllers/Api/OnlineSessionController.php` e `git diff --check`.
