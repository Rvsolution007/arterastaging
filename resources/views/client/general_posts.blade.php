@extends('layouts.client')

@section('main_bg', 'bg-[#f8fafc]')

@section('content')
    <div class="fade-in space-y-4 pb-32">
        <!-- Fixed Header -->
        <div style="position: sticky; top: 0; z-index: 30; background: #ffffff; border-bottom: 1px solid #f3f4f6;">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center transition-all active:scale-90">
                        <i data-lucide="chevron-left" class="w-5 h-5 text-gray-600"></i>
                    </a>
                    <div class="flex flex-col">
                        <span class="text-gray-900 font-bold text-[16px] leading-tight">General Posts</span>
                        <span class="text-gray-500 text-[11px] font-medium">AI Generated Content</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5 text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-4 flex gap-3 overflow-x-auto scrollbar-hide py-2">
            <a href="{{ route('general.posts.client', ['category_id' => 'all']) }}" 
                class="px-5 py-2.5 {{ !request()->has('category_id') || request()->category_id == 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-100' }} text-[14px] font-bold rounded-full whitespace-nowrap transition-all">
                All
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('general.posts.client', ['category_id' => $cat->id]) }}" 
                    class="px-5 py-2.5 {{ request()->category_id == $cat->id ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-100' }} text-[14px] font-bold rounded-full whitespace-nowrap transition-all">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <!-- Post Grid -->
        <div id="posts-grid" class="px-4 grid grid-cols-2 sm:grid-cols-3 gap-4">
            @forelse($general_posts as $post)
                @include('client.partials.general_post_card', ['post' => $post])
            @empty
                <div class="col-span-full py-20 flex flex-col items-center justify-center text-gray-400 space-y-4">
                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                        <i data-lucide="image-off" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <span class="font-medium">No posts found for this category</span>
                </div>
            @endforelse
        </div>

        <!-- See More Button -->
        @if($hasMore)
            <div id="see-more-wrapper" class="px-4 py-6 flex justify-center">
                <button id="see-more-btn" onclick="loadMorePosts()"
                    style="display:inline-flex;align-items:center;gap:8px;padding:12px 32px;border-radius:9999px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;box-shadow:0 4px 15px -3px rgba(79,70,229,0.4);transition:all 0.3s;letter-spacing:0.3px;">
                    <span id="see-more-text">See More</span>
                    <i data-lucide="chevron-down" style="width:16px;height:16px;"></i>
                </button>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
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
                // Render bg as full background
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
                
                if (layer.uppercase) {
                    el.style.textTransform = 'uppercase';
                }
                
                if (layer.char_spacing) {
                    // Convert Fabric.js char spacing (in 1000ths of an em) to pixels approx.
                    // For DOM, letter-spacing is applied as pixels based on font size.
                    // Fabric uses (char_spacing / 1000) * fontSize
                    el.style.letterSpacing = ((layer.char_spacing / 1000) * (layer.size * scale)) + 'px';
                }
                
                if (layer.shadow) {
                    const ox = (layer.shadow.offsetX || 0) * scale;
                    const oy = (layer.shadow.offsetY || 0) * scale;
                    const bl = (layer.shadow.blur || 0) * scale;
                    const c = layer.shadow.color || 'rgba(0,0,0,0.5)';
                    el.style.textShadow = `${ox}px ${oy}px ${bl}px ${c}`;
                }

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

    // Load More Posts
    let nextOffset = {{ $nextOffset ?? 0 }};
    let isLoading = false;

    function loadMorePosts() {
        if (isLoading) return;
        isLoading = true;

        const btn = document.getElementById('see-more-btn');
        const textEl = document.getElementById('see-more-text');
        if (btn) {
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            textEl.textContent = 'Loading...';
        }

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('offset', nextOffset);

        fetch(currentUrl.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const grid = document.getElementById('posts-grid');
            grid.insertAdjacentHTML('beforeend', data.html);

            // Re-initialize Lucide icons for new content
            if (window.lucide) lucide.createIcons();

            // Re-initialize AI thumbnails for newly added cards
            setTimeout(() => {
                grid.querySelectorAll('.ai-thumbnail-generator').forEach(el => {
                    if (el.children.length === 0) {
                        const config = JSON.parse(el.dataset.templateConfig || 'null');
                        if (config && config.config) {
                            renderAdvancedAiPost(config, el);
                        }
                    }
                });
            }, 150);

            nextOffset = data.nextOffset;

            if (!data.hasMore) {
                const wrapper = document.getElementById('see-more-wrapper');
                if (wrapper) wrapper.remove();
            } else {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                textEl.textContent = 'See More';
            }

            isLoading = false;
        })
        .catch(err => {
            console.error('Load more error:', err);
            if (btn) {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                textEl.textContent = 'See More';
            }
            isLoading = false;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const thumbnails = document.querySelectorAll('.ai-thumbnail-generator');
        thumbnails.forEach(el => {
            const config = JSON.parse(el.dataset.templateConfig || 'null');
            if (config && config.config) {
                setTimeout(() => renderAdvancedAiPost(config, el), 100);
            }
        });
    });
</script>
@endsection
