@extends('layouts.client')

@section('title', 'Select Poster')

@section('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto:wght@400;700;900&family=Poppins:wght@400;700;900&family=Montserrat:wght@400;700;900&family=Bebas+Neue&family=Pacifico&family=Dancing+Script:wght@400;700&family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;700&family=Lato:wght@400;700;900&family=Open+Sans:wght@400;700;800&family=Raleway:wght@400;700;900&family=Abril+Fatface&family=Comfortaa:wght@400;700&family=Righteous&family=Varela+Round&family=Caveat:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <style>
        /* CRITICAL: Disable parent scrolling to prevent double scrollbars */
        #main-content {
            overflow: hidden !important;
            padding-bottom: 0 !important;
            height: 100vh !important;
        }

        /* Hide the default navigation/FAB */
        nav,
        #fab-container,
        #fab-backdrop {
            display: none !important;
        }

        .selection-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #ffffff;
        }

        /* Header */
        .app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 56px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            flex-shrink: 0;
        }

        .back-link {
            color: #334155;
            padding: 8px;
            margin-left: -8px;
        }

        .header-title {
            flex: 1;
            font-size: clamp(14px, 4vw, 18px);
            font-weight: 700;
            color: #1e293b;
            margin-left: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .next-button {
            background-color: #4f46e5;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 700;
        }

        .next-button:active {
            transform: scale(0.9);
        }

        .filter-header-btn {
            background: #f1f5f9;
            color: #475569;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-header-btn:active {
            transform: scale(0.9);
        }

        /* Language Dropdown */
        .header-action-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        #languageDropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 150px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            z-index: 1000;
            display: none;
            overflow: hidden;
            transform-origin: top right;
            animation: dropdownFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-5px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #languageDropdown.show {
            display: block;
        }

        .lang-option {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(241, 245, 249, 0.5);
        }

        .lang-option:last-child {
            border-bottom: none;
        }

        .lang-option:hover {
            background-color: rgba(79, 70, 229, 0.05);
            color: #4f46e5;
            padding-left: 18px;
        }

        .lang-option.active {
            color: #4f46e5;
            background-color: rgba(79, 70, 229, 0.08);
            font-weight: 600;
        }

        .lang-option::after {
            content: '';
            width: 18px;
            height: 18px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s;
        }

        .lang-option.active::after {
            opacity: 1;
            transform: scale(1);
        }

        /* Preview Section - Professional Sizing */
        .preview-section {
            background-color: #f1f5f9;
            width: 100%;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            padding: 24px 16px;
        }

        .main-image-wrapper {
            width: 100%;
            max-width: 100%;
            max-height: 55vh;
            background-color: #ffffff;
            overflow: hidden;
            border-radius: 16px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .main-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Scrolling Content Section */
        .scroll-content {
            flex: 1;
            overflow-y: auto;
            background-color: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        .filter-tabs {
            position: sticky;
            top: 0;
            background: #ffffff;
            display: flex;
            gap: 10px;
            padding: 12px 16px;
            z-index: 10;
            border-bottom: 1px solid #f8fafc;
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            border: 1.5px solid #f1f5f9;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background-color: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        /* Grid */
        .posters-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 16px;
        }

        .poster-card {
            aspect-ratio: 4/5;
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
            cursor: pointer;
            border: 3px solid transparent;
        }

        .poster-card.selected {
            border-color: #4f46e5;
        }

        .poster-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-card {
            aspect-ratio: 9/16;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            position: relative;
        }

        .video-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        .video-badge.free {
            background: rgba(16, 185, 129, 0.8);
        }

        .video-play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            cursor: pointer;
        }

        .video-play-btn {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.2s;
        }

        .video-play-btn svg {
            width: 22px;
            height: 22px;
            color: #fff;
            margin-left: 3px;
        }

        .video-card:active .video-play-btn {
            transform: scale(0.9);
        }

        /* Video Modal */
        .video-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        .video-modal-backdrop.active {
            display: flex;
        }

        .video-modal-content {
            position: relative;
            width: 90vw;
            max-width: 400px;
            max-height: 85vh;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
        }

        .video-modal-content video {
            width: 100%;
            height: auto;
            max-height: 85vh;
            display: block;
        }

        .video-modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .video-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            text-align: center;
        }

        .video-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .video-empty-icon svg {
            width: 32px;
            height: 32px;
            color: #94a3b8;
        }

        .video-empty h3 {
            font-size: 16px;
            font-weight: 700;
            color: #475569;
            margin: 0 0 6px;
        }

        .video-empty p {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
            max-width: 220px;
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    <div class="selection-container">
        <header class="app-header">
            <a href="{{ route('home') }}" class="back-link">
                <i data-lucide="chevron-left"></i>
            </a>
            <h1 class="header-title">Select Design</h1>
            <div class="header-action-wrapper">
                <button class="filter-header-btn" onclick="toggleLanguageDropdown(event)">
                    <i data-lucide="languages" style="width: 20px; height: 20px;"></i>
                </button>
                <button class="next-button" style="background-color: #f1f5f9; color: #475569;" onclick="downloadDesign()">
                    <i data-lucide="download" style="width: 18px; height: 18px;"></i>
                </button>
                <button class="next-button" onclick="handleNext()">
                    <i data-lucide="edit-2" style="width: 18px; height: 18px;"></i>
                    Next
                </button>

                <!-- Language Dropdown -->
                <div id="languageDropdown">
                    <div class="lang-option active" data-id="all" onclick="filterByLang('all', this)">All</div>
                    @foreach($languages as $lang)
                        <div class="lang-option" data-id="{{ $lang->id }}" onclick="filterByLang('{{ $lang->id }}', this)">
                            {{ $lang->title }}
                        </div>
                    @endforeach
                </div>
            </div>
        </header>

        @php
            $defaultImage = $item ? $item->display_image : '';
            $firstFrameImage = null;
            if (is_array($frames) && count($frames) > 0) {
                $firstFrameImage = $frames[0]['frame_image'] ?? null;
            } elseif (is_object($frames) && method_exists($frames, 'first') && $frames->count() > 0) {
                $firstFrameImage = $frames->first()->frame_image ?? null;
            }
            $previewSrc = asset('uploads/' . ($firstFrameImage ?: $defaultImage));
            $isAiPost = isset($templateConfig) && $templateConfig && isset($templateConfig['config']);
        @endphp
        <div class="preview-section">
            <div class="main-image-wrapper" id="mainPreviewWrapper">
                @if($isAiPost)
                    {{-- AI Post: Layered preview container --}}
                    <div id="aiPreviewContainer" style="position: relative; width: 100%; background: #ffffff; overflow: hidden;"
                         data-template-config='@json($templateConfig)'
                         data-img-url="{{ $previewSrc }}">
                        <img id="mainPreview" src="{{ $previewSrc }}" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: none;" alt="Selected Design">
                        <div id="aiPreviewOverlay" style="position: absolute; inset: 0; pointer-events: none;"></div>
                    </div>
                @else
                    {{-- Standard post: simple image --}}
                    <img id="mainPreview" src="{{ $previewSrc }}" alt="Selected Design">
                @endif
            </div>
        </div>

        <div class="scroll-content scrollbar-hide">
            <div class="filter-tabs">
                <button class="tab-btn active" onclick="switchTab('images', this)">
                    <i data-lucide="image" style="width: 16px; height: 16px;"></i> Images
                </button>
                <button class="tab-btn" onclick="switchTab('videos', this)">
                    <i data-lucide="play" style="width: 16px; height: 16px;"></i> Videos
                </button>
            </div>

            <div id="imagesContent" class="posters-grid">
                @foreach($frames as $index => $frame)
                    @php $imgUrl = asset('uploads/' . $frame->frame_image); @endphp
                    <div class="poster-card {{ $index === 0 ? 'selected' : '' }}" data-lang="{{ $frame->language_id }}"
                        style="position:relative;overflow:hidden;{{ $isAiPost ? 'aspect-ratio:1/1;' : '' }}"
                        onclick="updatePreview('{{ $imgUrl }}', this)">
                        @if($isAiPost && isset($templateConfig))
                            <div class="ai-frame-thumb" data-config='@json($templateConfig["config"] ?? null)'
                                 data-skins-dir="{{ $templateConfig['skins_dir'] ?? '' }}"
                                 data-fonts-dir="{{ $templateConfig['fonts_dir'] ?? '' }}"
                                 data-img-url="{{ $imgUrl }}"
                                 data-ai-data='@json($templateConfig["ai_data"] ?? null)'></div>
                        @else
                            <img src="{{ $imgUrl }}" alt="Design">
                        @endif
                    </div>
                @endforeach
            </div>

            <div id="videosContent" class="posters-grid" style="display: none; grid-template-columns: repeat(2, 1fr);">
                @forelse($videos as $video)
                    @php
                        $videoUrl = (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                            ? Storage::disk('spaces')->url('uploads/video/' . $video->video)
                            : asset('uploads/video/' . $video->video);
                    @endphp
                    <div class="video-card" onclick="openVideoModal('{{ $videoUrl }}')">
                        <video src="{{ $videoUrl }}#t=0.5" preload="metadata" muted playsinline></video>
                        <div class="video-badge {{ $video->paid ? '' : 'free' }}">{{ $video->paid ? 'PREMIUM' : 'FREE' }}</div>
                        <div class="video-play-overlay">
                            <div class="video-play-btn">
                                <svg viewBox="0 0 24 24" fill="white" stroke="none"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="video-empty">
                        <div class="video-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 2 2 4-4"/><path d="M21 11.5V12a9 9 0 1 1-4-7.5"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <h3>No Videos Yet</h3>
                        <p>Videos for this post will appear here once they're added</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Video Playback Modal -->
    <div class="video-modal-backdrop" id="videoModal" onclick="closeVideoModal(event)">
        <div class="video-modal-content" onclick="event.stopPropagation()">
            <button class="video-modal-close" onclick="closeVideoModal()">&times;</button>
            <video id="videoModalPlayer" controls playsinline></video>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        let selectedImage = "{{ $previewSrc }}";

        function updatePreview(url, element) {
            document.getElementById('mainPreview').src = url;
            selectedImage = url;
            document.querySelectorAll('.poster-card').forEach(c => c.classList.remove('selected'));
            element.classList.add('selected');
        }

        function filterByLang(id, el) {
            document.querySelectorAll('.lang-option').forEach(b => b.classList.remove('active'));
            el.classList.add('active');

            document.querySelectorAll('.poster-card').forEach(card => {
                if (id === 'all' || card.getAttribute('data-lang') == id) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('languageDropdown').classList.remove('show');
        }

        function toggleLanguageDropdown(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('languageDropdown');
            dd.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function (event) {
            if (!event.target.closest('.header-action-wrapper')) {
                document.getElementById('languageDropdown').classList.remove('show');
            }
        }

        function switchTab(type, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (type === 'images') {
                document.getElementById('imagesContent').style.display = 'grid';
                document.getElementById('videosContent').style.display = 'none';
                // Pause all video thumbnails
                document.querySelectorAll('#videosContent video').forEach(v => v.pause());
            } else {
                document.getElementById('imagesContent').style.display = 'none';
                document.getElementById('videosContent').style.display = 'grid';
            }
        }

        // Video Modal
        function openVideoModal(url) {
            const modal = document.getElementById('videoModal');
            const player = document.getElementById('videoModalPlayer');
            player.src = url;
            modal.classList.add('active');
            player.play().catch(() => {});
        }

        function closeVideoModal(e) {
            if (e && e.target !== e.currentTarget) return;
            const modal = document.getElementById('videoModal');
            const player = document.getElementById('videoModalPlayer');
            player.pause();
            player.src = '';
            modal.classList.remove('active');
        }

        function handleNext() {
            window.location.href = "{{ route('universal.edit', ['type' => $type, 'id' => $id]) }}?design=" + encodeURIComponent(selectedImage);
        }

        function trackWebDownload(type, id, imageUrl) {
            fetch('{{ route("api.track-activity") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    userId: '{{ auth()->id() }}',
                    action: 'download_template',
                    item_type: type,
                    item_id: id,
                    platform: 'Web',
                    downloaded_image: imageUrl
                })
            }).catch(err => console.error('Error tracking download:', err));
        }

        function downloadDesign() {
            const link = document.createElement('a');
            link.href = selectedImage;
            link.download = 'design.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            trackWebDownload('{{ $type }}', '{{ $id }}', selectedImage);
        }

        // ── AI Post Preview Renderer (same as admin AI Preview) ──
        async function renderAdvancedAiPost(data, targetOverlay, container) {
            const config = data.config;
            const aiData = data.ai_data;
            if (!config || !config.layers) return;

            targetOverlay.innerHTML = '';

            // Resolve design resolution
            let designW = (config.info && config.info.width) ? config.info.width : 1024;
            let designH = (config.info && config.info.height) ? config.info.height : 1024;

            // Set aspect ratio on container
            container.style.aspectRatio = `${designW} / ${designH}`;

            // Wait a frame for layout to settle
            await new Promise(r => requestAnimationFrame(r));

            const areaW = container.clientWidth;
            if (areaW === 0) return;
            const scale = areaW / designW;

            // Load fonts from ZIP
            if (config.layers) {
                const fontPromises = [];
                config.layers.forEach(layer => {
                    if (layer.type === 'text' && layer.font) {
                        const fontName = layer.font;
                        const styleId = 'font-' + fontName.replace(/\s+/g, '-');
                        if (!document.getElementById(styleId)) {
                            const fontUrl = `${data.fonts_dir}/${encodeURIComponent(fontName)}.ttf`;
                            const style = document.createElement('style');
                            style.id = styleId;
                            style.textContent = `@font-face { font-family: "${fontName}"; src: url("${fontUrl}") format("truetype"); }`;
                            document.head.appendChild(style);
                            // Preload font
                            fontPromises.push(
                                new FontFace(fontName, `url("${fontUrl}")`).load()
                                    .then(f => document.fonts.add(f))
                                    .catch(() => {}) // Fallback to system/Google font
                            );
                        }
                    }
                });
                await Promise.all(fontPromises);
            }

            // Sort layers by z_index
            const sortedLayers = [...config.layers].sort((a, b) => (a.z_index || 0) - (b.z_index || 0));

            // Render each layer
            sortedLayers.forEach((layer, idx) => {
                const lname = (layer.name || '').toLowerCase();
                if (lname === 'bg' || lname === 'background') {
                    if (layer.type === 'image') {
                        let bgSrc = layer.src;
                        if (bgSrc.includes('../skins/')) bgSrc = bgSrc.split('/').pop();
                        const bgImg = document.createElement('img');
                        bgImg.src = `${data.skins_dir}/${bgSrc}`;
                        bgImg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:' + (layer.z_index || 0);
                        targetOverlay.appendChild(bgImg);
                    }
                    return;
                }

                const el = document.createElement('div');
                el.style.position = 'absolute';
                el.style.left = ((layer.x || 0) * scale) + 'px';
                el.style.top = ((layer.y || 0) * scale) + 'px';
                el.style.width = ((layer.w || layer.width || 0) * scale) + 'px';
                el.style.height = ((layer.h || layer.height || 0) * scale) + 'px';
                el.style.zIndex = layer.z_index || (idx + 10);
                el.style.pointerEvents = 'none';
                el.style.overflow = 'hidden';

                if (layer.type === 'text') {
                    // Get AI text content, fallback to template default
                    let text = layer.text || '';
                    if (aiData && aiData[layer.name] !== undefined) {
                        let aiText = aiData[layer.name];
                        if (Array.isArray(aiText)) aiText = aiText.join(' ');
                        if (typeof aiText === 'string') text = aiText;
                    }
                    text = text.replace(/\\n/g, '\n');

                    el.innerText = text;
                    el.style.color = (layer.color || '#000000').replace('0x', '#');
                    el.style.fontSize = ((layer.size || 20) * scale) + 'px';
                    el.style.fontFamily = `"${layer.font || 'sans-serif'}", sans-serif`;

                    const isBold = (layer.weight === 'bold' || (layer.font && layer.font.toLowerCase().includes('bold')));
                    el.style.fontWeight = isBold ? '700' : (layer.weight || '400');
                    el.style.textAlign = layer.justification || 'left';
                    el.style.lineHeight = layer.line_height || 1.1;
                    el.style.whiteSpace = 'pre-wrap';
                    el.style.overflowWrap = 'break-word';
                    el.style.display = 'block';

                } else if (layer.type === 'image') {
                    const img = document.createElement('img');
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.crossOrigin = 'anonymous';

                    let src = layer.src;
                    let isFrameSlot = lname.startsWith('image');

                    // Check AI image mappings
                    let mappedImg = null;
                    if (aiData && aiData._image_mappings && isFrameSlot) {
                        const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();
                        if (aiData._image_mappings[lname]) {
                            mappedImg = aiData._image_mappings[lname];
                        } else {
                            for (let key in aiData._image_mappings) {
                                if (key.replace(/[\s\-_]/g, '').toLowerCase() === cleanLName) {
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
                    } else if (isFrameSlot) {
                        // Use the base poster image for frame slots
                        const baseImg = container.dataset.imgUrl || '';
                        img.src = baseImg;
                        img.style.objectFit = 'cover';
                    } else {
                        // Template component (shape, sign, etc.)
                        if (src.includes('../skins/')) src = src.split('/').pop();
                        img.src = `${data.skins_dir}/${src}`;
                        img.style.objectFit = 'contain';
                    }

                    // Rounded corners for image frames
                    if (isFrameSlot && layer.radius) {
                        const radius = (layer.radius || 0) * scale;
                        img.style.borderRadius = radius + 'px';
                        el.style.borderRadius = radius + 'px';
                    }

                    el.appendChild(img);
                }

                targetOverlay.appendChild(el);
            });
        }

        // ── Initialize AI Preview ──
        document.addEventListener('DOMContentLoaded', () => {
            const aiContainer = document.getElementById('aiPreviewContainer');
            if (aiContainer) {
                const configData = JSON.parse(aiContainer.dataset.templateConfig || 'null');
                if (configData && configData.config) {
                    const overlay = document.getElementById('aiPreviewOverlay');
                    // Small delay to ensure container has rendered and has clientWidth
                    setTimeout(() => {
                        renderAdvancedAiPost(configData, overlay, aiContainer);
                    }, 200);
                } else {
                    // No config — show the base image
                    const img = document.getElementById('mainPreview');
                    if (img) img.style.display = 'block';
                }
            }
        });

        // ── Frame Thumbnail Renderer for details page ──
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.ai-frame-thumb').forEach(thumb => {
                const configStr = thumb.getAttribute('data-config');
                const skinsDir = thumb.getAttribute('data-skins-dir');
                const fontsDir = thumb.getAttribute('data-fonts-dir');
                const imgUrl = thumb.getAttribute('data-img-url');
                const aiDataStr = thumb.getAttribute('data-ai-data');
                if (!configStr || configStr === 'null') return;

                let config, aiData;
                try { config = JSON.parse(configStr); } catch(e) { return; }
                try { aiData = aiDataStr ? JSON.parse(aiDataStr) : null; } catch(e) { aiData = null; }
                if (!config || !config.layers || !config.info) return;

                const designW = config.info.width || 1080;
                const designH = config.info.height || 1080;
                const parent = thumb.parentElement;
                const thumbW = parent.offsetWidth || 120;
                const thumbH = parent.offsetHeight || 150;
                const scaleDown = Math.min(thumbW / designW, thumbH / designH);

                // Load fonts
                const fontsLoaded = [];
                config.layers.forEach(layer => {
                    if (layer.type === 'text' && layer.font && fontsDir) {
                        const fontName = layer.font;
                        if (!fontsLoaded.includes(fontName)) {
                            fontsLoaded.push(fontName);
                            try {
                                const fontUrl = `${fontsDir}/${encodeURIComponent(fontName)}.ttf`;
                                const ff = new FontFace(fontName, `url(${fontUrl})`);
                                document.fonts.add(ff);
                                ff.load().catch(() => {});
                            } catch(e) {}
                        }
                    }
                });

                document.fonts.ready.then(() => {
                    const overlay = document.createElement('div');
                    overlay.style.cssText = `position:absolute;top:0;left:0;width:${designW}px;height:${designH}px;transform:scale(${scaleDown});transform-origin:top left;overflow:hidden;pointer-events:none;`;

                    config.layers.forEach(layer => {
                        if (layer.type === 'image') {
                            const lname = (layer.name || '').toLowerCase();
                            const isFrameSlot = lname.startsWith('image');
                            const el = document.createElement('div');
                            el.style.cssText = `position:absolute;left:${layer.x}px;top:${layer.y}px;width:${layer.w || 0}px;height:${layer.h || 0}px;z-index:${layer.z_index || 0};overflow:hidden;`;
                            const img = document.createElement('img');
                            img.style.width = '100%'; img.style.height = '100%';

                            if (isFrameSlot) {
                                // Check AI mappings
                                let mappedSrc = null;
                                const mappings = (aiData && aiData._image_mappings) ? aiData._image_mappings : null;
                                if (mappings) {
                                    const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();
                                    mappedSrc = mappings[lname] || null;
                                    if (!mappedSrc) { for (let k in mappings) { if (k.replace(/[\s\-_]/g,'').toLowerCase()===cleanLName) { mappedSrc=mappings[k]; break; } } }
                                    if (!mappedSrc && (cleanLName==='image1'||cleanLName==='mainimage')) { mappedSrc = mappings['image1']||mappings['main_image']||mappings['image 1']; }
                                }
                                if (mappedSrc) {
                                    const ud = "{{ asset('uploads') }}/";
                                    if (!mappedSrc.startsWith('http')&&!mappedSrc.startsWith('/')&&!mappedSrc.startsWith('data:')) mappedSrc = ud + mappedSrc;
                                    img.src = mappedSrc;
                                } else {
                                    img.src = imgUrl;
                                }
                                img.style.objectFit = 'cover';
                                if (layer.radius) el.style.borderRadius = layer.radius + 'px';
                            } else {
                                let src = layer.src;
                                if (src.includes('../skins/')) src = src.split('/').pop();
                                img.src = `${skinsDir}/${src}`;
                                img.style.objectFit = 'contain';
                            }
                            el.appendChild(img);
                            overlay.appendChild(el);
                        } else if (layer.type === 'text') {
                            const text = (aiData && aiData[layer.name]) ? aiData[layer.name] : (layer.text || '');
                            const el = document.createElement('div');
                            el.innerText = text.replace(/\\n/g, '\n');
                            const fontSize = layer.size || 20;
                            const color = (layer.color || '#000').replace('0x', '#');
                            const isBold = (layer.weight === 'bold' || (layer.font && layer.font.toLowerCase().includes('bold')));
                            el.style.cssText = `position:absolute;left:${layer.x}px;top:${layer.y}px;width:${layer.w||100}px;height:${layer.h||30}px;z-index:${layer.z_index||0};color:${color};font-size:${fontSize}px;font-family:'${layer.font||'sans-serif'}',sans-serif;font-weight:${isBold?'700':(layer.weight||'400')};text-align:${layer.justification||'left'};line-height:${layer.line_height||1.1};overflow:hidden;white-space:pre-wrap;overflow-wrap:break-word;`;
                            if (layer.uppercase) el.style.textTransform = 'uppercase';
                            if (layer.char_spacing) el.style.letterSpacing = ((layer.char_spacing/1000)*fontSize) + 'px';
                            if (layer.shadow) {
                                el.style.textShadow = `${layer.shadow.offsetX||0}px ${layer.shadow.offsetY||0}px ${layer.shadow.blur||0}px ${layer.shadow.color||'rgba(0,0,0,0.5)'}`;
                            }
                            overlay.appendChild(el);
                        }
                    });

                    thumb.style.cssText = 'position:absolute;inset:0;overflow:hidden;';
                    thumb.appendChild(overlay);
                });
            });
        });

        lucide.createIcons();
    </script>
@endsection