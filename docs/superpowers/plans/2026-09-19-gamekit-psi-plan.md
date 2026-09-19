# Plano de implementação — GameKit Psi

## Frente 1 — Banco e domínio

1. Criar migrations para sessões, cartas e respostas, com índices por psicólogo, status e expiração.
2. Criar modelos Eloquent, casts, relações e enumerações/constantes de status.
3. Implementar serviço de templates determinísticos para Associação e Memória.
4. Criar serviço de token público com hash armazenado, expiração e invalidação.

## Frente 2 — Backend autenticado

1. Criar Form Requests para criação, edição, início, revisão e finalização.
2. Criar controller autenticado com consultas sempre escopadas ao psicólogo.
3. Criar endpoints de templates, sessões, início, geração de link, finalização e revisão.
4. Implementar envio seletivo de resposta ao prontuário sem gravação automática.
5. Adicionar rate limit e respostas genéricas na API pública.

## Frente 3 — Backend público

1. Criar controller para carregar sessão por token sem expor paciente ou psicólogo.
2. Validar expiração, status, carta atual e respostas permitidas.
3. Registrar respostas idempotentes e finalizar sessão.
4. Não registrar payload clínico em logs ou URLs.

## Frente 4 — Frontend Vue

1. Adicionar rotas autenticada e pública.
2. Criar GameKitView para configuração e revisão.
3. Criar GameKitPlayerView para uma carta por vez e progresso.
4. Criar componentes de carta, seleção, geração de link, acompanhamento e revisão.
5. Adicionar estados de carregamento, expiração, link copiado, encerramento e erro.
6. Seguir os tokens visuais clínico-serenos e responsividade tablet/mobile.

## Frente 5 — Segurança e QA

1. Testar isolamento entre psicólogos.
2. Testar token inválido, expirado, encerrado e rate limit.
3. Testar payloads inválidos e respostas duplicadas.
4. Testar seleção explícita antes de alterar prontuário.
5. Validar build, lint PHP, rotas, testes e diff.

## Ordem de entrega

Banco/domínio → serviços de template/token → APIs autenticadas → API pública → frontend → testes e documentação.

## Risco controlado

O MVP não usa IA nem conteúdo livre sem revisão. O link público não expõe identidade e o prontuário só é alterado por uma ação confirmada do psicólogo.
