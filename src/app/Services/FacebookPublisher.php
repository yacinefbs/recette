<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FacebookPublisher
{
    public function isConfigured(): bool
    {
        return filled(config('services.facebook.page_id')) && filled(config('services.facebook.page_token'));
    }

    public function message(Recipe $recipe): string
    {
        $text = trim(strip_tags((string) $recipe->description));
        $text = $text !== '' ? Str::limit($text, 220) : 'A recipe you are going to love.';

        return $recipe->name."\n\n".$text;
    }

    /** Publishes a link post (Facebook builds the preview from the page's Open Graph tags). */
    public function publish(Recipe $recipe, ?string $message = null): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Facebook is not configured (FACEBOOK_PAGE_ID / FACEBOOK_PAGE_TOKEN).');
        }

        $version = config('services.facebook.graph_version');
        $response = Http::asForm()->timeout(20)->post(
            "https://graph.facebook.com/{$version}/".config('services.facebook.page_id').'/feed',
            [
                'message' => $message ?: $this->message($recipe),
                'link' => route('recipes.show', $recipe),
                'access_token' => config('services.facebook.page_token'),
            ]
        );

        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException($response->json('error.message') ?? 'Facebook returned an unexpected response.');
        }

        $recipe->update([
            'posted_facebook' => true,
            'facebook_posted_at' => now(),
            'facebook_post_id' => $response->json('id'),
        ]);

        return $response->json('id');
    }
}
