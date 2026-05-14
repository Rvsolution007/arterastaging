<div class="space-y-2">
    <a href="{{ route('universal.edit', ['type' => 'post', 'id' => $post->id]) }}" 
        class="block aspect-square rounded-[2rem] overflow-hidden shadow-sm border border-white bg-white active:scale-[0.97] transition-all group relative">
        <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $post->frame_image)}}@else{{ asset('uploads/' . $post->frame_image) }}@endif" 
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            onerror="this.src='https://via.placeholder.com/300?text=Post'">
        
        @if($post->ai_generated_content)
            @php
                $templateConfig = $post->getTemplateConfig();
            @endphp
            <div class="ai-thumbnail-generator" 
                 data-img-url="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $post->frame_image)}}@else{{ asset('uploads/' . $post->frame_image) }}@endif"
                 data-template-config="{{ json_encode($templateConfig) }}"
                 style="position: absolute; inset: 0; pointer-events: none;">
            </div>
        @endif

        <div class="absolute inset-0 bg-black/0 group-active:bg-black/5 transition-colors text-white"></div>
    </a>
    @if($post->business_sub_category)
        <div class="px-2 text-center">
            <span class="text-[10px] font-semibold text-indigo-500 line-clamp-1">{{ $post->business_sub_category->name }}</span>
        </div>
    @endif
</div>
