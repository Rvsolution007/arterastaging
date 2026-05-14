<?php

namespace App\Http\Middleware;

use Closure;

class IsInstalled
{
    public function handle($request, Closure $next)
    {
        if (!$this->alreadyInstalled()) {
            return redirect("installation");
        }

        return $next($request);
    }

    public function alreadyInstalled()
    {
        return (file_exists(storage_path('installed')) && file_get_contents(storage_path('installed')) == "brand_kit" || file_get_contents(storage_path('installed')) == "brand_kit1" || file_get_contents(storage_path('installed')) == "brand_kit2" || file_get_contents(storage_path('installed')) == "brand_kit3" || file_get_contents(storage_path('installed')) == "brand_kit4" || file_get_contents(storage_path('installed')) == "brand_kit5" || file_get_contents(storage_path('installed')) == "brand_kit6" || file_get_contents(storage_path('installed')) == "brand_kit7" || file_get_contents(storage_path('installed')) == "brand_kit8" || file_get_contents(storage_path('installed')) == "brand_kit9");
    }
}
