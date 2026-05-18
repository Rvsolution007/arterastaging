<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppTranslation;

$newEn = [
    "checkout" => "Checkout", "enter_coupon_code" => "Enter Coupon Code", "apply" => "Apply",
    "total_payable" => "Total Payable", "get_plan_for_free" => "Get Plan for Free", "pay_with_razorpay" => "Pay with Razorpay",
    "plan" => "Plan"
];

$newHi = [
    "checkout" => "चेकआउट", "enter_coupon_code" => "कूपन कोड दर्ज करें", "apply" => "लागू करें",
    "total_payable" => "कुल देय", "get_plan_for_free" => "मुफ्त में योजना प्राप्त करें", "pay_with_razorpay" => "रेजरपे से भुगतान करें",
    "plan" => "योजना"
];

$newGu = [
    "checkout" => "ચેકઆઉટ", "enter_coupon_code" => "કૂપન કોડ દાખલ કરો", "apply" => "લાગુ કરો",
    "total_payable" => "કુલ ચૂકવવાપાત્ર", "get_plan_for_free" => "મફતમાં યોજના મેળવો", "pay_with_razorpay" => "રેઝરપે દ્વારા ચૂકવણી કરો",
    "plan" => "યોજના"
];

$newMr = [
    "checkout" => "चेकआउट", "enter_coupon_code" => "कूपन कोड प्रविष्ट करा", "apply" => "लागू करा",
    "total_payable" => "एकूण देय", "get_plan_for_free" => "विनामूल्य योजना मिळवा", "pay_with_razorpay" => "रेझरपेसह पैसे द्या",
    "plan" => "योजना"
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

