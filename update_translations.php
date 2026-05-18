<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppTranslation;

$newEn = [
    "custom_posts" => "Custom Posts", "category_posts" => "Category Posts", "festival_posts" => "Festival Posts",
    "magic_cloner" => "Magic Cloner", "daily_drip" => "Daily Drip", "bg_remover" => "BG Remover",
    "free" => "Free", "days_left" => "Days left", "valid" => "Valid", "usage_this_month" => "USAGE THIS MONTH",
    "view_plans" => "View Plans", "upgrade_plan" => "Upgrade Plan", "subscription_plans" => "Subscription Plans",
    "no_plans_available" => "No plans available", "all_packages" => "ALL PACKAGES", "monthly" => "Monthly",
    "yearly" => "Yearly", "save_more" => "SAVE MORE", "your_active_plan" => "Your Active Plan",
    "free_trial" => "Free Trial", "days" => "Days", "plan_limits_usage" => "PLAN LIMITS & USAGE",
    "1_year_plan" => "1 Year Plan", "1_month_plan" => "1 Month Plan", "included_features" => "INCLUDED FEATURES",
    "posts" => "Posts", "ad_only" => "Ad Only", "currently_active" => "Currently Active", "upgrade_to" => "Upgrade to"
];

$newHi = [
    "custom_posts" => "कस्टम पोस्ट", "category_posts" => "श्रेणी पोस्ट", "festival_posts" => "त्योहार पोस्ट",
    "magic_cloner" => "मैजिक क्लोनर", "daily_drip" => "दैनिक ड्रिप", "bg_remover" => "बीजी रिमूवर",
    "free" => "मुफ्त", "days_left" => "दिन बचे", "valid" => "मान्य", "usage_this_month" => "इस महीने का उपयोग",
    "view_plans" => "योजनाएं देखें", "upgrade_plan" => "योजना अपग्रेड करें", "subscription_plans" => "सदस्यता योजनाएं",
    "no_plans_available" => "कोई योजना उपलब्ध नहीं है", "all_packages" => "सभी पैकेज", "monthly" => "मासिक",
    "yearly" => "वार्षिक", "save_more" => "अधिक बचाएं", "your_active_plan" => "आपकी सक्रिय योजना",
    "free_trial" => "नि: शुल्क परीक्षण", "days" => "दिन", "plan_limits_usage" => "योजना सीमाएं और उपयोग",
    "1_year_plan" => "१ वर्ष की योजना", "1_month_plan" => "१ महीने की योजना", "included_features" => "शामिल विशेषताएं",
    "posts" => "पोस्ट", "ad_only" => "केवल विज्ञापन", "currently_active" => "वर्तमान में सक्रिय", "upgrade_to" => "में अपग्रेड करें"
];

$newGu = [
    "custom_posts" => "કસ્ટમ પોસ્ટ્સ", "category_posts" => "કેટેગરી પોસ્ટ્સ", "festival_posts" => "તહેવાર પોસ્ટ્સ",
    "magic_cloner" => "મેજિક ક્લોનર", "daily_drip" => "દૈનિક ડ્રિપ", "bg_remover" => "બીજી રીમૂવર",
    "free" => "મફત", "days_left" => "દિવસો બાકી", "valid" => "માન્ય", "usage_this_month" => "આ મહિનાનો ઉપયોગ",
    "view_plans" => "યોજનાઓ જુઓ", "upgrade_plan" => "યોજના અપગ્રેડ કરો", "subscription_plans" => "સબ્સ્ક્રિપ્શન યોજનાઓ",
    "no_plans_available" => "કોઈ યોજનાઓ ઉપલબ્ધ નથી", "all_packages" => "બધા પેકેજો", "monthly" => "માસિક",
    "yearly" => "વાર્ષિક", "save_more" => "વધુ બચાવો", "your_active_plan" => "તમારી સક્રિય યોજના",
    "free_trial" => "મફત અજમાયશ", "days" => "દિવસો", "plan_limits_usage" => "યોજના મર્યાદાઓ અને ઉપયોગ",
    "1_year_plan" => "૧ વર્ષની યોજના", "1_month_plan" => "૧ મહિનાની યોજના", "included_features" => "શામેલ સુવિધાઓ",
    "posts" => "પોસ્ટ્સ", "ad_only" => "માત્ર જાહેરાત", "currently_active" => "હાલમાં સક્રિય", "upgrade_to" => "માં અપગ્રેડ કરો"
];

$newMr = [
    "custom_posts" => "कस्टम पोस्ट", "category_posts" => "श्रेणी पोस्ट", "festival_posts" => "उत्सव पोस्ट",
    "magic_cloner" => "मॅजिक क्लोनर", "daily_drip" => "दैनिक ड्रिप", "bg_remover" => "बीजी रिमूव्हर",
    "free" => "मोफत", "days_left" => "दिवस शिल्लक", "valid" => "वैध", "usage_this_month" => "या महिन्याचा वापर",
    "view_plans" => "योजना पहा", "upgrade_plan" => "योजना अपग्रेड करा", "subscription_plans" => "सदस्यता योजना",
    "no_plans_available" => "कोणतीही योजना उपलब्ध नाही", "all_packages" => "सर्व पॅकेज", "monthly" => "मासिक",
    "yearly" => "वार्षिक", "save_more" => "अधिक बचत करा", "your_active_plan" => "तुमची सक्रिय योजना",
    "free_trial" => "विनामूल्य चाचणी", "days" => "दिवस", "plan_limits_usage" => "योजना मर्यादा आणि वापर",
    "1_year_plan" => "१ वर्षाची योजना", "1_month_plan" => "१ महिन्याची योजना", "included_features" => "समाविष्ट वैशिष्ट्ये",
    "posts" => "पोस्ट", "ad_only" => "केवळ जाहिरात", "currently_active" => "सध्या सक्रिय", "upgrade_to" => "मध्ये श्रेणीसुधारित करा"
];

$langs = ["en" => $newEn, "hi" => $newHi, "gu" => $newGu, "mr" => $newMr];

foreach($langs as $code => $newDict) {
    $record = AppTranslation::where("language_code", $code)->first();
    if($record) {
        $existing = is_array($record->translations) ? $record->translations : json_decode($record->translations, true);
        if(!$existing) $existing = [];
        $merged = array_merge($existing, $newDict);
        $record->translations = $merged;
        $record->save();
        echo "Updated $code\n";
    }
}
echo "Done.\n";

