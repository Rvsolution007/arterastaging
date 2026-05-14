@extends('layouts.client')

@section('content')
    <div class="fade-in bg-white min-h-screen pb-20">
        <!-- Header -->
        <div class="px-6 pt-10 pb-6 flex items-center gap-4">
            <a href="{{ route('more') }}"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-50 text-slate-600 active:scale-90 transition-transform">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </a>
            <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">Refund Policy</h2>
        </div>

        <div class="px-6 text-center mb-8">
            <div class="w-20 h-20 bg-emerald-50 rounded-3xl mx-auto flex items-center justify-center mb-4">
                <i data-lucide="credit-card" class="w-10 h-10 text-emerald-600"></i>
            </div>
            <p class="text-slate-500 text-sm px-6">Our refund policy ensures your satisfaction with our services.</p>
        </div>

        <!-- Content -->
        <div class="px-6">
            <div class="bg-slate-50 rounded-3xl p-6">
                <div class="prose prose-sm prose-slate max-w-none text-slate-600 leading-relaxed">
                    {!! App\Models\OtherSetting::getOtherSetting('refund_policy') !!}
                </div>
            </div>
        </div>

        <div class="px-6 mt-6 text-center">
            <p class="text-[11px] font-bold text-slate-300 uppercase tracking-[0.2em]">
                Last updated: {{ date('F Y') }}
            </p>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        lucide.createIcons();
    </script>
@endsection