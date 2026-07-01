<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Background Remover Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- imgly background removal -->
    <script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.4.3/dist/imgly-remove-background.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .drop-zone {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .drop-zone.dragover {
            border-color: #8b5cf6;
            background: rgba(139, 92, 246, 0.1);
        }
        .loader-spinner {
            border-top-color: #8b5cf6;
            -webkit-animation: spinner 1.5s linear infinite;
            animation: spinner 1.5s linear infinite;
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="antialiased overflow-x-hidden">
    <div class="relative min-h-screen flex flex-col items-center py-12 px-4 sm:px-6 lg:px-8">
        <!-- Decorative background elements -->
        <div class="fixed top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-blob"></div>
        <div class="fixed top-0 -right-4 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-blob animation-delay-2000"></div>
        <div class="fixed -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative w-full max-w-6xl z-10">
            <div class="text-center mb-10">
                <h1 class="text-5xl font-bold tracking-tight mb-4 bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-500 to-indigo-500 drop-shadow-sm">
                    AI Background Remover
                </h1>
                <p class="text-lg text-gray-300">
                    Drop up to 10 images at once. High-quality processing runs securely directly on your device.
                </p>
            </div>

            <!-- Main Workspace -->
            <div class="glass-panel rounded-3xl p-8 mb-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-50">
                    <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <div id="dropZone" class="drop-zone rounded-2xl p-12 text-center cursor-pointer flex flex-col items-center justify-center min-h-[300px] relative z-10 bg-black/20 hover:bg-black/40 transition-all">
                    <svg class="w-20 h-20 text-purple-400 mb-6 drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <h3 class="text-2xl font-semibold mb-3 text-white">Drag & drop your images here</h3>
                    <p class="text-gray-400 mb-8 text-lg">or click to browse (Max 10 images, PNG/JPG)</p>
                    <input type="file" id="fileInput" class="hidden" multiple accept="image/png, image/jpeg, image/webp">
                    <button class="px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 rounded-xl font-bold text-lg transition-all shadow-[0_0_20px_rgba(139,92,246,0.4)] hover:shadow-[0_0_30px_rgba(139,92,246,0.6)] transform hover:-translate-y-1" onclick="document.getElementById('fileInput').click()">
                        Select Images
                    </button>
                </div>
            </div>

            <!-- Global Actions -->
            <div id="globalActions" class="hidden flex flex-col sm:flex-row justify-between items-center mb-8 glass-panel rounded-2xl p-5 border-purple-500/30 border">
                <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                    <div class="h-10 w-10 rounded-full bg-purple-900/50 flex items-center justify-center border border-purple-500/50">
                        <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Processing Status</div>
                        <span id="progressText" class="font-bold text-lg text-white">0 of 0 processed</span>
                    </div>
                </div>
                <button id="downloadAllBtn" class="hidden px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 rounded-xl font-bold transition-all shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_25px_rgba(16,185,129,0.5)] flex items-center space-x-2 transform hover:-translate-y-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Download All Completed</span>
                </button>
            </div>

            <!-- Image Grid -->
            <div id="imageGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Image cards will be injected here -->
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const imageGrid = document.getElementById('imageGrid');
        const globalActions = document.getElementById('globalActions');
        const progressText = document.getElementById('progressText');
        const downloadAllBtn = document.getElementById('downloadAllBtn');

        let processingQueue = [];
        let isProcessing = false;
        let processedImages = [];
        let totalFiles = 0;
        let completedFiles = 0;

        const config = {
            publicPath: "{{ asset('assets/imgly') }}/",
            debug: true,
            model: "medium", // medium is much faster to download than isnet
            output: {
                format: "image/png",
                quality: 1.0
            }
        };

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('dragover');
        }

        function unhighlight(e) {
            dropZone.classList.remove('dragover');
        }

        dropZone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            processFiles(files);
        }

        function handleFiles(e) {
            const files = e.target.files;
            processFiles(files);
        }

        function processFiles(files) {
            const fileArray = Array.from(files).filter(f => f.type.startsWith('image/')).slice(0, 10);
            if (fileArray.length === 0) return;

            globalActions.classList.remove('hidden');
            totalFiles += fileArray.length;
            updateProgress();

            fileArray.forEach(file => {
                const id = Math.random().toString(36).substr(2, 9);
                createImageCard(id, file);
                processingQueue.push({ id, file });
            });

            if (!isProcessing) {
                processQueue();
            }
        }

        function createImageCard(id, file) {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(e) {
                const cardHtml = `
                    <div id="card-${id}" class="glass-panel rounded-2xl overflow-hidden relative group transform transition-all hover:-translate-y-2 hover:shadow-[0_10px_30px_rgba(0,0,0,0.5)]">
                        <div class="aspect-w-1 aspect-h-1 w-full relative" style="background-image: repeating-conic-gradient(#1e293b 0% 25%, #0f172a 0% 50%); background-position: 0 0, 10px 10px; background-size: 20px 20px;">
                            <img id="img-${id}" src="${e.target.result}" class="w-full h-56 object-contain p-2 transition-transform duration-500 group-hover:scale-110" alt="Original Image">
                        </div>
                        <div id="overlay-${id}" class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center transition-opacity duration-500 z-10">
                            <div class="loader-spinner w-12 h-12 border-4 border-slate-600 border-t-purple-500 rounded-full mb-4"></div>
                            <span class="text-sm font-semibold tracking-wide text-purple-200" id="status-${id}">Processing...</span>
                        </div>
                        <div class="p-4 flex flex-col border-t border-white/5 bg-slate-900/60 relative z-20">
                            <span class="text-sm truncate mb-3 text-gray-300 font-medium" title="${file.name}">${file.name}</span>
                            <button id="btn-${id}" class="hidden w-full py-2.5 bg-purple-600/80 hover:bg-purple-500 rounded-lg text-sm font-bold transition-all border border-purple-400/30 hover:border-purple-300/50" onclick="downloadImage('${id}', '${file.name}')">
                                Download PNG
                            </button>
                        </div>
                    </div>
                `;
                imageGrid.insertAdjacentHTML('afterbegin', cardHtml);
            }
        }

        async function processQueue() {
            if (processingQueue.length === 0) {
                isProcessing = false;
                if (processedImages.length > 0) {
                    downloadAllBtn.classList.remove('hidden');
                }
                progressText.innerHTML = `<span class="text-green-400">All processing complete!</span>`;
                return;
            }

            isProcessing = true;
            const currentTask = processingQueue.shift();
            
            try {
                const callConfig = {
                    ...config,
                    progress: (key, current, total) => {
                        const statusEl = document.getElementById(`status-${currentTask.id}`);
                        if (!statusEl) return;

                        let percentText = "";
                        if (total > 0) {
                            const percent = Math.round((current / total) * 100);
                            percentText = `${percent}%`;
                        } else {
                            // Some servers don't send Content-Length, so total is 0
                            const mb = (current / (1024 * 1024)).toFixed(1);
                            percentText = `${mb} MB`;
                        }

                        if (key.includes('fetch')) {
                            statusEl.innerHTML = `Downloading Model: ${percentText}`;
                            progressText.innerHTML = `<span class="text-yellow-400">Downloading AI Model: ${percentText}</span><br><span class="text-xs font-normal text-gray-400">(This happens only once, please wait...)</span>`;
                        } else if (key.includes('compute')) {
                            statusEl.innerHTML = `Removing BG: ${percentText}`;
                            progressText.innerHTML = `<span class="text-white">Removing Background: ${percentText}</span>`;
                        } else {
                            statusEl.innerHTML = `Processing...`;
                        }
                    }
                };

                // This triggers the download of the model on the very first run
                const imageBlob = await imglyRemoveBackground(currentTask.file, callConfig);
                const url = URL.createObjectURL(imageBlob);
                
                const imgEl = document.getElementById(`img-${currentTask.id}`);
                const overlay = document.getElementById(`overlay-${currentTask.id}`);
                const btn = document.getElementById(`btn-${currentTask.id}`);
                
                imgEl.src = url;
                overlay.classList.add('opacity-0', 'pointer-events-none');
                btn.classList.remove('hidden');
                
                processedImages.push({
                    url: url,
                    name: `nobg_${currentTask.file.name.replace(/\.[^/.]+$/, "")}.png`
                });

                imgEl.dataset.downloadUrl = url;

                completedFiles++;
                updateProgress();

            } catch (error) {
                console.error("Error processing image:", error);
                const statusEl = document.getElementById(`status-${currentTask.id}`);
                const overlay = document.getElementById(`overlay-${currentTask.id}`);
                statusEl.textContent = "Failed! Check console.";
                statusEl.classList.add('text-red-400');
                const spinner = overlay.querySelector('.loader-spinner');
                if(spinner) spinner.style.display = 'none';
                
                completedFiles++;
                updateProgress();
            }

            processQueue();
        }

        function updateProgress() {
            progressText.textContent = `${completedFiles} of ${totalFiles} processed`;
        }

        window.downloadImage = function(id, originalName) {
            const imgEl = document.getElementById(`img-${id}`);
            const url = imgEl.dataset.downloadUrl;
            if (!url) return;

            const newName = `nobg_${originalName.replace(/\.[^/.]+$/, "")}.png`;
            const a = document.createElement('a');
            a.href = url;
            a.download = newName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        downloadAllBtn.addEventListener('click', () => {
            processedImages.forEach((item, index) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = item.url;
                    a.download = item.name;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }, index * 300);
            });
        });
    </script>
</body>
</html>
