@extends("layouts.app")

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.premium-ai-panel {
    font-family: 'Inter', sans-serif;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    margin-bottom: 2rem;
    overflow: hidden;
}
.premium-ai-header {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    padding: 1.25rem 1.5rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}
.premium-ai-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.15rem;
    letter-spacing: 0.01em;
}
.premium-ai-header p {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.9;
}
.premium-ai-body {
    padding: 1.5rem;
    background: #f8fafc;
}
.color-picker-wrap {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 8px 12px;
    transition: all 0.2s;
}
.color-picker-wrap:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.color-picker-input {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    padding: 0;
    background: none;
    cursor: pointer;
    overflow: hidden;
    -webkit-appearance: none;
}
.color-picker-input::-webkit-color-swatch-wrapper {
    padding: 0;
}
.color-picker-input::-webkit-color-swatch {
    border: none;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
}
.color-picker-hex {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    color: #334155;
    font-size: 0.95rem;
    margin-left: 12px;
}
.premium-btn {
    background: #1e293b;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 100%;
}
.premium-btn:hover {
    background: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
.premium-textarea {
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 1rem;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    color: #334155;
    background: #fff;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    resize: vertical;
}
.premium-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Edit Mini Website Template</h4>
            </div>
            <div class="card-body">
                
                <!-- Premium AI Prompt Generator -->
                <div class="premium-ai-panel">
                    <div class="premium-ai-header">
                        <i class="fas fa-robot" style="font-size: 1.5rem;"></i>
                        <div>
                            <h5>AI Prompt Generator</h5>
                            <p>Need to redesign? Generate a prompt below, paste it into ChatGPT/Claude, and paste the code back here!</p>
                        </div>
                    </div>
                    <div class="premium-ai-body">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="d-block font-weight-bold text-uppercase" style="font-size: 0.75rem; color: #64748b; letter-spacing: 0.05em; margin-bottom: 8px;">Primary Color</label>
                                <div class="color-picker-wrap">
                                    <input type="color" class="color-picker-input" id="primaryColor" value="#000000" onchange="document.getElementById('primaryColorHex').innerText = this.value">
                                    <span class="color-picker-hex" id="primaryColorHex">#000000</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="d-block font-weight-bold text-uppercase" style="font-size: 0.75rem; color: #64748b; letter-spacing: 0.05em; margin-bottom: 8px;">Secondary Color</label>
                                <div class="color-picker-wrap">
                                    <input type="color" class="color-picker-input" id="secondaryColor" value="#ffffff" onchange="document.getElementById('secondaryColorHex').innerText = this.value">
                                    <span class="color-picker-hex" id="secondaryColorHex">#ffffff</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="premium-btn w-100" onclick="generateAIPrompt()">
                                    <i class="fas fa-magic"></i> Generate Prompt
                                </button>
                            </div>
                        </div>
                        <div class="form-group mb-0 mt-4" id="promptResultBox" style="display: none;">
                            <textarea class="form-control premium-textarea mb-3" id="generatedPrompt" rows="5" readonly></textarea>
                            <button type="button" class="btn btn-success" style="border-radius: 10px; font-weight: 600;" onclick="copyPrompt()">
                                <i class="fas fa-copy"></i> Copy Prompt
                            </button>
                        </div>
                    </div>
                </div>

                <form action="{{ route('mini-website-template.update', $template->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group mb-3">
                        <label for="name">Template Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $template->name }}" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="preview_image">Preview Image (Leave blank to keep existing)</label>
                        <input type="file" class="form-control" id="preview_image" name="preview_image" accept="image/*">
                        @if($template->preview_image)
                            <div class="mt-2">
                                <img src="{{ asset('public/uploads/'.$template->preview_image) }}" style="height: 100px; border-radius: 4px;">
                            </div>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="html_content">HTML/CSS/JS Code</label>
                        <p class="text-muted small mb-2">Available Placeholders: [[BUSINESS_NAME]], [[PHONE]], [[EMAIL]], [[ADDRESS]], [[WEBSITE]], [[LOGO_URL]], [[ABOUT_US]], [[FACEBOOK]], [[TWITTER]], [[INSTAGRAM]], [[YOUTUBE]], [[LINKEDIN]]</p>
                        <textarea class="form-control" id="html_content" name="html_content" rows="15" style="font-family: monospace;" required>{{ $template->html_content }}</textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Template</button>
                    <a href="{{ route('mini-website-template.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function generateAIPrompt() {
    let color1 = document.getElementById('primaryColor').value;
    let color2 = document.getElementById('secondaryColor').value;
    
    let promptText = `Please write a complete, single-file HTML, CSS, and JS code for a modern, responsive Mini Website (Digital Business Card / Link-in-Bio).
Theme Requirements:
- Primary Color: ${color1}
- Secondary Color: ${color2}
- Layout: Mobile-first (max-width 480px, centered on desktop like a phone screen).
- Style: Professional, clean layout. Use FontAwesome for icons.

Dynamic Data Placeholders (Use EXACTLY as written, they will be string-replaced by backend):
- [[BUSINESS_NAME]] (Business Name)
- [[LOGO_URL]] (Logo Image Source)
- [[PHONE]] (Phone Number)
- [[EMAIL]] (Email Address)
- [[ADDRESS]] (Physical Address)
- [[WEBSITE]] (Website Link)
- [[MAP_URL]] (Google Maps Link)
- [[WHATSAPP_NUMBER]] (WhatsApp Number with country code, e.g. 919876543210)
- [[CLIENTS_COUNT]] (e.g. 5000+)
- [[YEARS_EXPERIENCE]] (e.g. 10+)
- [[ABOUT_US]] (Company Description)
- [[PRODUCTS_SERVICES]] (Products & Services Description)
- [[FACEBOOK]], [[TWITTER]], [[INSTAGRAM]], [[YOUTUBE]], [[LINKEDIN]] (Social Media URLs)

Sections to Include (Top to Bottom):
1. Header Banner: A top decorative header background (pattern or solid).
2. Profile: A circular Logo overlapping the header, centered, with [[BUSINESS_NAME]] below it.
3. Quick Actions (4 Circular Buttons in a row): 
   - Call (tel:[[PHONE]])
   - WhatsApp (https://wa.me/[[WHATSAPP_NUMBER]])
   - Location ([[MAP_URL]])
   - Email (mailto:[[EMAIL]])
4. Contact Details List (Icon + Text layout):
   - Address pin icon: [[ADDRESS]]
   - Envelope icon: [[EMAIL]]
   - Globe icon: [[WEBSITE]]
   - Phone icon: [[PHONE]]
5. Stats/Highlights Row (3 boxes):
   - Clients: [[CLIENTS_COUNT]]
   - Experience: [[YEARS_EXPERIENCE]] Years
   - Support: 24/7
6. Social Links: 5 circular outline icons in a row (Facebook, Twitter, Instagram, Youtube, Linkedin) linking to their respective placeholders.
7. About Us Section: 
   - Section Title: "ABOUT US" (styled in Primary Color)
   - "Business Name : [[BUSINESS_NAME]]"
   - Description text: [[ABOUT_US]]
8. Services/Products Section:
   - Section Title: "OUR SERVICES"
   - Description text: [[PRODUCTS_SERVICES]]
   
Do NOT write markdown, explanations, or backticks. Output ONLY the raw HTML code with inline CSS/JS.`;

    document.getElementById('generatedPrompt').value = promptText;
    document.getElementById('promptResultBox').style.display = 'block';
}

function copyPrompt() {
    let copyText = document.getElementById("generatedPrompt");
    copyText.select();
    copyText.setSelectionRange(0, 99999); 
    document.execCommand("copy");
    alert("Prompt copied to clipboard!");
}
</script>
@endsection
