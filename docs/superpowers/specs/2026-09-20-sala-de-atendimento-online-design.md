# Sala de Atendimento Online — Design

## Objetivo

Permitir que psicólogo e paciente realizem um atendimento individual ao vivo dentro do PsicoAgenda, com vídeo, voz e chat. Durante a chamada, o psicólogo acessa o prontuário e os dados autorizados do paciente na mesma tela; o paciente vê somente a sala de atendimento.

## Escopo do MVP

- Sala 1:1 iniciada a partir de um compromisso da agenda.
- Vídeo e áudio em tempo real via WebRTC.
- Chat em tempo real durante a sessão.
- Interface do psicólogo com chamada, chat, dados essenciais e prontuário.
- Interface do paciente com chamada, chat e controles de câmera/microfone.
- Sala com token/link temporário e autorização por participante.
- Estados de espera, entrada, reconexão, encerramento e falha de permissão.
- Registro opcional do atendimento pelo psicólogo após o encerramento.

## Fora do escopo do MVP

- Gravação de áudio, vídeo ou tela.
- Compartilhamento de tela.
- Chamadas em grupo.
- Transcrição automática ou análise de conteúdo da conversa.
- Persistência do histórico completo do chat.
- Acesso do paciente ao prontuário, agenda ou outros dados internos.

## Experiência e fluxo

1. O psicólogo abre um compromisso elegível e seleciona “Iniciar atendimento online”.
2. O backend cria uma sala temporária vinculada ao compromisso e ao psicólogo autenticado.
3. O psicólogo entra na sala e pode copiar o link de entrada do paciente.
4. O paciente acessa o link e informa apenas os dados mínimos necessários para validar a entrada.
5. Ambos autorizam câmera e microfone no navegador.
6. A sala exibe vídeo, áudio, estado de conexão e chat.
7. O psicólogo alterna entre a chamada e o painel lateral do paciente, sem enviar dados do prontuário ao cliente do paciente.
8. Qualquer participante pode sair; o psicólogo pode encerrar a sala.
9. Após o encerramento, a sala deixa de aceitar novas conexões e o psicólogo pode registrar a evolução normalmente no prontuário.

## Arquitetura proposta

- **Mídia:** WebRTC em conexão 1:1.
- **Sinalização e presença:** Laravel Reverb/WebSockets, com eventos mínimos para oferta, resposta, ICE candidates, entrada, saída e estado da sala.
- **Fallback de rede:** servidor TURN configurado fora da aplicação, sem armazenar mídia.
- **Aplicação:** Laravel 12 controla autorização, ciclo de vida da sala e APIs; Vue 3 controla as duas interfaces.
- **Chat:** mensagens trafegadas em tempo real. O MVP não salva o conteúdo após o encerramento.

O backend deve separar claramente endpoints do psicólogo e endpoints públicos/temporários do paciente. Os endpoints do psicólogo devem aplicar autenticação Sanctum e escopo pelo psicólogo proprietário; os endpoints do paciente devem aceitar somente o token temporário da sala e retornar os dados mínimos da chamada.

## Segurança e privacidade

- Nunca enviar prontuário, histórico clínico, dados financeiros ou lista de pacientes para a interface do paciente.
- Armazenar somente o hash do token público da sala, quando o ciclo de vida exigir persistência.
- Expirar tokens, invalidar a sala ao encerramento e impedir reutilização indevida.
- Não registrar conteúdo de áudio, vídeo ou chat em logs, analytics ou mensagens de erro.
- Não registrar tokens de sala, Sanctum ou credenciais nos logs.
- Validar que o compromisso pertence ao psicólogo autenticado antes de criar ou encerrar a sala.
- Exibir consentimento e aviso de privacidade antes de habilitar câmera e microfone.
- Aplicar limites de origem, rate limit e proteção contra tentativa de enumeração de salas.

## Estados e falhas

- Aguardando psicólogo.
- Aguardando paciente.
- Conectando câmera e microfone.
- Em atendimento.
- Reconectando.
- Microfone/câmera bloqueados pelo navegador.
- Participante desconectado.
- Sala encerrada ou expirada.

Cada estado deve ter texto claro em português, foco de teclado preservado e ação de recuperação quando possível.

## Critérios de aceite

- Psicólogo e paciente conseguem entrar na mesma sala com vídeo e áudio.
- Chat funciona nos dois lados sem persistir o histórico após o encerramento.
- O psicólogo visualiza o prontuário do paciente correto na sala.
- O paciente nunca recebe dados do prontuário ou de outros pacientes.
- Usuário não autorizado não consegue criar, consultar ou encerrar salas de outro psicólogo.
- Sala expirada ou encerrada não aceita novas entradas.
- A chamada informa claramente falhas de permissão e reconexão.
- Nenhum áudio, vídeo, chat ou token sensível aparece em logs.

## Validação planejada

- Testes de autorização para criação, entrada e encerramento.
- Testes de isolamento da resposta pública do paciente.
- Testes de expiração e invalidação de sala.
- Teste manual em navegadores com câmera/microfone permitidos e bloqueados.
- Teste manual de reconexão e encerramento por cada participante.
- `php artisan test`, `npm run build`, `php artisan route:list`, PHP lint e `git diff --check`.

## Decisão registrada

O primeiro lançamento será ao vivo e sem gravação. Recursos como gravação, compartilhamento de tela, chamadas em grupo e transcrição ficam fora desta especificação.
