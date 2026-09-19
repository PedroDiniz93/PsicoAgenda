# GameKit Psi — geração assistida por IA

## Objetivo

Adicionar geração assistida por OpenAI ao GameKit Psi. O psicólogo informa perfil estruturado, tema, tipo de atividade, dificuldade, duração e quantidade de cartas; a IA devolve cartas com três opções clinicamente possíveis, sem resposta correta marcada. O resultado é sempre revisado antes de ser salvo como modelo reutilizável.

## Escopo

- Geração de 6 a 12 cartas por solicitação.
- Três opções por carta.
- Formatos Associação e Memória e variações compatíveis com o catálogo.
- Edição e remoção antes da aprovação.
- Geração novamente de uma carta específica.
- Salvamento nomeado como modelo reutilizável.
- Duplicação de modelo para novas sessões.
- Histórico de versões sem alterar sessões já realizadas.

Ficam fora desta fase: geração automática durante a resposta do paciente, diagnóstico, recomendação clínica, correção de respostas, fine-tuning, uso de prontuário como contexto, geração de imagens e chamadas diretas do navegador à OpenAI.

## Dados permitidos

O prompt recebe somente faixa etária, tema, tipo de atividade, dificuldade, duração, quantidade de cartas e linguagem. Nunca recebe nome, identificadores, diagnóstico, prontuário, histórico, resposta anterior ou relato identificável do paciente.

## Contrato de saída

Cada carta deverá seguir este formato:

~~~
{
  "context": "Situação curta",
  "question": "O que você poderia pensar ou fazer?",
  "options": [
    "Opção possível 1",
    "Opção possível 2",
    "Opção possível 3"
  ]
}
~~~

O backend rejeitará quantidade diferente de três opções, strings vazias, campos acima do limite, conteúdo duplicado e payload fora do schema. Nenhuma opção terá campo de resposta correta.

## Fluxo

1. O psicólogo abre o GameKit e escolhe parâmetros.
2. O backend valida limites e monta instruções fixas.
3. O backend chama a Responses API com Structured Outputs e schema estrito. A documentação oficial recomenda saída estruturada para garantir aderência ao JSON Schema. [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs)
4. A requisição usará store: false, sem solicitar armazenamento posterior da resposta pela API. [Responses API](https://developers.openai.com/api/reference/cli/resources/responses/methods/create)
5. O backend valida a resposta, normaliza cartas e salva como rascunho.
6. O psicólogo edita, remove, reordena e aprova.
7. O modelo aprovado recebe nome e pode ser reutilizado ou duplicado.

## Modelo de dados

### GameKitTemplate

Pertence ao psicólogo e contém nome, formato, parâmetros de geração, status (draft ou approved), versão atual e timestamps.

### GameKitTemplateCard

Pertence ao template e contém posição, contexto, pergunta e exatamente três opções.

### GameKitTemplateVersion

Registra uma cópia imutável das cartas aprovadas, parâmetros usados e data de aprovação. Sessões já iniciadas apontam para uma versão e não mudam quando o modelo é editado.

## API

~~~
POST   /api/gamekit/ai/generate
POST   /api/gamekit/templates
GET    /api/gamekit/templates
GET    /api/gamekit/templates/{id}
PUT    /api/gamekit/templates/{id}
POST   /api/gamekit/templates/{id}/duplicate
DELETE /api/gamekit/templates/{id}
POST   /api/gamekit/templates/{id}/generate-card
~~~

Todas as rotas exigem autenticação, e cada recurso deve ser consultado pelo psicólogo dono. A chave fica apenas no backend em OPENAI_API_KEY; o modelo é configurável por OPENAI_GAMEKIT_MODEL.

## Segurança, custo e falhas

- Limitar cartas e tamanho dos parâmetros por solicitação.
- Limitar gerações por psicólogo em janela de tempo.
- Timeout curto e ausência de retry automático.
- Não registrar prompt, resposta ou conteúdo clínico em logs.
- Registrar apenas status técnico, duração e contagem.
- Em falha, manter o jogo anterior intacto e exibir erro neutro.
- Exigir revisão humana antes de aprovar ou reutilizar.
- Sanitizar conteúdo editado antes de renderizar.

## Critérios de aceite

- Uma geração retorna de 6 a 12 cartas válidas, cada uma com três opções.
- O psicólogo consegue editar, remover e aprovar antes de salvar.
- Um modelo aprovado pode ser reutilizado e duplicado.
- Sessões antigas permanecem imutáveis.
- Nenhum dado identificável chega à IA.
- Falhas da API não criam modelos incompletos.
- A chave não aparece no frontend, payload de resposta ou logs.

## Validação

- Testes do schema e normalização de três opções.
- Testes de autorização e isolamento entre psicólogos.
- Testes de limite de cartas, rate limit, timeout e resposta inválida.
- Testes de aprovação, duplicação e imutabilidade de versões.
- npm run build, php -l, php artisan test, php artisan route:list e git diff --check.
