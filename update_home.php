<?php
$file = 'resources/views/landing/home.blade.php';
$content = file_get_contents($file);

$insertHtml = <<<HTML

{{-- ============================================
   SEARCH & FESTIVAL CALENDAR SECTION
   ============================================ --}}
<section class="calendar-section" style="padding: 64px 0; background: #fafafa;">
    <div class="container-full">
        <!-- Search Bar -->
        <div class="search-container" style="max-width: 800px; margin: 0 auto 48px; position: relative;">
            <div class="search-box" style="position: relative;">
                <input type="text" id="templateSearch" placeholder="Search over 7,00,000 templates..." style="width: 100%; padding: 18px 24px 18px 56px; border-radius: 50px; border: 1px solid #ddd; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.3s;" autocomplete="off">
                <i class="fas fa-search" style="position: absolute; left: 24px; top: 50%; transform: translateY(-50%); color: #888; font-size: 18px;"></i>
                <button id="searchBtn" class="btn-sharp blue" style="position: absolute; right: 8px; top: 8px; padding: 10px 24px; border-radius: 40px; border: none; font-weight: 600;">Search</button>
            </div>
            <!-- AJAX Results Dropdown -->
            <div id="searchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 8px; z-index: 100; max-height: 400px; overflow-y: auto; padding: 16px;">
                <div id="searchResultsContent"></div>
            </div>
        </div>

        <!-- Festival Calendar -->
        <div class="calendar-header" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h2 class="heading-lg" style="font-size: 24px; display: flex; align-items: center; gap: 12px;">
                <i class="far fa-calendar-alt" style="color: var(--blue);"></i> Festival Calendar {{ date('Y') }}
            </h2>
            <a href="{{ route('seo.festival_hub') }}" style="color: var(--blue); font-weight: 500; text-decoration: none;">View more</a>
        </div>

        @if(!empty(\$festivalsByDate))
        <div class="calendar-tabs" style="display: flex; gap: 12px; margin-bottom: 32px; overflow-x: auto; padding-bottom: 8px;">
            @php \$i = 0; @endphp
            @foreach(\$festivalsByDate as \$key => \$data)
            <button class="calendar-tab {{ \$i == 0 ? 'active' : '' }}" data-target="cal-date-{{ Str::slug(\$key) }}" style="padding: 10px 24px; border-radius: 40px; border: 1px solid {{ \$i == 0 ? 'var(--blue)' : '#ddd' }}; background: {{ \$i == 0 ? 'var(--blue)' : '#fff' }}; color: {{ \$i == 0 ? '#fff' : '#333' }}; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.3s;">
                {{ \$data['date_string'] }}
            </button>
            @php \$i++; @endphp
            @endforeach
        </div>

        <div class="calendar-content">
            @php \$i = 0; @endphp
            @foreach(\$festivalsByDate as \$key => \$data)
            <div class="calendar-pane {{ \$i == 0 ? 'active' : '' }}" id="cal-date-{{ Str::slug(\$key) }}" style="display: {{ \$i == 0 ? 'block' : 'none' }};">
                <div class="templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 24px;">
                    @foreach(\$data['festivals'] as \$f)
                    <a href="{{ route('seo.festival', ['festivalSlug' => Str::slug(\$f->title)]) }}" class="template-card" style="display: block; text-decoration: none; border-radius: 16px; overflow: hidden; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.3s;">
                        <div class="template-img" style="aspect-ratio: 1/1; overflow: hidden; background: #f0f0f0;">
                            @php
                                \$imgSrc = \$f->image ? (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean' ? Storage::disk('spaces')->url('uploads/'.\$f->image) : asset('uploads/'.\$f->image)) : '';
                            @endphp
                            <img src="{{ \$imgSrc }}" alt="{{ \$f->title }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                        </div>
                        <div class="template-info" style="padding: 16px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #111; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ \$f->title }}</h3>
                            <p style="font-size: 13px; color: #666; margin: 0;">{{ \Carbon\Carbon::parse(\$f->festivals_date)->format('j F, Y') }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @php \$i++; @endphp
            @endforeach
        </div>
        @endif
    </div>
</section>
HTML;

$searchStr = '</section>';
$pos = strpos($content, $searchStr);
if ($pos !== false) {
    $content = substr_replace($content, "</section>\n" . $insertHtml, $pos, strlen($searchStr));
    file_put_contents($file, $content);
    echo "Inserted HTML successfully.\n";
} else {
    echo "Could not find </section>\n";
}
