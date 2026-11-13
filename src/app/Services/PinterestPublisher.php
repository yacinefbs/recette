<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PinterestPublisher
{
    public function isConfigured(): bool
    {
        return filled(config('services.pinterest.access_token'));
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function boards(): array
    {
        $this->ensureConfigured();

        $boards = [];
        $bookmark = null;
        do {
            $query = $bookmark ? ['bookmark' => $bookmark] : [];
            $response = Http::withToken(config('services.pinterest.access_token'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get('https://api.pinterest.com/v5/boards', $query);

            if ($response->failed() || ! is_array($response->json('items'))) {
                throw new RuntimeException($response->json('message') ?? 'Pinterest did not return the board list.');
            }

            foreach ($response->json('items') as $board) {
                if (isset($board['id'], $board['name'])) {
                    $boards[] = ['id' => (string) $board['id'], 'name' => (string) $board['name']];
                }
            }

            $bookmark = $response->json('bookmark');
        } while ($bookmark);

        return $boards;
    }

    public function publish(Recipe $recipe, string $boardId): string
    {
        $this->ensureConfigured();

        if ($boardId === '') {
            throw new RuntimeException('Choose a Pinterest board before publishing.');
        }

        $description = trim(strip_tags((string) $recipe->description));
        $response = Http::withToken(config('services.pinterest.access_token'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->post('https://api.pinterest.com/v5/pins', [
                'board_id' => $boardId,
                'title' => Str::limit($recipe->name, 100, ''),
                'description' => Str::limit($description, 500, ''),
                'link' => route('recipes.show', $recipe),
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $recipe->image,
                ],
            ]);

        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException($response->json('message') ?? 'Pinterest returned an unexpected response.');
        }

        $id = (string) $response->json('id');
        $recipe->forceFill([
            'posted_pinterest' => true,
            'pinterest_posted_at' => now(),
            'pinterest_pin_id' => $id,
        ])->save();

        return $id;
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Pinterest is not configured (PINTEREST_ACCESS_TOKEN).');
        }
    }
}
