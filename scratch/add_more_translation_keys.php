<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$newKeys = [
    // --- home_screen.dart ---
    'update_business' => [
        'en' => 'Update Business',
        'hi' => 'व्यापार अपडेट करें',
        'gu' => 'વ્યાપાર અપડેટ કરો',
        'mr' => 'व्यवसाय अपडेट करा',
    ],
    'free' => [
        'en' => 'Free',
        'hi' => 'मुफ्त',
        'gu' => 'મફત',
        'mr' => 'मोफत',
    ],
    'quick_start' => [
        'en' => 'Quick Start',
        'hi' => 'त्वरित प्रारंभ',
        'gu' => 'ઝડપી શરૂઆત',
        'mr' => 'त्वरित प्रारंभ',
    ],
    'search_categories_festivals' => [
        'en' => 'Search categories, festivals...',
        'hi' => 'श्रेणियां, त्योहार खोजें...',
        'gu' => 'શ્રેણીઓ, તહેવારો શોધો...',
        'mr' => 'श्रेण्या, सण शोधा...',
    ],
    'story' => [
        'en' => 'Story',
        'hi' => 'स्टोरी',
        'gu' => 'સ્ટોરી',
        'mr' => 'स्टोरी',
    ],
    'upcoming_festivals' => [
        'en' => 'Upcoming Festivals',
        'hi' => 'आगामी त्योहार',
        'gu' => 'આગામી તહેવારો',
        'mr' => 'आगामी सण',
    ],
    'no_festivals_found' => [
        'en' => 'No festivals found',
        'hi' => 'कोई त्योहार नहीं मिला',
        'gu' => 'કોઈ તહેવાર મળ્યો નથી',
        'mr' => 'कोणतेही सण आढळले नाहीत',
    ],
    'festival' => [
        'en' => 'Festival',
        'hi' => 'त्योहार',
        'gu' => 'તહેવાર',
        'mr' => 'सण',
    ],
    'no_categories_found' => [
        'en' => 'No categories found',
        'hi' => 'कोई श्रेणी नहीं मिली',
        'gu' => 'કોઈ શ્રેણી મળી નથી',
        'mr' => 'कोणत्याही श्रेण्या आढळल्या नाहीत',
    ],
    'category' => [
        'en' => 'Category',
        'hi' => 'श्रेणी',
        'gu' => 'શ્રેણી',
        'mr' => 'श्रेणी',
    ],
    'new_custom_posts' => [
        'en' => 'New Custom Posts',
        'hi' => 'नई कस्टम पोस्ट',
        'gu' => 'નવી કસ્ટમ પોસ્ટ્સ',
        'mr' => 'नवीन कस्टम पोस्ट',
    ],
    'view_all' => [
        'en' => 'View All',
        'hi' => 'सभी देखें',
        'gu' => 'બધા જુઓ',
        'mr' => 'सर्व पहा',
    ],
    'usage_update' => [
        'en' => 'Usage Update',
        'hi' => 'उपयोग अपडेट',
        'gu' => 'ઉપયોગ અપડેટ',
        'mr' => 'वापर अपडेट',
    ],
    'custom_posts_used' => [
        'en' => 'Custom Posts used.',
        'hi' => 'कस्टम पोस्ट उपयोग किए गए।',
        'gu' => 'કસ્ટમ પોસ્ટ્સ ઉપયોગમાં લેવાય છે.',
        'mr' => 'कस्टम पोस्ट वापरले गेले.',
    ],
    'news_updates' => [
        'en' => 'News Updates',
        'hi' => 'समाचार अपडेट',
        'gu' => 'સમાચાર અપડેટ્સ',
        'mr' => 'बातम्या अपडेट्स',
    ],
    'videos' => [
        'en' => 'Videos',
        'hi' => 'वीडियो',
        'gu' => 'વિડિઓઝ',
        'mr' => 'व्हिडिओ',
    ],

    // --- template_grid_screen.dart ---
    'custom_templates' => [
        'en' => 'Custom Templates',
        'hi' => 'कस्टम टेम्प्लेट',
        'gu' => 'કસ્ટમ નમૂનાઓ',
        'mr' => 'कस्टम टेम्पलेट्स',
    ],
    'quick' => [
        'en' => 'Quick',
        'hi' => 'त्वरित',
        'gu' => 'ઝડપી',
        'mr' => 'त्वरित',
    ],
    'search_templates_categories' => [
        'en' => 'Search templates, categories...',
        'hi' => 'टेम्पलेट, श्रेणियां खोजें...',
        'gu' => 'નમૂનાઓ, શ્રેણીઓ શોધો...',
        'mr' => 'टेम्पलेट्स, श्रेण्या शोधा...',
    ],
    'create_something_new' => [
        'en' => 'Create Something New',
        'hi' => 'कुछ नया बनाएं',
        'gu' => 'કંઈક નવું બનાવો',
        'mr' => 'काहीतरी नवीन तयार करा',
    ],
    'post' => [
        'en' => 'Post',
        'hi' => 'पोस्ट',
        'gu' => 'પોસ્ટ',
        'mr' => 'पोस्ट',
    ],
    'ads_infographics' => [
        'en' => 'Ads/Infographics',
        'hi' => 'विज्ञापन/इन्फोग्राफिक्स',
        'gu' => 'જાહેરાતો/ઇન્ફોગ્રાફિક્સ',
        'mr' => 'जाहिराती/इन्फोग्राफिक्स',
    ],
    'new_posts' => [
        'en' => 'New Posts',
        'hi' => 'नई पोस्ट',
        'gu' => 'નવી પોસ્ટ્સ',
        'mr' => 'नवीन पोस्ट',
    ],
    'no_templates_yet' => [
        'en' => 'No templates yet',
        'hi' => 'अभी कोई टेम्पलेट नहीं',
        'gu' => 'હજી કોઈ નમૂનાઓ નથી',
        'mr' => 'अद्याप कोणतेही टेम्पलेट नाही',
    ],
    'templates' => [
        'en' => 'Templates',
        'hi' => 'टेम्पलेट',
        'gu' => 'નમૂનાઓ',
        'mr' => 'टेम्पलेट्स',
    ],

    // --- my_business_screen.dart ---
    'edit' => [
        'en' => 'EDIT',
        'hi' => 'संपादित करें',
        'gu' => 'સંપાદિત કરો',
        'mr' => 'संपादित करा',
    ],
    'ai_setup' => [
        'en' => 'AI Setup',
        'hi' => 'एआई सेटअप',
        'gu' => 'એઆઈ સેટઅપ',
        'mr' => 'एआय सेटअप',
    ],
    'products' => [
        'en' => 'Products',
        'hi' => 'उत्पाद',
        'gu' => 'ઉત્પાદનો',
        'mr' => 'उत्पादने',
    ],
    'catalogue_setting' => [
        'en' => 'Catalogue Setting',
        'hi' => 'कैटलॉग सेटिंग',
        'gu' => 'કૅટેલોગ સેટિંગ',
        'mr' => 'कॅटलॉग सेटिंग',
    ],
    'frames' => [
        'en' => 'Frames',
        'hi' => 'फ्रेम्स',
        'gu' => 'ફ્રેમ્સ',
        'mr' => 'फ्रेम्स',
    ],
    'my_businesses' => [
        'en' => 'My Businesses',
        'hi' => 'मेरे व्यापार',
        'gu' => 'મારા વ્યાપારો',
        'mr' => 'माझे व्यवसाय',
    ],
    'downloads' => [
        'en' => 'Downloads',
        'hi' => 'डाउनलोड',
        'gu' => 'ડાઉનલોડ્સ',
        'mr' => 'डाउनलोड्स',
    ],

    // --- ai_trends_screen.dart ---
    'new' => [
        'en' => 'NEW',
        'hi' => 'नया',
        'gu' => 'નવું',
        'mr' => 'नवीन',
    ],
    'business_special' => [
        'en' => 'Business Special',
        'hi' => 'व्यापार विशेष',
        'gu' => 'વ્યાપાર વિશેષ',
        'mr' => 'व्यवसाय विशेष',
    ],
    'reels_maker' => [
        'en' => 'Reels Maker',
        'hi' => 'रील्स मेकर',
        'gu' => 'રીલ્સ મેકર',
        'mr' => 'रील्स मेकर',
    ],
    'hot' => [
        'en' => 'HOT',
        'hi' => 'हॉट',
        'gu' => 'હોટ',
        'mr' => 'हॉट',
    ],
];

$langs = \App\Models\AppTranslation::where('status', 1)->get();

foreach ($langs as $lang) {
    $code = $lang->language_code;
    $translations = $lang->translations ?? [];
    if (is_string($translations)) {
        $translations = json_decode($translations, true) ?? [];
    }

    $updated = false;
    foreach ($newKeys as $key => $values) {
        if (!isset($translations[$key]) && isset($values[$code])) {
            $translations[$key] = $values[$code];
            $updated = true;
        }
    }

    if ($updated) {
        $lang->translations = $translations;
        $lang->save();
        echo "Updated {$lang->title} ({$code}) - now has " . count($translations) . " keys\n";
    } else {
        echo "No update needed for {$lang->title} ({$code})\n";
    }
}

echo "Done! Secondary translation keys added.\n";
