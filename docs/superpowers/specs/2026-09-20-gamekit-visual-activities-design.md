# GameKit — Atividades visuais geradas por IA

## Objetivo

Permitir que o psicólogo crie materiais visuais infantis para colorir, recortar ou fazer as duas atividades. A imagem será gerada pela IA, revisada pelo psicólogo e poderá ser baixada, impressa ou enviada para um GameKit.

O objetivo terapêutico orienta a geração, mas nunca é exibido para a criança. A primeira versão será um fluxo para o psicólogo; a criança apenas acessará a atividade pronta.

## Escopo do MVP

Incluído:

- criar atividade visual a partir de tema e objetivo terapêutico;
- escolher tipo: `coloring`, `cutting` ou `coloring_cutting`;
- escolher estilo: `simple`, `intermediate` ou `detailed`;
- gerar imagem em preto e branco adequada para impressão;
- visualizar prévia antes de usar;
- gerar nova versão;
- baixar ou imprimir;
- enviar a atividade para um GameKit;
- listar e arquivar materiais gerados;
- limite de cinco gerações concluídas por psicólogo a cada semana;
- mensagens claras de cota disponível, cota esgotada e falha de geração.

Fora do MVP:

- editor de desenho dentro do navegador;
- geração direta pela criança;
- publicação em biblioteca compartilhada entre psicólogos;
- edição pixel a pixel;
- geração de personagens licenciados;
- impressão automática ou integração com impressora.

## Experiência do psicólogo

Na área GameKit haverá a seção **Atividades visuais**, com:

- botão **Criar atividade visual**;
- indicador `X de 5 imagens usadas nesta semana`;
- histórico com imagem, tema, tipo, data e status;
- filtros por tipo e status;
- ação de arquivar sem apagar o arquivo imediatamente.

### Criação

O formulário contém:

1. tipo de atividade;
2. tema livre;
3. objetivo terapêutico obrigatório, usado apenas para orientar a geração e mantido fora da experiência da criança;
4. estilo visual;
5. botão de geração com o custo do crédito visível.

O tema deve ser validado por tamanho e conteúdo. O objetivo terapêutico não pode aceitar dados identificáveis da criança, prontuário ou diagnóstico detalhado como requisito para a geração.

### Revisão

Após a geração, o psicólogo vê:

- imagem em tamanho amplo;
- descrição técnica curta do resultado;
- aviso de que a imagem deve ser revisada antes de ser entregue;
- **Gerar outra versão**;
- **Baixar / imprimir**;
- **Enviar ao GameKit**;
- **Arquivar**.

Regenerar consome uma nova geração somente quando a nova imagem for concluída com sucesso. Falhas técnicas não consomem crédito.

## Requisitos de imagem

O prompt interno deve exigir:

- ilustração infantil, amigável e não realista;
- composição simples e legível;
- contornos pretos espessos e fechados;
- fundo branco;
- ausência de texto, logotipos e marcas;
- ausência de violência, armas, terror, sexualização, sofrimento explícito ou conteúdo discriminatório;
- nenhum dado pessoal ou clínico visível;
- para `coloring`: áreas amplas e separadas para pintura;
- para `cutting`: poucas peças, linhas externas claras e instruções separadas da arte;
- para `coloring_cutting`: contornos de pintura e linhas de recorte visualmente distintos.

O psicólogo sempre será o aprovador final. A aplicação não deve apresentar a imagem como clinicamente validada pela IA.

## Arquitetura proposta

### Backend

Criar uma entidade própria `GameKitVisualActivity` vinculada ao psicólogo e, opcionalmente, ao paciente ou à sessão GameKit.

Campos mínimos:

- `id`;
- `psychologist_id`;
- `patient_id` nullable;
- `gamekit_session_id` nullable;
- `type`;
- `style`;
- `theme`;
- `therapeutic_goal`;
- `prompt_version`;
- `provider`;
- `model`;
- `storage_path`;
- `mime_type`;
- `width` e `height`;
- `status` (`generating`, `ready`, `failed`, `archived`);
- `failure_reason` nullable;
- `created_at`, `updated_at`.

Não armazenar dados clínicos adicionais no prompt ou nos metadados da imagem. O objetivo terapêutico deve ter retenção limitada e acesso somente do psicólogo proprietário.

### API autenticada

Sugestão de endpoints:

