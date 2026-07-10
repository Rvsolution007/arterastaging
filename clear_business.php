<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$business = \App\Models\Business::find(25);
if ($business) {
    echo "Before:\n";
    echo "Address: " . $business->address . "\n";
    echo "Website: " . $business->website . "\n";

    // Set to null to simulate user only adding mobile and email
    $business->address = '';
    $business->website = '';
    $business->save();

    echo "After:\n";
    echo "Address: " . $business->address . "\n";
    echo "Website: " . $business->website . "\n";
} else {
    echo "Business not found\n";
}
