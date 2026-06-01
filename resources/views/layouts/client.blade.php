<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Application')</title>
    <script>
        // One-time cleanup: unregister any old service workers that may be blocking CDN resources
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => {
                regs.forEach(r => r.unregister());
            });
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Pacifico&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        border: "hsl(220 15% 90%)",
                        input: "hsl(220 15% 90%)",
                        ring: "hsl(230 65% 55%)",
                        background: "hsl(220 20% 97%)",
                        foreground: "hsl(220 20% 15%)",
                        primary: {
                            DEFAULT: "hsl(230 65% 55%)",
                            foreground: "hsl(0 0% 100%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(220 15% 94%)",
                            foreground: "hsl(220 10% 45%)",
                        },
                        destructive: {
                            DEFAULT: "hsl(0 65% 55%)",
                            foreground: "hsl(0 0% 100%)",
                        },
                        muted: {
                            DEFAULT: "hsl(220 15% 94%)",
                            foreground: "hsl(220 10% 45%)",
                        },
                        accent: {
                            DEFAULT: "hsl(250 60% 60%)",
                            foreground: "hsl(0 0% 100%)",
                        },
                        card: {
                            DEFAULT: "hsl(0 0% 100%)",
                            foreground: "hsl(220 20% 15%)",
                        },
                        fab: "hsl(250 60% 60%)",
                        success: "hsl(160 60% 45%)",
                        warning: "hsl(35 90% 55%)",
                        info: "hsl(200 80% 55%)",
                    },
                    borderRadius: {
                        lg: "1rem",
                        xl: "1.25rem",
                        "2xl": "1.5rem",
                        "3xl": "2rem",
                        "4xl": "2.5rem",
                        "5xl": "3rem",
                    },
                    animation: {
                        'fade-in': 'fade-in 0.4s ease-out',
                        'scale-in': 'scale-in 0.2s ease-out',
                    },
                    keyframes: {
                        'fade-in': { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        'scale-in': { '0%': { transform: 'scale(0.95)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --primary: 230 65% 55%;
            --accent: 250 60% 60%;
            --gradient-primary: linear-gradient(135deg, hsl(230 65% 55%), hsl(250 60% 60%));
        }

        body {
            overscroll-behavior: none;
            touch-action: manipulation;
            background-color: white;
            overflow: hidden;
        }

        .mobile-container {
            max-width: 448px;
            margin: 0 auto;
            height: 100vh;
            position: relative;
            background-color: hsl(220 20% 97%);
            overflow: hidden;
        }

        .bg-gradient-primary {
            background: var(--gradient-primary);
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .fade-in {
            animation: fade-in 0.4s ease-out;
        }

        .safe-area-pb {
            padding-bottom: env(safe-area-inset-bottom);
        }

        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: white;
            transition: background 0.2s;
        }

        .settings-item:active {
            background: #f9fafb;
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e7eb;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        input:checked+.slider {
            background-color: hsl(230 65% 55%);
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }
    </style>
    @yield('styles')
</head>

<body class="bg-gray-100">
    <div class="mobile-container relative shadow-2xl">
        <main id="main-content" class="h-screen overflow-y-auto pb-24 scrollbar-hide @yield('main_bg', 'bg-white')">
            @yield('content')
        </main>

        @auth
            <!-- Profile Sidebar Overlay -->
            <div id="profile-overlay" class="hidden absolute inset-0 bg-black/40 backdrop-blur-sm z-[100] animate-fade-in">
                <div id="profile-sidebar"
                    class="absolute left-0 top-0 bottom-0 w-[280px] bg-white shadow-2xl transition-transform duration-300 -translate-x-full flex flex-col">
                    <!-- Sidebar Header -->
                    <div class="p-6 bg-indigo-600 text-white">
                        <div class="flex justify-between items-start mb-4">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center overflow-hidden border-2 border-white/50 shadow-lg {{ isset($business->logo) ? 'bg-white' : 'bg-white/20' }}">
                                @if($business && $business->logo)
                                    <img src="{{ asset('uploads/' . $business->logo) }}" class="w-full h-full object-cover"
                                        onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/initials/svg?seed={{ urlencode($business->name ?? optional(Auth::user())->name) }}'">
                                @else
                                    <i data-lucide="user" class="w-8 h-8 text-white"></i>
                                @endif
                            </div>
                            <button onclick="toggleProfileSidebar(false)"
                                class="p-1 hover:bg-white/20 rounded-full transition-colors">
                                <i data-lucide="x" class="w-6 h-6"></i>
                            </button>
                        </div>
                        <h2 class="text-xl font-bold tracking-tight">{{ optional(Auth::user())->name }}</h2>
                        <p class="text-indigo-100 text-xs font-medium opacity-80">{{ optional(Auth::user())->email }}</p>
                    </div>

                    <!-- Sidebar Content -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-2">
                        <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100 mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                                    <i data-lucide="building-2" class="w-5 h-5"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-[13px] font-bold text-gray-800">{{ $business->name ?? 'Update Business' }}</span>
                                    <span class="text-[10px] font-semibold text-indigo-600 uppercase tracking-wider">Active
                                        Profile</span>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('client.business.edit') }}"
                            class="flex items-center gap-4 p-3.5 hover:bg-slate-50 rounded-2xl transition-colors group">
                            <i data-lucide="edit-3" class="w-5 h-5 text-slate-400 group-hover:text-indigo-600"></i>
                            <span class="text-sm font-bold text-slate-700">Edit Business Profile</span>
                        </a>

                        <a href="#"
                            class="flex items-center gap-4 p-3.5 hover:bg-slate-50 rounded-2xl transition-colors group">
                            <i data-lucide="user-cog" class="w-5 h-5 text-slate-400 group-hover:text-indigo-600"></i>
                            <span class="text-sm font-bold text-slate-700">Account Settings</span>
                        </a>
                        <a href="#"
                            class="flex items-center gap-4 p-3.5 hover:bg-slate-50 rounded-2xl transition-colors group">
                            <i data-lucide="shield-check" class="w-5 h-5 text-slate-400 group-hover:text-indigo-600"></i>
                            <span class="text-sm font-bold text-slate-700">Subscription</span>
                        </a>
                        <a href="#"
                            class="flex items-center gap-4 p-3.5 hover:bg-slate-50 rounded-2xl transition-colors group">
                            <i data-lucide="help-circle" class="w-5 h-5 text-slate-400 group-hover:text-indigo-600"></i>
                            <span class="text-sm font-bold text-slate-700">Help & Support</span>
                        </a>
                    </div>

                    <!-- Sidebar Footer -->
                    <div class="p-4 border-t border-slate-100">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-3 py-4 bg-rose-50 text-rose-600 rounded-2xl font-bold text-[14px] transition-all hover:bg-rose-100 active:scale-[0.98]">
                                <i data-lucide="log-out" class="w-5 h-5"></i>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth

        <!-- FAB Backdrop Blur -->
        <div id="fab-backdrop"
            class="hidden absolute inset-0 bg-white/20 backdrop-blur-md z-40 transition-all duration-300 animate-fade-in">
        </div>


        <!-- FAB -->
        <div id="fab-container" class="absolute bottom-28 right-4 z-50 pointer-events-none w-full max-w-md">
            <div class="relative w-full h-full pointer-events-auto flex justify-end pr-4">
                <div id="fab-menu"
                    class="hidden absolute bottom-16 right-0 flex flex-col gap-3 animate-fade-in items-end"></div>
                <button id="fab-trigger"
                    class="w-14 h-14 rounded-full bg-indigo-600 text-white flex items-center justify-center transition-all duration-300 shadow-[0_8px_25px_-5px_rgba(79,70,229,0.5)] hover:scale-105 active:scale-95">
                    <i data-lucide="plus" class="w-8 h-8 transition-transform duration-300" id="fab-icon"></i>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav
            class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white/95 backdrop-blur-md border-t border-gray-100 z-40 safe-area-pb">
            <div class="flex items-center justify-around py-2">
                <a href="{{ route('home') }}"
                    class="nav-item group flex flex-col items-center gap-1 py-1 px-3 @if(Route::is('home')) text-primary active @else text-gray-400 @endif transition-all">
                    <div
                        class="relative p-2 rounded-xl @if(Route::is('home')) bg-primary/10 @endif transition-all group-active:scale-90">
                        <i data-lucide="home" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">Home</span>
                </a>
                <a href="{{ route('custom') }}"
                    class="nav-item group flex flex-col items-center gap-1 py-1 px-3 @if(Route::is('custom')) text-primary active @else text-gray-400 @endif transition-all">
                    <div
                        class="relative p-2 rounded-xl @if(Route::is('custom')) bg-primary/10 @endif transition-all group-active:scale-90">
                        <i data-lucide="image" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">Custom</span>
                </a>
                <a href="{{ route('business') }}"
                    class="nav-item group flex flex-col items-center gap-1 py-1 px-3 @if(Route::is('business')) text-primary active @else text-gray-400 @endif transition-all">
                    <div
                        class="relative p-2 rounded-xl @if(Route::is('business')) bg-primary/10 @endif transition-all group-active:scale-90">
                        <i data-lucide="briefcase" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">My Business</span>
                </a>
                <a href="{{ route('aitrends') }}"
                    class="nav-item group flex flex-col items-center gap-1 py-1 px-3 @if(Route::is('aitrends')) text-primary active @else text-gray-400 @endif transition-all">
                    <div
                        class="relative p-2 rounded-xl @if(Route::is('aitrends')) bg-primary/10 @endif transition-all group-active:scale-90">
                        <i data-lucide="sparkles" class="w-5.5 h-5.5"></i><span
                            class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">AI Trends</span>
                </a>
                <a href="{{ route('more') }}"
                    class="nav-item group flex flex-col items-center gap-1 py-1 px-3 @if(Route::is('more')) text-primary active @else text-gray-400 @endif transition-all">
                    <div
                        class="relative p-2 rounded-xl @if(Route::is('more')) bg-primary/10 @endif transition-all group-active:scale-90">
                        <i data-lucide="menu" class="w-5.5 h-5.5"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">More</span>
                </a>
            </div>
        </nav>
    </div>

    @yield('scripts')

    <script>
        // Initializing Lucide Icons
        lucide.createIcons();

        // FAB Menu Items
        const fabMenuItems = [

            { t: 'AI Trends', i: 'sparkles', c: 'bg-indigo-600' },
            { t: 'Snap Shot', i: 'camera', c: 'bg-sky-500' },
            { t: 'Reels Maker', i: 'play', c: 'bg-rose-500' },
            { t: 'Quotation', i: 'file-text', c: 'bg-amber-500' }
        ];

        // FAB Logic
        const fabTrigger = document.getElementById('fab-trigger');
        const fabMenu = document.getElementById('fab-menu');
        const fabIcon = document.getElementById('fab-icon');
        const fabBackdrop = document.getElementById('fab-backdrop');
        const profileSidebar = document.getElementById('profile-sidebar');
        const profileOverlay = document.getElementById('profile-overlay');

        let isFabOpen = false;

        function toggleProfileSidebar(state) {
            if (!profileSidebar || !profileOverlay) return;
            if (state) {
                profileOverlay.classList.remove('hidden');
                setTimeout(() => {
                    profileSidebar.style.transform = 'translateX(0)';
                }, 10);
            } else {
                profileSidebar.style.transform = 'translateX(-100%)';
                setTimeout(() => {
                    profileOverlay.classList.add('hidden');
                }, 300);
            }
        }

        // Close sidebar when clicking overlay
        if (profileOverlay) {
            profileOverlay.addEventListener('click', (e) => {
                if (e.target === profileOverlay) toggleProfileSidebar(false);
            });
        }

        function toggleFAB(state) {
            isFabOpen = state !== undefined ? state : !isFabOpen;

            if (fabTrigger) {
                fabTrigger.style.backgroundColor = isFabOpen ? '#64748b' : '';
                const icon = fabTrigger.querySelector('#fab-icon');
                if (icon) {
                    icon.style.transform = isFabOpen ? 'rotate(45deg)' : 'rotate(0deg)';
                }
            }

            if (isFabOpen) {
                fabMenu.classList.remove('hidden');
                fabBackdrop.classList.remove('hidden');
                fabMenu.innerHTML = fabMenuItems.map((item, i) => `
                    <button ${item.id ? `id="${item.id}"` : ''} class="flex items-center gap-3 animate-scale-in" style="animation-delay: ${i * 40}ms">
                        <span class="bg-white px-5 py-2 rounded-full shadow-lg text-[15px] font-bold text-slate-800 tracking-tight border border-white whitespace-nowrap">${item.t}</span>
                        <div class="w-12 h-12 rounded-full ${item.c} shadow-lg flex items-center justify-center text-white active:scale-90 transition-transform"><i data-lucide="${item.i}" class="w-6 h-6"></i></div>
                    </button>
                `).join('');
                lucide.createIcons();
            } else {
                fabMenu.classList.add('hidden');
                fabBackdrop.classList.add('hidden');
            }
        }

        if (fabTrigger) {
            fabTrigger.addEventListener('click', () => toggleFAB());
        }

        if (fabBackdrop) {
            fabBackdrop.addEventListener('click', () => toggleFAB(false));
        }


    </script>
    @yield('scripts')
</body>

</html>