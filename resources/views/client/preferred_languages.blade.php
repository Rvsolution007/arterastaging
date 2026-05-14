@extends('layouts.client')

@section('main_bg', 'bg-white')

@section('content')
    <div class="fade-in pb-32">
        <!-- Header -->
        <div
            class="px-5 pt-7 pb-5 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-10 border-b border-gray-50">
            <div class="flex items-center gap-4">
                <a href="{{ route('more') }}"
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-900 border border-gray-100 active:scale-90 transition-transform">
                    <i data-lucide="chevron-left" class="w-6 h-6"></i>
                </a>
                <h1 class="text-[20px] font-bold text-gray-900 tracking-tight">Preferred Languages</h1>
            </div>
            <button onclick="savePreferredLanguages()" class="font-bold text-blue-600 text-[15px]">Save</button>
        </div>

        <div class="px-5 mt-4">
            <p class="text-[14px] text-gray-500 font-medium mb-6">Select languages you want to see posts in</p>
        </div>

        <!-- Language List -->
        <div class="px-5 grid grid-cols-2 gap-4">
            @php
                $selected = explode(',', optional(Auth::user())->preferred_languages ?? '');
            @endphp
            @foreach($languages as $language)
                <div onclick="toggleLanguage({{ $language->id }})" class="language-card relative group cursor-pointer"
                    id="lang-{{ $language->id }}">
                    <div
                        class="lang-container aspect-square rounded-[2rem] bg-gray-50 border-2 transition-all duration-300 flex flex-col items-center justify-center p-6 gap-3
                                        {{ in_array($language->id, $selected) ? 'border-blue-500 bg-blue-50/50' : 'border-gray-100 hover:border-blue-200' }}">

                        <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-sm border border-white">
                            <img src="{{ asset('uploads/' . $language->image) }}" class="w-full h-full object-cover"
                                alt="{{ $language->title }}">
                        </div>

                        <span
                            class="lang-title font-bold text-[15px] capitalize {{ in_array($language->id, $selected) ? 'text-blue-600' : 'text-gray-700' }}">
                            {{ $language->title }}
                        </span>

                        <div
                            class="check-icon absolute top-4 right-4 w-7 h-7 bg-blue-500 rounded-full flex items-center justify-center shadow-lg shadow-blue-500/30 transition-all duration-300 {{ in_array($language->id, $selected) ? 'opacity-100 scale-100' : 'opacity-0 scale-50' }}">
                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Float Save Button -->
        <div class="fixed bottom-24 left-0 right-0 px-5">
            <button onclick="savePreferredLanguages()"
                class="w-full py-4 bg-gray-900 text-white rounded-2xl font-bold text-[16px] shadow-xl active:scale-95 transition-transform">
                Save Preferences
            </button>
        </div>

        <!-- Success Toast -->
        <div id="toast"
            class="fixed bottom-32 left-1/2 -translate-x-1/2 px-6 py-3 bg-gray-900 text-white rounded-full font-bold text-[14px] shadow-xl opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
            Preferences Saved!
        </div>
    </div>

    <style>
        .language-card:active {
            transform: scale(0.96);
        }
    </style>

    <script>
        let selectedLanguages = @json($selected).map(id => parseInt(id)).filter(id => !isNaN(id));

        function toggleLanguage(id) {
            const index = selectedLanguages.indexOf(id);
            const container = document.querySelector(`#lang-${id} .lang-container`);
            const title = document.querySelector(`#lang-${id} .lang-title`);
            const icon = document.querySelector(`#lang-${id} .check-icon`);

            if (index > -1) {
                selectedLanguages.splice(index, 1);
                container.classList.remove('border-blue-500', 'bg-blue-50/50');
                container.classList.add('border-gray-100');
                title.classList.remove('text-blue-600');
                title.classList.add('text-gray-700');
                icon.classList.add('opacity-0', 'scale-50');
                icon.classList.remove('opacity-100', 'scale-100');
            } else {
                selectedLanguages.push(id);
                container.classList.add('border-blue-500', 'bg-blue-50/50');
                container.classList.remove('border-gray-100');
                title.classList.add('text-blue-600');
                title.classList.remove('text-gray-700');
                icon.classList.add('opacity-100', 'scale-100');
                icon.classList.remove('opacity-0', 'scale-50');
            }
        }

        function savePreferredLanguages() {
            fetch("{{ route('preferred.languages.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ language_ids: selectedLanguages })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast();
                        setTimeout(() => {
                            window.location.href = "{{ route('more') }}";
                        }, 800);
                    }
                });
        }

        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('opacity-0', 'translate-y-4');
            toast.classList.add('opacity-100', 'translate-y-0');
        }
    </script>
@endsection