- `GET /api/gamekit/visual-activities` — lista paginada do proprietário;
- `POST /api/gamekit/visual-activities/generate` — valida cota e inicia geração;
- `GET /api/gamekit/visual-activities/{id}` — prévia e metadados;
- `POST /api/gamekit/visual-activities/{id}/regenerate` — gera nova versão;
- `POST /api/gamekit/visual-activities/{id}/download` — retorna URL temporária;
- `POST /api/gamekit/visual-activities/{id}/attach` — vincula ao GameKit;
- `PATCH /api/gamekit/visual-activities/{id}/archive` — arquiva.

Todas as consultas devem filtrar por `psychologist_id` no servidor. O frontend nunca define a propriedade do registro.

### Cota semanal

Criar um registro de uso ou uma contagem transacional por psicólogo e semana ISO. A reserva do crédito deve ocorrer dentro de transação, com proteção contra requisições simultâneas. O crédito é confirmado apenas quando a geração for salva como `ready`.

O endpoint deve retornar:

```json
{
  "used": 3,
  "limit": 5,
  "remaining": 2,
  "resets_at": "2026-09-21T00:00:00-03:00"
}
```

Aplicar rate limit adicional por usuário e endpoint, além do limite de negócio.

### Geração

Usar o serviço de IA já existente no GameKit, isolando a implementação em um método específico para imagens. O prompt deve ser montado no backend a partir de valores permitidos, sem aceitar instruções arbitrárias do cliente como prompt final.

Preferir processamento assíncrono se o provedor suportar fila/webhook. Enquanto isso, o frontend exibe estado de geração, impede envio duplicado e permite cancelar a navegação sem perder o status do registro.

Guardar somente a imagem final e metadados mínimos necessários. URLs de download devem ser temporárias e protegidas.

## Segurança e privacidade

- autenticação obrigatória para criar e revisar;
- autorização por proprietário em todos os endpoints;
- validação de enum, tamanho, MIME e extensão;
- limite de tema e objetivo para evitar abuso de tokens;
- sanitização de nomes e mensagens exibidas;
- filtro de conteúdo antes e depois da geração;
- não enviar nome, CPF, prontuário, diagnóstico ou texto de sessão para a IA;
- logs sem prompt clínico completo e sem URL permanente da imagem;
- política de retenção para imagens arquivadas;
- tratamento de falha sem expor detalhes do provedor ao usuário;
- botão e mensagem de revisão humana antes de compartilhar com a criança.

## Interface pública da criança

O link deve mostrar apenas:

- título amigável da atividade;
- imagem;
- instrução simples conforme o tipo;
- botão de baixar ou imprimir, se permitido pelo psicólogo;
- nenhum objetivo terapêutico, nome completo, e-mail ou dado clínico.

O token público deve seguir o mesmo padrão de expiração e hash já usado pelos outros módulos do GameKit.

## Acessibilidade e UX

- controles com labels visíveis;
- foco no primeiro campo ao abrir o formulário;
- estados de carregamento anunciados com `aria-live`;
- erro de geração explicado em linguagem simples;
- prévia com `alt` descritivo;
- contraste compatível com WCAG 2.2 AA;
- funcionamento completo por teclado;
- revisão em viewport equivalente a zoom de 200%;
- imagem não deve ser o único meio de entender a instrução: o texto da ação também deve existir.

## Testes de aceitação

### Backend

- psicólogo consegue gerar quando possui crédito;
- sexta geração na mesma semana é recusada;
- falha técnica não reduz a cota;
- duas requisições simultâneas não ultrapassam cinco créditos;
- psicólogo não acessa atividade de outro psicólogo;
- paciente opcional não é enviado à IA;
- prompt não permite conteúdo proibido ou texto arbitrário do cliente;
- URL de download expira e não permite acesso sem autorização;
- atividade pode ser anexada somente a GameKit pertencente ao psicólogo.

### Frontend/E2E

- formulário valida campos e mostra créditos restantes;
- botão de geração entra em estado de carregamento;
- prévia aparece após sucesso;
- regenerar atualiza a versão e a cota;
- baixar/imprimir funciona;
- enviar ao GameKit confirma o vínculo;
- cota esgotada bloqueia geração com orientação clara;
- modal e formulário funcionam por teclado;
- axe-core não encontra violações sérias ou críticas;
- layout permanece utilizável em 200% de zoom.

## Critérios de sucesso

- o psicólogo gera um material em poucos passos sem precisar escrever um prompt técnico;
- toda imagem é revisada antes de ser compartilhada;
- nenhuma informação clínica aparece para a criança;
- o limite semanal é confiável mesmo com cliques repetidos;
- a atividade pronta pode ser impressa ou conectada ao GameKit sem duplicar cadastro.
