<?php

namespace App\Services;

use App\Models\GameKitSession;
use Illuminate\Support\Str;

class GameKitService
{
    public function templates(): array
    {
        return [
            ['id' => 'emotion_situation', 'label' => 'Emoção ↔ situação'],
            ['id' => 'thought_consequence', 'label' => 'Pensamento ↔ consequência'],
            ['id' => 'social_response', 'label' => 'Problema social ↔ resposta'],
        ];
    }

    public function generateCards(string $theme): array
    {
        $cards = [
            ['pair_key' => 'social_approach', 'prompt_a' => 'Durante uma conversa em grupo, uma pessoa parece desconfortável e fala pouco.', 'prompt_b' => 'Como você poderia incluí-la na conversa?', 'options' => ['Faria uma pergunta aberta e daria espaço para ela responder.', 'Mudaria de assunto sem perceber como ela está.', 'Convidaria a pessoa a participar, sem pressioná-la.']],
            ['pair_key' => 'new_friend', 'prompt_a' => 'Uma pessoa nova chegou à turma e parece tímida no intervalo.', 'prompt_b' => 'Qual atitude poderia ajudá-la a se sentir acolhida?', 'options' => ['A convidaria para se juntar ao meu grupo.', 'Esperaria que ela se aproximasse sozinha.', 'Me apresentaria e perguntaria sobre seus interesses.']],
            ['pair_key' => 'interruption', 'prompt_a' => 'Durante um trabalho em grupo, um colega é interrompido várias vezes.', 'prompt_b' => 'O que você poderia fazer nesse momento?', 'options' => ['Pediria que o grupo deixasse o colega concluir.', 'Ficaria em silêncio para evitar conflito.', 'Retomaria a ideia do colega e perguntaria se ele quer continuar.']],
            ['pair_key' => 'mistake_support', 'prompt_a' => 'Em uma apresentação, um colega esquece uma parte importante e fica nervoso.', 'prompt_b' => 'Como você poderia oferecer apoio?', 'options' => ['Daria uma pista discreta para ajudá-lo a retomar.', 'Riria para aliviar meu próprio desconforto.', 'Esperaria e ofereceria ajuda depois da apresentação.']],
            ['pair_key' => 'friend_distance', 'prompt_a' => 'Um amigo se afasta e parece chateado, mas não explica o motivo.', 'prompt_b' => 'Como você poderia descobrir o que está acontecendo?', 'options' => ['Perguntaria com cuidado se ele gostaria de conversar.', 'Respeitaria o espaço e mostraria que estou disponível.', 'Concluiria que ele não quer mais minha amizade.']],
            ['pair_key' => 'new_connection', 'prompt_a' => 'Em um evento, você percebe alguém sozinho e gostaria de iniciar uma conversa.', 'prompt_b' => 'Como poderia fazer essa aproximação?', 'options' => ['Me apresentaria e faria uma pergunta simples.', 'Esperaria um momento adequado antes de me aproximar.', 'Convidaria a pessoa para conversar com um grupo conhecido.']],
        ];

        return array_map(fn ($card, $index) => [...$card, 'position' => $index + 1], $cards, array_keys($cards));
    }

    public function createSession(array $data, int $psychologistId): GameKitSession
    {
        $session = GameKitSession::create([
            ...$data,
            'psychologist_id' => $psychologistId,
            'format' => 'association_memory',
            'status' => 'draft',
        ]);

        foreach ($this->generateCards($session->theme) as $card) {
            $session->cards()->create($card);
        }

        return $session->load('cards');
    }

    public function issuePublicToken(GameKitSession $session): string
    {
        $token = Str::random(64);
        $session->forceFill([
            'public_token_hash' => hash('sha256', $token),
            'public_token_expires_at' => now()->addHours(8),
            'status' => 'active',
            'started_at' => $session->started_at ?? now(),
        ])->save();

        return $token;
    }

    public function findByToken(string $token): ?GameKitSession
    {
        return GameKitSession::with('cards')
            ->where('public_token_hash', hash('sha256', $token))
            ->where('status', 'active')
            ->where('public_token_expires_at', '>', now())
            ->first();
    }

    public function finish(GameKitSession $session): void
    {
        $session->forceFill([
            'status' => 'finished',
            'finished_at' => now(),
            'public_token_hash' => null,
        ])->save();
    }
}
