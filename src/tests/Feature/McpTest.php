<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_card_is_published(): void
    {
        $this->get('/.well-known/mcp/server-card.json')->assertOk()
            ->assertJsonPath('serverInfo.name', 'topictrail-recipes')
            ->assertJsonPath('transport.endpoint', url('/mcp'));
    }

    public function test_initialize_list_and_search(): void
    {
        Recipe::create(['tasty_id' => 1, 'name' => 'Chicken Pie']);
        Recipe::create(['tasty_id' => 2, 'name' => 'Beef Stew']);

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []])
            ->assertOk()->assertJsonPath('result.serverInfo.name', 'topictrail-recipes');
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'])
            ->assertJsonPath('result.tools.0.name', 'search_recipes');

        $res = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
            'params' => ['name' => 'search_recipes', 'arguments' => ['query' => 'chicken']]]);
        $text = $res->json('result.content.0.text');
        $this->assertStringContainsString('Chicken Pie', $text);
        $this->assertStringNotContainsString('Beef Stew', $text);
    }

    public function test_notifications_and_errors(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->assertStatus(202);
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'nope'])->assertJsonPath('error.code', -32601);
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'search_recipes', 'arguments' => []]])
            ->assertJsonPath('error.code', -32602);
        $this->get('/mcp')->assertStatus(405);
    }
}
