@extends('layouts.client')

@section('content')
    <div class="fade-in bg-white min-h-screen relative">
        <!-- Profile Header -->
        <div class="px-6 pt-10 pb-8 flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div
                    class="w-16 h-16 rounded-full flex items-center justify-center border border-slate-200 overflow-hidden {{ isset($business->logo) ? 'bg-white' : 'bg-slate-100' }}">
                    @if($business && $business->logo)
                        <img src="{{ asset('uploads/' . $business->logo) }}" class="w-full h-full object-cover"
                            onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/initials/svg?seed={{ urlencode($business->name ?? optional(Auth::user())->name) }}&backgroundColor=312e81'">
                    @else
                        <i data-lucide="user" class="w-9 h-9 text-slate-400"></i>
                    @endif
                </div>
                <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">{{ $business->name ?? optional(Auth::user())->name }}
                </h2>
            </div>
            <a href="{{ route('client.business.edit') }}"
                class="text-indigo-600 font-bold text-[15px] tracking-wide">EDIT</a>
        </div>

        <!-- Action Grid -->
        <div class="px-6 grid grid-cols-2 gap-4 mb-8">
            <a href="{{ route('setup.wizard') }}"
                class="bg-white border border-slate-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] rounded-3xl p-7 flex flex-col items-center gap-4 active:scale-95 transition-all text-center">
                <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center shadow-md shadow-indigo-200">
                    <i data-lucide="bot" class="w-6 h-6 text-white"></i>
                </div>
                <span class="font-bold text-gray-700 text-[14px]">AI Setup</span>
            </a>
            <a href="{{ route('products') }}"
                class="bg-white border border-slate-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] rounded-3xl p-7 flex flex-col items-center gap-4 active:scale-95 transition-all text-center">
                <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center">
                    <i data-lucide="package" class="w-6 h-6 text-slate-600"></i>
                </div>
                <span class="font-bold text-gray-700 text-[14px]">Products</span>
            </a>
            <a href="{{ route('catalogue.columns') }}"
                class="bg-white border border-slate-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] rounded-3xl p-7 flex flex-col items-center gap-4 active:scale-95 transition-all text-center col-span-2">
                <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center">
                    <i data-lucide="layout-list" class="w-6 h-6 text-slate-600"></i>
                </div>
                <span class="font-bold text-gray-700 text-[14px]">Catalogue Setting</span>
            </a>
            <div
                class="bg-white border border-slate-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] rounded-3xl p-7 flex flex-col items-center gap-4 active:scale-95 transition-all">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 flex items-center justify-center">
                    <i data-lucide="briefcase" class="w-6 h-6 text-indigo-500"></i>
                </div>
                <span class="font-bold text-gray-700 text-[14px]">My Businesses</span>
            </div>
            <div
                class="bg-white border border-slate-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] rounded-3xl p-7 flex flex-col items-center gap-4 active:scale-95 transition-all">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 flex items-center justify-center">
                    <i data-lucide="download" class="w-6 h-6 text-indigo-500"></i>
                </div>
                <span class="font-bold text-gray-700 text-[14px]">Downloads</span>
            </div>
        </div>

        <div class="px-2 space-y-1">
            <a href="{{ route('client.business.edit') }}"
                class="settings-item border-0 hover:bg-slate-50 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-slate-50 flex items-center justify-center text-gray-400">
                        <i data-lucide="layout" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-[16px]">Business Profile</span>
                </div>
                <i data-lucide="chevron-right" class="w-5.5 h-5.5 text-gray-300"></i>
            </a>
            <a href="{{ route('notifications') }}"
                class="settings-item border-0 hover:bg-slate-50 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-slate-50 flex items-center justify-center text-red-400">
                        <i data-lucide="bell" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-[16px]">Notifications</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($notification_count > 0)
                        <span
                            class="bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full">{{ $notification_count }}</span>
                    @endif
                    <i data-lucide="chevron-right" class="w-5.5 h-5.5 text-gray-300"></i>
                </div>
            </a>
        </div>

        <div class="px-6 mt-10 mb-4">
            <h3 class="text-[18px] font-bold text-gray-600 tracking-tight">Help & Support</h3>
        </div>

        <div class="px-2 pb-40">
            <a href="{{ route('support') }}"
                class="settings-item border-0 hover:bg-slate-50 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-slate-50 flex items-center justify-center text-gray-400">
                        <i data-lucide="message-circle" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-[16px]">Help & Support</span>
                </div>
                <i data-lucide="chevron-right" class="w-5.5 h-5.5 text-gray-300"></i>
            </a>
            <a href="{{ route('faqs') }}"
                class="settings-item border-0 hover:bg-slate-50 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-slate-50 flex items-center justify-center text-gray-400">
                        <i data-lucide="help-circle" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-[16px]">FAQs</span>
                </div>
                <i data-lucide="chevron-right" class="w-5.5 h-5.5 text-gray-300"></i>
            </a>
        </div>
    </div>
@endsection