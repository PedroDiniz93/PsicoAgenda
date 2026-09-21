<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const isPrivacy = computed(() => route.name === 'privacy-policy');
const pageTitle = computed(() => (isPrivacy.value ? 'Política de Privacidade' : 'Termos de Serviço'));
const pageIntro = computed(() => (isPrivacy.value
    ? 'Como o PsicoAgenda trata dados pessoais, clínicos e de agenda com responsabilidade e transparência.'
    : 'Condições para uso do PsicoAgenda por profissionais de psicologia e suas equipes autorizadas.'));
</script>

<template>
    <main class="min-h-screen bg-[var(--spa-bg)] px-4 py-6 text-[var(--spa-ink)] sm:px-6 sm:py-10">
        <div class="mx-auto max-w-6xl">
            <header class="flex flex-col gap-5 border-b border-[var(--spa-border-soft)] pb-7 sm:flex-row sm:items-center sm:justify-between">
                <router-link to="/login" class="inline-flex items-center gap-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-[var(--spa-focus)]">
                    <img :src="'/images/psicoagenda-icon.png'" alt="" class="size-11 object-contain" />
                    <span class="font-display text-2xl font-semibold text-[var(--spa-accent)]">PsicoAgenda</span>
                </router-link>
                <nav class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold text-[var(--spa-accent)]" aria-label="Links institucionais">
                    <router-link to="/politica-de-privacidade" :class="isPrivacy ? 'underline underline-offset-4' : 'hover:underline hover:underline-offset-4'">Política de Privacidade</router-link>
                    <router-link to="/termos-de-servico" :class="!isPrivacy ? 'underline underline-offset-4' : 'hover:underline hover:underline-offset-4'">Termos de Serviço</router-link>
                </nav>
            </header>

            <div class="grid gap-10 py-10 lg:grid-cols-[220px_minmax(0,720px)] lg:gap-16">
                <aside class="hidden lg:block">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--spa-ink-muted)]">Documento</p>
                    <p class="mt-3 font-display text-xl font-semibold text-[var(--spa-ink)]">{{ pageTitle }}</p>
                    <p class="mt-3 text-sm leading-6 text-[var(--spa-ink-muted)]">Última atualização: 21 de setembro de 2026</p>
                </aside>

                <article class="min-w-0 rounded-2xl border border-[var(--spa-border-soft)] bg-[var(--spa-surface)] px-5 py-7 shadow-[var(--spa-shadow-sm)] sm:px-9 sm:py-10">
                    <header class="border-b border-[var(--spa-border-soft)] pb-8">
                        <p class="text-sm font-semibold text-[var(--spa-secondary)]">PsicoAgenda / PsicoControl</p>
                        <h1 class="mt-3 font-display text-4xl font-semibold leading-tight text-[var(--spa-ink)] sm:text-5xl">{{ pageTitle }}</h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-[var(--spa-ink-soft)]">{{ pageIntro }}</p>
                        <p class="mt-5 text-sm leading-6 text-[var(--spa-ink-muted)]">Em caso de dúvida ou solicitação relacionada a este documento, escreva para <a class="font-semibold text-[var(--spa-accent)] underline underline-offset-2" href="mailto:pedro_diniz_l2@hotmail.com">pedro_diniz_l2@hotmail.com</a>.</p>
                    </header>

                    <div v-if="isPrivacy" class="legal-content">
                        <section><h2>1. Quem somos</h2><p>O PsicoAgenda é uma aplicação de gestão para profissionais de psicologia, voltada à organização de agenda, pacientes, prontuários, finanças, comunicação e atividades de apoio. Para assuntos sobre privacidade, o canal de contato é <a href="mailto:pedro_diniz_l2@hotmail.com">pedro_diniz_l2@hotmail.com</a>.</p></section>
                        <section><h2>2. Dados tratados</h2><p>Dependendo dos recursos utilizados, o serviço pode tratar:</p><ul><li>dados de cadastro e autenticação do profissional, como nome, e-mail e credenciais protegidas;</li><li>dados profissionais e de configuração da conta;</li><li>dados de pacientes inseridos pelo profissional, incluindo identificação, contato, informações administrativas e observações;</li><li>dados de agenda, sessões, cobrança, lembretes e atendimento online;</li><li>registros clínicos e respostas de atividades, quando o profissional escolher usar esses recursos;</li><li>dados técnicos necessários para segurança, funcionamento, prevenção de abuso e diagnóstico de falhas.</li></ul><p>O profissional deve inserir apenas os dados necessários para a finalidade escolhida e possui responsabilidade pelas informações que registra no serviço.</p></section>
                        <section><h2>3. Para que usamos os dados</h2><p>Os dados são tratados para fornecer os recursos solicitados, manter a conta autenticada, organizar agenda e prontuários, emitir relatórios e exportações, enviar lembretes configurados, proteger o serviço, atender solicitações e cumprir obrigações legais.</p><p>Não usamos dados clínicos para publicidade comportamental. Não vendemos dados pessoais.</p></section>
                        <section><h2>4. Integração com o Google Calendar</h2><p>Quando o profissional escolhe conectar o Google Calendar, o PsicoAgenda solicita autorização para criar, consultar e remover eventos relacionados à agenda, conforme os recursos usados no aplicativo.</p><p>O token de acesso é armazenado de forma protegida no servidor e não é exibido na interface, em logs ou para outros usuários. Eventos externos são apresentados de forma limitada na agenda, sem expor descrições, convidados, locais ou outros metadados desnecessários.</p><p>A conexão pode ser desfeita nas configurações do PsicoAgenda. A autorização também pode ser removida nas configurações da Conta Google.</p></section>
                        <section><h2>5. Compartilhamento e operadores</h2><p>Podemos utilizar provedores de infraestrutura, armazenamento, e-mail, comunicação, vídeo e processamento técnico para operar o serviço. Esses fornecedores recebem somente o acesso necessário à execução da finalidade e devem adotar medidas de proteção compatíveis.</p><p>Quando o profissional ativa uma integração de terceiros, os dados necessários podem ser enviados ao respectivo provedor, de acordo com a autorização concedida e os termos desse provedor.</p></section>
                        <section><h2>6. Segurança e retenção</h2><p>Adotamos controles técnicos e organizacionais razoáveis, incluindo autenticação, restrição de acesso, proteção de credenciais e criptografia de informações sensíveis armazenadas.</p><p>Os dados permanecem enquanto a conta ou a finalidade existir, ou pelo período necessário para cumprir obrigações legais, resolver disputas e manter registros de segurança. O profissional pode solicitar a exclusão da conta e dos dados, respeitadas as hipóteses legais de conservação.</p></section>
                        <section><h2>7. Direitos do titular</h2><p>O titular pode solicitar confirmação do tratamento, acesso, correção, anonimização, bloqueio, eliminação quando aplicável, informação sobre compartilhamentos e revisão de situações previstas na legislação. As solicitações devem ser enviadas para <a href="mailto:pedro_diniz_l2@hotmail.com">pedro_diniz_l2@hotmail.com</a>, com informações suficientes para localizar o pedido com segurança.</p></section>
                        <section><h2>8. Alterações desta política</h2><p>Esta política pode ser atualizada para refletir mudanças no serviço, na legislação ou nas práticas de tratamento. A versão vigente estará sempre disponível nesta página, com a data da última atualização.</p></section>
                    </div>

                    <div v-else class="legal-content">
                        <section><h2>1. O serviço</h2><p>O PsicoAgenda é uma ferramenta de apoio à gestão de consultórios e práticas de psicologia. Ele organiza informações fornecidas pelo usuário, mas não substitui o julgamento profissional, as obrigações éticas ou os sistemas oficiais exigidos pela legislação.</p></section>
                        <section><h2>2. Conta e responsabilidade do usuário</h2><p>O usuário deve fornecer informações verdadeiras, manter suas credenciais em sigilo e impedir o acesso de pessoas não autorizadas. Cada conta deve ser utilizada pelo profissional ou pela equipe autorizada dentro dos limites contratados.</p><p>O usuário é responsável por verificar os dados cadastrados, permissões concedidas, exportações realizadas e comunicações enviadas a pacientes.</p></section>
                        <section><h2>3. Dados de pacientes e terceiros</h2><p>O usuário declara possuir base legal e autorização necessária para inserir e utilizar dados de pacientes, responsáveis e demais terceiros no serviço. O usuário deve respeitar o sigilo profissional, a LGPD, as normas do Conselho Federal de Psicologia e as demais regras aplicáveis à sua atividade.</p></section>
                        <section><h2>4. Integrações</h2><p>Recursos como Google Calendar, envio de mensagens, e-mail, vídeo e ferramentas de inteligência artificial dependem de serviços de terceiros. O uso dessas integrações está sujeito às autorizações, limites, disponibilidade e termos dos respectivos provedores.</p><p>O usuário deve revisar as permissões antes de ativar uma integração e pode desconectá-la quando desejar, quando o recurso permitir.</p></section>
                        <section><h2>5. Uso aceitável</h2><p>É proibido usar o serviço para acessar dados de terceiros sem autorização, violar direitos, praticar fraude, distribuir código malicioso, tentar contornar controles de segurança ou utilizar a plataforma para finalidade ilegal.</p></section>
                        <section><h2>6. Disponibilidade e alterações</h2><p>Podemos atualizar, suspender ou descontinuar recursos para manutenção, segurança, evolução do produto ou cumprimento de obrigações. Buscaremos preservar os dados e comunicar mudanças relevantes quando isso for razoavelmente possível.</p><p>Nenhum serviço online é livre de interrupções. O usuário deve manter cópias e exportações adequadas aos seus deveres profissionais e legais.</p></section>
                        <section><h2>7. Conteúdo e propriedade</h2><p>O usuário mantém os direitos sobre os dados que inserir. O PsicoAgenda mantém os direitos sobre sua aplicação, marca, código, interface e materiais próprios. O usuário concede apenas a autorização necessária para hospedar, processar e exibir seus dados durante a prestação do serviço.</p></section>
                        <section><h2>8. Encerramento</h2><p>O usuário pode deixar de utilizar o serviço e solicitar o encerramento da conta pelo canal de contato. O acesso pode ser limitado ou encerrado em caso de violação destes termos, risco à segurança, obrigação legal ou uso abusivo.</p></section>
                        <section><h2>9. Contato</h2><p>Para dúvidas, solicitações ou comunicações sobre estes termos, escreva para <a href="mailto:pedro_diniz_l2@hotmail.com">pedro_diniz_l2@hotmail.com</a>.</p></section>
                    </div>

                    <footer class="mt-10 border-t border-[var(--spa-border-soft)] pt-6 text-sm leading-6 text-[var(--spa-ink-muted)]">Este documento é uma versão informativa do serviço e deve ser revisado pelo responsável jurídico antes da publicação definitiva.</footer>
                </article>
            </div>
        </div>
    </main>
</template>

<style scoped>
.legal-content { color: var(--spa-ink-soft); font-size: 0.975rem; line-height: 1.8; }
.legal-content section { scroll-margin-top: 2rem; }
.legal-content section + section { margin-top: 2.25rem; }
.legal-content h2 { color: var(--spa-ink); font-family: 'Source Serif 4', Georgia, serif; font-size: 1.45rem; font-weight: 600; line-height: 1.35; margin-bottom: 0.7rem; }
.legal-content p + p { margin-top: 0.9rem; }
.legal-content ul { list-style: disc; margin: 0.8rem 0 0.9rem 1.25rem; }
.legal-content li + li { margin-top: 0.4rem; }
.legal-content a { color: var(--spa-accent); font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
</style>
