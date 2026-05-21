<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppTranslation;

class AppLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $englishTranslations = [
            "home" => "Home",
            "categories" => "Categories",
            "search_categories" => "Search categories...",
            "my_business" => "My Business",
            "settings" => "Settings",
            "logout" => "Logout",
            "app_language" => "App Language",
            "select_language" => "Select Language",
            "custom_posts" => "Custom Posts",
            "general_posts" => "General Posts",
            "subscription_plans" => "Subscription Plans",
            "more" => "More",
            "profile" => "Profile",
            "edit_profile" => "Edit Profile",
            "save" => "Save",
            "cancel" => "Cancel",
            "delete" => "Delete",
            "are_you_sure" => "Are you sure?",
            "yes" => "Yes",
            "no" => "No",
            "success" => "Success",
            "error" => "Error",
            "premium" => "Premium",
            "free" => "Free",
            "download" => "Download",
            "share" => "Share",
            "whatsapp" => "WhatsApp",
            "facebook" => "Facebook",
            "instagram" => "Instagram",
            "twitter" => "Twitter",
            "choose_plan" => "Choose Plan",
            "upgrade" => "Upgrade",
            "active" => "Active",
            "expired" => "Expired",
        ];

        $hindiTranslations = [
            "home" => "होम",
            "categories" => "श्रेणियां",
            "search_categories" => "श्रेणियां खोजें...",
            "my_business" => "मेरा व्यापार",
            "settings" => "सेटिंग्स",
            "logout" => "लॉगआउट",
            "app_language" => "ऐप की भाषा",
            "select_language" => "भाषा चुनें",
            "custom_posts" => "कस्टम पोस्ट",
            "general_posts" => "सामान्य पोस्ट",
            "subscription_plans" => "सब्सक्रिप्शन प्लान",
            "more" => "अधिक",
            "profile" => "प्रोफ़ाइल",
            "edit_profile" => "प्रोफ़ाइल संपादित करें",
            "save" => "सहेजें",
            "cancel" => "रद्द करें",
            "delete" => "हटाएं",
            "are_you_sure" => "क्या आप निश्चित हैं?",
            "yes" => "हां",
            "no" => "नहीं",
            "success" => "सफलता",
            "error" => "त्रुटि",
            "premium" => "प्रीमियम",
            "free" => "मुफ़्त",
            "download" => "डाउनलोड करें",
            "share" => "शेयर करें",
            "whatsapp" => "व्हाट्सएप",
            "facebook" => "फेसबुक",
            "instagram" => "इंस्टाग्राम",
            "twitter" => "ट्विटर",
            "choose_plan" => "प्लान चुनें",
            "upgrade" => "अपग्रेड करें",
            "active" => "सक्रिय",
            "expired" => "समाप्त",
        ];

        $gujaratiTranslations = [
            "home" => "હોમ",
            "categories" => "શ્રેણીઓ",
            "search_categories" => "શ્રેણીઓ શોધો...",
            "my_business" => "મારો વ્યવસાય",
            "settings" => "સેટિંગ્સ",
            "logout" => "લોગઆઉટ",
            "app_language" => "એપની ભાષા",
            "select_language" => "ભાષા પસંદ કરો",
            "custom_posts" => "કસ્ટમ પોસ્ટ્સ",
            "general_posts" => "સામાન્ય પોસ્ટ્સ",
            "subscription_plans" => "સબ્સ્ક્રિપ્શન પ્લાન",
            "more" => "વધુ",
            "profile" => "પ્રોફાઇલ",
            "edit_profile" => "પ્રોફાઇલ સંપાદિત કરો",
            "save" => "સાચવો",
            "cancel" => "રદ કરો",
            "delete" => "કાઢી નાખો",
            "are_you_sure" => "શું તમે ચોક્કસ છો?",
            "yes" => "હા",
            "no" => "ના",
            "success" => "સફળતા",
            "error" => "ભૂલ",
            "premium" => "પ્રીમિયમ",
            "free" => "મફત",
            "download" => "ડાઉનલોડ કરો",
            "share" => "શેર કરો",
            "whatsapp" => "વોટ્સએપ",
            "facebook" => "ફેસબુક",
            "instagram" => "ઇન્સ્ટાગ્રામ",
            "twitter" => "ટ્વિટર",
            "choose_plan" => "પ્લાન પસંદ કરો",
            "upgrade" => "અપગ્રેડ કરો",
            "active" => "સક્રિય",
            "expired" => "સમાપ્ત",
        ];

        $marathiTranslations = [
            "home" => "होम",
            "categories" => "श्रेणी",
            "search_categories" => "श्रेणी शोधा...",
            "my_business" => "माझा व्यवसाय",
            "settings" => "सेटिंग्ज",
            "logout" => "लॉगआउट",
            "app_language" => "अॅपची भाषा",
            "select_language" => "भाषा निवडा",
            "custom_posts" => "कस्टम पोस्ट",
            "general_posts" => "सामान्य पोस्ट",
            "subscription_plans" => "सबस्क्रिप्शन प्लॅन",
            "more" => "अधिक",
            "profile" => "प्रोफाइल",
            "edit_profile" => "प्रोफाइल संपादित करा",
            "save" => "जतन करा",
            "cancel" => "रद्द करा",
            "delete" => "हटवा",
            "are_you_sure" => "तुला खात्री आहे का?",
            "yes" => "होय",
            "no" => "नाही",
            "success" => "यश",
            "error" => "त्रुटी",
            "premium" => "प्रीमियम",
            "free" => "मोफत",
            "download" => "डाउनलोड करा",
            "share" => "शेअर करा",
            "whatsapp" => "व्हॉट्सअॅप",
            "facebook" => "फेसबुक",
            "instagram" => "इन्स्टाग्राम",
            "twitter" => "ट्विटर",
            "choose_plan" => "प्लॅन निवडा",
            "upgrade" => "अपग्रेड करा",
            "active" => "सक्रिय",
            "expired" => "कालबाह्य",
        ];

        AppTranslation::updateOrCreate(['language_code' => 'en'], [
            'title' => 'English',
            'status' => 1,
            'translations' => $englishTranslations
        ]);

        AppTranslation::updateOrCreate(['language_code' => 'hi'], [
            'title' => 'Hindi (हिंदी)',
            'status' => 1,
            'translations' => $hindiTranslations
        ]);

        AppTranslation::updateOrCreate(['language_code' => 'gu'], [
            'title' => 'Gujarati (ગુજરાતી)',
            'status' => 1,
            'translations' => $gujaratiTranslations
        ]);

        AppTranslation::updateOrCreate(['language_code' => 'mr'], [
            'title' => 'Marathi (मराठी)',
            'status' => 1,
            'translations' => $marathiTranslations
        ]);
    }
}
