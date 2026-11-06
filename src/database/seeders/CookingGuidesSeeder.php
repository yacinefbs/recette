<?php

namespace Database\Seeders;

use App\Models\CookingGuide;
use App\Models\CookingGuideCategory;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CookingGuidesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'techniques',
                'name' => 'Cooking Techniques',
                'name_fr' => 'Techniques de cuisine',
                'icon' => 'fa-fire-alt',
                'description' => 'Master essential kitchen gestures, pan heat, knife skills and reliable cooking methods.',
                'seo_title' => 'Cooking Techniques & Culinary Methods | TopicTrail',
                'seo_description' => 'Learn step-by-step cooking techniques: sautéing, braising, keeping meats juicy, and perfect boiling.',
                'sort_order' => 1,
            ],
            [
                'slug' => 'ingredients',
                'name' => 'Ingredient Guides',
                'name_fr' => 'Guides des ingrédients',
                'icon' => 'fa-pepper-hot',
                'description' => 'Understand how ingredients behave, their culinary properties and when to use each variety.',
                'seo_title' => 'Ingredient Guides – Properties, Uses & Differences | TopicTrail',
                'seo_description' => 'Detailed culinary guides explaining ingredients: flour types, leaveners, cooking oils and fresh herbs.',
                'sort_order' => 2,
            ],
            [
                'slug' => 'food-storage',
                'name' => 'Food Storage & Preservation',
                'name_fr' => 'Conservation des aliments',
                'icon' => 'fa-snowflake',
                'description' => 'Keep food fresh longer, reduce kitchen waste and follow science-backed food safety rules.',
                'seo_title' => 'Food Storage & Kitchen Preservation Guides | TopicTrail',
                'seo_description' => 'Extend ingredient freshness, freeze correctly, organize your fridge and prevent food spoilage.',
                'sort_order' => 3,
            ],
            [
                'slug' => 'kitchen-hacks',
                'name' => 'Kitchen Tips & Hacks',
                'name_fr' => 'Astuces pratiques',
                'icon' => 'fa-lightbulb',
                'description' => 'Quick fixes for everyday cooking accidents, time-saving tricks and clever kitchen hacks.',
                'seo_title' => 'Kitchen Hacks & Quick Cooking Fixes | TopicTrail',
                'seo_description' => 'Easy solutions to rescue oversalted soups, fix thin sauces, prep faster, and cut down kitchen stress.',
                'sort_order' => 4,
            ],
            [
                'slug' => 'baking',
                'name' => 'Baking Tips & Guides',
                'name_fr' => 'Pâtisserie et boulangerie',
                'icon' => 'fa-birthday-cake',
                'description' => 'Demystify oven baking: cake chemistry, measuring flour, leavening agents and fool-proof crusts.',
                'seo_title' => 'Baking Tips, Cake Science & Oven Guides | TopicTrail',
                'seo_description' => 'Learn why cakes sink, how to measure flour by weight, room-temperature butter rules and cookie tricks.',
                'sort_order' => 5,
            ],
            [
                'slug' => 'cooking-basics',
                'name' => 'Cooking Basics',
                'name_fr' => 'Les fondamentaux',
                'icon' => 'fa-graduation-cap',
                'description' => 'Foundational culinary knowledge for beginners and home cooks refining their core skills.',
                'seo_title' => 'Cooking Basics for Everyday Home Cooks | TopicTrail',
                'seo_description' => 'Understanding kitchen measurements, converting cups to grams, seasoning fundamentals and terminology.',
                'sort_order' => 6,
            ],
        ];

        $catMap = [];
        foreach ($categories as $catData) {
            $cat = CookingGuideCategory::updateOrCreate(['slug' => $catData['slug']], $catData);
            $catMap[$catData['slug']] = $cat->id;
        }

        $now = Carbon::now();

        $guides = [
            // 1. Cooking Rice (Techniques)
            [
                'category_id' => $catMap['techniques'],
                'slug' => 'how-to-cook-rice-perfectly-every-time',
                'title' => 'How to Cook Rice Perfectly Every Time (Never Mushy, Never Burnt)',
                'meta_title' => 'How to Cook Perfect Fluffy Rice Every Time | TopicTrail',
                'meta_description' => 'The foolproof absorption method for perfect white and brown rice. Correct water ratios, resting rules, and why washing starch matters.',
                'excerpt' => 'Forget sticky mush or crunchy burnt bottoms. Master the simple absorption technique with the exact water-to-rice ratios for flawless, fluffy grains every single time.',
                'quick_answer' => 'For standard long-grain white rice, use a 1 : 1.5 ratio (1 cup washed rice to 1.5 cups water). Bring to a rolling boil, cover with a tight lid, reduce heat to the lowest simmer for 15 minutes, remove from heat, and let it rest undisturbed for 10 minutes with the lid on before fluffing with a fork.',
                'what_you_need' => ['1 cup long-grain white rice', '1.5 cups water or broth', '1/2 tsp kosher salt', '1 tsp butter or olive oil (optional)', 'Heavy-bottomed saucepan with tight lid', 'Fine-mesh sieve'],
                'content_sections' => [
                    [
                        'title' => 'Step 1: Always Rinse the Rice',
                        'body' => 'Place your dry rice in a fine-mesh strainer and rinse under cold running water for 30–60 seconds while gently agitating the grains with your fingers. This removes excess surface amylose starch produced during milling, which is the #1 culprit behind gummy, sticky rice.',
                    ],
                    [
                        'title' => 'Step 2: The Golden Water Ratio',
                        'body' => 'For standard long-grain white rice (such as Jasmine or Basmati), the ideal saucepan ratio is 1 cup rice to 1.5 cups water. Brown rice requires 1 cup rice to 2 cups water due to its fibrous bran layer. Always add 1/2 teaspoon of salt to season the grains from the inside out.',
                    ],
                    [
                        'title' => 'Step 3: Simmer Gently on Low Heat',
                        'body' => 'Combine rice, water, and salt in your saucepan over medium-high heat. As soon as bubbles break the surface vigorously, cover immediately with a tight lid and turn your burner to the absolute lowest setting. Do not remove the lid or stir during cooking; letting steam escape drops the internal temperature and ruins the hydration cycle.',
                    ],
                    [
                        'title' => 'Step 4: The 10-Minute Rest (The Secret Step)',
                        'body' => 'After 15 minutes of simmering, turn off the burner and move the pot off the hot zone. Keep the lid sealed and let it sit undisturbed for 10 full minutes. The residual heat evenly redistributes moisture from the bottom of the pot throughout every grain. Finally, uncover and gently fluff with a fork.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Stirring the rice while it cooks', 'fix' => 'Stirring breaks delicate grain walls and releases starch, turning your pot into sticky paste. Never stir rice once covered.'],
                    ['mistake' => 'Skipping the 10-minute resting period', 'fix' => 'Uncovering immediately leaves the top layer dry and the bottom mushy. Resting standardizes moisture.'],
                    ['mistake' => 'Cooking over too high heat', 'fix' => 'Excess heat evaporates the liquid before the grain centers hydrate, resulting in burned bottoms and hard cores.'],
                ],
                'pro_tips' => [
                    'Toast the washed, drained grains in a splash of olive oil or butter for 2 minutes before adding boiling liquid for a richer nutty aroma.',
                    'Use chicken or vegetable broth instead of tap water for an instant flavor upgrade.',
                ],
                'problem_keywords' => ['my rice is sticky', 'mushy rice', 'burnt rice', 'hard rice in center', 'rice water ratio', 'how to cook rice'],
                'reading_time_minutes' => 5,
                'featured' => true,
                'related_tool_key' => 'converter',
                'related_tool_headline' => 'Need to Convert Rice Cups to Grams?',
                'related_tool_text' => '1 cup of uncooked white rice weighs approximately 185g. Use our free Cooking Converter to scale any measurement instantly.',
                'faqs' => [
                    ['question' => 'How much water do I need for 2 cups of rice?', 'answer' => 'For 2 cups of long-grain white rice in a saucepan, use 3 cups of water (ratio 1:1.5) and 1 teaspoon of salt.'],
                    ['question' => 'Why is my rice mushy?', 'answer' => 'Too much water or failing to rinse surface starch before cooking causes mushy rice. Stick to the 1:1.5 ratio.'],
                    ['question' => 'Can I freeze cooked rice?', 'answer' => 'Yes! Cool it quickly within 1 hour, portion into airtight freezer bags, and freeze for up to 3 months. Reheat until steaming hot throughout.'],
                ],
                'sources' => [
                    ['title' => 'Harold McGee - On Food and Cooking: The Science and Lore of the Kitchen', 'authority' => 'Culinary Science Standard'],
                    ['title' => 'USDA FoodData Central - Rice Cooking Guidelines', 'authority' => 'USDA FDC'],
                ],
            ],

            // 2. Chicken Breast (Techniques)
            [
                'category_id' => $catMap['techniques'],
                'slug' => 'how-to-keep-chicken-breast-moist-and-tender',
                'title' => 'How to Keep Chicken Breast Moist and Juicy (The 4 Rules)',
                'meta_title' => 'How to Cook Juicy Chicken Breast Every Time | TopicTrail',
                'meta_description' => 'Say goodbye to dry, rubbery chicken breast. Learn dry brining, even thickness pounding, internal thermometer guidelines and resting rules.',
                'excerpt' => 'Boneless skinless chicken breast is lean and unforgiving. Follow four culinary principles to lock in juices and cook succulent, tender chicken every single time.',
                'quick_answer' => 'To keep chicken breast tender and juicy: (1) Pound it to an even 1/2-inch thickness so it cooks uniformly, (2) Dry brine with salt at least 30 minutes before cooking, (3) Pull it from the heat when an instant-read thermometer reads 160°F (71°C) as carryover cooking will bring it to the safe 165°F (74°C), and (4) Rest 5 minutes before slicing.',
                'what_you_need' => ['Chicken breasts (boneless, skinless)', 'Kosher salt & black pepper', 'Plastic wrap & meat mallet or rolling pin', 'Instant-read digital meat thermometer', 'Cast iron or stainless steel skillet', '1 tbsp high smoke-point cooking oil'],
                'content_sections' => [
                    [
                        'title' => 'Rule 1: Pound to Even Thickness',
                        'body' => 'A natural chicken breast is thick at one end and thin at the other. If you cook it as-is, the tapered end dries out into cardboard before the thick center is safe. Place breasts between sheets of plastic wrap and pound the thick end gently until the entire piece is an even 1/2 to 3/4 inch.',
                    ],
                    [
                        'title' => 'Rule 2: Dry Brine (Salt Ahead of Time)',
                        'body' => 'Season breasts with kosher salt (about 1/2 tsp per breast) 30 to 45 minutes before cooking. Salt dissolves into the surface moisture, creates a concentrated brine, and draws back deep into muscle fibers. This denatures protein strands so they retain water when heated.',
                    ],
                    [
                        'title' => 'Rule 3: Sear Hot, then Cook Through',
                        'body' => 'Preheat a heavy skillet over medium-high heat with a tablespoon of oil until shimmering. Sear for 4–5 minutes undisturbed until a deep golden crust forms, flip once, lower the heat to medium, and cook for another 3–4 minutes.',
                    ],
                    [
                        'title' => 'Rule 4: Pull at 160°F and Rest for 5 Minutes',
                        'body' => 'Use a digital thermometer inserted into the thickest part. Remove the chicken from the pan when it hits 160°F (71°C). Residual carryover heat will raise the internal temperature to the USDA-safe 165°F (74°C) on the cutting board while muscle fibers relax and trap moisture.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Slicing the chicken straight out of the hot pan', 'fix' => 'All the flavorful juices will pour onto your cutting board. Always wait 5 full minutes before slicing.'],
                    ['mistake' => 'Cooking straight from the freezer without fully thawing', 'fix' => 'The outer surface overcooks while the center remains raw. Thaw safely in the refrigerator overnight.'],
                    ['mistake' => 'Cooking until 175°F or higher out of fear of food safety', 'fix' => 'Lean chicken breast contains almost zero intramuscular fat. Overcooking beyond 165°F expels all moisture.'],
                ],
                'pro_tips' => [
                    'Cut against the grain when slicing cooked chicken breast to shorten muscle fibers for a melt-in-your-mouth bite.',
                    'Add a tablespoon of butter, crushed garlic and fresh thyme in the last minute of cooking and baste the breast.',
                ],
                'problem_keywords' => ['my chicken is too dry', 'my chicken is dry', 'dry chicken breast', 'rubbery chicken', 'chicken overcooked', 'juicy chicken breast'],
                'reading_time_minutes' => 4,
                'featured' => true,
                'related_tool_key' => 'nutrition',
                'related_tool_headline' => 'Track Your Chicken Macros',
                'related_tool_text' => '100g of roasted chicken breast packs 31g of pure protein for only 165 kcal. Calculate your exact serving nutrition with our Nutrition Calculator.',
                'faqs' => [
                    ['question' => 'What is the safe internal temperature for chicken?', 'answer' => 'According to USDA guidelines, poultry must reach an internal temperature of 165°F (74°C). Removing it at 160°F and resting achieves this safely.'],
                    ['question' => 'Can I bake chicken breasts in the oven instead?', 'answer' => 'Yes! Bake at 400°F (200°C) for 18–22 minutes until an internal thermometer reads 160°F, then rest.'],
                ],
                'sources' => [
                    ['title' => 'USDA Food Safety and Inspection Service (FSIS) - Poultry Safety Guidelines', 'authority' => 'USDA FSIS'],
                ],
            ],

            // 3. Storing Fresh Herbs (Food Storage)
            [
                'category_id' => $catMap['food-storage'],
                'slug' => 'how-to-store-fresh-herbs',
                'title' => 'How to Store Fresh Herbs and Keep Them Crisp for Up to 3 Weeks',
                'meta_title' => 'How to Store Fresh Herbs to Last Weeks | TopicTrail',
                'meta_description' => 'Stop throwing away slimy cilantro and parsley. The definitive guide to storing tender and woody herbs with water glasses and damp paper towels.',
                'excerpt' => 'Nothing is more frustrating than buying vibrant herbs only to find brown slime three days later. Learn how to treat tender and woody herbs differently so they stay crisp for weeks.',
                'quick_answer' => 'Separate herbs into two groups: (1) Tender herbs like parsley and cilantro thrive like cut flowers in a jar of water in the fridge covered loosely with a plastic bag. Basil stays on the room-temperature counter (cold turns it black). (2) Woody herbs like rosemary and thyme should be wrapped in a slightly damp paper towel and stored in a resealable bag in the crisper drawer.',
                'what_you_need' => ['Mason jars or drinking glasses', 'Paper towels', 'Zip-top plastic bags or reusable silicone bags', 'Clean kitchen shears'],
                'content_sections' => [
                    [
                        'title' => 'Tender Herbs: The Bouquet Method (Parsley, Cilantro, Dill, Mint)',
                        'body' => 'Trim 1/2 inch off the bottom stems with sharp shears. Fill a clean glass or mason jar with 1 to 2 inches of cold water. Place the herb stems into the water just like a bouquet of flowers. Cover the leaves loosely with an upside-down plastic bag to retain humidity and place in the refrigerator. Change the water every 2 to 3 days.',
                    ],
                    [
                        'title' => 'The Basil Exception: Never Refrigerate Fresh Basil',
                        'body' => 'Basil is native to warm tropical climates. Temperatures below 50°F (10°C) break down cell walls and oxidize chlorophyll, turning leaves black and mushy in hours. Keep your basil bouquet on your kitchen counter away from direct sun, and change water every two days.',
                    ],
                    [
                        'title' => 'Woody Herbs: The Damp Paper Towel Roll (Rosemary, Thyme, Sage, Oregano)',
                        'body' => 'Woody herbs have tough, resinous stems that rot if submerged in water. Instead, wash and dry them thoroughly in a salad spinner. Dampen a paper towel with cool water, wring it out completely, wrap the herbs loosely, and slide into a zip-top bag with the zipper left slightly cracked open for minimal airflow.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Putting basil inside the refrigerator', 'fix' => 'Cold air turns basil black. Keep basil on the kitchen counter in a jar of fresh water.'],
                    ['mistake' => 'Storing herbs in the airtight grocery produce bag with condensation', 'fix' => 'Trapped condensation without air circulation accelerates fungal rot. Always unpackage and prep immediately.'],
                ],
                'pro_tips' => [
                    'If you have surplus herbs, finely chop them, pack into ice cube trays, fill with extra virgin olive oil, and freeze. Pop into hot pans for instant sauté flavor.',
                ],
                'problem_keywords' => ['slimy cilantro', 'black basil', 'wilted parsley', 'how to store herbs', 'fresh herbs rotting'],
                'reading_time_minutes' => 4,
                'featured' => true,
                'related_tool_key' => 'what-can-i-cook',
                'related_tool_headline' => 'Got Leftover Herbs to Use Up?',
                'related_tool_text' => 'Don\'t let fresh herbs go to waste. Use our What Can I Cook tool to find delicious recipes matching the ingredients in your fridge.',
                'faqs' => [
                    ['question' => 'How long can cilantro last in water?', 'answer' => 'Stored as a bouquet in the fridge with a loose plastic hood and fresh water every 3 days, cilantro easily lasts 2 to 3 weeks.'],
                    ['question' => 'Can you freeze fresh mint?', 'answer' => 'Yes, freezing in olive oil cubes or plain water preserves mint for cocktails, sauces, and cooking for up to 6 months.'],
                ],
                'sources' => [
                    ['title' => 'UC Davis Postharvest Technology Center - Herb Storage Practices', 'authority' => 'UC Davis'],
                ],
            ],

            // 4. Cooked Chicken Shelf Life (Food Storage)
            [
                'category_id' => $catMap['food-storage'],
                'slug' => 'how-long-does-cooked-chicken-last-in-the-fridge',
                'title' => 'How Long Does Cooked Chicken Last in the Fridge? (Safety Guide)',
                'meta_title' => 'How Long Does Cooked Chicken Last in the Fridge? | TopicTrail',
                'meta_description' => 'Official USDA rules on cooked chicken shelf life. How to tell if cooked chicken has spoiled, proper refrigeration temps and freezing tips.',
                'excerpt' => 'Prepping meals or dealing with rotisserie leftovers? Discover the exact shelf life of cooked chicken, the 2-hour safety rule, and how to spot spoilage.',
                'quick_answer' => 'According to official USDA Food Safety guidelines, cooked chicken lasts 3 to 4 days in a refrigerator set at 40°F (4°C) or lower. If you will not consume it within 4 days, freeze it in an airtight container where it remains safe for up to 4 months.',
                'what_you_need' => ['Airtight glass or BPA-free plastic food containers', 'Freezer bags (if freezing)', 'Refrigerator thermometer'],
                'content_sections' => [
                    [
                        'title' => 'The 3 to 4 Day Rule',
                        'body' => 'Whether grilled chicken breasts, baked thighs, chicken soup, or shredded rotisserie chicken, pathogenic bacteria like Listeria, Salmonella, and Campylobacter can multiply even at cold refrigerator temperatures. 4 days is the safe scientific threshold.',
                    ],
                    [
                        'title' => 'The 2-Hour Window (Danger Zone)',
                        'body' => 'Never let cooked chicken sit at room temperature for more than 2 hours (or 1 hour if the ambient room temperature is over 90°F / 32°C). Bacteria multiply fastest between 40°F and 140°F (4°C - 60°C). Divide hot chicken into shallow containers so it cools down rapidly in the fridge.',
                    ],
                    [
                        'title' => 'Signs of Spoiled Cooked Chicken',
                        'body' => 'Smell: A sour, ammonia-like, or funky odor indicates spoilage. Texture: A slimy or slippery sheen on the meat surface means bacterial colonies have formed. Color: A gray or greenish discoloration means it must be discarded immediately. When in doubt, throw it out.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Leaving a whole roasted chicken on the counter to cool for hours', 'fix' => 'Large meat masses retain heat too long. Carve into smaller pieces and refrigerate within 2 hours.'],
                    ['mistake' => 'Believing reheating spoiled chicken will make it safe', 'fix' => 'Reheating kills live bacteria but does not destroy heat-stable enterotoxins already produced.'],
                ],
                'pro_tips' => [
                    'Label your leftover containers with masking tape and the date cooked so there is never any guesswork mid-week.',
                ],
                'problem_keywords' => ['cooked chicken in fridge', 'is 5 day old chicken safe', 'chicken smell test', 'food safety cooked poultry'],
                'reading_time_minutes' => 3,
                'featured' => false,
                'related_tool_key' => 'meal-planner',
                'related_tool_headline' => 'Plan Meals & Rotate Leftovers Easily',
                'related_tool_text' => 'Avoid food waste and eat fresh every day. Build a custom 7-day schedule with our free Weekly Meal Planner.',
                'faqs' => [
                    ['question' => 'Can you eat cooked chicken after 5 days?', 'answer' => 'Health authorities advise against it. Even without an obvious bad smell, bacterial levels can cause foodborne illness.'],
                    ['question' => 'How to freeze leftover cooked chicken?', 'answer' => 'Shred or cube the meat, place in a freezer zip bag, press out all excess air to prevent freezer burn, and freeze up to 4 months.'],
                ],
                'sources' => [
                    ['title' => 'USDA Food Safety and Inspection Service (FSIS) - Leftovers and Food Safety', 'authority' => 'USDA'],
                ],
            ],

            // 5. Baking Soda vs Baking Powder (Ingredients)
            [
                'category_id' => $catMap['ingredients'],
                'slug' => 'baking-soda-vs-baking-powder',
                'title' => 'Baking Soda vs. Baking Powder: What Is the Difference?',
                'meta_title' => 'Baking Soda vs Baking Powder Difference Explained | TopicTrail',
                'meta_description' => 'Understand the chemical differences between baking soda and baking powder. Learn when to use each, substitution ratios, and how to test for freshness.',
                'excerpt' => 'They look identical and both make baked goods rise, but swapping them 1:1 will ruin your cookies. Learn the essential differences and how to substitute correctly.',
                'quick_answer' => 'Baking soda is 100% pure sodium bicarbonate and requires an acidic ingredient (like buttermilk, lemon juice, or brown sugar) to activate and release carbon dioxide. Baking powder contains baking soda plus an acidifying salt (cream of tartar) and cornstarch; it activates automatically with heat and moisture.',
                'what_you_need' => ['Baking soda', 'Baking powder', 'White vinegar or warm water (for freshness testing)'],
                'content_sections' => [
                    [
                        'title' => 'Baking Soda (Sodium Bicarbonate)',
                        'body' => 'Baking soda is an alkaline base. When combined with an acid (lemon juice, yogurt, cocoa powder, molasses, buttermilk, vinegar), it creates a chemical reaction producing carbon dioxide gas bubbles that expand in the heat of your oven. Because it neutralizes acid, it also accelerates browning (the Maillard reaction).',
                    ],
                    [
                        'title' => 'Baking Powder (Complete Leavener)',
                        'body' => 'Baking powder is a complete leavening package. Most commercial powders are "double-acting": they react once when hydrated in batter, and a second time when exposed to oven heat. Use baking powder when your recipe does not contain enough acidic ingredients.',
                    ],
                    [
                        'title' => 'Can You Substitute One for the Other?',
                        'body' => 'You cannot swap them equally: baking soda is roughly 3 to 4 times stronger than baking powder. If you run out of baking powder, make your own by mixing 1/4 tsp baking soda + 1/2 tsp cream of tartar for every 1 tsp of baking powder needed.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Using baking soda without an acidic component in the batter', 'fix' => 'Without acid, baking soda fails to rise and leaves a soapy, bitter metallic aftertaste.'],
                    ['mistake' => 'Using expired leaveners that have lost potency', 'fix' => 'Test baking soda in a spoonful of vinegar (it should fizz violently). Test baking powder in warm water.'],
                ],
                'pro_tips' => [
                    'Recipes that call for BOTH leaveners use baking powder for primary rise and baking soda to neutralize excess acidity and boost browning (like chocolate chip cookies).',
                ],
                'problem_keywords' => ['substitute baking soda for baking powder', 'why is my cake metallic', 'baking leaveners', 'double acting baking powder'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'substitution',
                'related_tool_headline' => 'Missing an Essential Baking Ingredient?',
                'related_tool_text' => 'Out of buttermilk, cream of tartar, or baking powder? Find exact culinary ratios with our Ingredient Substitution Tool.',
                'faqs' => [
                    ['question' => 'How do I know if my baking powder is still good?', 'answer' => 'Stir 1/2 tsp baking powder into 1/4 cup hot water. If it fizzes vigorously immediately, it is still fresh.'],
                    ['question' => 'What happens if I use too much baking soda?', 'answer' => 'Excess soda creates a harsh metallic taste and causes baked goods to brown prematurely and collapse.'],
                ],
                'sources' => [
                    ['title' => 'King Arthur Baking Science Department', 'authority' => 'King Arthur Baking'],
                ],
            ],

            // 6. Convert Cups to Grams (Cooking Basics)
            [
                'category_id' => $catMap['cooking-basics'],
                'slug' => 'how-to-convert-cups-to-grams',
                'title' => 'How to Convert Cups to Grams (Flour, Sugar, Butter & More)',
                'meta_title' => 'Convert Cups to Grams: Master Kitchen Conversion Chart | TopicTrail',
                'meta_description' => 'The ultimate cups to grams conversion chart for home bakers. Accurate gram weights for flour, white sugar, brown sugar, butter, oats, and liquids.',
                'excerpt' => 'A cup is a unit of volume, not weight. A cup of fluffy flour weighs 120 grams while a cup of packed sugar weighs 200 grams. Master exact kitchen weight conversions.',
                'quick_answer' => 'Weight depends on the ingredient density: 1 cup of All-Purpose Flour = 120g; 1 cup of Granulated White Sugar = 200g; 1 cup of Brown Sugar (packed) = 213g; 1 cup of Butter = 227g (2 sticks); 1 cup of Water or Milk = 240g.',
                'what_you_need' => ['Digital kitchen scale', 'Measuring cups and spoons', 'Standard ingredients'],
                'content_sections' => [
                    [
                        'title' => 'Why Volume Measuring Leads to Baking Failures',
                        'body' => 'Scooping flour directly from the bag with a measuring cup compresses the powder. A scooped cup can weigh anywhere from 135g to 160g instead of the intended 120g, leading to dry, dense cakes and rubbery cookies. Professional bakers always weigh dry ingredients in grams.',
                    ],
                    [
                        'title' => 'Reference Weights for 1 Cup (Standard US 240ml Cup)',
                        'body' => 'All-Purpose Flour: 120g | Bread Flour: 125g | Cocoa Powder: 85g | Granulated Sugar: 200g | Powdered / Icing Sugar: 120g | Packed Brown Sugar: 213g | Unsalted Butter: 227g | Rolled Oats: 90g | Honey: 340g.',
                    ],
                    [
                        'title' => 'The Spoon & Sweep Method (If You Have No Scale)',
                        'body' => 'Fluff your flour with a fork in the container. Gently spoon it into your measuring cup until it heaps over the top. Then sweep across the rim with the flat back of a knife. Never tap or shake the cup.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Assuming 1 cup of flour equals 1 cup of sugar in grams', 'fix' => 'Sugar is denser than flour; 1 cup of sugar weighs nearly 70% more than 1 cup of flour.'],
                    ['mistake' => 'Scooping flour directly with the cup', 'fix' => 'Always fluff and spoon, or better yet, weigh on a digital kitchen scale.'],
                ],
                'pro_tips' => [
                    'A digital kitchen scale eliminates measuring cups, reduces dishwashing, and guarantees 100% reproducible baking outcomes.',
                ],
                'problem_keywords' => ['cups to grams', 'how many grams in a cup of flour', 'cup of butter in grams', 'baking conversions'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'converter',
                'related_tool_headline' => 'One-Click Cooking Converter',
                'related_tool_text' => 'Convert cups, tablespoons, fluid ounces, grams, and oven temperatures with our free Cooking Converter tool.',
                'faqs' => [
                    ['question' => 'How many grams is 1/2 cup of butter?', 'answer' => '1/2 cup of butter equals 1 stick or 113.5 grams (4 ounces).'],
                    ['question' => 'How many tablespoons are in 1 cup?', 'answer' => 'There are 16 tablespoons in 1 US cup (or 48 teaspoons).'],
                ],
                'sources' => [
                    ['title' => 'NIST Handbook 133 - Checking the Net Contents of Packaged Goods', 'authority' => 'NIST'],
                ],
            ],

            // 7. Substitute Butter (Ingredients)
            [
                'category_id' => $catMap['ingredients'],
                'slug' => 'how-to-substitute-butter-in-baking',
                'title' => 'How to Substitute Butter in Baking and Cooking (Best Alternatives)',
                'meta_title' => 'Best Butter Substitutes for Baking and Cooking | TopicTrail',
                'meta_description' => 'Out of butter? Learn how to replace butter with vegetable oil, Greek yogurt, applesauce, or coconut oil with exact culinary ratios.',
                'excerpt' => 'Whether you ran out of butter or need a dairy-free alternative, different substitutes work best depending on whether you are baking cakes, cookies, or sautéing.',
                'quick_answer' => 'For cakes and quick breads, substitute 1 cup of melted butter with 3/4 cup of neutral oil (vegetable or canola) for ultra-moist crumb. For reduced-calorie baking, swap 1:1 with unsweetened applesauce or plain Greek yogurt. For pie crusts or cookies where solid fat is needed, use chilled coconut oil in a 1:1 ratio.',
                'what_you_need' => ['Neutral vegetable oil or olive oil', 'Unsweetened applesauce', 'Plain Greek yogurt', 'Solid coconut oil'],
                'content_sections' => [
                    [
                        'title' => '1. Neutral Vegetable Oil (Best for Cakes and Muffins)',
                        'body' => 'Oil is 100% fat, whereas butter is roughly 80% fat and 18% water. Use 3/4 cup of oil for every 1 cup of butter called for. Oil remains liquid at room temperature, making cakes and brownies remarkably moist for days.',
                    ],
                    [
                        'title' => '2. Coconut Oil (Best for Cookies and Pie Crusts)',
                        'body' => 'Use refined coconut oil (to avoid a strong coconut flavor) in a 1:1 ratio. Because it solidifies when chilled, it mimics the creaming and structure-building properties of cold butter in doughs.',
                    ],
                    [
                        'title' => '3. Applesauce or Greek Yogurt (Best for Healthy Baking)',
                        'body' => 'Unsweetened applesauce provides moisture and natural sweetness with almost zero fat. Replace up to half the butter with applesauce to keep calories low without compromising tenderness.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Using oil 1:1 to replace solid butter in cookies', 'fix' => 'Cookies will spread into flat, greasy puddles. Use solid fat like coconut oil or margarine for cookies.'],
                    ['mistake' => 'Using extra virgin olive oil in delicate vanilla cakes', 'fix' => 'Strong olive notes will overpower subtle flavors. Use neutral canola or mild sunflower oil.'],
                ],
                'pro_tips' => [
                    'When replacing butter in pan searing, use olive oil combined with a touch of ghee for higher smoke tolerance without burning.',
                ],
                'problem_keywords' => ['butter substitute', 'out of butter what to use', 'replace butter in cookies', 'dairy free baking butter'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'substitution',
                'related_tool_headline' => 'Find Tested Ingredient Substitutions',
                'related_tool_text' => 'Explore hundreds of kitchen-tested alternatives with scaled ratios using our Ingredient Substitution Tool.',
                'faqs' => [
                    ['question' => 'Can I use margarine instead of butter?', 'answer' => 'Yes, standard stick margarine with at least 80% fat can be substituted 1:1 for butter in most recipes.'],
                    ['question' => 'How much oil replaces 1 stick of butter?', 'answer' => '1 stick of butter (1/2 cup) can be replaced by 6 tablespoons (3/8 cup) of neutral vegetable oil.'],
                ],
                'sources' => [
                    ['title' => 'The Culinary Institute of America - Baking and Pastry Fundamentals', 'authority' => 'CIA'],
                ],
            ],

            // 8. Fix Oversalted Soup (Kitchen Hacks)
            [
                'category_id' => $catMap['kitchen-hacks'],
                'slug' => 'how-to-fix-oversalted-soup',
                'title' => 'How to Fix an Oversalted Soup or Sauce (What Works vs. What Fails)',
                'meta_title' => 'How to Fix Oversalted Soup: Real Culinary Solutions | TopicTrail',
                'meta_description' => 'Did your hand slip on the salt shaker? Discover proven methods to rescue oversalted soups: dilution, acid counter-balancing, and the potato myth.',
                'excerpt' => 'Accidentally dumping too much salt can feel like a disaster. Learn real culinary chemistry techniques to rebalance the flavor profile and save your dish.',
                'quick_answer' => 'The most reliable way to fix an oversalted soup is dilution: add unsalted broth, water, or extra vegetables. Counter-balance the salt perception by adding a splash of acid (lemon juice or apple cider vinegar) or a dollop of fat/dairy (cream, sour cream, or butter). Note: dropping in a raw potato does not magically suck out salt.',
                'what_you_need' => ['Unsalted broth or water', 'Fresh lemon juice or apple cider vinegar', 'Heavy cream, coconut milk or butter', 'Additional unseasoned ingredients'],
                'content_sections' => [
                    [
                        'title' => 'Method 1: Dilution (The Only True Chemical Fix)',
                        'body' => 'Saltiness is a concentration ratio. The only way to physically lower sodium concentration is by increasing the volume. Add 1 to 2 cups of unsalted water or low-sodium stock. If the soup becomes too watery, add extra vegetables or cooked noodles and simmer briefly.',
                    ],
                    [
                        'title' => 'Method 2: Balance with Acid and Sweetness',
                        'body' => 'Our taste buds register contrast. Adding a teaspoon of fresh lemon juice, unseasoned rice vinegar, or a pinch of brown sugar distracts your palate and masks the sharp harshness of excess sodium without removing the salt itself.',
                    ],
                    [
                        'title' => 'Method 3: Smooth with Fat or Dairy',
                        'body' => 'Fat coats the tongue and dulls salinity receptors. Stir in a splash of heavy cream, a tablespoon of Greek yogurt, or a chunk of cold unsalted butter to transform an aggressively salty broth into a balanced, rich bisque.',
                    ],
                    [
                        'title' => 'The Raw Potato Myth Busted',
                        'body' => 'Generations of home cooks recommend simmering a raw potato to "soak up the salt." Scientific laboratory tests by food scientists have shown that while a potato absorbs liquid, it absorbs salt and water in the exact same proportion as the soup, leaving the salinity concentration identical.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Adding more spices hoping to overpower salt', 'fix' => 'This simply makes the soup chaotic and overwhelmingly harsh. Stick to acid or dilution.'],
                    ['mistake' => 'Relying on the potato trick and waiting 45 minutes', 'fix' => 'Dilute immediately with unsalted broth instead.'],
                ],
                'pro_tips' => [
                    'Always taste soup near the end of cooking. Liquids reduce as they simmer, naturally concentrating saltiness.',
                ],
                'problem_keywords' => ['my soup is too salty', 'oversalted sauce', 'fix salty chili', 'potato salt myth'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'converter',
                'related_tool_headline' => 'Calculate Liquid Ratios',
                'related_tool_text' => 'Scale broth and liquid volumes precisely with our free Cooking Converter.',
                'faqs' => [
                    ['question' => 'Does sugar neutralize salt?', 'answer' => 'Sugar does not remove salt, but it balances the taste on your tongue when used sparingly (1/2 to 1 teaspoon).'],
                ],
                'sources' => [
                    ['title' => 'Robert Wolke - What Einstein Told His Cook: Kitchen Science Explained', 'authority' => 'Food Chemistry'],
                ],
            ],

            // 9. Why Cakes Sink (Baking)
            [
                'category_id' => $catMap['baking'],
                'slug' => 'why-do-cakes-sink-in-the-middle',
                'title' => 'Why Do Cakes Sink in the Middle? (5 Causes & How to Fix Them)',
                'meta_title' => 'Why Do Cakes Sink in the Middle? 5 Fixes | TopicTrail',
                'meta_description' => 'Did your cake collapse in the center as it cooled? Learn the 5 main causes of sunken cakes: oven door opening, excess leavener, and underbaking.',
                'excerpt' => 'Pulling a cake out of the oven only to watch the center cave in is heartbreaking. Discover the 5 baking mistakes that cause sunken cakes and how to avoid them.',
                'quick_answer' => 'Cakes sink in the middle for three primary reasons: (1) Opening the oven door too early, which releases the heat before gluten sets; (2) Using too much baking powder or soda, which creates gas bubbles too large to support their own weight; or (3) Underbaking the center. Always test with a toothpick in the exact middle before removing from the oven.',
                'what_you_need' => ['Oven thermometer', 'Wooden toothpicks or cake tester', 'Kitchen scale for accurate leavener measurement'],
                'content_sections' => [
                    [
                        'title' => '1. Opening the Oven Door Too Early',
                        'body' => 'In the first 75% of baking time, cake batter is an unstable foam of expanding air bubbles trapped in liquefying starches. Opening the oven door causes a sudden rush of cool air, dropping the temperature and collapsing the fragile structure before proteins set.',
                    ],
                    [
                        'title' => '2. Too Much Baking Powder or Baking Soda',
                        'body' => 'More leavener does not mean a taller cake. Excess baking powder generates too much carbon dioxide too fast. The bubbles grow too large, merge, and burst, causing the cake to rise aggressively and then crater into itself.',
                    ],
                    [
                        'title' => '3. Underbaking (The Toothpick Test)',
                        'body' => 'The perimeter of a cake pan cooks significantly faster than the center. If you pull the cake when the edges look golden, the center starches may still be ungelatinized liquid. Always insert a toothpick into the dead center: it must come out clean or with a few moist crumbs.',
                    ],
                    [
                        'title' => '4. Inaccurate Oven Temperature',
                        'body' => 'Most home oven dials are off by 25°F to 50°F. If your oven runs cool, the cake will not bake through before the chemical leavener exhausts itself. Hang an inexpensive oven thermometer inside to verify actual temperature.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Slamming the oven door shut', 'fix' => 'Vibrations easily collapse delicate batter bubbles. Always close the oven door gently.'],
                    ['mistake' => 'Leaving batter sitting on the counter for 30 minutes before baking', 'fix' => 'Chemical leaveners activate immediately upon mixing. Bake promptly.'],
                ],
                'pro_tips' => [
                    'If a cake sinks slightly in the center, do not panic! Level it with a serrated knife, flip it upside down onto your cake board, and frost the flat bottom.',
                ],
                'problem_keywords' => ['my cake did not rise', 'cake collapsed in middle', 'sunken cake', 'why cake sinks in center'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'converter',
                'related_tool_headline' => 'Check Oven Temperatures',
                'related_tool_text' => 'Convert Fahrenheit, Celsius, and gas marks effortlessly with our Cooking Converter.',
                'faqs' => [
                    ['question' => 'How can I fix a cake that already sank?', 'answer' => 'Fill the indentation with whipped cream, berries, chocolate ganache, or lemon curd, or trim it flat.'],
                ],
                'sources' => [
                    ['title' => 'Shirley O. Corriher - Bakewise: The Hows and Whys of Successful Baking', 'authority' => 'Bakewise'],
                ],
            ],

            // 10. Prevent Pasta Sticking (Kitchen Hacks)
            [
                'category_id' => $catMap['kitchen-hacks'],
                'slug' => 'how-to-prevent-pasta-from-sticking',
                'title' => 'How to Prevent Pasta from Sticking Together (And Why Oil Fails)',
                'meta_title' => 'How to Prevent Pasta from Sticking Together | TopicTrail',
                'meta_description' => 'The authentic Italian way to cook pasta without sticking. Why adding oil to pasta water is a mistake, boiling water volume, and sauce emulsion tricks.',
                'excerpt' => 'Do your noodles clump into a giant gummy knot? Learn why olive oil in the pasta water ruins your sauce and the 3 scientific habits that guarantee silky pasta.',
                'quick_answer' => 'To prevent pasta from sticking: (1) Use plenty of rapidly boiling water (at least 4 quarts per pound of pasta), (2) Stir vigorously during the first 2 minutes when surface starches are most adhesive, and (3) Never rinse cooked pasta with water unless making a cold salad. Do not add oil to the cooking water, as it coats the noodles and prevents the sauce from adhering.',
                'what_you_need' => ['Large stockpot (at least 6-8 quarts)', 'Tongs or pasta fork', 'Colander', 'Kosher salt'],
                'content_sections' => [
                    [
                        'title' => 'Why Adding Oil to Pasta Water is a Mistake',
                        'body' => 'Many home cooks pour oil into their pasta water thinking it stops sticking. Because oil is less dense than water, it floats on top and does nothing while cooking. When you drain the pasta, the oil coats the noodles in a slick film, ensuring that your tomato or cream sauce slides right off to the bottom of the plate.',
                    ],
                    [
                        'title' => 'Rule 1: Use Plenty of Water at a Rolling Boil',
                        'body' => 'Pasta needs room to swim. When you dump dry pasta into too little water, the temperature drops dramatically and the concentration of released starch in the pot spikes. Use 4 to 6 quarts of water per pound of pasta and keep the heat high so water stays at an active rolling boil.',
                    ],
                    [
                        'title' => 'Rule 2: Stir Within the First 90 Seconds',
                        'body' => 'The moment dry pasta touches boiling water, the starch granules on the surface swell and turn into glue. If the strands touch each other during this initial gelatinization phase, they weld together. Stir thoroughly for the first 90 seconds until the surface starch dissolves into the water.',
                    ],
                    [
                        'title' => 'Rule 3: Finish Pasta in the Sauce with Starchy Water',
                        'body' => 'Drain pasta 1 to 2 minutes before al dente. Transfer directly into your simmering sauce pan with a ladle of the cloudy, starchy pasta cooking water. Toss over heat for 60 seconds; the starch creates a creamy emulsion that binds the sauce to the pasta.',
                    ],
                ],
                'common_mistakes' => [
                    ['mistake' => 'Rinsing cooked pasta under the tap', 'fix' => 'Rinsing strips away the warm starchy coating that sauce needs to cling to.'],
                    ['mistake' => 'Letting drained pasta sit in the colander for 10 minutes', 'fix' => 'Noodles will glue together rapidly as steam escapes. Always toss with sauce immediately.'],
                ],
                'pro_tips' => [
                    'Water should taste seasoned like the sea (about 1 to 2 tablespoons of kosher salt per 4 quarts). This is your only chance to flavor the inside of the pasta dough.',
                ],
                'problem_keywords' => ['my pasta is sticking together', 'clumpy spaghetti', 'oil in pasta water', 'how to cook pasta al dente'],
                'reading_time_minutes' => 4,
                'featured' => false,
                'related_tool_key' => 'nutrition',
                'related_tool_headline' => 'Calculate Your Pasta Macros',
                'related_tool_text' => 'Cooked pasta differs in calories and volume from dry pasta. Calculate exact portion macros with our free Nutrition Calculator.',
                'faqs' => [
                    ['question' => 'Should you ever rinse pasta?', 'answer' => 'Only when making a cold pasta salad to halt cooking and remove excess starch so it does not congeal cold.'],
                    ['question' => 'What does Al Dente mean?', 'answer' => 'Italian for "to the tooth". Pasta should be tender but retain a firm, slight resistance in the center when bitten.'],
                ],
                'sources' => [
                    ['title' => 'Marcella Hazan - Essentials of Classic Italian Cooking', 'authority' => 'Italian Culinary Standard'],
                ],
            ],
        ];

        foreach ($guides as $guideData) {
            $guide = CookingGuide::updateOrCreate(
                ['slug' => $guideData['slug']],
                $guideData + [
                    'is_published' => true,
                    'published_at' => $now,
                ]
            );

            // Connect relevant recipes if recipes exist in database
            if ($guide->slug === 'how-to-cook-rice-perfectly-every-time') {
                $riceRecipes = Recipe::where('name', 'LIKE', '%rice%')->limit(3)->pluck('id');
                $guide->recipes()->sync($riceRecipes);
            } elseif ($guide->slug === 'how-to-keep-chicken-breast-moist-and-tender') {
                $chickenRecipes = Recipe::where('name', 'LIKE', '%chicken%')->limit(3)->pluck('id');
                $guide->recipes()->sync($chickenRecipes);
            } elseif ($guide->slug === 'how-to-prevent-pasta-from-sticking') {
                $pastaRecipes = Recipe::where('name', 'LIKE', '%pasta%')->orWhere('name', 'LIKE', '%spaghetti%')->limit(3)->pluck('id');
                $guide->recipes()->sync($pastaRecipes);
            }
        }
    }
}
