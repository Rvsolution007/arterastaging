@extends('layouts.client')

@section('main_bg', 'bg-[#f8fafc]')

@section('content')
    <div class="fade-in space-y-2 pb-32">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-100 sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-sm overflow-hidden">
                    @if(isset($business->logo))
                        <img src="{{ asset('uploads/' . $business->logo) }}" class="w-full h-full object-cover">
                    @else
                        <i data-lucide="user" class="w-5 h-5 text-white"></i>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-gray-900 font-bold text-[14px] leading-tight">{{ $business->name ?? 'Rv Ceramic' }}</span>
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

        <!-- Search Bar -->
        <div class="mx-4 my-4">
            <div
                class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl px-5 py-3.5 transition-all focus-within:border-indigo-200 focus-within:shadow-md group">
                <i data-lucide="search"
                    class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
                <input type="text" placeholder="Search templates, categories..."
                    class="flex-1 bg-transparent outline-none text-gray-900 placeholder:text-gray-400 text-[14px]" />
                <button
                    class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center transition-all hover:bg-slate-100 active:scale-90">
                    <i data-lucide="mic" class="w-4 h-4 text-gray-400"></i>
                </button>
            </div>
        </div>

        <div class="px-4 space-y-8 mt-2">
            <section>
                <h3 class="text-[17px] font-bold text-[#1e293b] mb-5 tracking-tight flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                    Create Something New
                </h3>
                <div class="flex gap-6 overflow-x-auto scrollbar-hide py-2 px-1 items-end">
                    <!-- Post -->
                    <div
                        class="flex flex-col items-center gap-2.5 min-w-[110px] group cursor-pointer active:scale-[0.98] transition-all">
                        <div
                            class="w-full aspect-square rounded-[1.8rem] bg-gradient-to-b from-[#dae0eb] to-[#e4d6eb] flex items-center justify-center shadow-sm border border-white/50 relative overflow-hidden">
                            <div class="absolute inset-0 bg-white/10 active:bg-black/5 transition-colors"></div>
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center border-[1.5px] border-[#4b5563]/30">
                                <i data-lucide="layout-grid" class="w-5 h-5 text-[#4b5563]/70 relative z-10"></i>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="font-bold text-[13px] text-gray-700 tracking-tight">Post</span>
                            <span class="text-[11px] font-medium text-slate-500">2000×2000</span>
                        </div>
                    </div>
                    <!-- Story / Reel -->
                    <div
                        class="flex flex-col items-center gap-2.5 min-w-[95px] group cursor-pointer active:scale-[0.98] transition-all">
                        <div
                            class="w-full aspect-[9/16] rounded-[1.5rem] bg-gradient-to-b from-[#fce4e6] to-[#f4f3ca] flex items-center justify-center shadow-sm border border-white/50 relative overflow-hidden">
                            <div class="absolute inset-0 bg-white/10 active:bg-black/5 transition-colors"></div>
                            <div
                                class="w-10 h-10 rounded-2xl flex items-center justify-center border-[1.5px] border-[#7c2d3d]/30 relative">
                                <div class="flex flex-col items-center pt-0.5">
                                    <i data-lucide="clapperboard" class="w-6 h-6 text-[#7c2d3d]/60 mb-[-4px]"></i>
                                    <i data-lucide="plus" class="w-3.5 h-3.5 text-[#7c2d3d]/80"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="font-bold text-[13px] text-gray-700 tracking-tight">Story</span>
                            <span class="text-[11px] font-medium text-slate-500">1080×1920</span>
                        </div>
                    </div>
                    <!-- Ads / Infographics -->
                    <div
                        class="flex flex-col items-center gap-2.5 min-w-[110px] group cursor-pointer active:scale-[0.98] transition-all">
                        <div
                            class="w-full aspect-[9/16] rounded-[1.8rem] bg-gradient-to-b from-[#d5efed] to-[#e4eef1] flex items-center justify-center shadow-sm border border-white/50 relative overflow-hidden">
                            <div class="absolute inset-0 bg-white/10 active:bg-black/5 transition-colors"></div>
                            <div
                                class="w-11 h-11 rounded-lg flex items-center justify-center border-[1.5px] border-[#1e3a8a]/30">
                                <i data-lucide="image" class="w-6 h-6 text-[#1e3a8a]/60 relative z-10"></i>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <span
                                class="font-bold text-[13px] text-gray-700 tracking-tight text-center">Ads/Infographics</span>
                            <span class="text-[11px] font-medium text-slate-500">2000×2500</span>
                        </div>
                    </div>
                </div>
            </section>

            @if(isset($my_custom_frames) && $my_custom_frames->count() > 0)
            <section>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-[17px] font-bold text-[#1e293b] tracking-tight flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                            <i data-lucide="layout-template" class="w-4 h-4 text-blue-500"></i>
                        </div>
                        My Custom Posts
                    </h3>
                </div>
                <div class="grid grid-cols-2 gap-4 py-1 px-1 place-items-center" id="custom-frames-container">
                    @foreach($my_custom_frames as $frame)
                        <a href="{{ route('universal.edit', ['type' => 'business_custom_frame', 'id' => $frame->db_id]) }}"
                            class="w-full max-w-[155px] rounded-2xl overflow-hidden shadow-md border border-gray-100 active:scale-95 transition-transform group relative bg-white"
                            data-frame-id="{{ $frame->db_id }}"
                            id="frame-card-{{ $frame->db_id }}">
                            {{-- Shimmer placeholder (sits on top) --}}
                            <div class="cf-shimmer absolute inset-0 z-20" id="shimmer-{{ $frame->db_id }}">
                                <div class="w-full h-full bg-gradient-to-r from-slate-100 via-slate-200 to-slate-100 animate-pulse rounded-2xl"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-8 h-8 rounded-full border-2 border-indigo-200 border-t-indigo-500 animate-spin"></div>
                                        <span class="text-[10px] font-semibold text-slate-400 animate-pulse">Rendering...</span>
                                    </div>
                                </div>
                            </div>
                            {{-- DOM-based rendering container - will be sized by JS --}}
                            <div class="dom-preview w-full overflow-hidden relative" id="preview-{{ $frame->db_id }}" style="background:#fff;"></div>
                        </a>
                    @endforeach
                </div>
            </section>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
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
                console.warn('Render failed #'+config.db_id, e);
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

        var container = document.getElementById('preview-' + frameId);
        if (!container || !jsonRules || !jsonRules.layers) {
            hideShimmer(frameId);
            return;
        }

        var designW = (jsonRules.info && jsonRules.info.width) || 1080;
        var designH = (jsonRules.info && jsonRules.info.height) || 1080;
        var areaW = container.clientWidth || 220;
        var scale = areaW / designW;
        var scaledH = designH * scale;

        // Set the container height based on actual design dimensions
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
                    // Solid color background
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

        // Wait for all images to load before hiding shimmer
        if (pendingImages.length > 0) {
            var loaded = 0;
            var total = pendingImages.length;
            var done = false;
            function checkDone() {
                loaded++;
                if (!done && loaded >= total) {
                    done = true;
                    hideShimmer(frameId);
                }
            }
            pendingImages.forEach(function(img) {
                if (img.complete && img.naturalWidth > 0) {
                    checkDone();
                } else {
                    img.addEventListener('load', checkDone);
                    img.addEventListener('error', checkDone);
                }
            });
            // Fallback: hide shimmer after 4 seconds regardless
            setTimeout(function() {
                if (!done) { done = true; hideShimmer(frameId); }
            }, 4000);
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
        var s = document.getElementById('shimmer-'+frameId);
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
@endsection
