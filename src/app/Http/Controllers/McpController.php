<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Minimal read-only MCP server (Streamable HTTP, JSON responses) exposing recipe search. */
class McpController extends Controller
{
    private const PROTOCOL = '2025-06-18';

    public function card(): JsonResponse
    {
        return response()->json([
            'serverInfo' => ['name' => 'topictrail-recipes', 'title' => config('app.name').' recipes', 'version' => '1.0.0'],
            'description' => 'Read-only search over '.config('app.name').' cooking recipes.',
            'protocolVersion' => self::PROTOCOL,
            'transport' => ['type' => 'streamable-http', 'endpoint' => route('mcp.handle')],
            'documentationUrl' => url('/llms.txt'),
            'capabilities' => ['tools' => ['listChanged' => false]],
            'authentication' => ['required' => false],
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function handle(Request $request)
    {
        $msg = $request->json()->all();
        if (! is_array($msg) || ($msg['jsonrpc'] ?? null) !== '2.0' || ! isset($msg['method'])) {
            return $this->error(null, -32600, 'Invalid Request');
        }

        $id = $msg['id'] ?? null;
        if ($id === null) {
            return response('', 202); // notification
        }

        return match ($msg['method']) {
            'initialize' => $this->result($id, [
                'protocolVersion' => self::PROTOCOL,
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => ['name' => 'topictrail-recipes', 'version' => '1.0.0'],
                'instructions' => 'Use search_recipes to find recipes by name keyword; results include page URLs.',
            ]),
            'ping' => $this->result($id, new \stdClass),
            'tools/list' => $this->result($id, ['tools' => [$this->toolDefinition()]]),
            'tools/call' => $this->callTool($id, (array) ($msg['params'] ?? [])),
            default => $this->error($id, -32601, 'Method not found'),
        };
    }

    private function toolDefinition(): array
    {
        return [
            'name' => 'search_recipes',
            'description' => 'Search recipes by name keyword. Returns up to 10 recipes with title, URL and servings.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => ['query' => ['type' => 'string', 'description' => 'Keyword, e.g. chicken', 'maxLength' => 100]],
                'required' => ['query'],
            ],
            'annotations' => ['readOnlyHint' => true, 'openWorldHint' => false],
        ];
    }

    private function callTool($id, array $params)
    {
        if (($params['name'] ?? null) !== 'search_recipes') {
            return $this->error($id, -32602, 'Unknown tool');
        }
        $q = trim((string) ($params['arguments']['query'] ?? ''));
        if ($q === '' || mb_strlen($q) > 100) {
            return $this->error($id, -32602, 'query is required (max 100 characters)');
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $rows = Recipe::query()->select(['id', 'name', 'num_servings', 'total_time_minutes'])
            ->where('name', 'like', $like)->orderByDesc('id')->limit(10)->get();

        $items = $rows->map(fn ($r) => [
            'title' => $r->name,
            'url' => route('recipes.show', $r),
            'servings' => $r->num_servings,
            'total_time_minutes' => $r->total_time_minutes,
        ])->all();

        return $this->result($id, [
            'content' => [['type' => 'text', 'text' => $items ? json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'No recipe found.']],
            'isError' => false,
        ]);
    }

    private function result($id, $result): JsonResponse
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    private function error($id, int $code, string $message): JsonResponse
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
    }
}
