@extends('layouts.client')

@section('main_bg', 'bg-[#f8fafc]')

@section('content')
    <div class="fade-in">
        <!-- Header -->
        <div class="px-4 pt-6 pb-4">
            <h1 class="text-[20px] font-bold text-gray-900 tracking-tight">Settings</h1>
        </div>

        <div class="space-y-6">
            <!-- Business Settings -->
            <section>
                <h3 class="text-[17px] font-bold text-gray-900 mb-2 px-5">Business Settings</h3>
                <div class="bg-white border-y border-gray-100">
                    <div class="settings-item cursor-pointer" onclick="openLanguageDrawer()">
                        <div class="flex items-center gap-4">
                            <i data-lucide="languages" class="w-6 h-6 text-gray-500"></i>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 text-[15px]">Preferred Languages</span>
                                <span class="text-[13px] text-gray-400 font-medium" id="selectedLanguagesText">
                                    {{ optional(Auth::user())->preferred_languages ?: 'Select languages' }}
                                </span>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </div>
                    <div class="settings-item">
                        <div class="flex items-center gap-4">
                            <i data-lucide="at-sign" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Add Watermark</span>
                        </div>
                        <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                    </div>
                </div>
            </section>

            <!-- App Preferences -->
            <section>
                <h3 class="text-[17px] font-bold text-gray-900 mb-2 px-5">App Preferences</h3>
                <div class="bg-white border-y border-gray-100">
                    <a href="{{ route('notifications') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="bell" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Notifications</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($notification_count > 0)
                                <span
                                    class="bg-red-500 text-white text-[11px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                                    {{ $notification_count }}
                                </span>
                            @endif
                            <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                        </div>
                    </a>
                    <div class="settings-item">
                        <div class="flex items-center gap-4">
                            <i data-lucide="moon" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Dark Mode</span>
                        </div>
                        <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                    </div>
                    <div class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="globe" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">App Language</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[14px] text-gray-400 font-medium">English</span>
                            <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                        </div>
                    </div>
                    <div class="settings-item">
                        <div class="flex items-center gap-4">
                            <i data-lucide="share-2" class="w-6 h-6 text-gray-500"></i>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 text-[15px]">Add Share Text</span>
                                <span class="text-[13px] text-gray-400 font-medium">Include share text when sharing</span>
                            </div>
                        </div>
                        <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                    </div>
                </div>
            </section>

            <!-- About App -->
            <section>
                <h3 class="text-[17px] font-bold text-gray-900 mb-2 px-5">About App</h3>
                <div class="bg-white border-y border-gray-100">
                    <a href="{{ route('support') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="help-circle" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Help & Support</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </a>
                    <a href="{{ route('faqs') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="message-circle" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">FAQs</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </a>
                    <div class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="rss" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Blog</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </div>
                    <div class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="message-square" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Feedback</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </div>
                    <a href="{{ url('privacy-policy') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="lock" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Privacy Policy</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </a>
                    <a href="{{ url('terms-condition') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="file-text" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Terms & Conditions</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </a>
                    <a href="{{ url('refund-policy') }}" class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="credit-card" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Refund Policy</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </a>
                </div>
            </section>

            <!-- Follow Us & Delete Section -->
            <section class="mt-4">
                <div class="bg-white border-y border-gray-100">
                    <div class="settings-item">
                        <div class="flex items-center gap-4">
                            <i data-lucide="user-plus" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Follow Us</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center text-gray-700 border border-gray-100">
                                <i data-lucide="facebook" class="w-4 h-4"></i>
                            </div>
                            <div
                                class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center text-gray-700 border border-gray-100">
                                <i data-lucide="instagram" class="w-4 h-4 text-gray-800"></i>
                            </div>
                            <div
                                class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center text-gray-700 border border-gray-100 text-[12px] font-black">
                                X</div>
                            <div
                                class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center text-gray-700 border border-gray-100">
                                <i data-lucide="play" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                    <div class="settings-item cursor-pointer">
                        <div class="flex items-center gap-4">
                            <i data-lucide="user-x" class="w-6 h-6 text-gray-500"></i>
                            <span class="font-bold text-gray-800 text-[15px]">Delete Your Account</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                    </div>
                </div>
            </section>

            <!-- Footer Info -->
            <div class="text-center pt-8 pb-12 space-y-4">
                <button
                    class="text-red-500 font-bold text-[18px] tracking-tight hover:brightness-110 active:scale-95 transition-all">Logout</button>
                <div class="flex flex-col items-center gap-1 opacity-60">
                    <p class="text-[13px] font-bold text-gray-600">App Version 6.49</p>
                    <div class="flex items-center gap-1.5 font-bold text-[13px] text-gray-600">
                        Made with <i data-lucide="heart" class="w-4 h-4 text-red-500 fill-red-500"></i> in India
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Language Selection Drawer -->
    <div id="languageDrawer"
        class="hidden fixed inset-0 z-[100] transition-opacity duration-300 bg-black/40 backdrop-blur-sm opacity-0">
        <div
            class="fixed inset-0 md:inset-x-0 md:left-1/2 md:-translate-x-1/2 md:max-w-[450px] md:my-8 md:rounded-[32px] bg-white transform translate-y-full transition-transform duration-300 ease-out flex flex-col shadow-2xl safe-area-inset overflow-hidden">
            <!-- Header -->
            <div class="flex items-center gap-4 px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10 shrink-0">
                <button onclick="closeLanguageDrawer()"
                    class="p-2 -ml-2 text-gray-900 hover:bg-gray-50 rounded-full transition-colors">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </button>
                <h2 class="text-[20px] font-black text-gray-900 tracking-tight">Select Language</h2>
            </div>

            <!-- Search Bar -->
            <div class="px-6 py-4 shrink-0">
                <div class="relative group">
                    <i data-lucide="search"
                        class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <input type="text" id="languageSearch" placeholder="Search languages"
                        class="w-full bg-gray-50 border-0 rounded-2xl py-4 pl-12 pr-4 text-[16px] font-medium text-gray-900 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-gray-400"
                        oninput="filterLanguages()">
                </div>
            </div>

            <!-- List View -->
            <div class="overflow-y-auto flex-1 px-6 custom-scrollbar min-h-0" id="languageListContainer">
                @php
                    $all_indian_languages = ['Hindi', 'English', 'Gujarati', 'Marathi', 'Telugu', 'Tamil', 'Bengali', 'Punjabi', 'Kannada', 'Malayalam', 'Urdu', 'Odia', 'Assamese', 'Maithili', 'Sanskrit', 'Konkani', 'Manipuri'];
                    $selected_langs = array_map('trim', explode(',', optional(Auth::user())->preferred_languages ?: ''));
                @endphp
                <div class="space-y-1 py-2">
                    @foreach($all_indian_languages as $lang)
                        <div class="language-row group flex items-center justify-between py-4 pr-1 border-b border-gray-50 cursor-pointer active:bg-gray-50 transition-colors {{ in_array($lang, $selected_langs) ? 'is-selected' : '' }}"
                            onclick="toggleLanguage(this, '{{ $lang }}')" data-lang-name="{{ strtolower($lang) }}">
                            <span
                                class="text-[17px] font-bold text-gray-800 transition-colors group-[.is-selected]:text-blue-600">{{ $lang }}</span>
                            <div
                                class="selection-indicator transition-all transform {{ in_array($lang, $selected_langs) ? 'scale-100 opacity-100' : 'scale-0 opacity-0' }}">
                                <div
                                    class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center shadow-lg shadow-blue-200">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-white stroke-[3]"></i>
                                </div>
                            </div>
                            <input type="checkbox" name="languages[]" value="{{ $lang }}" class="hidden" {{ in_array($lang, $selected_langs) ? 'checked' : '' }}>
                        </div>
                    @endforeach
                    <!-- No Results Placeholder -->
                    <div id="noResults" class="hidden flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i data-lucide="languages" class="w-8 h-8 text-gray-300"></i>
                        </div>
                        <p class="text-gray-400 font-bold text-[16px]">No languages found</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="p-6 border-t border-gray-50 bg-white/80 backdrop-blur-md shrink-0 flex gap-4">
                <button onclick="closeLanguageDrawer()"
                    class="flex-1 py-4 text-gray-500 font-bold text-[16px] rounded-2xl border-2 border-transparent hover:bg-gray-50 transition-all">Cancel</button>
                <button onclick="applyLanguages()"
                    class="flex-[1.5] py-4 bg-blue-600 text-white font-bold text-[16px] rounded-2xl shadow-xl shadow-blue-200 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span id="applyButtonText">Apply Changes</span>
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
    </div>

    <style>
        .settings-item {
            @apply flex items-center justify-between p-5 border-b border-gray-50 last:border-0 hover:bg-gray-50/80 active:bg-gray-100/50 transition-colors;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #e2e8f0;
            transition: .3s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:checked+.slider {
            background-color: #3b82f6;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .language-row.is-selected .selection-indicator {
            @apply scale-100 opacity-100;
        }

        .language-row.is-selected {
            @apply bg-blue-50/30;
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>

    <script>
        function openLanguageDrawer() {
            const drawer = document.getElementById('languageDrawer');
            const content = drawer.querySelector('div');
            drawer.classList.remove('hidden');
            setTimeout(() => {
                drawer.classList.add('opacity-100');
                content.classList.remove('translate-y-full');
            }, 50);
        }

        function closeLanguageDrawer() {
            const drawer = document.getElementById('languageDrawer');
            const content = drawer.querySelector('div');
            drawer.classList.remove('opacity-100');
            content.classList.add('translate-y-full');
            setTimeout(() => {
                drawer.classList.add('hidden');
            }, 300);
        }

        function toggleLanguage(row, lang) {
            const checkbox = row.querySelector('input');
            const indicator = row.querySelector('.selection-indicator');
            const selectedCount = document.querySelectorAll('input[name="languages[]"]:checked').length;

            if (!checkbox.checked && selectedCount >= 5) {
                showToast("You can select up to 5 languages maximum", "warning");
                return;
            }

            checkbox.checked = !checkbox.checked;

            if (checkbox.checked) {
                row.classList.add('is-selected');
                indicator.classList.remove('scale-0', 'opacity-0');
                indicator.classList.add('scale-100', 'opacity-100');
            } else {
                row.classList.remove('is-selected');
                indicator.classList.add('scale-0', 'opacity-0');
                indicator.classList.remove('scale-100', 'opacity-100');
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-24 left-1/2 -translate-x-1/2 px-6 py-3 rounded-2xl text-white font-bold text-[14px] shadow-2xl z-[200] transition-all transform translate-y-2 opacity-0 ${type === 'warning' ? 'bg-orange-500' : 'bg-blue-600'}`;
            toast.innerText = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function filterLanguages() {
            const query = document.getElementById('languageSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.language-row');
            let hasResults = false;

            rows.forEach(row => {
                const name = row.getAttribute('data-lang-name');
                if (name.includes(query)) {
                    row.classList.remove('hidden');
                    hasResults = true;
                } else {
                    row.classList.add('hidden');
                }
            });

            document.getElementById('noResults').classList.toggle('hidden', hasResults);
        }

        function applyLanguages() {
            const selected = Array.from(document.querySelectorAll('input[name="languages[]"]:checked')).map(cb => cb.value);
            const btn = document.getElementById('applyButtonText');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Applying...';
            lucide.createIcons();

            fetch('{{ route('update.preferred_languages') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ languages: selected })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('selectedLanguagesText').innerText = data.preferred_languages || 'Select languages';
                        closeLanguageDrawer();

                        // Reset button text
                        btn.innerText = 'Apply Changes';
                        lucide.createIcons();
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerText = 'Try Again';
                });
        }
    </script>
@endsection