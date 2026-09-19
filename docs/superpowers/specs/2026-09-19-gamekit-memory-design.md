# GameKit Psi — Jogo da Memória

## Objetivo

Adicionar ao GameKit Psi um jogo terapêutico de memória visual, separado do jogo atual de Associação e Memória. O psicólogo poderá criar, revisar, reutilizar e compartilhar uma atividade em que o paciente encontra pares de conceitos relacionados.

## Direção de produto

O jogo terá uma apresentação clínica acolhedora, adequada para adolescentes e adultos, sem estética infantil obrigatória. As cartas serão inicialmente textuais, com ícones e cores suaves; uploads de imagens ficam fora do primeiro ciclo.

O diferencial da experiência será a combinação de uma grade de cartas viradas, feedback terapêutico após cada par e um resumo final útil para conversa em sessão.

## Arquitetura de dados

Serão criadas tabelas independentes do fluxo atual:

- `gamekit_memory_games`: psicólogo proprietário, nome, tema, faixa etária, dificuldade, quantidade de pares e status.
- `gamekit_memory_pairs`: jogo proprietário, posição, rótulo do lado A, rótulo do lado B, conceito/emoção, explicação terapêutica e token visual.
- `gamekit_memory_sessions`: execução pública de um jogo, token com hash e expiração, status e timestamps.
- `gamekit_memory_results`: resultado agregado da sessão, com tentativas, pares encontrados, duração aproximada e conclusão.

Nenhuma tabela dependerá de `gamekit_cards` ou `gamekit_templates`. Todas as consultas autenticadas serão filtradas pelo psicólogo atual. O token público será armazenado apenas como hash e terá expiração; não serão registrados cliques individuais.

## Fluxos do psicólogo

1. O catálogo do GameKit mostra o jogo atual e o Jogo da Memória.
2. Ao escolher Jogo da Memória, o psicólogo informa tema, faixa etária, dificuldade e 6, 8 ou 12 pares.
3. O sistema gera um modelo inicial determinístico, que pode ser revisado.
4. Cada par pode ser editado em uma modal com lado A, lado B, conceito, feedback e aparência.
5. O psicólogo salva o jogo para reutilização, podendo renomear, duplicar ou excluir.
6. Ao iniciar uma sessão, o sistema cria uma execução e permite gerar o link temporário.
7. A tela do psicólogo exibe o status da sessão e o resultado agregado quando o paciente conclui.

## Fluxo público do paciente

- O player identifica o formato `memory` e carrega somente os pares necessários para a partida.
- As cartas são embaralhadas no cliente; cada par produz duas cartas viradas.
- O paciente pode abrir duas cartas por vez. Cartas iguais permanecem abertas; cartas diferentes retornam ao estado fechado após uma pausa curta.
- A interface mostra progresso de pares e tentativas, mas não apresenta ranking ou punição.
- Ao encontrar um par, exibe o feedback terapêutico configurado pelo psicólogo.
- Ao completar todos os pares, envia um único resultado agregado e mostra resumo da atividade.
- Em caso de erro de rede, o player mantém a partida localmente e permite tentar enviar o resultado novamente.

## Direção visual

- Âncora Organic: sage, azul suave e areia clara, em continuidade com o design system Serene Practice.
- Cartas com cantos arredondados, borda leve, sombra discreta e animação de flip curta.
- Grade de 4 colunas no desktop e 2 no mobile.
- Estados acessíveis de foco, contraste adequado, `aria-label` nas cartas e alternativa textual para o conteúdo visual.
- Mensagens em português claro: “Par encontrado”, “Tente outra combinação” e “Atividade concluída”.

## API e componentes

Rotas autenticadas previstas:

- `GET/POST /api/gamekit/memory/games`
- `GET/PUT/DELETE /api/gamekit/memory/games/{id}`
- `POST /api/gamekit/memory/games/{id}/duplicate`
- `POST /api/gamekit/memory/games/{id}/sessions`
- `POST /api/gamekit/memory/sessions/{id}/link`
- `GET /api/gamekit/memory/play/{token}`
- `POST /api/gamekit/memory/play/{token}/result`

Componentes Vue previstos:

- catálogo/edição dentro de `GameKitView.vue` ou componente dedicado;
- `GameKitMemoryPlayerView.vue` para o link público;
- componentes pequenos para carta, grade, progresso e resumo, se a implementação justificar a separação.

## Validação e critérios de aceite

- O catálogo exibe dois jogos e o jogo atual não muda de comportamento.
- É possível criar, editar, salvar, duplicar e excluir um Jogo da Memória.
- O link abre uma grade responsiva, embaralha as cartas e impede mais de duas cartas abertas simultaneamente.
- A sessão termina automaticamente no último par e salva apenas o resultado agregado.
- Tokens, jogos e sessões são sempre limitados ao psicólogo proprietário.
- `npm run build`, `php -l` nos arquivos PHP alterados, `php artisan route:list --path=gamekit` e testes Laravel relacionados passam.

## Fora do primeiro ciclo

- Upload ou biblioteca de imagens.
- Geração de pares por IA.
- Ranking entre pacientes.
- Registro individual de cada clique.
- Compartilhamento público sem expiração.
