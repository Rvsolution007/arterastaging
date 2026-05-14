@extends('layouts.client')

@section('content')
    <div class="fade-in bg-white min-h-screen pb-20">
        <!-- Header -->
        <div class="px-6 pt-10 pb-6 flex items-center gap-4">
            <a href="{{ route('business') }}"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-50 text-slate-600 active:scale-90 transition-transform">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </a>
            <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">Help & Support</h2>
        </div>

        <div class="px-6 text-center mb-10">
            <div class="w-24 h-24 bg-indigo-50 rounded-3xl mx-auto flex items-center justify-center mb-6">
                <i data-lucide="headphones" class="w-12 h-12 text-indigo-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">How can we help?</h3>
            <p class="text-slate-500 text-sm px-10">Choose your preferred way to connect with our support team.</p>
        </div>

        <div class="px-6 space-y-4">
            <!-- WhatsApp -->
            @if(isset($app_setting['whatsapp_number']))
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $app_setting['whatsapp_number']) }}" target="_blank"
                    class="flex items-center justify-between p-5 bg-emerald-50 rounded-3xl active:scale-95 transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                            <i data-lucide="message-square" class="w-6 h-6"></i>
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="font-bold text-emerald-900 text-[16px]">WhatsApp Us</span>
                            <span class="text-emerald-600/70 text-xs font-semibold uppercase tracking-wider">Fast
                                Response</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="w-6 h-6 text-emerald-400"></i>
                </a>
            @endif

            <!-- Call -->
            @if(isset($app_setting['contact']))
                <a href="tel:{{ $app_setting['contact'] }}"
                    class="flex items-center justify-between p-5 bg-sky-50 rounded-3xl active:scale-95 transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-sky-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-sky-200">
                            <i data-lucide="phone" class="w-6 h-6"></i>
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="font-bold text-sky-900 text-[16px]">Call Support</span>
                            <span
                                class="text-sky-600/70 text-xs font-semibold uppercase tracking-wider">{{ $app_setting['contact'] }}</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="w-6 h-6 text-sky-400"></i>
                </a>
            @endif

            <!-- Email -->
            @if(isset($app_setting['email']))
                <a href="mailto:{{ $app_setting['email'] }}"
                    class="flex items-center justify-between p-5 bg-indigo-50 rounded-3xl active:scale-95 transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                            <i data-lucide="mail" class="w-6 h-6"></i>
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="font-bold text-indigo-900 text-[16px]">Email Us</span>
                            <span
                                class="text-indigo-600/70 text-xs font-semibold uppercase tracking-wider">{{ $app_setting['email'] }}</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="w-6 h-6 text-indigo-400"></i>
                </a>
            @endif
        </div>

        <div
            class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-6 bg-gradient-to-t from-white via-white pointer-events-none">
            <p class="text-center text-[11px] font-bold text-slate-300 uppercase tracking-[0.2em] pointer-events-auto">
                Support available 24/7
            </p>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        lucide.createIcons();
    </script>
@endsection