<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GameKitAiService
{
    public function generate(array $params, bool $single = false): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('A geração por IA ainda não está configurada no servidor.');
        }

        $count = $single ? 1 : max(6, min(12, (int) ($params['card_count'] ?? 6)));
        $input = [
            'faixa_etaria' => (string) ($params['age_group'] ?? ''),
            'tema' => (string) ($params['theme'] ?? ''),
            'tipo_de_atividade' => (string) ($params['activity_type'] ?? 'associação e memória'),
            'dificuldade' => (string) ($params['difficulty'] ?? ''),
            'duracao_minutos' => (int) ($params['duration_minutes'] ?? 45),
            'quantidade_de_cartas' => $count,
            'idioma' => (string) ($params['language'] ?? 'pt-BR'),
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(25)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.gamekit_model', 'gpt-4o-mini'),
                'store' => false,
                'instructions' => 'Você cria material terapêutico educativo para uso de psicólogos. Gere situações neutras, acolhedoras e adequadas à faixa etária. Cada carta deve ter contexto, pergunta e exatamente três opções plausíveis. Não existe resposta certa ou errada. Não faça diagnóstico, não use nomes reais, não mencione prontuário e não peça dados pessoais. Responda somente no JSON solicitado.',
                'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'gamekit_cards',
                        'strict' => true,
                        'schema' => $this->schema($count),
                    ],
                ],
            ]);

        if ($response->failed()) {
            if ($response->status() === 401) {
                throw new RuntimeException('A chave da OpenAI é inválida ou expirou.');
            }
            if ($response->status() === 429) {
                throw new RuntimeException('A conta da OpenAI está sem créditos ou atingiu o limite. Verifique o faturamento da API.');
            }
            if ($response->status() === 400) {
                throw new RuntimeException('A OpenAI recusou os parâmetros estruturados da geração.');
            }
            throw new RuntimeException('Não foi possível gerar as cartas agora.');
        }

        $text = $response->json('output_text');
        if (! is_string($text) || trim($text) === '') {
            $text = data_get($response->json(), 'output.0.content.0.text');
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('A resposta da IA veio vazia.');
        }

        try {
            $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('A resposta da IA não pôde ser validada.');
        }

        return $this->validateCards($payload['cards'] ?? [], $count);
    }

    public function generateMemory(array $params): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('A geração por IA ainda não está configurada no servidor.');
        }

        $count = max(6, min(12, (int) ($params['pair_count'] ?? 6)));
        $input = [
            'faixa_etaria' => (string) ($params['age_group'] ?? ''),
            'tema' => (string) ($params['theme'] ?? ''),
            'dificuldade' => (string) ($params['difficulty'] ?? ''),
            'quantidade_de_pares' => $count,
            'idioma' => 'pt-BR',
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.gamekit_model', 'gpt-4o-mini'),
                'store' => false,
                'instructions' => 'Você cria pares para um jogo terapêutico educativo usado por psicólogos. Gere associações acolhedoras e adequadas à faixa etária informada. Cada par deve relacionar duas cartas curtas, sem respostas certas ou erradas. Escolha um ícone da lista permitida que represente o par. Não faça diagnóstico, não use nomes reais, não peça dados pessoais e não use linguagem infantilizada para adolescentes ou adultos. O feedback deve convidar à reflexão, sem prescrever tratamento. Responda somente no JSON solicitado.',
                'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'gamekit_memory_pairs',
                        'strict' => true,
                        'schema' => $this->memorySchema($count),
                    ],
                ],
            ]);

        if ($response->failed()) {
            if ($response->status() === 401) {
                throw new RuntimeException('A chave da OpenAI é inválida ou expirou.');
            }
            if ($response->status() === 429) {
                throw new RuntimeException('A conta da OpenAI está sem créditos ou atingiu o limite. Verifique o faturamento da API.');
            }
            throw new RuntimeException('Não foi possível gerar os pares agora.');
        }

        $text = $response->json('output_text') ?: data_get($response->json(), 'output.0.content.0.text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('A resposta da IA veio vazia.');
        }

        try {
            $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('A resposta da IA não pôde ser validada.');
        }

        return $this->validateMemoryPairs($payload['pairs'] ?? [], $count);
    }

    public function generateRoutine(array $params): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('A geração por IA ainda não está configurada no servidor.');
        }

        $count = max(4, min(12, (int) ($params['block_count'] ?? 6)));
        $input = [
            'faixa_etaria' => (string) ($params['age_group'] ?? ''),
            'tema' => (string) ($params['theme'] ?? ''),
            'resumo' => (string) ($params['summary'] ?? ''),
            'quantidade_de_momentos' => $count,
            'idioma' => 'pt-BR',
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.gamekit_model', 'gpt-4o-mini'),
                'store' => false,
                'instructions' => 'Você cria uma rotina diária terapêutica educativa para ser construída por um psicólogo junto com seu paciente. Gere momentos realistas, acolhedores e flexíveis, equilibrando estudo ou trabalho, descanso, lazer e autocuidado. Respeite a faixa etária e o tema. Não faça diagnóstico, não prescreva tratamento, não use nomes reais ou dados pessoais. Use horários entre 06:00 e 23:00, sem sobreposição, e descrições curtas em português. Responda somente no JSON solicitado.',
                'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'gamekit_routine',
                        'strict' => true,
                        'schema' => $this->routineSchema($count),
                    ],
                ],
            ]);

        if ($response->failed()) {
            if ($response->status() === 401) {
                throw new RuntimeException('A chave da OpenAI é inválida ou expirou.');
            }
            if ($response->status() === 429) {
                throw new RuntimeException('A conta da OpenAI está sem créditos ou atingiu o limite. Verifique o faturamento da API.');
            }
            throw new RuntimeException('Não foi possível gerar a rotina agora.');
        }

        $text = $response->json('output_text') ?: data_get($response->json(), 'output.0.content.0.text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('A resposta da IA veio vazia.');
        }

        try {
            $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('A resposta da IA não pôde ser validada.');
        }

        return $this->validateRoutineBlocks($payload['blocks'] ?? [], $count);
    }

    public function generateHangmanWords(array $params): array
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('A geração por IA ainda não está configurada no servidor.');
        }
        $count = max(4, min(20, (int) ($params['word_count'] ?? 8)));
        $input = ['faixa_etaria' => (string) ($params['age_group'] ?? ''), 'tema' => (string) ($params['theme'] ?? ''), 'quantidade' => $count, 'idioma' => 'pt-BR'];
        $response = Http::withToken($apiKey)->acceptJson()->timeout(25)->post('https://api.openai.com/v1/responses', [
            'model' => config('services.openai.gamekit_model', 'gpt-4o-mini'), 'store' => false,
            'instructions' => 'Você cria palavras simples e divertidas para um jogo da forca infantil usado por psicólogos em contexto recreativo. Respeite a faixa etária e o tema. Use apenas palavras comuns em português, sem nomes reais, conteúdo sensível, violência ou diagnóstico. Não inclua frases, hífens ou números. Responda somente no JSON solicitado.',
            'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'gamekit_hangman_words', 'strict' => true, 'schema' => $this->hangmanSchema($count)]],
        ]);
        if ($response->failed()) {
            if ($response->status() === 401) throw new RuntimeException('A chave da OpenAI é inválida ou expirou.');
            if ($response->status() === 429) throw new RuntimeException('A conta da OpenAI está sem créditos ou atingiu o limite. Verifique o faturamento da API.');
            throw new RuntimeException('Não foi possível gerar as palavras agora.');
        }
        $text = $response->json('output_text') ?: data_get($response->json(), 'output.0.content.0.text');
        if (! is_string($text) || trim($text) === '') throw new RuntimeException('A resposta da IA veio vazia.');
        try { $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new RuntimeException('A resposta da IA não pôde ser validada.'); }
        return $this->validateHangmanWords($payload['words'] ?? [], $count);
    }

    private function memorySchema(int $count): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'pairs' => [
                    'type' => 'array', 'minItems' => $count, 'maxItems' => $count,
                    'items' => [
                        'type' => 'object', 'additionalProperties' => false,
                        'properties' => [
                            'label_a' => ['type' => 'string'],
                            'label_b' => ['type' => 'string'],
                            'concept' => ['type' => 'string'],
                            'feedback' => ['type' => 'string'],
                            'icon' => ['type' => 'string', 'enum' => ['Wind', 'HeartHandshake', 'PauseCircle', 'Shield', 'Sun', 'Sprout', 'MessageCircleHeart', 'HelpCircle', 'Compass', 'Sparkles', 'Cloud', 'CircleCheck']],
                        ],
                        'required' => ['label_a', 'label_b', 'concept', 'feedback', 'icon'],
                    ],
                ],
            ],
            'required' => ['pairs'],
        ];
    }

    private function routineSchema(int $count): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'blocks' => [
                    'type' => 'array', 'minItems' => $count, 'maxItems' => $count,
                    'items' => [
                        'type' => 'object', 'additionalProperties' => false,
                        'properties' => [
                            'start_time' => ['type' => 'string', 'pattern' => '^([0-1][0-9]|2[0-3]):[0-5][0-9]$'],
                            'duration_minutes' => ['type' => 'integer', 'minimum' => 5, 'maximum' => 240],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'category' => ['type' => 'string', 'enum' => ['study', 'rest', 'leisure', 'self_care']],
                            'icon' => ['type' => 'string', 'enum' => ['BookOpen', 'Moon', 'Sparkles', 'Heart', 'CircleCheck', 'Coffee', 'Dumbbell', 'Users']],
                        ],
                        'required' => ['start_time', 'duration_minutes', 'title', 'description', 'category', 'icon'],
                    ],
                ],
            ],
            'required' => ['blocks'],
        ];
    }

    private function hangmanSchema(int $count): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'properties' => ['words' => ['type' => 'array', 'minItems' => $count, 'maxItems' => $count, 'items' => ['type' => 'string']]], 'required' => ['words']];
    }

    private function validateHangmanWords(array $words, int $count): array
    {
        $clean = collect($words)->map(fn ($word) => is_string($word) ? trim($word) : '')->filter()->map(fn ($word) => preg_replace('/[^\p{L} ]/u', '', $word))->map(fn ($word) => mb_strtolower(trim($word)))->filter(fn ($word) => mb_strlen($word) >= 3 && mb_strlen($word) <= 24)->unique()->values()->all();
        if (count($clean) !== $count) throw new RuntimeException('A IA gerou uma quantidade inválida de palavras.');
        return $clean;
    }

    private function validateRoutineBlocks(array $blocks, int $count): array
    {
        if (count($blocks) !== $count) {
            throw new RuntimeException('A IA gerou uma quantidade inválida de momentos.');
        }

        $allowedCategories = ['study', 'rest', 'leisure', 'self_care'];
        $allowedIcons = ['BookOpen', 'Moon', 'Sparkles', 'Heart', 'CircleCheck', 'Coffee', 'Dumbbell', 'Users'];

        return collect($blocks)->map(function ($block) use ($allowedCategories, $allowedIcons) {
            if (! is_array($block) || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', (string) ($block['start_time'] ?? '')) || ! is_numeric($block['duration_minutes'] ?? null) || ! is_string($block['title'] ?? null) || trim($block['title']) === '' || ! is_string($block['description'] ?? null) || ! in_array($block['category'] ?? null, $allowedCategories, true)) {
                throw new RuntimeException('Um momento gerado não passou na validação.');
            }

            return [
                'start_time' => $block['start_time'],
                'duration_minutes' => max(5, min(240, (int) $block['duration_minutes'])),
                'title' => trim($block['title']),
                'description' => trim($block['description']),
                'category' => $block['category'],
                'icon' => in_array($block['icon'] ?? null, $allowedIcons, true) ? $block['icon'] : 'CircleCheck',
            ];
        })->sortBy('start_time')->values()->all();
    }

    private function validateMemoryPairs(array $pairs, int $count): array
    {
        if (count($pairs) !== $count) {
            throw new RuntimeException('A IA gerou uma quantidade inválida de pares.');
        }

        return collect($pairs)->map(function ($pair, $index) {
            foreach (['label_a', 'label_b', 'concept', 'feedback'] as $key) {
                if (! is_array($pair) || ! is_string($pair[$key] ?? null) || trim($pair[$key]) === '') {
                    throw new RuntimeException('Um par gerado não passou na validação.');
                }
            }
            return [
                'label_a' => trim($pair['label_a']),
                'label_b' => trim($pair['label_b']),
                'concept' => trim($pair['concept']),
                'feedback' => trim($pair['feedback']),
                'icon' => in_array($pair['icon'], ['Wind', 'HeartHandshake', 'PauseCircle', 'Shield', 'Sun', 'Sprout', 'MessageCircleHeart', 'HelpCircle', 'Compass', 'Sparkles', 'Cloud', 'CircleCheck'], true) ? $pair['icon'] : 'Sparkles',
                'accent' => ['sky', 'lavender', 'clay', 'sun', 'rose', 'sage', 'amber', 'blue', 'moss', 'violet', 'teal', 'gold'][$index % 12],
            ];
        })->values()->all();
    }

    private function schema(int $count): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'cards' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'context' => ['type' => 'string'],
                            'question' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'minItems' => 3,
                                'maxItems' => 3,
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['context', 'question', 'options'],
                    ],
                ],
            ],
            'required' => ['cards'],
        ];
    }

    private function validateCards(array $cards, int $count): array
    {
        if (count($cards) !== $count) {
            throw new RuntimeException('A IA gerou uma quantidade inválida de cartas.');
        }

        return collect($cards)->map(function ($card) {
            if (! is_array($card) || ! is_string($card['context'] ?? null) || ! is_string($card['question'] ?? null) || ! is_array($card['options'] ?? null) || count($card['options']) !== 3) {
                throw new RuntimeException('Uma carta gerada não passou na validação.');
            }
            $options = array_values(array_filter(array_map('trim', $card['options']), fn ($value) => is_string($value) && $value !== ''));
            if (count($options) !== 3 || count(array_unique($options)) !== 3) {
                throw new RuntimeException('As opções de uma carta precisam ser três alternativas distintas.');
            }
            return [
                'context' => trim($card['context']),
                'question' => trim($card['question']),
                'options' => $options,
            ];
        })->values()->all();
    }
}
