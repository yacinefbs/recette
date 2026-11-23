<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\FacebookPublisher;
use App\Services\TastyService;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Client\RequestException;
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
            'toolPosts' => $this->toolPosts(),
            'counts' => [
                'pending' => Recipe::where('posted_facebook', false)->whereNull('facebook_scheduled_at')->count(),
                'scheduled' => Recipe::where('posted_facebook', false)->whereNotNull('facebook_scheduled_at')->count(),
                'posted' => Recipe::where('posted_facebook', true)->count(),
            ],
            'publisher' => $facebook,
        ]);
    }

    public function fetchRecipes(TastyService $tasty)
    {
        try {
            $count = $tasty->import();
        } catch (RequestException $e) {
            report($e);

            return back()->with('error', $e->response->status() === 429
                ? 'Recipe API quota reached. Please try again later.'
                : 'Unable to fetch recipes from the recipe API.');
        }

        return back()->with('status', "$count recipe(s) processed. Total recipes: ".Recipe::count().'.');
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

    public function publishTool(Request $request, string $tool, FacebookPublisher $facebook)
    {
        $tools = $this->toolPosts();
        abort_unless(isset($tools[$tool]), 404);
        $data = $request->validate(['message' => ['required', 'string', 'min:5', 'max:2000']]);

        try {
            $facebook->publishLink($tools[$tool]['url'], trim($data['message']));
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, 'Facebook: '.$e->getMessage(), 422);
        }

        return $this->respond($request, true, '"'.$tools[$tool]['title'].'" posted to Facebook.');
    }

    /**
     * Public pages available for manual, repeatable Facebook promotion.
     *
     * @return array<string, array{title:string,description:string,url:string,image:string,message:string}>
     */
    private function toolPosts(): array
    {
        return [
            'meal-planner' => [
                'title' => 'Weekly Meal Planner',
                'description' => 'Plan a week of meals and build a grocery list in one place.',
                'url' => route('meal-planner.index'),
                'image' => asset('assets/images/social/weekly-meal-planner-og.jpg'),
                'message' => "Make the week’s meals easier to plan. 🍽️\n\nCreate a flexible 7-day meal plan, find recipe ideas and put together a grocery list — all in one free tool, with no account needed.\n\nStart planning your week with TopicTrail:",
            ],
            'cooking-timer' => [
                'title' => 'Cooking Timer',
                'description' => 'Run multiple kitchen timers together, with quick presets and custom countdowns.',
                'url' => route('tools.cooking-timer'),
                'image' => asset('assets/images/social/cooking-timer-og.jpg'),
                'message' => "Juggling dinner and side dishes? ⏲️\n\nKeep several kitchen timers running on one page. Pick a quick cooking preset or set a custom countdown — free and ready when you are.\n\nGive the Cooking Timer a try:",
            ],
            'air-fryer-calculator' => [
                'title' => 'Air Fryer Calculator',
                'description' => 'Find sourced starting temperatures and cooking times for vegetables, meat, seafood, and frozen foods.',
                'url' => route('tools.air-fryer-calculator'),
                'image' => asset('assets/images/social/air-fryer-calculator-og.jpg'),
                'message' => "Not sure how long to cook it in your air fryer? 🍟\n\nFind a sourced starting temperature and cooking time for vegetables, chicken, fish, and frozen favorites. Search 49 foods, switch between °C and °F, and start a kitchen timer — free on TopicTrail.\n\nTry the Air Fryer Calculator:",
            ],
            'baking-calculator' => [
                'title' => 'Baking Calculator',
                'description' => 'Scale bread and pizza dough recipes with hydration, baker’s percentages, flour blends, and sourdough starter.',
                'url' => route('tools.baking-calculator'),
                'image' => asset('assets/images/social/baking-calculator-og.jpg'),
                'message' => "Scaling bread or pizza dough? 🍞🍕\n\nCalculate flour, water, salt, and yeast by total dough weight, flour weight, or portions. Adjust baker’s percentages, mix flour types, and account for sourdough starter without counting its flour and water twice.\n\nTry TopicTrail’s free Baking Calculator:",
            ],
            'electricity-cost-calculator' => [
                'title' => 'Electricity Cost Calculator',
                'description' => 'Estimate appliance electricity use, monthly costs, and potential savings with your own energy rate.',
                'url' => route('tools.electricity.calculator'),
                'image' => asset('assets/images/social/electricity-cost-calculator-og.jpg'),
                'message' => "Curious how much your home appliances use? ⚡\n\nEstimate daily, monthly, and yearly electricity use and cost, compare multiple appliances, and explore possible savings using your own electricity rate. Free to use on TopicTrail:\n\n",
            ],
            'recipe-cost-calculator' => [
                'title' => 'Recipe Cost Calculator',
                'description' => 'Estimate ingredient costs and cost per serving with your own grocery prices.',
                'url' => route('tools.recipe-cost-calculator'),
                'image' => asset('assets/images/social/recipe-cost-calculator-og.jpg'),
                'message' => "Wondering what a recipe really costs to make? 🧾\n\nEnter your ingredient amounts and the package prices you paid to estimate the full recipe cost and cost per serving. Compare two recipes, save them in your browser, and keep your grocery prices private.\n\nTry the free Recipe Cost Calculator:",
            ],
            'pantry-inventory' => [
                'title' => 'Pantry Inventory Manager',
                'description' => 'Keep track of kitchen ingredients, quantities and the dates you record, right in your browser.',
                'url' => route('tools.pantry-inventory'),
                'image' => asset('assets/images/social/pantry-inventory-og.jpg'),
                'message' => "What’s already in your kitchen? 🥕\n\nKeep a simple pantry inventory, track quantities and recorded package dates, and discover recipe ideas using ingredients you have. Your inventory stays in your browser.\n\nTake a look at the Pantry Inventory Manager:",
            ],
            'cooking-converter' => [
                'title' => 'Cooking Converter',
                'description' => 'Convert common cooking measurements and temperatures while you follow a recipe.',
                'url' => route('tools.converter'),
                'image' => asset('assets/images/social/cooking-converter-og.jpg'),
                'message' => "Cups, grams, tablespoons or oven temperatures? 🥄\n\nConvert common kitchen measurements and temperature units with TopicTrail’s free Cooking Converter.\n\nOpen the Cooking Converter:",
            ],
            'nutrition-calculator' => [
                'title' => 'Nutrition Calculator',
                'description' => 'Estimate nutrition for recipe ingredients and serving sizes.',
                'url' => route('tools.nutrition-calculator'),
                'image' => asset('assets/images/social/nutrition-calculator-og.jpg'),
                'message' => "Curious about the nutrition in your ingredients? 🥗\n\nUse the free TopicTrail Nutrition Calculator to estimate nutrition for foods and serving quantities. It’s a handy guide for meal planning.\n\nTry the Nutrition Calculator:",
            ],
            'what-can-i-cook' => [
                'title' => 'What Can I Cook?',
                'description' => 'Enter ingredients you have and find recipes that use them.',
                'url' => route('tools.what'),
                'image' => asset('assets/images/social/what-can-i-cook-og.jpg'),
                'message' => "Not sure what to cook with what’s in the kitchen? 🍳\n\nEnter a few ingredients you have and find recipe ideas that use them with TopicTrail’s free What Can I Cook? tool.\n\nFind your next recipe with TopicTrail:",
            ],
            'ingredient-substitution' => [
                'title' => 'Ingredient Substitution Tool',
                'description' => 'Find practical substitutes and ratios when a recipe ingredient is missing.',
                'url' => route('tools.ingredient-substitution'),
                'image' => asset('assets/images/social/ingredient-substitution-og.jpg'),
                'message' => "Missing an ingredient halfway through a recipe? 🧈\n\nLook up practical substitutions and suggested ratios with TopicTrail’s free Ingredient Substitution Tool.\n\nFind an ingredient substitute with TopicTrail:",
            ],
        ];
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
