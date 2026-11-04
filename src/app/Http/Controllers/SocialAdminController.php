<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\FacebookPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SocialAdminController extends Controller
{
    public function loginForm()
    {
        return Auth::check() ? redirect()->route('social.index') : view('social.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Invalid credentials.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('social.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('social.login');
    }

    public function index(Request $request, FacebookPublisher $facebook)
    {
        $q = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['pending', 'posted'], true) ? $request->query('status') : 'pending';

        $recipes = Recipe::query()
            ->select(['id', 'name', 'description', 'image_path', 'posted_facebook', 'facebook_posted_at', 'facebook_post_id'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->where('posted_facebook', $status === 'posted')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('social.index', [
            'recipes' => $recipes,
            'q' => $q,
            'status' => $status,
            'facebookReady' => $facebook->isConfigured(),
            'counts' => [
                'pending' => Recipe::where('posted_facebook', false)->count(),
                'posted' => Recipe::where('posted_facebook', true)->count(),
            ],
            'publisher' => $facebook,
        ]);
    }

    public function publishFacebook(Request $request, Recipe $recipe, FacebookPublisher $facebook)
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:2000']]);

        if ($recipe->posted_facebook) {
            $msg = "\"{$recipe->name}\" is already marked as posted.";

            return $request->expectsJson() ? response()->json(['ok' => false, 'message' => $msg], 409) : back()->with('error', $msg);
        }

        try {
            $facebook->publish($recipe, $data['message'] ?? null);
        } catch (Throwable $e) {
            report($e);

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Facebook: '.$e->getMessage()], 422)
                : back()->with('error', 'Facebook: '.$e->getMessage());
        }

        $msg = "\"{$recipe->name}\" posted to Facebook.";

        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $msg]) : back()->with('status', $msg);
    }

    /** Manual override, e.g. a recipe posted by hand outside this tool. */
    public function toggleFacebook(Recipe $recipe)
    {
        $posted = ! $recipe->posted_facebook;
        $recipe->update([
            'posted_facebook' => $posted,
            'facebook_posted_at' => $posted ? now() : null,
            'facebook_post_id' => $posted ? $recipe->facebook_post_id : null,
        ]);

        return back()->with('status', $posted ? 'Marked as posted.' : 'Marked as not posted.');
    }
}
