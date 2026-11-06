<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\FacebookPublisher;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $status = in_array($request->query('status'), ['pending', 'scheduled', 'posted'], true) ? $request->query('status') : 'pending';

        $recipes = Recipe::query()
            ->select(['id', 'name', 'description', 'image_path', 'posted_facebook', 'facebook_posted_at', 'facebook_post_id', 'facebook_scheduled_at'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->when($status === 'posted', fn ($query) => $query->where('posted_facebook', true))
            ->when($status === 'scheduled', fn ($query) => $query->where('posted_facebook', false)->whereNotNull('facebook_scheduled_at'))
            ->when($status === 'pending', fn ($query) => $query->where('posted_facebook', false)->whereNull('facebook_scheduled_at'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('social.index', [
            'recipes' => $recipes,
            'q' => $q,
            'status' => $status,
            'facebookReady' => $facebook->isConfigured(),
            'counts' => [
                'pending' => Recipe::where('posted_facebook', false)->whereNull('facebook_scheduled_at')->count(),
                'scheduled' => Recipe::where('posted_facebook', false)->whereNotNull('facebook_scheduled_at')->count(),
                'posted' => Recipe::where('posted_facebook', true)->count(),
            ],
            'publisher' => $facebook,
        ]);
    }

    public function publishFacebook(Request $request, Recipe $recipe, FacebookPublisher $facebook)
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:2000']]);

        try {
            $conflict = DB::transaction(function () use ($recipe, $facebook, $data) {
                $recipe = Recipe::whereKey($recipe->id)->lockForUpdate()->firstOrFail();
                if ($recipe->posted_facebook || $recipe->facebook_scheduled_at) {
                    return true;
                }
                $facebook->publish($recipe, $data['message'] ?? null);

                return false;
            });
            if ($conflict) {
                return $this->respond($request, false, 'This recipe is already posted or scheduled.', 409);
            }
        } catch (Throwable $e) {
            report($e);

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Facebook: '.$e->getMessage()], 422)
                : back()->with('error', 'Facebook: '.$e->getMessage());
        }

        $msg = "\"{$recipe->name}\" posted to Facebook.";

        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $msg]) : back()->with('status', $msg);
    }

    public function scheduleFacebook(Request $request, Recipe $recipe, FacebookPublisher $facebook)
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'schedule_mode' => ['required', 'in:calendar,delay'],
            'scheduled_at' => ['required_if:schedule_mode,calendar', 'nullable', 'date_format:Y-m-d\TH:i'],
            'delay' => ['required_if:schedule_mode,delay', 'nullable', 'integer', 'min:1', 'max:525600'],
            'delay_unit' => ['required_if:schedule_mode,delay', 'nullable', 'in:minutes,hours'],
        ]);

        if ($data['schedule_mode'] === 'calendar') {
            $when = $this->moroccoTime($data['scheduled_at']);
        } else {
            $seconds = (int) $data['delay'] * ($data['delay_unit'] === 'hours' ? 3600 : 60);
            $when = CarbonImmutable::now('UTC')->addSeconds($seconds);
        }

        if ($when->getTimestamp() - CarbonImmutable::now('UTC')->getTimestamp() < 600) {
            throw ValidationException::withMessages(['schedule' => 'Choose a time at least 10 minutes from now. Facebook may impose additional limits.']);
        }

        try {
            $conflict = DB::transaction(function () use ($recipe, $facebook, $data, $when) {
                $recipe = Recipe::whereKey($recipe->id)->lockForUpdate()->firstOrFail();
                if ($recipe->posted_facebook || $recipe->facebook_scheduled_at) {
                    return true;
                }
                $facebook->schedule($recipe, $when, $data['message'] ?? null);

                return false;
            });
            if ($conflict) {
                return $this->respond($request, false, 'This recipe is already posted or scheduled.', 409);
            }
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, 'Scheduling failed. Check Meta Business Suite before retrying: Facebook may have received the request. '.$e->getMessage(), 422);
        }

        return $this->respond($request, true, 'Scheduled on Facebook for '.$when->setTimezone('Africa/Casablanca')->format('Y-m-d H:i T').'.');
    }

    public function syncFacebook(Request $request, Recipe $recipe, FacebookPublisher $facebook)
    {
        try {
            $result = DB::transaction(function () use ($recipe, $facebook) {
                $recipe = Recipe::whereKey($recipe->id)->lockForUpdate()->firstOrFail();
                if (! $recipe->facebook_scheduled_at || ! $recipe->facebook_post_id || $recipe->posted_facebook) {
                    return 'not_scheduled';
                }
                if (! $facebook->isPublished($recipe)) {
                    return 'pending';
                }
                $recipe->forceFill([
                    'posted_facebook' => true,
                    'facebook_posted_at' => null,
                    'facebook_scheduled_at' => null,
                ])->save();

                return 'posted';
            });
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, 'Could not verify the Facebook post: '.$e->getMessage(), 422);
        }

        return match ($result) {
            'posted' => $this->respond($request, true, 'Facebook confirms this post is published.'),
            'pending' => $this->respond($request, true, 'Facebook reports that this post is not yet published.'),
            default => $this->respond($request, false, 'This recipe is not scheduled.', 409),
        };
    }

    private function moroccoTime(string $value): CarbonImmutable
    {
        $zone = new DateTimeZone('Africa/Casablanca');
        $wall = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('UTC'));
        $matches = [];
        foreach ($zone->getTransitions($wall->getTimestamp() - 172800, $wall->getTimestamp() + 172800) as $transition) {
            $timestamp = $wall->getTimestamp() - $transition['offset'];
            if ((new DateTimeImmutable('@'.$timestamp))->setTimezone($zone)->format('Y-m-d\TH:i') === $value) {
                $matches[$timestamp] = $timestamp;
            }
        }
        if (count($matches) !== 1) {
            throw ValidationException::withMessages(['scheduled_at' => 'This Morocco local time is ambiguous or does not exist due to a clock change. Choose another time.']);
        }

        return CarbonImmutable::createFromTimestampUTC(reset($matches));
    }

    private function respond(Request $request, bool $ok, string $message, int $status = 200)
    {
        return $request->expectsJson()
            ? response()->json(['ok' => $ok, 'message' => $message], $status)
            : back()->with($ok ? 'status' : 'error', $message);
    }

    /** Manual override, e.g. a recipe posted by hand outside this tool. */
    public function toggleFacebook(Recipe $recipe)
    {
        $posted = DB::transaction(function () use ($recipe) {
            $recipe = Recipe::whereKey($recipe->id)->lockForUpdate()->firstOrFail();
            if ($recipe->facebook_scheduled_at) {
                return null;
            }
            $posted = ! $recipe->posted_facebook;
            $recipe->update([
                'posted_facebook' => $posted,
                'facebook_posted_at' => $posted ? now() : null,
                'facebook_post_id' => $posted ? $recipe->facebook_post_id : null,
            ]);

            return $posted;
        });
        if ($posted === null) {
            return back()->with('error', 'Verify the scheduled post on Facebook before changing its status.');
        }

        return back()->with('status', $posted ? 'Marked as posted.' : 'Marked as not posted.');
    }
}
