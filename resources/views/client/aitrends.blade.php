@extends('layouts.client')

@section('content')
    <div class="fade-in space-y-6">
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

        <!-- AI Trends Section -->
        <div class="space-y-5">
            <div class="px-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center shadow-sm">
                        <i data-lucide="sparkles" class="w-5.5 h-5.5 text-indigo-500"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-[18px] font-bold text-gray-900 tracking-tight">AI Trends</h3>
                        <span
                            class="bg-indigo-100/50 text-indigo-600 text-[10px] font-bold px-2.5 py-1 rounded-md">NEW</span>
                    </div>
                </div>
                <button
                    class="text-indigo-600 text-[14px] font-bold flex items-center gap-1 hover:opacity-70 transition-opacity">View
                    All <i data-lucide="chevron-right" class="w-4.5 h-4.5"></i></button>
            </div>

            <div class="px-4 flex gap-3 overflow-x-auto scrollbar-hide">
                @foreach($custom_posts as $index => $post)
                    <button
                        class="px-6 py-3 rounded-full text-[14px] font-bold {{ $index == 0 ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-slate-100 text-gray-500' }} whitespace-nowrap active:scale-95 transition-all">{{ $post->name }}</button>
                @endforeach
            </div>

            <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-1">
                @foreach($custom_posts as $post)
                    <div
                        class="rounded-3xl min-w-[180px] aspect-[4/5] relative overflow-hidden cursor-pointer group shadow-md active:scale-95 transition-all border border-gray-50">
                        <img src="{{ asset('uploads/' . $post->icon) }}"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Business Special Section -->
        <div class="space-y-5">
            <div class="px-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center shadow-sm">
                        <i data-lucide="briefcase" class="w-5.5 h-5.5 text-emerald-500"></i>
                    </div>
                    <h3 class="text-[18px] font-bold text-gray-900 tracking-tight">Business Special</h3>
                </div>
                <button
                    class="text-indigo-600 text-[14px] font-bold flex items-center gap-1 hover:opacity-70 transition-opacity">View
                    All <i data-lucide="chevron-right" class="w-4.5 h-4.5"></i></button>
            </div>
            <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-1">
                <div
                    class="min-w-[180px] aspect-[4/5] rounded-[2rem] overflow-hidden shadow-md hover:shadow-lg transition-all cursor-pointer group active:scale-95 border border-gray-50">
                    <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=400&q=80"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                </div>
                <div
                    class="min-w-[180px] aspect-[4/5] rounded-[2rem] overflow-hidden shadow-md hover:shadow-lg transition-all cursor-pointer group active:scale-95 border border-gray-50">
                    <img src="https://images.unsplash.com/photo-1604594849809-dfedbc827105?w=400&q=80"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                </div>
            </div>
        </div>

        <!-- Reels Maker Section -->
        <div class="space-y-5 pb-32">
            <div class="px-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-sky-50 flex items-center justify-center shadow-sm">
                        <i data-lucide="play" class="w-5.5 h-5.5 text-sky-500 fill-sky-500"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-[18px] font-bold text-gray-900 tracking-tight">Reels Maker</h3>
                        <span
                            class="bg-rose-50 text-rose-500 text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider">HOT</span>
                    </div>
                </div>
                <button
                    class="text-indigo-600 text-[14px] font-bold flex items-center gap-1 hover:opacity-70 transition-opacity">View
                    All <i data-lucide="chevron-right" class="w-4.5 h-4.5"></i></button>
            </div>
            <div class="px-4 flex gap-4 overflow-x-auto scrollbar-hide py-1">
                @foreach($videos as $video)
                    <div
                        class="min-w-[130px] aspect-[9/16] rounded-[1.8rem] overflow-hidden shadow-md hover:shadow-lg transition-all cursor-pointer group active:scale-95 border border-gray-50">
                        <img src="{{ asset('uploads/' . $video->video) }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection