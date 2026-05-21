<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// New keys to add for full app translation
$newKeys = [
    'preferred_languages' => [
        'en' => 'Preferred Languages',
        'hi' => 'पसंदीदा भाषाएं',
        'gu' => 'પસંદગીની ભાષાઓ',
        'mr' => 'पसंतीच्या भाषा',
    ],
    'business_settings' => [
        'en' => 'Business Settings',
        'hi' => 'व्यापार सेटिंग्स',
        'gu' => 'બિઝનેસ સેટિંગ્સ',
        'mr' => 'व्यवसाय सेटिंग्ज',
    ],
    'app_preferences' => [
        'en' => 'App Preferences',
        'hi' => 'ऐप प्राथमिकताएं',
        'gu' => 'એપ પસંદગીઓ',
        'mr' => 'अॅप प्राधान्ये',
    ],
    'notifications' => [
        'en' => 'Notifications',
        'hi' => 'सूचनाएं',
        'gu' => 'સૂચનાઓ',
        'mr' => 'सूचना',
    ],
    'about_app' => [
        'en' => 'About App',
        'hi' => 'ऐप के बारे में',
        'gu' => 'એપ વિશે',
        'mr' => 'अॅपबद्दल',
    ],
    'help_support' => [
        'en' => 'Help & Support',
        'hi' => 'सहायता और समर्थन',
        'gu' => 'મદદ અને સહાય',
        'mr' => 'मदत आणि समर्थन',
    ],
    'faqs' => [
        'en' => 'FAQs',
        'hi' => 'सामान्य प्रश्न',
        'gu' => 'વારંવાર પૂછાતા પ્રશ્નો',
        'mr' => 'वारंवार विचारले जाणारे प्रश्न',
    ],
    'blog' => [
        'en' => 'Blog',
        'hi' => 'ब्लॉग',
        'gu' => 'બ્લોગ',
        'mr' => 'ब्लॉग',
    ],
    'report_problem' => [
        'en' => 'Report a Problem',
        'hi' => 'समस्या रिपोर्ट करें',
        'gu' => 'સમસ્યાની જાણ કરો',
        'mr' => 'समस्या कळवा',
    ],
    'privacy_policy' => [
        'en' => 'Privacy Policy',
        'hi' => 'गोपनीयता नीति',
        'gu' => 'ગોપનીયતા નીતિ',
        'mr' => 'गोपनीयता धोरण',
    ],
    'terms_conditions' => [
        'en' => 'Terms & Conditions',
        'hi' => 'नियम और शर्तें',
        'gu' => 'નિયમો અને શરતો',
        'mr' => 'अटी आणि शर्ती',
    ],
    'refund_policy' => [
        'en' => 'Refund Policy',
        'hi' => 'रिफंड नीति',
        'gu' => 'રિફંડ નીતિ',
        'mr' => 'परतावा धोरण',
    ],
    'partner_program' => [
        'en' => 'Partner Program',
        'hi' => 'पार्टनर प्रोग्राम',
        'gu' => 'પાર્ટનર પ્રોગ્રામ',
        'mr' => 'पार्टनर प्रोग्राम',
    ],
    'partner_dashboard' => [
        'en' => 'Partner Dashboard',
        'hi' => 'पार्टनर डैशबोर्ड',
        'gu' => 'પાર્ટનર ડેશબોર્ડ',
        'mr' => 'पार्टनर डॅशबोर्ड',
    ],
    'view_earnings' => [
        'en' => 'View earnings & request withdrawal',
        'hi' => 'कमाई देखें और निकासी अनुरोध करें',
        'gu' => 'કમાણી જુઓ અને ઉપાડની વિનંતી કરો',
        'mr' => 'कमाई पहा आणि पैसे काढण्याची विनंती करा',
    ],
    'usage_this_month' => [
        'en' => 'USAGE THIS MONTH',
        'hi' => 'इस महीने का उपयोग',
        'gu' => 'આ મહિનાનો ઉપયોગ',
        'mr' => 'या महिन्याचा वापर',
    ],
    'upgrade_plan' => [
        'en' => 'Upgrade Plan',
        'hi' => 'प्लान अपग्रेड करें',
        'gu' => 'પ્લાન અપગ્રેડ કરો',
        'mr' => 'प्लॅन अपग्रेड करा',
    ],
    'view_plans' => [
        'en' => 'View Plans',
        'hi' => 'प्लान देखें',
        'gu' => 'પ્લાન જુઓ',
        'mr' => 'प्लॅन पहा',
    ],
    'custom' => [
        'en' => 'Custom',
        'hi' => 'कस्टम',
        'gu' => 'કસ્ટમ',
        'mr' => 'कस्टम',
    ],
    'business' => [
        'en' => 'Business',
        'hi' => 'बिज़नेस',
        'gu' => 'બિઝનેસ',
        'mr' => 'बिझनेस',
    ],
    'ai_trends' => [
        'en' => 'AI Trends',
        'hi' => 'AI ट्रेंड्स',
        'gu' => 'AI ટ્રેન્ડ્સ',
        'mr' => 'AI ट्रेंड्स',
    ],
    'daily_drip' => [
        'en' => 'Daily Drip',
        'hi' => 'डेली ड्रिप',
        'gu' => 'ડેઇલી ડ્રિપ',
        'mr' => 'डेली ड्रिप',
    ],
    'magic_cloner' => [
        'en' => 'Magic Cloner',
        'hi' => 'मैजिक क्लोनर',
        'gu' => 'મેજિક ક્લોનર',
        'mr' => 'मॅजिक क्लोनर',
    ],
    'festival_posts' => [
        'en' => 'Festival Posts',
        'hi' => 'त्योहार पोस्ट',
        'gu' => 'તહેવાર પોસ્ટ',
        'mr' => 'सणांच्या पोस्ट',
    ],
    'category_posts' => [
        'en' => 'Category Posts',
        'hi' => 'श्रेणी पोस्ट',
        'gu' => 'શ્રેણી પોસ્ટ',
        'mr' => 'श्रेणी पोस्ट',
    ],
    'days_left' => [
        'en' => 'days left',
        'hi' => 'दिन शेष',
        'gu' => 'દિવસ બાકી',
        'mr' => 'दिवस शिल्लक',
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
        echo "Updated {$lang->title} ({$code}) - now has " . count($translations) . " keys" . PHP_EOL;
    } else {
        echo "No update needed for {$lang->title} ({$code})" . PHP_EOL;
    }
}

echo "\nDone! All translation keys updated.\n";
