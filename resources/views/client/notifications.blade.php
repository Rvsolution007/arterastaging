@extends('layouts.client')

@section('content')
    <div class="fade-in bg-white min-h-screen pb-20">
        <!-- Header -->
        <div class="px-6 pt-10 pb-6 flex items-center gap-4">
            <a href="{{ route('business') }}"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-50 text-slate-600 active:scale-90 transition-transform">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </a>
            <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">Notifications</h2>
        </div>

        <div class="px-6 space-y-4">
            @forelse($notifications as $notification)
                <div
                    class="bg-white border border-slate-100 p-4 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.05)] flex gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex-shrink-0 flex items-center justify-center">
                        @if($notification->image)
                            <img src="{{ asset('uploads/' . $notification->image) }}"
                                class="w-full h-full object-cover rounded-2xl">
                        @else
                            <i data-lucide="bell" class="w-6 h-6 text-indigo-500"></i>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-bold text-gray-900 text-[15px] leading-tight">{{ $notification->title }}</h3>
                            <span
                                class="text-[10px] font-bold text-slate-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-500 text-[13px] leading-relaxed line-clamp-2">{{ $notification->message }}</p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="bell-off" class="w-10 h-10 text-slate-300"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">No Notifications</h3>
                    <p class="text-slate-400 text-sm max-w-[200px]">You haven't received any notifications yet.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        lucide.createIcons();
    </script>
@endsection