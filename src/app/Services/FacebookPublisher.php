<?php

namespace App\Services;

use App\Models\Recipe;
use Carbon\CarbonInterface;
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
        $id = $this->send($recipe, $message);

        $recipe->update([
            'posted_facebook' => true,
            'facebook_posted_at' => now(),
            'facebook_post_id' => $id,
        ]);

        return $id;
    }

    public function schedule(Recipe $recipe, CarbonInterface $when, ?string $message = null): string
    {
        $id = $this->send($recipe, $message, $when);
        $recipe->forceFill([
            'facebook_scheduled_at' => $when->copy()->utc(),
            'facebook_post_id' => $id,
        ])->save();

        return $id;
    }

    public function isPublished(Recipe $recipe): bool
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Facebook is not configured.');
        }

        $response = Http::withToken(config('services.facebook.page_token'))->timeout(20)->get(
            'https://graph.facebook.com/'.config('services.facebook.graph_version').'/'.rawurlencode($recipe->facebook_post_id),
            ['fields' => 'is_published']
        );

        if ($response->failed() || ! is_bool($response->json('is_published'))) {
            throw new RuntimeException($response->json('error.message') ?? 'Facebook did not return the publication status.');
        }

        return $response->json('is_published');
    }

    private function send(Recipe $recipe, ?string $message, ?CarbonInterface $when = null): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Facebook is not configured (FACEBOOK_PAGE_ID / FACEBOOK_PAGE_TOKEN).');
        }

        $version = config('services.facebook.graph_version');
        $payload = [
            'message' => $message ?: $this->message($recipe),
            'link' => route('recipes.show', $recipe),
            'access_token' => config('services.facebook.page_token'),
        ];
        if ($when) {
            $payload['published'] = 'false';
            $payload['scheduled_publish_time'] = $when->getTimestamp();
        }
        $response = Http::asForm()->timeout(20)->post(
            "https://graph.facebook.com/{$version}/".config('services.facebook.page_id').'/feed',
            $payload
        );

        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException($response->json('error.message') ?? 'Facebook returned an unexpected response.');
        }

        return $response->json('id');
    }
}
