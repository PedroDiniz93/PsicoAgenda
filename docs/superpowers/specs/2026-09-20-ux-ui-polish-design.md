# Polimento UX/UI das telas principais

## Objetivo

Melhorar a clareza dos estados vazios, a localização em português e a experiência responsiva das telas principais do PsicoAgenda, preservando a identidade visual clínica e acolhedora existente.

## Direção visual

Manter a direção clínica orgânica já presente: tipografia serifada para títulos, azul terapêutico para ações primárias, verde para estados positivos, cartões arredondados e superfícies claras. O trabalho será de polimento, não de redesign completo.

## Escopo

- Localizar datas e meses exibidos na agenda e no financeiro.
- Diferenciar estados de carregamento, vazio e erro no dashboard.
- Compactar a agenda semanal quando não houver atendimentos.
- Renomear “Próximos Pacientes” para “Próximas sessões”.
- Ajustar o cartão de próximas sessões no mobile para evitar quebras ruins.
- Remover a duplicidade visual do avatar no cabeçalho.
- Corrigir o texto da busca de pacientes para refletir os campos aceitos.
- Ocultar ou simplificar paginação quando não houver resultados.
- Preservar textos reais do produto; não inserir dados fictícios.

## Restrições

- Não alterar regras de negócio nem endpoints.
- Não mudar a estrutura de dados da API.
- Não substituir a identidade visual por outro tema.
- Manter navegação, ações e permissões atuais.
- Garantir que os estados vazio e de erro continuem acionáveis.

## Componentes afetados

1. Dashboard e componentes de resumo semanal.
2. Cabeçalho global.
3. Tela de agenda semanal.
4. Tela de pacientes e seus filtros/paginação.
5. Tela financeira e seletor de período.
6. Utilitários de formatação de datas, se já existentes.

## Estados e acessibilidade

- Loading usará indicador visual próprio, sem parecer uma coluna de dados zerada.
- Empty state explicará a situação e oferecerá uma ação relevante.
- Erros manterão mensagem legível e caminho de recuperação.
- Botões e controles manterão labels acessíveis e alvos de toque adequados.
- A validação visual será complementada por build e testes existentes; teclado e leitor de tela serão registrados como limitações se não puderem ser automatizados nesta etapa.

## Validação

- `npm run build`.
- Testes Laravel existentes.
- Capturas desktop e mobile das telas alteradas.
- Nova inspeção visual dos estados vazios e da localização.

