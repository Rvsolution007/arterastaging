@extends('layouts.client')

@section('content')
    <div class="fade-in space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-100 sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm overflow-hidden cursor-pointer {{ isset($business->logo) ? 'bg-white' : 'bg-indigo-600' }}"
                    onclick="toggleProfileSidebar(true)">
                    @if($business && $business->logo)
                        <img src="{{ asset('uploads/' . $business->logo) }}" class="w-full h-full object-cover"
                            onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/initials/svg?seed={{ urlencode($business->name ?? optional(Auth::user())->name) }}&backgroundColor=312e81'">
                    @else
                        <i data-lucide="user" class="w-5 h-5 text-white"></i>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-gray-900 font-bold text-[14px] leading-tight">{{ $business->name ?? 'Update Business' }}</span>
                    <span class="text-gray-500 text-[11px] font-medium">Business</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('notifications') }}"
                    class="relative w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center transition-all hover:bg-slate-200 active:scale-90">
                    <i data-lucide="bell" class="w-5 h-5 text-gray-500"></i>
                    @if(isset($notification_count) && $notification_count > 0)
                        <span
                            class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $notification_count > 9 ? '9+' : $notification_count }}</span>
                    @endif
                </a>
                <button
                    class="bg-indigo-600 text-white px-5 py-2 rounded-xl flex items-center gap-2 text-[13px] font-semibold transition-all shadow-md hover:brightness-110 active:scale-95">
                    <i data-lucide="zap" class="w-4 h-4 fill-white text-white"></i> Quick
                </button>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mx-4 my-4">
        <div
            class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl px-5 py-3.5 transition-all focus-within:border-indigo-200 focus-within:shadow-md group">
            <i data-lucide="search" class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
            <input type="text" id="searchInput" placeholder="Search categories, festivals..."
                class="flex-1 bg-transparent outline-none text-gray-900 placeholder:text-gray-400 text-[14px]" />
        </div>
    </div>

    @if(isset($stories) && $stories->count() > 0)
    <!-- Stories Section -->
    <div class="px-4 py-2 flex gap-4 overflow-x-auto scrollbar-hide pb-3">
        @foreach($stories as $sIdx => $story)
            @php
                $storyImages = [];
                if($story->story_images && is_array($story->story_images)) {
                    foreach($story->story_images as $si) {
                        $storyImages[] = str_contains($si, 'uploads') ? asset($si) : asset('uploads/'.$si);
                    }
                } elseif($story->image) {
                    $storyImages[] = str_contains($story->image, 'uploads') ? asset($story->image) : asset('uploads/'.$story->image);
                }
            @endphp
            <div class="flex flex-col items-center gap-1.5 cursor-pointer flex-shrink-0"
                 onclick='openStory(@json($storyImages), "{{ $story->external_link_title ?? "" }}", "{{ $story->external_link ?? "" }}")'>
                <div class="w-[66px] h-[66px] rounded-full p-[2.5px] bg-gradient-to-tr from-yellow-400 via-orange-500 to-fuchsia-600 shadow-sm active:scale-95 transition-transform">
                    <div class="w-full h-full rounded-full border-2 border-white overflow-hidden bg-gray-100">
                        <img src="{{ $storyImages[0] ?? '' }}" class="w-full h-full object-cover">
                    </div>
                </div>
                <span class="text-[10.5px] font-medium text-gray-800 w-16 truncate text-center">{{ $story->story_type ?? 'Story' }}</span>
            </div>
        @endforeach
    </div>
    @endif

    <!-- 1. Festival Calendar Section (Upcoming Festivals 2026) -->
    <div class="space-y-4 pt-2">
        <div class="px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i data-lucide="calendar" class="w-6 h-6 text-gray-400"></i>
                <h3 class="text-[19px] font-bold text-gray-800 tracking-tight"> Upcoming Festivals {{ now()->year }}
                </h3>
            </div>
            <div class="text-indigo-600 text-[13px] font-semibold">{{ now()->format('F') }}</div>
        </div>

        <!-- Date Picker -->
        <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-2">
            @php $today = \Carbon\Carbon::now(); @endphp
            @for($i = -2; $i < 10; $i++)
                @php $date = $today->copy()->addDays($i); @endphp
                <div onclick="fetchFestivalsByDate('{{ $date->format('Y-m-d') }}', this)"
                    class="date-picker-item flex flex-col items-center min-w-[55px] cursor-pointer bg-white border border-gray-100 text-gray-400 rounded-2xl py-3 transition-all duration-300">
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $date->format('D') }}</span>
                    <span class="text-lg font-black mt-0.5">{{ $date->format('d') }}</span>
                </div>
            @endfor
        </div>

        <!-- Festival Cards -->
        <div id="festival-cards-container" class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-1">
            @foreach($festivals as $festival)
                <a href="{{ route('universal.details', ['type' => 'festival', 'id' => $festival->id]) }}"
                    class="min-w-[155px] aspect-[4/5] rounded-[2rem] overflow-hidden relative shadow-sm cursor-pointer active:scale-95 transition-all">
                    <img src="{{ asset('uploads/' . $festival->image) }}" class="absolute inset-0 w-full h-full object-cover">
                    <div
                        class="absolute bottom-4 left-4 bg-black/20 backdrop-blur-[2px] text-white text-[15px] font-black px-2 rounded-lg">
                        {{ \Carbon\Carbon::parse($festival->festivals_date)->format('d') }}
                    </div>
                </a>
            @endforeach
            @if($festivals->isEmpty())
                <div class="w-full text-center py-8 text-gray-400 font-medium">No festivals found</div>
            @endif
        </div>
    </div>

    <!-- 2. Category Posts (Categories) -->
    <div class="space-y-4 pt-4">
        <div class="px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i data-lucide="layers" class="w-5 h-5 text-gray-500"></i>
                <h3 class="text-[18px] font-bold text-gray-800 tracking-tight">Category Posts</h3>
            </div>
        </div>
        <div id="categoriesSection" class="px-4 flex gap-4 overflow-x-auto scrollbar-hide pb-2">
            @foreach($categories as $category)
                <a href="{{ route('universal.details', ['type' => 'category', 'id' => $category->id]) }}"
                    class="searchable-item rounded-3xl min-w-[145px] aspect-[3.5/4] relative overflow-hidden cursor-pointer group shadow-sm active:scale-95 transition-all"
                    data-search-text="{{ strtolower($category->name) }}">
                    <img src="{{ asset('uploads/' . $category->icon) }}"
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex flex-col justify-end p-4">
                        <span class="text-white font-bold text-xs tracking-tight">{{ $category->name }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- 4. Custom Posts (My Custom Post Previews) -->
    @if(isset($my_custom_frames) && $my_custom_frames->count() > 0)
    <div class="space-y-4 pt-4">
        <div class="px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i data-lucide="briefcase" class="w-5 h-5 text-gray-500"></i>
                <h3 class="text-[18px] font-bold text-gray-800 tracking-tight">Custom Posts</h3>
            </div>
            <a href="{{ route('custom') }}" class="text-gray-500 text-[13px] font-bold flex items-center gap-1">View All <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
        </div>
        <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide pb-2" id="home-custom-frames-container">
            @foreach($my_custom_frames as $frame)
                <a href="{{ route('universal.edit', ['type' => 'business_custom_frame', 'id' => $frame->db_id]) }}"
                    class="min-w-[155px] w-[155px] flex-shrink-0 rounded-[1.25rem] overflow-hidden shadow-sm border border-gray-100 active:scale-95 transition-transform group relative bg-white"
                    data-frame-id="{{ $frame->db_id }}"
                    id="home-frame-card-{{ $frame->db_id }}">
                    {{-- Shimmer placeholder --}}
                    <div class="cf-shimmer absolute inset-0 z-20" id="home-shimmer-{{ $frame->db_id }}">
                        <div class="w-full h-full bg-gradient-to-r from-slate-100 via-slate-200 to-slate-100 animate-pulse rounded-2xl"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 rounded-full border-2 border-indigo-200 border-t-indigo-500 animate-spin"></div>
                                <span class="text-[10px] font-semibold text-slate-400 animate-pulse">Rendering...</span>
                            </div>
                        </div>
                    </div>
                    {{-- DOM-based rendering container --}}
                    <div class="dom-preview w-full overflow-hidden relative" id="home-preview-{{ $frame->db_id }}" style="background:#fff;"></div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 6. News Updates -->
    @if(isset($news) && $news->count() > 0)
    <div class="space-y-4 pt-4">
        <div class="px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i data-lucide="newspaper" class="w-5 h-5 text-gray-500"></i>
                <h3 class="text-[18px] font-bold text-gray-800 tracking-tight">News Updates</h3>
            </div>
        </div>
        <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide pb-2">
            @foreach($news as $article)
                <a href="{{ $article->link ?? '#' }}" target="_blank"
                    class="searchable-item bg-white rounded-3xl min-w-[280px] max-w-[280px] flex flex-col overflow-hidden cursor-pointer shadow-sm border border-gray-100 active:scale-95 transition-all"
                    data-search-text="{{ strtolower($article->title) }}">
                    <div class="w-full h-[140px] relative">
                        <img src="{{ asset('uploads/' . $article->image) }}" class="absolute inset-0 w-full h-full object-cover">
                    </div>
                    <div class="p-4 flex flex-col bg-slate-50/50">
                        <span class="text-gray-900 font-bold text-[14.5px] line-clamp-2 leading-snug">{{ $article->title }}</span>
                        <span class="text-gray-500 text-[12.5px] mt-1.5 line-clamp-2 leading-relaxed">{!! strip_tags($article->description) !!}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 7. Videos -->
    @if(isset($videos) && $videos->count() > 0)
    <div class="space-y-5 pt-4 pb-24">
        <div class="px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-sky-50 flex items-center justify-center">
                    <i data-lucide="play" class="w-5 h-5 text-sky-500 fill-sky-500"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="text-[19px] font-bold text-slate-900 tracking-tight">Videos</h3>
                </div>
            </div>
        </div>
        <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-1">
            @foreach($videos as $video)
                <div class="min-w-[130px] aspect-[9/16] rounded-[1.8rem] overflow-hidden relative shadow-md border border-white/50 active:scale-95 transition-all group">
                    <img src="{{ asset('uploads/' . $video->image) }}" onerror="this.src='{{ asset('assets/images/video_placeholder.png') }}'" class="w-full h-full object-cover">
                    <!-- Play icon overlay -->
                    <div class="absolute inset-0 bg-black/20 flex items-center justify-center pointer-events-none group-active:bg-black/40 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-white/30 backdrop-blur-sm flex items-center justify-center">
                            <i data-lucide="play" class="w-5 h-5 text-white fill-white ml-0.5"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Bottom sections removed as per request -->

    <!-- Full-screen Story Viewer Overlay (Multi-Image) -->
    <div id="storyViewerOverlay" class="fixed inset-0 bg-black z-[100] hidden flex-col transition-opacity duration-300 opacity-0">
        <!-- Segmented Progress Bar Container -->
        <div id="storyProgressContainer" class="w-full px-2 pt-4 pb-2 flex gap-1 z-10 absolute top-0"></div>
        
        <!-- Header -->
        <div class="absolute top-8 left-4 z-10 flex items-center gap-2">
            <div class="w-9 h-9 rounded-full border-2 border-white overflow-hidden bg-gray-200">
                <img src="{{ asset('uploads/' . ($business->logo ?? '')) }}" class="w-full h-full object-cover" onerror="this.src='https://api.dicebear.com/7.x/initials/svg?seed=B'">
            </div>
            <span class="text-white text-sm font-semibold shadow-black drop-shadow-md">{{ $business->name ?? 'Business' }}</span>
            <span class="text-white/70 text-xs font-medium ml-1">1h</span>
        </div>

        <!-- Close Button -->
        <div class="absolute top-8 right-4 z-10 bg-black/20 backdrop-blur-md rounded-full p-1 cursor-pointer" onclick="closeStory(event)">
            <i data-lucide="x" class="w-6 h-6 text-white drop-shadow-md"></i>
        </div>

        <!-- Left tap zone (go back) -->
        <div class="absolute left-0 top-0 w-[40%] h-full z-[5]" onclick="storyPrev(event)"></div>
        <!-- Right tap zone (go next) -->
        <div class="absolute right-0 top-0 w-[60%] h-full z-[5]" onclick="storyNext(event)"></div>

        <!-- Story Image -->
        <img id="storyImage" src="" class="w-full h-full object-contain my-auto select-none pointer-events-none" />

        <!-- Optional Link Button -->
        <a id="storyLink" href="#" target="_blank" class="absolute bottom-12 left-1/2 -translate-x-1/2 bg-white/10 backdrop-blur-md border border-white/30 text-white px-6 py-2.5 rounded-full font-bold text-[14px] hidden items-center gap-2 active:scale-95 transition-all w-fit whitespace-nowrap z-10" onclick="event.stopPropagation()">
            <span id="storyLinkText">View More</span>
            <i data-lucide="chevron-up" class="w-4 h-4 ml-1"></i>
        </a>
    </div>

@endsection

@section('scripts')
    <script>
        // Search Functionality
        const searchInput = document.getElementById('searchInput');
        const searchableItems = document.querySelectorAll('.searchable-item');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            searchableItems.forEach(item => {
                const text = item.getAttribute('data-search-text') || '';
                if (query === '' || text.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Story Viewer Logic (Multi-Image with Segmented Progress)
        let storyTimer = null;
        let storyImages = [];
        let storyCurrentIndex = 0;
        const SLIDE_DURATION = 5000; // 5 seconds per slide

        function openStory(imgArray, linkTitle, linkUrl) {
            // Accept both array and string (backward compat)
            if (typeof imgArray === 'string') {
                storyImages = [imgArray];
            } else {
                storyImages = imgArray || [];
            }
            if (storyImages.length === 0) return;

            storyCurrentIndex = 0;

            const overlay = document.getElementById('storyViewerOverlay');
            const link = document.getElementById('storyLink');
            const linkText = document.getElementById('storyLinkText');

            // Link setup
            if (linkUrl && linkUrl !== '') {
                link.href = linkUrl;
                linkText.innerText = linkTitle || 'View More';
                link.classList.remove('hidden');
                link.classList.add('flex');
            } else {
                link.classList.add('hidden');
                link.classList.remove('flex');
            }

            // Build segmented progress bar
            buildProgressBars(storyImages.length);

            // Show overlay
            overlay.classList.remove('hidden');
            overlay.style.display = 'flex';
            if (window.lucide) lucide.createIcons();

            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
                showSlide(0);
            }, 10);
        }

        function buildProgressBars(count) {
            const container = document.getElementById('storyProgressContainer');
            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const seg = document.createElement('div');
                seg.className = 'h-0.5 bg-white/30 rounded-full flex-1 overflow-hidden';
                const fill = document.createElement('div');
                fill.className = 'h-full bg-white rounded-full';
                fill.id = 'storyProgressFill_' + i;
                fill.style.width = '0%';
                fill.style.transition = 'none';
                seg.appendChild(fill);
                container.appendChild(seg);
            }
        }

        function showSlide(index) {
            if (index < 0 || index >= storyImages.length) {
                closeStory();
                return;
            }
            storyCurrentIndex = index;
            const img = document.getElementById('storyImage');
            img.src = storyImages[index];

            // Update progress bars
            for (let i = 0; i < storyImages.length; i++) {
                const fill = document.getElementById('storyProgressFill_' + i);
                if (!fill) continue;
                fill.style.transition = 'none';
                if (i < index) {
                    fill.style.width = '100%'; // already viewed
                } else {
                    fill.style.width = '0%';   // not yet
                }
            }

            // Animate current segment
            setTimeout(() => {
                const currentFill = document.getElementById('storyProgressFill_' + index);
                if (currentFill) {
                    currentFill.style.transition = 'width ' + SLIDE_DURATION + 'ms linear';
                    currentFill.style.width = '100%';
                }
            }, 50);

            // Auto-advance timer
            clearTimeout(storyTimer);
            storyTimer = setTimeout(() => {
                if (storyCurrentIndex < storyImages.length - 1) {
                    showSlide(storyCurrentIndex + 1);
                } else {
                    closeStory();
                }
            }, SLIDE_DURATION);
        }

        function storyNext(e) {
            if (e) e.stopPropagation();
            if (storyCurrentIndex < storyImages.length - 1) {
                showSlide(storyCurrentIndex + 1);
            } else {
                closeStory();
            }
        }

        function storyPrev(e) {
            if (e) e.stopPropagation();
            if (storyCurrentIndex > 0) {
                showSlide(storyCurrentIndex - 1);
            } else {
                // Restart current slide
                showSlide(0);
            }
        }

        function closeStory(e) {
            if (e) e.stopPropagation();
            const overlay = document.getElementById('storyViewerOverlay');

            clearTimeout(storyTimer);

            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
                overlay.style.display = 'none';
                document.getElementById('storyProgressContainer').innerHTML = '';
            }, 300);
        }

        // Custom Posts Filter Functionality
        function filterCustomPosts(categoryId, element) {
            // Update filter pill styles
            document.querySelectorAll('.custom-post-filter').forEach(pill => {
                pill.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
                pill.classList.add('bg-slate-100', 'text-gray-500');
            });

            element.classList.remove('bg-slate-100', 'text-gray-500');
            element.classList.add('bg-indigo-600', 'text-white', 'shadow-md');

            // Filter posts
            const posts = document.querySelectorAll('.custom-post-item');
            posts.forEach(post => {
                if (categoryId === 'all' || post.getAttribute('data-category') === categoryId) {
                    post.style.display = '';
                    post.style.opacity = '1';
                } else {
                    post.style.display = 'none';
                    post.style.opacity = '0';
                }
            });
        }

        function fetchFestivalsByDate(date, element) {
            const isAlreadySelected = element.classList.contains('bg-indigo-600');

            // Update UI states
            document.querySelectorAll('.date-picker-item').forEach(el => {
                el.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-100', 'scale-110', 'mx-1');
                el.classList.add('bg-white', 'border', 'border-gray-100', 'text-gray-400');

                const daySpan = el.querySelector('span:first-child');
                if (daySpan) {
                    daySpan.classList.remove('text-indigo-100');
                    daySpan.classList.add('text-gray-400');
                }
            });

            if (isAlreadySelected) {
                // Deselect: already removed styles above, now fetch default upcoming
                date = '';
            } else {
                // Select
                element.classList.remove('bg-white', 'border', 'border-gray-100', 'text-gray-400');
                element.classList.add('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-100', 'scale-110', 'mx-1');

                const daySpan = element.querySelector('span:first-child');
                if (daySpan) {
                    daySpan.classList.remove('text-gray-400');
                    daySpan.classList.add('text-indigo-100');
                }
            }

            // Fetch data
            const container = document.getElementById('festival-cards-container');
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';

            fetch('{{ route('festivals.by.date') }}?date=' + date)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        container.innerHTML = data.html;
                    }
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                })
                .catch(error => {
                    console.error('Error fetching festivals:', error);
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                });
        }

        // AI Thumbnail Rendering Logic
        async function renderAdvancedAiPost(data, targetContainer = null) {
            const config = data.config;
            const aiData = data.ai_data;
            const advancedOverlay = targetContainer;
            if (!config || !advancedOverlay) return;
            advancedOverlay.innerHTML = ''; 

            // HIDE base image to show white background
            const baseImg = advancedOverlay.parentElement.querySelector('img');
            if (baseImg) baseImg.style.display = 'none';

            let designW = (config.info && config.info.width) ? config.info.width : 1024;
            let designH = (config.info && config.info.height) ? config.info.height : 1024;
            const areaW = advancedOverlay.clientWidth;
            if (areaW === 0) return; 

            const scale = areaW / designW;

            if (config.layers) {
                config.layers.forEach(layer => {
                    if (layer.type === 'text' && layer.font) {
                        const fontName = layer.font;
                        const fontUrl = `${data.fonts_dir}/${encodeURIComponent(fontName)}.ttf`;
                        const styleId = 'font-' + fontName.replace(/\s+/g, '-');
                        if (!document.getElementById(styleId)) {
                            const style = document.createElement('style');
                            style.id = styleId;
                            style.textContent = `@font-face { font-family: "${fontName}"; src: url("${fontUrl}"); }`;
                            document.head.appendChild(style);
                        }
                    }
                });
            }

            config.layers.forEach((layer, idx) => {
                if (layer.name === 'bg' || layer.name === 'background') {
                    if (layer.type === 'image') {
                        let bgSrc = layer.src;
                        if (bgSrc.includes('../skins/')) bgSrc = bgSrc.split('/').pop();
                        const bgImg = document.createElement('img');
                        bgImg.src = `${data.skins_dir}/${bgSrc}`;
                        bgImg.style.position = 'absolute';
                        bgImg.style.inset = '0';
                        bgImg.style.width = '100%';
                        bgImg.style.height = '100%';
                        bgImg.style.objectFit = 'cover';
                        bgImg.style.zIndex = layer.z_index || 0;
                        advancedOverlay.appendChild(bgImg);
                    }
                    return;
                }

                const el = document.createElement('div');
                el.style.position = 'absolute';
                el.style.left = (layer.x * scale) + 'px';
                el.style.top = (layer.y * scale) + 'px';
                el.style.width = ((layer.w || layer.width || 0) * scale) + 'px';
                el.style.height = ((layer.h || layer.height || 0) * scale) + 'px';
                el.style.zIndex = layer.z_index || (idx + 10);
                el.style.pointerEvents = 'none';

                if (layer.type === 'text') {
                    const text = (aiData && aiData[layer.name]) ? aiData[layer.name] : (layer.text || '');
                    el.innerText = text.replace(/\\n/g, '\n');
                    el.style.color = (layer.color || '#000000').replace('0x', '#');
                    el.style.fontSize = (layer.size * scale) + 'px';
                    el.style.fontFamily = layer.font || 'sans-serif';
                    const isBold = (layer.weight === 'bold' || (layer.font && layer.font.toLowerCase().includes('bold')));
                    el.style.fontWeight = isBold ? '700' : (layer.weight || '400');
                    el.style.textAlign = layer.justification || 'left';
                    el.style.lineHeight = layer.line_height || 1.1;
                    el.style.overflow = 'hidden';
                    el.style.whiteSpace = 'pre-wrap';
                    el.style.overflowWrap = 'break-word';
                    el.style.display = 'block';
                } else if (layer.type === 'image') {
                    const img = document.createElement('img');
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'contain';
                    
                    let src = layer.src;
                    const lname = (layer.name || '').toLowerCase();

                    let mappedImg = null;
                    if (aiData && aiData._image_mappings) {
                        const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();
                        if (aiData._image_mappings[lname]) {
                            mappedImg = aiData._image_mappings[lname];
                        } else {
                            for (let key in aiData._image_mappings) {
                                const cleanKey = key.replace(/[\s\-_]/g, '').toLowerCase();
                                if (cleanLName === cleanKey) {
                                    mappedImg = aiData._image_mappings[key];
                                    break;
                                }
                            }
                        }
                        if (!mappedImg && (cleanLName === 'image1' || cleanLName === 'mainimage')) {
                            mappedImg = aiData._image_mappings['image1'] || aiData._image_mappings['main_image'] || aiData._image_mappings['image 1'];
                        }
                    }

                    if (mappedImg) {
                        let mapUrl = mappedImg;
                        const uploadsDir = "{{ asset('uploads') }}";
                        if (!mapUrl.startsWith('http') && !mapUrl.startsWith('/') && !mapUrl.startsWith('data:')) {
                            mapUrl = `${uploadsDir}/${mapUrl}`;
                        }
                        img.src = mapUrl;
                        img.style.objectFit = 'cover';
                    } else if (lname === 'image1' || lname === 'main_image' || lname.startsWith('image')) {
                        img.src = advancedOverlay.dataset.imgUrl;
                        img.style.objectFit = 'cover';
                    } else {
                        if (src.includes('../skins/')) src = src.split('/').pop();
                        img.src = `${data.skins_dir}/${src}`;
                        img.style.objectFit = 'contain';
                    }

                    if (lname.includes('sign')) {
                        const minSize = 2;
                        const w = (layer.w || layer.width || 0) * scale;
                        if (w < minSize) el.style.width = minSize + 'px';
                    }

                    if (lname.startsWith('image')) {
                        const radius = (layer.radius || 40) * scale;
                        img.style.borderRadius = radius + 'px';
                        el.style.borderRadius = radius + 'px';
                    }
                    el.appendChild(img);
                }
                advancedOverlay.appendChild(el);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const thumbnails = document.querySelectorAll('.ai-thumbnail-generator');
            thumbnails.forEach(el => {
                const config = JSON.parse(el.dataset.templateConfig || 'null');
                if (config && config.config) {
                    setTimeout(() => renderAdvancedAiPost(config, el), 150);
                }
            });
        });
    </script>

    {{-- Custom Post Preview Renderer (same as custom page) --}}
    @if(isset($my_custom_frames) && $my_custom_frames->count() > 0)
    <script>
    (function() {
        'use strict';
        var frameConfigs = @json($my_custom_frames_raw ?? []);
        var templateBaseUrl = '{{ asset("uploads/template") }}';
        var uploadsBaseUrl = '{{ asset("uploads") }}';

        @if(isset($business))
        var bizInfo = {
            name: @json($business->name ?? ''),
            email: @json($business->email ?? ''),
            mobile_no: @json($business->mobile_no ?? ''),
            website: @json($business->website ?? ''),
            address: @json($business->address ?? ''),
            logo: '{{ isset($business->logo) ? asset("uploads/" . $business->logo) : "" }}'
        };
        @else
        var bizInfo = { name:'', email:'', mobile_no:'', website:'', address:'', logo:'' };
        @endif

        if (!frameConfigs || frameConfigs.length === 0) return;

        function renderAll() {
            frameConfigs.forEach(function(config) {
                try {
                    renderDom(config);
                } catch(e) {
                    console.warn('Home render failed #'+config.db_id, e);
                    hideShimmer(config.db_id);
                }
            });
        }

        function renderDom(config) {
            var frameId = config.db_id;
            var jsonRules = config.json_rules;
            var zipName = config.zip_name;
            var skinFolder = config.skin_folder || zipName;
            var aiContent = config.cached_content || {};
            var productImages = config.product_images || {};

            var container = document.getElementById('home-preview-' + frameId);
            if (!container || !jsonRules || !jsonRules.layers) {
                hideShimmer(frameId);
                return;
            }

            var designW = (jsonRules.info && jsonRules.info.width) || 1080;
            var designH = (jsonRules.info && jsonRules.info.height) || 1080;
            var areaW = container.clientWidth || 220;
            var scale = areaW / designW;
            var scaledH = designH * scale;

            container.style.height = scaledH + 'px';
            container.style.position = 'relative';
            container.style.overflow = 'hidden';

            var skinBase = templateBaseUrl + '/' + zipName + '/skins/' + skinFolder + '/';
            var layers = (jsonRules.layers || []).slice().sort(function(a,b){ return (a.z_index||0)-(b.z_index||0); });
            var pendingImages = [];

            layers.forEach(function(layer, idx) {
                var lname = (layer.name || '').toLowerCase();

                if (lname === 'bg' || lname === 'background') {
                    if (layer.type === 'image') {
                        var bgImg = document.createElement('img');
                        bgImg.src = skinBase + (layer.src||'').split('/').pop();
                        bgImg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:'+(layer.z_index||0);
                        bgImg.draggable = false;
                        pendingImages.push(bgImg);
                        container.appendChild(bgImg);
                    } else if (layer.color) {
                        var bgDiv = document.createElement('div');
                        var bgColor = (layer.color||'#fff').replace('0x','#');
                        bgDiv.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;z-index:'+(layer.z_index||0)+';background:'+bgColor;
                        container.appendChild(bgDiv);
                    }
                    return;
                }

                var el = document.createElement('div');
                el.style.position = 'absolute';
                el.style.left = ((layer.x||0)*scale)+'px';
                el.style.top = ((layer.y||0)*scale)+'px';
                el.style.width = ((layer.w||layer.width||0)*scale)+'px';
                el.style.height = ((layer.h||layer.height||0)*scale)+'px';
                el.style.zIndex = layer.z_index || (idx+10);
                el.style.pointerEvents = 'none';
                el.style.overflow = 'hidden';

                if (layer.type === 'text') {
                    var text = aiContent[layer.name] || layer.text || '';
                    if (layer.uppercase) text = text.toUpperCase();
                    el.innerText = text;
                    el.style.color = (layer.color||'#000').replace('0x','#');
                    el.style.fontSize = ((layer.size||20)*scale)+'px';
                    el.style.fontFamily = layer.font || 'Arial, sans-serif';
                    var isBold = (layer.weight==='bold'||layer.weight==700||(layer.font&&layer.font.toLowerCase().includes('bold')));
                    el.style.fontWeight = isBold ? '700' : (layer.weight||'400');
                    el.style.textAlign = layer.justification || 'left';
                    el.style.lineHeight = layer.line_height || 1.1;
                    el.style.whiteSpace = 'pre-wrap';
                    el.style.overflowWrap = 'break-word';
                    if (layer.font) {
                        var fontUrl = templateBaseUrl+'/'+zipName+'/fonts/'+encodeURIComponent(layer.font)+'.ttf';
                        var styleId = 'font-'+layer.font.replace(/\s+/g,'-');
                        if (!document.getElementById(styleId)) {
                            var s = document.createElement('style');
                            s.id = styleId;
                            s.textContent = '@font-face{font-family:"'+layer.font+'";src:url("'+fontUrl+'")}';
                            document.head.appendChild(s);
                        }
                    }
                } else if (layer.type === 'image') {
                    var img = document.createElement('img');
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.draggable = false;
                    var imgSrc = (layer.src||'').split('/').pop();

                    if (lname.startsWith('image')) {
                        var pImg = productImages[layer.name] || productImages['image1'];
                        if (pImg) {
                            img.src = uploadsBaseUrl + '/' + pImg.replace(/^uploads\//,'');
                        } else {
                            img.src = skinBase + imgSrc;
                        }
                        img.style.objectFit = 'cover';
                        var radius = ((layer.radius||40)*scale);
                        img.style.borderRadius = radius+'px';
                        el.style.borderRadius = radius+'px';
                    } else if (lname.includes('logo') && bizInfo.logo) {
                        img.src = bizInfo.logo;
                        img.style.objectFit = 'contain';
                    } else {
                        img.src = skinBase + imgSrc;
                        img.style.objectFit = 'contain';
                    }
                    pendingImages.push(img);
                    el.appendChild(img);
                }
                container.appendChild(el);
            });

            if (pendingImages.length > 0) {
                var loaded = 0, total = pendingImages.length, done = false;
                function checkDone() {
                    loaded++;
                    if (!done && loaded >= total) { done = true; hideShimmer(frameId); }
                }
                pendingImages.forEach(function(img) {
                    if (img.complete && img.naturalWidth > 0) checkDone();
                    else { img.addEventListener('load', checkDone); img.addEventListener('error', checkDone); }
                });
                setTimeout(function() { if (!done) { done = true; hideShimmer(frameId); } }, 4000);
            } else {
                hideShimmer(frameId);
            }
        }

        function renderFrameOverlay(container, frameData, parentW, parentH) {
            var fc = frameData.config;
            var fBase = templateBaseUrl+'/'+frameData.zip_name+'/skins/'+frameData.skin_folder+'/';
            
            // Calculate frame's native dimensions
            var fNativeW = (fc.info && fc.info.width) || 0;
            var fNativeH = (fc.info && fc.info.height) || 0;
            if (!fNativeW || !fNativeH) {
                (fc.layers||[]).forEach(function(l) {
                    var r = (l.x||0) + (l.width||l.w||0);
                    var b = (l.y||0) + (l.height||l.h||0);
                    if (r > fNativeW) fNativeW = r;
                    if (b > fNativeH) fNativeH = b;
                });
            }
            if (!fNativeW || !fNativeH) { fNativeW = parentW; fNativeH = parentH; }

            var areaW = container.clientWidth || 220;
            var containerScale = areaW / parentW;
            var areaH = parentH * containerScale;

            var scX = areaW / fNativeW;
            var scY = areaH / fNativeH;
            var overlayImages = [];

            (fc.layers||[]).slice().sort(function(a,b){return (a.z_index||0)-(b.z_index||0);}).forEach(function(fl) {
                var n = (fl.name||fl.id||'').toLowerCase();
                if (fl.type==='image' && n.match(/^image\d*/)) return;
                var w=(fl.width||fl.w||0)*scX, h=(fl.height||fl.h||0)*scY, x=(fl.x||0)*scX, y=(fl.y||0)*scY;
                if (!w||!h) return;

                var el = document.createElement('div');
                el.style.cssText = 'position:absolute;pointer-events:none;overflow:hidden;left:'+x+'px;top:'+y+'px;width:'+w+'px;height:'+h+'px;z-index:'+((fl.z_index||0)+10000);

                if (fl.type === 'image') {
                    var img = document.createElement('img');
                    if (n === 'bg' || n === 'background') {
                        img.style.cssText = 'width:100%;height:100%;object-fit:fill;';
                    } else {
                        img.style.cssText = 'width:100%;height:100%;object-fit:contain;';
                    }
                    img.draggable = false;
                    img.src = (n.includes('logo') && bizInfo.logo) ? bizInfo.logo : fBase+(fl.src||'').split('/').pop();
                    overlayImages.push(img);
                    el.appendChild(img);
                } else if (fl.type === 'text') {
                    var txt = fl.text||'';
                    if (n.includes('name')||n.includes('company')) txt=bizInfo.name||txt;
                    else if (n.includes('email')) txt=bizInfo.email||txt;
                    else if (n.includes('phone')||n.includes('mobile')||n.includes('number')) txt=bizInfo.mobile_no||txt;
                    else if (n.includes('website')||n.includes('web')) txt=bizInfo.website||txt;
                    else if (n.includes('address')||n.includes('location')) txt=bizInfo.address||txt;
                    if (fl.uppercase) txt=txt.toUpperCase();
                    el.innerText = txt;
                    el.style.color=(fl.color||'#000').replace('0x','#');
                    el.style.fontSize=((fl.size||20)*scX)+'px';
                    el.style.fontWeight=fl.weight==='bold'||fl.weight==700?'700':(fl.weight||'400');
                    el.style.fontFamily='Arial, sans-serif';
                    el.style.textAlign=fl.justification||'left';
                    el.style.lineHeight=fl.line_height||1.1;
                    el.style.whiteSpace='pre-wrap';
                }
                container.appendChild(el);
            });
            return overlayImages;
        }

        function hideShimmer(frameId) {
            var s = document.getElementById('home-shimmer-'+frameId);
            if (s) {
                s.style.transition = 'opacity 0.4s ease';
                s.style.opacity = '0';
                setTimeout(function(){ s.style.display = 'none'; }, 400);
            }
        }

        if (document.readyState==='loading') document.addEventListener('DOMContentLoaded', renderAll);
        else setTimeout(renderAll, 50);
    })();
    </script>
    @endif
@endsection