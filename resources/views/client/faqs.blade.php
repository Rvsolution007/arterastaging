@extends('layouts.client')

@section('content')
    <div class="fade-in bg-white min-h-screen pb-20">
        <!-- Header -->
        <div class="px-6 pt-10 pb-6 flex items-center gap-4">
            <a href="{{ route('business') }}"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-50 text-slate-600 active:scale-90 transition-transform">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </a>
            <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">Common Questions</h2>
        </div>

        <div class="px-6 space-y-4">
            @php
                $faqs = [
                    [
                        'q' => 'How to edit my business profile?',
                        'a' => 'Go to the Business tab and click on the "EDIT" button at the top right of your profile header. You can then update your name, logo, and other details.'
                    ],
                    [
                        'q' => 'Where can I see my downloads?',
                        'a' => 'You can access all your saved designs and downloaded content through the "Downloads" button on the Business page action grid.'
                    ],
                    [
                        'q' => 'How to change the language?',
                        'a' => 'You can change the language from the "More" tab under App Preferences. This will update the content language across the app.'
                    ],
                    [
                        'q' => 'Are the templates free to use?',
                        'a' => 'Most templates are free. Premium templates are marked with a "Pro" badge and require an active subscription to download.'
                    ],
                    [
                        'q' => 'How can I contact support?',
                        'a' => 'Visit the "Help & Support" section on the Business page to connect with us via WhatsApp, Call, or Email.'
                    ]
                ];
            @endphp

            @foreach($faqs as $index => $faq)
                <div class="faq-item border border-slate-100 rounded-3xl overflow-hidden transition-all duration-300">
                    <button onclick="toggleFaq({{ $index }})"
                        class="w-full flex items-center justify-between p-6 bg-white hover:bg-slate-50/50 transition-colors text-left">
                        <span class="font-bold text-gray-800 pr-4">{{ $faq['q'] }}</span>
                        <i data-lucide="chevron-down" id="faq-icon-{{ $index }}"
                            class="w-5 h-5 text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div id="faq-ans-{{ $index }}" class="hidden px-6 pb-6 animate-fade-in">
                        <p class="text-slate-500 text-[14px] leading-relaxed border-t border-slate-50 pt-4">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="px-6 mt-12 text-center">
            <p class="text-slate-400 text-sm mb-4">Still have questions?</p>
            <a href="{{ route('support') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-full font-bold shadow-lg shadow-indigo-100 active:scale-95 transition-all">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                Contact Support
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        lucide.createIcons();

        function toggleFaq(index) {
            const ans = document.getElementById(`faq-ans-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);

            // Toggle current
            const isHidden = ans.classList.contains('hidden');

            // Close all others
            document.querySelectorAll('[id^="faq-ans-"]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.style.transform = 'rotate(0deg)');

            if (isHidden) {
                ans.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            }
        }
    </script>
@endsection