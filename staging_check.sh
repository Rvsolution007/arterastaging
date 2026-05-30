#!/bin/bash
cd /var/www/stagingartera

echo "=== GIT PULL ==="
git pull origin staging 2>&1

echo ""
echo "=== FCM TOKENS IN DATABASE ==="
php artisan tinker --execute="echo \App\Models\AndroidLogin::count() . ' total tokens';" 2>&1

echo ""
echo "=== RECENT FCM LOGS ==="
grep -i 'fcm' storage/logs/laravel.log 2>/dev/null | tail -20

echo ""
echo "=== LAST 5 NOTIFICATION SENDS ==="
grep -i 'Broadcast complete' storage/logs/laravel.log 2>/dev/null | tail -5

echo "=== DONE ==="
