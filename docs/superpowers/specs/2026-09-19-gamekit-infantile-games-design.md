# GameKit Psi — jogos infantis recreativos

## Objetivo

Adicionar ao GameKit dois jogos recreativos para crianças: Jogo da Velha Mutante e Jogo da Forca. Os jogos devem reutilizar o fluxo existente de modelos salvos, sessões e links temporários para o paciente.

## Experiência do psicólogo

- O catálogo do GameKit exibe cards para os dois novos jogos.
- Cada jogo tem uma tela própria, com configuração, modelos salvos e início de sessão.
- O Jogo da Velha não exige geração por IA: o psicólogo inicia uma partida e escolhe jogar contra o computador ou com outra pessoa no mesmo dispositivo.
- A Forca recebe tema, faixa etária e quantidade de palavras; a IA gera uma lista infantil e apropriada para revisão manual antes do salvamento.
- O psicólogo pode editar, excluir, salvar e reutilizar modelos.
- Ao iniciar uma sessão, o sistema gera um link temporário para o paciente.

## Regras do Jogo da Velha Mutante

- Tabuleiro 3×3, dois jogadores e objetivo de formar três símbolos em linha, coluna ou diagonal.
- Cada jogador mantém no máximo três peças ativas.
- Ao colocar a quarta peça, a peça mais antiga do mesmo jogador desaparece automaticamente.
- A peça que desaparecerá fica visualmente destacada para facilitar a compreensão da criança.
- O jogo termina assim que houver uma linha; o resultado é vitória, derrota ou empate por encerramento.
- O player exibe uma explicação breve antes da primeira jogada e anima a remoção da peça antiga.

## Regras da Forca

- A sessão usa uma palavra por vez, com teclado visual e letras já tentadas destacadas.
- A palavra mostra espaços, acentos tratados de forma amigável e feedback de acerto/erro.
- A lista gerada pela IA é validada no servidor e permanece editável antes de salvar.
- A IA recebe somente tema, faixa etária e quantidade; não recebe nome, prontuário ou dados do paciente.
- A geração não faz diagnóstico, não usa nomes reais e evita palavras impróprias ou conteúdo sensível.
- A sessão registra palavras concluídas, acertos, erros e status final.

## Arquitetura

- Criar módulos backend separados para modelos e sessões de cada jogo, mantendo escopo pelo psicólogo autenticado.
- Reutilizar o padrão de tokens temporários já usado pelos jogos existentes, armazenando apenas hash do token.
- Criar rotas autenticadas para listar, criar, editar, excluir e iniciar sessões.
- Criar rotas públicas limitadas para carregar o jogo e enviar o resultado.
- Adicionar um método específico ao serviço de IA para geração e validação das palavras da Forca.
- Não misturar partidas recreativas com respostas clínicas ou prontuário; eventual vínculo ao paciente será apenas metadado da sessão.

## Interface

- Manter a direção visual orgânica e acolhedora do GameKit, com cards grandes, tipografia legível e áreas de toque confortáveis.
- Jogo da Velha: tabuleiro central, símbolos grandes, destaque da peça mais antiga e feedback curto.
- Forca: palavra central, teclado responsivo, contador de tentativas e feedback visual não assustador.
- Os players devem funcionar em celular, tablet e computador.

## Validação e segurança

- Validar no servidor todas as palavras, movimentos, índices de casas e resultados recebidos.
- Impedir que um link expirado ou revogado seja usado.
- Limitar tentativas públicas por sessão e evitar exposição de dados do psicólogo ou paciente.
- Validar PHP, build Vue, rotas e testes disponíveis.

