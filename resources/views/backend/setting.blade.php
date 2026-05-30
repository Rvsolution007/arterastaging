@extends('layouts.app')

@section('extra_css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        body, .content-wrapper, .card, .form-control {
            font-family: 'Poppins', sans-serif !important;
        }

        .content-wrapper {
            background-color: #f8fafc;
        }

        .card.card-primary {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            background: #ffffff;
            overflow: hidden;
        }

        .card-primary > .card-header, .card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border-bottom: none !important;
            padding: 1.25rem 1.75rem;
        }

        .card-primary > .card-header h3, .card-primary > .card-header .card-title, .card-title {
            color: #ffffff !important;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .panel-title {
            cursor: pointer;
            font-weight: 600;
            color: #1e293b;
            font-size: 1.1rem;
            margin: 0;
            padding: 10px 0;
        }

        h4.tab-title {
            font-family: "Poppins", sans-serif;
            font-weight: 600;
            font-size: 22px;
            color: #1e293b;
        }

        .vertab-content ul,
        .vertab-content ol {
            padding-left: 15px;
        }

        .select2-container {
            display: inline;
        }

        .form-label, label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
        }

        .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            font-size: 0.875rem;
            color: #334155;
            transition: all 0.2s ease;
            background-color: #f8fafc;
            height: auto;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background-color: #ffffff;
        }

        .btn-success {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
            border: none;
            padding: 0.6rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
        }

        .btn-info {
            background: linear-gradient(to right, #38bdf8, #0ea5e9) !important;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            color: white !important;
        }

        @media (min-width:768px) {
            .vertab-container {
                z-index: 10;
                background-color: #fff;
                padding: 0 !important;
                margin-top: 10px;
                margin-bottom: 50px;
            }

            .vertab-menu {
                padding: 15px 10px;
                border-right: 1px solid #f1f5f9;
                background: linear-gradient(180deg, #e0f2fe 0%, #ffffff 100%);
                border-radius: 12px;
            }

            .vertab-menu .list-group {
                margin-bottom: 0;
            }

            .vertab-menu .list-group>a {
                margin-bottom: 5px;
                border-radius: 8px;
                color: #64748b;
                background-image: none;
                background-color: transparent;
                border: none;
                padding: 12px 16px;
                font-weight: 500;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                transition: all 0.2s ease;
            }

            .vertab-menu .list-group>a img {
                width: 20px;
                height: 20px;
                opacity: 0.6;
                transition: opacity 0.2s ease;
            }
            .vertab-menu .list-group>a i {
                width: 28px;
                color: #94a3b8 !important;
                transition: color 0.2s ease;
            }

            .vertab-menu .list-group>a.active,
            .vertab-menu .list-group>a:hover,
            .vertab-menu .list-group>a:focus {
                background-color: #f8fafc;
                color: #6366f1;
                font-weight: 600;
            }

            .vertab-menu .list-group>a.active {
                background-color: #eef2ff;
                border-left: 4px solid #6366f1;
            }

            .vertab-menu .list-group>a.active img, .vertab-menu .list-group>a:hover img {
                opacity: 1;
                filter: invert(34%) sepia(91%) saturate(2361%) hue-rotate(224deg) brightness(98%) contrast(98%);
            }
            .vertab-menu .list-group>a.active i, .vertab-menu .list-group>a:hover i {
                color: #6366f1 !important;
            }

            .vertab-content {
                padding: 25px 30px;
                color: #334155;
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
                margin-left: 15px;
            }

            .vertab-accordion .vertab-content:not(.active) {
                display: none;
            }

            .vertab-accordion .vertab-content.active .collapse {
                display: block;
            }

            .vertab-container .panel-heading {
                display: inline-block;
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #eef2ff;
                padding-bottom: 0.5rem;
                width: 100%;
            }

            .vertab-container .panel-body {
                border-top: none !important;
            }
        }

        @media (max-width:767px) {
            .vertab-container {
                margin-top: 20px;
                margin-bottom: 20px;
            }

            .vertab-container .vertab-menu {
                display: none;
            }

            .vertab-container .panel-heading {
                background-color: #f8fafc;
                color: #64748b;
                padding: 15px;
                border-bottom: 1px solid #f1f5f9;
                border-radius: 8px;
                margin-bottom: 10px;
            }

            .vertab-container .panel-heading.active {
                border-left: 4px solid #6366f1;
                background-color: #eef2ff;
                color: #6366f1;
            }

            .panel-collapse.collapse,
            .panel-collapse.collapsing {
                background-color: #ffffff !important;
                padding: 15px 0;
            }

            .vertab-container .panel-collapse .panel-body {
                border-top: none !important;
            }
        }

        .list-group-item+.list-group-item.active {
            margin-top: 0px;
        }
        
        .alert {
            font-family: 'Poppins', sans-serif;
            border-radius: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header border-bottom">
                    <h3 class="card-title"><b>Setting</b></h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mt-2">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="container-fluid">
                                <div class="row vertab-container">
                                    <div class="col-lg-2 col-md-3 col-sm-12 vertab-menu">
                                        <div class="list-group">
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/App Setting.svg')}}"
                                                    alt="Image">App Setting</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fas fa-robot fa-lg mr-1 text-primary"></i>AI Configuration
                                                Setting</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fas fa-ad fa-lg mr-1 text-primary"></i>Ads Setting</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Notification.svg')}}"
                                                    alt="Image">Notification</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Email Setting.svg')}}"
                                                    alt="Image">Email Setting</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Payment Setting.svg')}}"
                                                    alt="Image">Payment Setting</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fas fa-link fa-lg mr-1 text-primary"></i>Api Setting</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fa-brands fa-whatsapp fa-lg mr-1 text-primary"></i>Whatsapp
                                                Setting</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fas fa-database fa-lg mr-1 text-primary"></i>Storage Setting</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/App Upadate Popup.svg')}}"
                                                    alt="Image">App Update Popup</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Privacy Policy.svg')}}"
                                                    alt="Image">Privacy Policy</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Refund Policy.svg')}}"
                                                    alt="Image">Refund Policy</a>
                                            <a href="#" class="list-group-item text-left"><img type="image" class="mr-2"
                                                    src="{{asset('assets/images/Setting Icon/Terms & Condition.svg')}}"
                                                    alt="Image">Terms & Condition</a>
                                            <a href="#" class="list-group-item text-left"><i
                                                    class="fa-brands fa-whatsapp fa-lg mr-1 text-primary"></i> Whatsapp
                                                Contact</a>
                                        </div>
                                    </div>
                                    <div id="accordion" class="col-lg-10 col-md-9 col-sm-12 vertab-accordion panel-group">
                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse1">
                                                    App Setting
                                                </h4>
                                            </div>
                                            <div id="collapse1" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/app-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('app_title', 'App Title', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[app_title]', App\Models\AppSetting::getAppSetting('app_title'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('app_logo', 'App Logo', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    <div class="row">
                                                                        <div class="col-3"><input class="form-control"
                                                                                type="file" id="app_logo"
                                                                                name="name[app_logo]"></div>
                                                                        <div class="col-9" id="preview"><img type="image"
                                                                                class="shadow bg-white rounded"
                                                                                src="@if(App\Models\AppSetting::getAppSetting('app_logo')) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @endif @else{{asset('assets/images/no-image.png')}}@endif"
                                                                                alt="Image"
                                                                                style="width: 100px;height: 100px" /></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('admin_favicon', 'Admin Favicon', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    <div class="row">
                                                                        <div class="col-3"><input class="form-control"
                                                                                type="file" id="admin_favicon"
                                                                                name="name[admin_favicon]"></div>
                                                                        <div class="col-9" id="preview1"><img type="image"
                                                                                class="shadow bg-white rounded"
                                                                                src="@if(App\Models\AppSetting::getAppSetting('admin_favicon')) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @endif @else{{asset('assets/images/no-image.png')}}@endif"
                                                                                alt="Image"
                                                                                style="width: 40px;height: 40px" /></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('author', 'Author', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[author]', App\Models\AppSetting::getAppSetting('author'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('description', 'Description', ['class' => 'col-sm-2 col-form-label']) !!}
                                                                <div class="col-sm-10">
                                                                    <textarea name="name[description]" id="desc_text"
                                                                        class="form-control"
                                                                        required>{!! App\Models\AppSetting::getAppSetting('description') !!}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('api_key', 'Api Key', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[api_key]', App\Models\AppSetting::getAppSetting('api_key'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('product_enable', 'Product Enable', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9 mt-1">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="product_enable" value="1"
                                                                            name="name[product_enable]"
                                                                            @if(App\Models\AppSetting::getAppSetting('product_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('app_timezone', 'Timezone', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-sm-9">
                                                                    <select class="form-control" id="timezone"
                                                                        name="name[app_timezone]" required>
                                                                        @foreach($timezone as $t)
                                                                            <option value="{{$t->timezone}}"
                                                                                @if(App\Models\AppSetting::getAppSetting("app_timezone") == $t->timezone)
                                                                                selected @endif>{{$t->name}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('currency', 'Currency', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    <select class="form-control" id="currency"
                                                                        name="name[currency]" required>
                                                                        <option value="INR"
                                                                            @if(App\Models\AppSetting::getAppSetting('currency') == "INR")
                                                                            selected @endif>INR</option>
                                                                        <option value="USD"
                                                                            @if(App\Models\AppSetting::getAppSetting('currency') == "USD")
                                                                            selected @endif>USD</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('app_version', 'App Version', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[app_version]', App\Models\AppSetting::getAppSetting('app_version'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('contact', 'Contact', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[contact]', App\Models\AppSetting::getAppSetting('contact'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('email', 'Email', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[email]', App\Models\AppSetting::getAppSetting('email'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('website', 'Website', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[website]', App\Models\AppSetting::getAppSetting('website'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('developed_by', 'Developed By', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[developed_by]', App\Models\AppSetting::getAppSetting('developed_by'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse_ai">
                                                    AI Configuration Setting
                                                </h4>
                                            </div>
                                            <div id="collapse_ai" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/ai-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    {{-- AI Provider Selector --}}
                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('ai_provider', 'AI Provider', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    <select class="form-control" id="ai_provider_select"
                                                                        name="name[ai_provider]">
                                                                        <option value="vertex"
                                                                            @if(App\Models\AiSetting::getAiSetting('ai_provider') == 'vertex' || !App\Models\AiSetting::getAiSetting('ai_provider'))
                                                                            selected @endif>Vertex AI (Google Cloud)
                                                                        </option>
                                                                        <option value="gemini"
                                                                            @if(App\Models\AiSetting::getAiSetting('ai_provider') == 'gemini')
                                                                            selected @endif>Gemini API (API Key)</option>
                                                                        <option value="chatgpt"
                                                                            @if(App\Models\AiSetting::getAiSetting('ai_provider') == 'chatgpt')
                                                                            selected @endif>ChatGPT (OpenAI)</option>
                                                                    </select>
                                                                    <small class="text-muted">Choose your AI provider —
                                                                        Vertex AI requires a Google Cloud project, Gemini
                                                                        API uses a direct API key, ChatGPT uses OpenAI API key</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- ==================== VERTEX AI SECTION ==================== --}}
                                                    <div id="vertex-ai-section">
                                                        <div class="alert alert-info mt-2"
                                                            style="border-left: 4px solid #17a2b8;">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            <strong>Vertex AI Configuration</strong> — Upload your Service Account JSON file securely. Credentials are encrypted (AES-256) before storage.
                                                        </div>

                                                        {{-- Service Account JSON Upload --}}
                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('service_account_json', 'Service Account JSON', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-9">
                                                                        @php $hasEncryptedCreds = !empty(App\Models\AiSetting::getAiSetting('google_application_credentials_encrypted')); @endphp
                                                                        @if($hasEncryptedCreds)
                                                                            <div class="mb-2">
                                                                                <span class="badge badge-success" style="font-size: 13px; padding: 6px 12px; border-radius: 6px;">
                                                                                    <i class="fas fa-lock mr-1"></i> Credentials Uploaded &amp; Encrypted
                                                                                </span>
                                                                                <button type="button" class="btn btn-outline-danger btn-sm ml-2" id="remove_vertex_credentials"
                                                                                    onclick="if(confirm('Are you sure you want to remove the stored credentials?')){document.getElementById('remove_credentials_flag').value='1'; this.closest('form').submit();}">
                                                                                    <i class="fas fa-trash-alt mr-1"></i> Remove
                                                                                </button>
                                                                                <input type="hidden" name="remove_credentials" id="remove_credentials_flag" value="0">
                                                                            </div>
                                                                            <small class="text-muted d-block mb-2">Upload a new file below to replace the existing credentials.</small>
                                                                        @else
                                                                            <div class="mb-2">
                                                                                <span class="badge badge-danger" style="font-size: 13px; padding: 6px 12px; border-radius: 6px;">
                                                                                    <i class="fas fa-exclamation-triangle mr-1"></i> No Credentials Uploaded
                                                                                </span>
                                                                            </div>
                                                                        @endif
                                                                        <input type="file" name="service_account_json" class="form-control vertex-field" accept=".json" id="service_account_json_input">
                                                                        <small class="text-muted">Upload your Google Cloud Service Account JSON file. It will be encrypted and stored securely.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Project ID (auto-filled from JSON, editable) --}}
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('google_cloud_project_id', 'Google Cloud Project ID', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-9">
                                                                        {!! Form::text('name[google_cloud_project_id]', App\Models\AiSetting::getAiSetting('google_cloud_project_id'), ['class' => 'form-control vertex-field', 'placeholder' => 'e.g. my-gcp-project-123', 'id' => 'vertex_project_id']) !!}
                                                                        <small class="text-muted">Auto-filled from uploaded JSON. You can edit if needed.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('vertex_location', 'Vertex AI Location', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-9">
                                                                        {!! Form::text('name[vertex_location]', App\Models\AiSetting::getAiSetting('vertex_location'), ['class' => 'form-control vertex-field', 'placeholder' => 'e.g. us-central1']) !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('ai_model', 'Vertex AI Model', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-sm-9">
                                                                        {!! Form::text('name[ai_model]', App\Models\AiSetting::getAiSetting('ai_model'), ['class' => 'form-control vertex-field', 'placeholder' => 'e.g. gemini-2.0-flash']) !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- ==================== GEMINI API SECTION ==================== --}}
                                                    <div id="gemini-api-section" style="display: none;">
                                                        <div class="alert alert-primary mt-2"
                                                            style="border-left: 4px solid #4285f4;">
                                                            <i class="fas fa-key mr-1"></i>
                                                            <strong>Gemini API Configuration</strong> — Enter your Gemini
                                                            API
                                                            Key from <a href="https://aistudio.google.com/app/apikey"
                                                                target="_blank">Google AI Studio</a>
                                                            and select the model to use.
                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('gemini_api_key', 'Gemini API Key', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-sm-9">
                                                                        <div class="input-group">
                                                                            @php $savedGeminiKey = App\Models\AiSetting::getAiSetting('gemini_api_key'); @endphp
                                                                            <input type="password" 
                                                                                name="name[gemini_api_key]" 
                                                                                class="form-control gemini-field" 
                                                                                placeholder="Enter your Gemini API Key" 
                                                                                id="gemini_api_key_input"
                                                                                value="{{ $savedGeminiKey ? '••••••••' . substr($savedGeminiKey, -4) : '' }}"
                                                                                data-has-key="{{ $savedGeminiKey ? '1' : '0' }}"
                                                                                onfocus="if(this.dataset.hasKey==='1' && !this.dataset.edited){this.value='';this.dataset.edited='1';}"
                                                                                oninput="this.dataset.edited='1';">
                                                                            <div class="input-group-append">
                                                                                <button class="btn btn-outline-secondary"
                                                                                    type="button" id="toggle_gemini_key">
                                                                                    <i class="fas fa-eye"
                                                                                        id="gemini_key_eye"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">Get your API key from <a
                                                                                href="https://aistudio.google.com/app/apikey"
                                                                                target="_blank">Google AI Studio</a></small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('gemini_model', 'Gemini Model', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-sm-9">
                                                                        {!! Form::text('name[gemini_model]', App\Models\AiSetting::getAiSetting('gemini_model') ?: 'gemini-2.0-flash', ['class' => 'form-control gemini-field', 'placeholder' => 'e.g. gemini-2.0-flash']) !!}
                                                                        <small class="text-muted">Available models:
                                                                            gemini-2.0-flash, gemini-1.5-pro,
                                                                            gemini-1.5-flash, etc.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- ==================== CHATGPT API SECTION ==================== --}}
                                                    <div id="chatgpt-api-section" style="display: none;">
                                                        <div class="alert alert-success mt-2"
                                                            style="border-left: 4px solid #10a37f;">
                                                            <i class="fas fa-key mr-1"></i>
                                                            <strong>ChatGPT API Configuration</strong> — Enter your OpenAI
                                                            API
                                                            Key from <a href="https://platform.openai.com/api-keys"
                                                                target="_blank">OpenAI Developer Platform</a>
                                                            and select the model to use.
                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('chatgpt_api_key', 'ChatGPT API Key', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-sm-9">
                                                                        <div class="input-group">
                                                                            @php $savedChatgptKey = App\Models\AiSetting::getAiSetting('chatgpt_api_key'); @endphp
                                                                            <input type="password" 
                                                                                name="name[chatgpt_api_key]" 
                                                                                class="form-control chatgpt-field" 
                                                                                placeholder="Enter your ChatGPT API Key" 
                                                                                id="chatgpt_api_key_input"
                                                                                value="{{ $savedChatgptKey ? '••••••••' . substr($savedChatgptKey, -4) : '' }}"
                                                                                data-has-key="{{ $savedChatgptKey ? '1' : '0' }}"
                                                                                onfocus="if(this.dataset.hasKey==='1' && !this.dataset.edited){this.value='';this.dataset.edited='1';}"
                                                                                oninput="this.dataset.edited='1';">
                                                                            <div class="input-group-append">
                                                                                <button class="btn btn-outline-secondary"
                                                                                    type="button" id="toggle_chatgpt_key">
                                                                                    <i class="fas fa-eye"
                                                                                        id="chatgpt_key_eye"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">Get your API key from <a
                                                                                href="https://platform.openai.com/api-keys"
                                                                                target="_blank">OpenAI Platform</a></small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    {!! Form::label('chatgpt_model', 'ChatGPT Model', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                    <div class="col-xl-10 col-md-9 col-sm-9">
                                                                        {!! Form::text('name[chatgpt_model]', App\Models\AiSetting::getAiSetting('chatgpt_model') ?: 'gpt-4o-mini', ['class' => 'form-control chatgpt-field', 'placeholder' => 'e.g. gpt-4o-mini']) !!}
                                                                        <small class="text-muted">Available models:
                                                                            gpt-4o, gpt-4o-mini,
                                                                            gpt-3.5-turbo, etc.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-2">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                                <button type="button" id="test-ai-connection"
                                                                    class="btn btn-info ml-2">
                                                                    <i class="fas fa-robot mr-1" id="robot-icon"></i> Test
                                                                    Connection
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                    <div id="ai-status-card" class="ai-status-card">
                                                        <button class="ai-status-close" id="ai-close-btn">&times;</button>
                                                        <div class="ai-status-header">
                                                            <div id="ai-status-icon" class="ai-status-icon-wrap"></div>
                                                            <h5 id="ai-status-title" class="ai-status-title">AI Status</h5>
                                                        </div>
                                                        <div class="ai-status-body">
                                                            <p id="ai-status-msg" class="ai-status-msg"></p>
                                                        </div>
                                                    </div>

                                                    {{-- AI Playground Section (Professional Redesign) --}}
                                                    <div class="playground-wrapper mt-5">
                                                        <div class="playground-card-new">
                                                            <div class="playground-header">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="playground-bot-icon-wrap">
                                                                        <i class="fas fa-brain-circuit"></i>
                                                                    </div>
                                                                    <div class="ml-3">
                                                                        <h4 class="m-0 text-white font-weight-bold">AI
                                                                            Intelligence Lab</h4>
                                                                        <div class="playground-status-pill mt-1">
                                                                            <span class="pulse-dot"></span>
                                                                            <span id="current-ai-label">Active Engine:
                                                                                Ready</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="playground-header-actions">
                                                                    <button type="button" id="clear-console"
                                                                        class="playground-action-btn" title="Clear Console">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="playground-body p-0">
                                                                <div class="row no-gutters">
                                                                    <!-- Input Area -->
                                                                    <div class="col-lg-5 border-right-pro">
                                                                        <div class="p-4">
                                                                            <h6
                                                                                class="text-uppercase small font-weight-bold text-muted mb-3">
                                                                                Training Prompt</h6>
                                                                            <div class="playground-input-group">
                                                                                <textarea id="playground-prompt"
                                                                                    class="form-control playground-input-v2"
                                                                                    rows="6"
                                                                                    placeholder="Type a creative prompt to test the model..."></textarea>
                                                                                <div class="playground-input-footer">
                                                                                    <span
                                                                                        class="char-count text-muted small">Ready
                                                                                        for input</span>
                                                                                    <button type="button"
                                                                                        id="send-playground-msg"
                                                                                        class="playground-submit-btn">
                                                                                        <span>Generate</span>
                                                                                        <i class="fas fa-sparkles ml-2"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Output Area -->
                                                                    <div class="col-lg-7">
                                                                        <div class="p-4 h-100 flex-column d-flex">
                                                                            <div
                                                                                class="d-flex justify-content-between align-items-center mb-3">
                                                                                <h6
                                                                                    class="text-uppercase small font-weight-bold text-muted m-0">
                                                                                    Generated Intelligence</h6>
                                                                                <div id="playground-copy-wrap"
                                                                                    style="display:none">
                                                                                    <button type="button" id="copy-response"
                                                                                        class="btn btn-sm btn-outline-light-pro py-0 px-2"
                                                                                        style="font-size: 10px;">
                                                                                        <i class="fas fa-copy mr-1"></i>
                                                                                        Copy Response
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                            <div class="playground-display-v2 flex-grow-1">
                                                                                <div id="playground-response"
                                                                                    class="playground-response-inner">
                                                                                    <div class="playground-welcome">
                                                                                        <div class="terminal-header">
                                                                                            <span class="dot red"></span>
                                                                                            <span class="dot yellow"></span>
                                                                                            <span class="dot green"></span>
                                                                                        </div>
                                                                                        <div class="welcome-content">
                                                                                            <i
                                                                                                class="fas fa-terminal mb-3"></i>
                                                                                            <p>Run your first generation to
                                                                                                see intelligence in action.
                                                                                            </p>
                                                                                            <code
                                                                                                class="small d-block mt-2 text-muted">System idle... awaiting instructions.</code>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div id="playground-model-info"
                                                                                class="playground-footer-meta mt-2"
                                                                                style="display:none">
                                                                                <span class="badge badge-indigo-soft"><i
                                                                                        class="fas fa-microchip mr-1"></i>
                                                                                    <span
                                                                                        id="resp-model-name"></span></span>
                                                                                <span class="ml-auto text-muted small"
                                                                                    id="resp-time-meta"></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <style>
                                                        #robot-icon.pulse {
                                                            animation: ai-pulse 1.5s infinite;
                                                        }

                                                        @keyframes ai-pulse {
                                                            0% {
                                                                transform: scale(1);
                                                                opacity: 1;
                                                                text-shadow: 0 0 0 rgba(23, 162, 184, 0);
                                                            }

                                                            50% {
                                                                transform: scale(1.2);
                                                                opacity: 0.8;
                                                                text-shadow: 0 0 10px rgba(23, 162, 184, 0.7);
                                                            }

                                                            100% {
                                                                transform: scale(1);
                                                                opacity: 1;
                                                                text-shadow: 0 0 0 rgba(23, 162, 184, 0);
                                                            }
                                                        }

                                                        .ai-status-card {
                                                            position: fixed;
                                                            top: -300px;
                                                            right: 20px;
                                                            width: 450px;
                                                            max-width: calc(100vw - 40px);
                                                            border-radius: 14px;
                                                            overflow: hidden;
                                                            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.08);
                                                            z-index: 9999;
                                                            transition: top 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                                                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                                        }

                                                        .ai-status-card.show {
                                                            top: 70px;
                                                        }

                                                        .ai-status-card.ai-loading {
                                                            background: linear-gradient(135deg, #1a1d2e, #2d3250);
                                                            border-left: 5px solid #17a2b8;
                                                        }

                                                        .ai-status-card.ai-success {
                                                            background: linear-gradient(135deg, #0d2818, #1a4a2e);
                                                            border-left: 5px solid #28a745;
                                                        }

                                                        .ai-status-card.ai-error {
                                                            background: linear-gradient(135deg, #2d1215, #4a1a1f);
                                                            border-left: 5px solid #dc3545;
                                                        }

                                                        .ai-status-header {
                                                            display: flex;
                                                            align-items: center;
                                                            padding: 16px 40px 8px 18px;
                                                            gap: 12px;
                                                        }

                                                        .ai-status-icon-wrap {
                                                            font-size: 28px;
                                                            flex-shrink: 0;
                                                            width: 36px;
                                                            text-align: center;
                                                        }

                                                        .ai-status-title {
                                                            margin: 0;
                                                            font-size: 17px;
                                                            font-weight: 700;
                                                            color: #fff !important;
                                                            letter-spacing: 0.3px;
                                                        }

                                                        .ai-status-body {
                                                            padding: 0 18px 16px 66px;
                                                        }

                                                        .ai-status-msg {
                                                            margin: 0;
                                                            font-size: 13px;
                                                            line-height: 1.55;
                                                            color: rgba(255, 255, 255, 0.85) !important;
                                                            max-height: 120px;
                                                            overflow-y: auto;
                                                            word-wrap: break-word;
                                                            padding-right: 5px;
                                                        }

                                                        .ai-status-msg::-webkit-scrollbar {
                                                            width: 4px;
                                                        }

                                                        .ai-status-msg::-webkit-scrollbar-thumb {
                                                            background: rgba(255, 255, 255, 0.25);
                                                            border-radius: 4px;
                                                        }

                                                        .ai-status-close {
                                                            position: absolute;
                                                            top: 8px;
                                                            right: 10px;
                                                            background: rgba(255, 255, 255, 0.1);
                                                            border: none;
                                                            color: rgba(255, 255, 255, 0.7);
                                                            font-size: 18px;
                                                            width: 28px;
                                                            height: 28px;
                                                            border-radius: 50%;
                                                            cursor: pointer;
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                            transition: all 0.2s;
                                                        }

                                                        /* Professional Playground Redesign Styles */
                                                        .playground-wrapper {
                                                            margin-bottom: 50px;
                                                        }

                                                        .playground-card-new {
                                                            background: #ffffff;
                                                            border-radius: 20px;
                                                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.03);
                                                            overflow: hidden;
                                                            border: none;
                                                        }

                                                        .playground-header {
                                                            background: linear-gradient(135deg, #1e1b4b, #312e81);
                                                            padding: 24px 30px;
                                                            display: flex;
                                                            justify-content: space-between;
                                                            align-items: center;
                                                        }

                                                        .playground-bot-icon-wrap {
                                                            width: 50px;
                                                            height: 50px;
                                                            background: rgba(255, 255, 255, 0.1);
                                                            backdrop-filter: blur(5px);
                                                            border-radius: 14px;
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                            color: #818cf8;
                                                            font-size: 24px;
                                                            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.05);
                                                        }

                                                        .playground-status-pill {
                                                            display: inline-flex;
                                                            align-items: center;
                                                            background: rgba(255, 255, 255, 0.08);
                                                            padding: 3px 10px;
                                                            border-radius: 20px;
                                                            font-size: 11px;
                                                            color: rgba(255, 255, 255, 0.7);
                                                        }

                                                        .pulse-dot {
                                                            width: 6px;
                                                            height: 6px;
                                                            background: #34d399;
                                                            border-radius: 50%;
                                                            margin-right: 6px;
                                                            box-shadow: 0 0 5px #34d399;
                                                            animation: dot-pulse 2s infinite;
                                                        }

                                                        @keyframes dot-pulse {
                                                            0% {
                                                                opacity: 1;
                                                                transform: scale(1);
                                                            }

                                                            50% {
                                                                opacity: 0.4;
                                                                transform: scale(1.2);
                                                            }

                                                            100% {
                                                                opacity: 1;
                                                                transform: scale(1);
                                                            }
                                                        }

                                                        .playground-action-btn {
                                                            background: transparent;
                                                            border: none;
                                                            color: rgba(255, 255, 255, 0.5);
                                                            width: 36px;
                                                            height: 36px;
                                                            border-radius: 10px;
                                                            transition: all 0.2s;
                                                            cursor: pointer;
                                                        }

                                                        .playground-action-btn:hover {
                                                            background: rgba(255, 255, 255, 0.1);
                                                            color: white;
                                                        }

                                                        .border-right-pro {
                                                            border-right: 1px solid #f1f5f9;
                                                        }

                                                        .playground-input-group {
                                                            background: #f8fafc;
                                                            border: 1px solid #e2e8f0;
                                                            border-radius: 16px;
                                                            padding: 2px;
                                                            transition: border-color 0.2s;
                                                        }

                                                        .playground-input-group:focus-within {
                                                            border-color: #818cf8;
                                                            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.1);
                                                        }

                                                        .playground-input-v2 {
                                                            background: transparent;
                                                            border: none !important;
                                                            padding: 16px;
                                                            font-size: 15px;
                                                            resize: none;
                                                            outline: none !important;
                                                            box-shadow: none !important;
                                                        }

                                                        .playground-input-footer {
                                                            padding: 12px 16px;
                                                            display: flex;
                                                            justify-content: space-between;
                                                            align-items: center;
                                                            border-top: 1px solid #f1f5f9;
                                                        }

                                                        .playground-submit-btn {
                                                            background: #4f46e5;
                                                            color: white;
                                                            border: none;
                                                            padding: 8px 18px;
                                                            border-radius: 10px;
                                                            font-weight: 600;
                                                            font-size: 14px;
                                                            transition: all 0.2s;
                                                            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
                                                        }

                                                        .playground-submit-btn:hover {
                                                            background: #4338ca;
                                                            transform: translateY(-1px);
                                                            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.4);
                                                        }

                                                        .playground-display-v2 {
                                                            background: #f1f5f9;
                                                            border-radius: 16px;
                                                            min-height: 220px;
                                                            overflow-y: auto;
                                                        }

                                                        .playground-response-inner {
                                                            height: 100%;
                                                            position: relative;
                                                        }

                                                        .playground-welcome {
                                                            height: 100%;
                                                            display: flex;
                                                            flex-direction: column;
                                                            padding: 20px;
                                                        }

                                                        .terminal-header {
                                                            display: flex;
                                                            gap: 6px;
                                                            margin-bottom: 30px;
                                                        }

                                                        .terminal-header .dot {
                                                            width: 10px;
                                                            height: 10px;
                                                            border-radius: 50%;
                                                        }

                                                        .dot.red {
                                                            background: #ff5f56;
                                                        }

                                                        .dot.yellow {
                                                            background: #ffbd2e;
                                                        }

                                                        .dot.green {
                                                            background: #27c93f;
                                                        }

                                                        .welcome-content {
                                                            flex-grow: 1;
                                                            display: flex;
                                                            flex-direction: column;
                                                            align-items: center;
                                                            justify-content: center;
                                                            text-align: center;
                                                        }

                                                        .welcome-content i {
                                                            font-size: 40px;
                                                            color: #cbd5e1;
                                                        }

                                                        .welcome-content p {
                                                            color: #64748b;
                                                            font-weight: 500;
                                                        }

                                                        .playground-msg-bubble {
                                                            background: white;
                                                            padding: 20px;
                                                            border-radius: 12px;
                                                            margin: 15px;
                                                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                                                            font-size: 14px;
                                                            color: #334155;
                                                            line-height: 1.7;
                                                            font-family: 'Fira Code', 'Courier New', monospace;
                                                            border-left: 4px solid #6366f1;
                                                            animation: bubble-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                                                        }

                                                        @keyframes bubble-pop {
                                                            from {
                                                                opacity: 0;
                                                                transform: scale(0.95) translateY(10px);
                                                            }

                                                            to {
                                                                opacity: 1;
                                                                transform: scale(1) translateY(0);
                                                            }
                                                        }

                                                        .playground-footer-meta {
                                                            display: flex;
                                                            align-items: center;
                                                            padding-top: 10px;
                                                        }

                                                        .badge-indigo-soft {
                                                            background: #e0e7ff;
                                                            color: #4338ca;
                                                            padding: 5px 10px;
                                                            border-radius: 8px;
                                                        }

                                                        .btn-outline-light-pro {
                                                            border: 1px solid #e2e8f0;
                                                            color: #64748b;
                                                            background: white;
                                                            transition: all 0.2s;
                                                        }

                                                        .btn-outline-light-pro:hover {
                                                            background: #f8fafc;
                                                            color: #1e293b;
                                                            border-color: #cbd5e1;
                                                        }

                                                        /* Typing Indicator */
                                                        .typing-dots {
                                                            display: flex;
                                                            padding: 20px;
                                                            gap: 4px;
                                                        }

                                                        .typing-dots .dot {
                                                            width: 8px;
                                                            height: 8px;
                                                            background: #94a3b8;
                                                            border-radius: 50%;
                                                            animation: typing-blink 1.4s infinite;
                                                        }

                                                        .typing-dots .dot:nth-child(2) {
                                                            animation-delay: 0.2s;
                                                        }

                                                        .typing-dots .dot:nth-child(3) {
                                                            animation-delay: 0.4s;
                                                        }

                                                        @keyframes typing-blink {

                                                            0%,
                                                            100% {
                                                                opacity: 0.3;
                                                                transform: scale(1);
                                                            }

                                                            50% {
                                                                opacity: 1;
                                                                transform: scale(1.1);
                                                            }
                                                        }
                                                    </style>

                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function () {
                                                            const testBtn = document.getElementById('test-ai-connection');
                                                            const robotIcon = document.getElementById('robot-icon');
                                                            const statusCard = document.getElementById('ai-status-card');
                                                            const statusTitle = document.getElementById('ai-status-title');
                                                            const statusMsg = document.getElementById('ai-status-msg');
                                                            const statusIcon = document.getElementById('ai-status-icon');
                                                            const closeBtn = document.getElementById('ai-close-btn');
                                                            const providerSelect = document.getElementById('ai_provider_select');
                                                            const vertexSection = document.getElementById('vertex-ai-section');
                                                            const geminiSection = document.getElementById('gemini-api-section');
                                                            const chatgptSection = document.getElementById('chatgpt-api-section');

                                                            // Toggle provider sections
                                                            function toggleProviderSections() {
                                                                const provider = providerSelect.value;
                                                                if (provider === 'vertex') {
                                                                    vertexSection.style.display = 'block';
                                                                    geminiSection.style.display = 'none';
                                                                    chatgptSection.style.display = 'none';
                                                                    // Set required on vertex fields, remove from others
                                                                    document.querySelectorAll('.vertex-field').forEach(f => f.setAttribute('required', 'required'));
                                                                    document.querySelectorAll('.gemini-field').forEach(f => f.removeAttribute('required'));
                                                                    document.querySelectorAll('.chatgpt-field').forEach(f => f.removeAttribute('required'));
                                                                } else if (provider === 'gemini') {
                                                                    vertexSection.style.display = 'none';
                                                                    geminiSection.style.display = 'block';
                                                                    chatgptSection.style.display = 'none';
                                                                    // Set required on gemini fields, remove from others
                                                                    document.querySelectorAll('.gemini-field').forEach(f => f.setAttribute('required', 'required'));
                                                                    document.querySelectorAll('.vertex-field').forEach(f => f.removeAttribute('required'));
                                                                    document.querySelectorAll('.chatgpt-field').forEach(f => f.removeAttribute('required'));
                                                                } else if (provider === 'chatgpt') {
                                                                    vertexSection.style.display = 'none';
                                                                    geminiSection.style.display = 'none';
                                                                    chatgptSection.style.display = 'block';
                                                                    // Set required on chatgpt fields, remove from others
                                                                    document.querySelectorAll('.chatgpt-field').forEach(f => f.setAttribute('required', 'required'));
                                                                    document.querySelectorAll('.vertex-field').forEach(f => f.removeAttribute('required'));
                                                                    document.querySelectorAll('.gemini-field').forEach(f => f.removeAttribute('required'));
                                                                }
                                                            }

                                                            providerSelect.addEventListener('change', toggleProviderSections);
                                                            toggleProviderSections(); // Initialize on page load

                                                            // --- Playground v2 Logic ---
                                                            const sendPlaygroundBtn = document.getElementById('send-playground-msg');
                                                            const playgroundPrompt = document.getElementById('playground-prompt');
                                                            const playgroundResponse = document.getElementById('playground-response');
                                                            const clearConsoleBtn = document.getElementById('clear-console');
                                                            const copyBtn = document.getElementById('copy-response');
                                                            const copyWrap = document.getElementById('playground-copy-wrap');
                                                            const modelInfo = document.getElementById('playground-model-info');
                                                            const modelName = document.getElementById('resp-model-name');
                                                            const timeMeta = document.getElementById('resp-time-meta');
                                                            const aiLabel = document.getElementById('current-ai-label');

                                                            // Clear Console
                                                            if (clearConsoleBtn) {
                                                                clearConsoleBtn.addEventListener('click', function () {
                                                                    playgroundResponse.innerHTML = `
                                                                                <div class="playground-welcome">
                                                                                    <div class="terminal-header">
                                                                                        <span class="dot red"></span>
                                                                                        <span class="dot yellow"></span>
                                                                                        <span class="dot green"></span>
                                                                                    </div>
                                                                                    <div class="welcome-content">
                                                                                        <i class="fas fa-terminal mb-3"></i>
                                                                                        <p>Console cleared. Ready for instructions.</p>
                                                                                    </div>
                                                                                </div>`;
                                                                    if (copyWrap) copyWrap.style.display = 'none';
                                                                    if (modelInfo) modelInfo.style.display = 'none';
                                                                    playgroundPrompt.value = '';
                                                                });
                                                            }

                                                            // Copy to Clipboard
                                                            if (copyBtn) {
                                                                copyBtn.addEventListener('click', function () {
                                                                    const bubble = document.querySelector('.playground-msg-bubble');
                                                                    if (!bubble) return;
                                                                    const text = bubble.innerText;
                                                                    navigator.clipboard.writeText(text).then(() => {
                                                                        const originalHtml = copyBtn.innerHTML;
                                                                        copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
                                                                        copyBtn.classList.add('btn-success');
                                                                        setTimeout(() => {
                                                                            copyBtn.innerHTML = originalHtml;
                                                                            copyBtn.classList.remove('btn-success');
                                                                        }, 2000);
                                                                    });
                                                                });
                                                            }

                                                            if (sendPlaygroundBtn) {
                                                                sendPlaygroundBtn.addEventListener('click', function () {
                                                                    const prompt = playgroundPrompt.value.trim();
                                                                    if (!prompt) {
                                                                        playgroundPrompt.classList.add('is-invalid');
                                                                        setTimeout(() => playgroundPrompt.classList.remove('is-invalid'), 2000);
                                                                        return;
                                                                    }

                                                                    const startTime = performance.now();
                                                                    sendPlaygroundBtn.disabled = true;
                                                                    sendPlaygroundBtn.innerHTML = '<span>Processing...</span>';
                                                                    if (aiLabel) aiLabel.innerText = 'Active Engine: Generating...';

                                                                    // Show professional typing indicator
                                                                    playgroundResponse.innerHTML = `
                                                                                <div class="typing-indicator-wrap" style="padding: 20px; display: flex; align-items: center; gap: 8px;">
                                                                                    <div class="typing-dots">
                                                                                        <div class="dot" style="width: 8px; height: 8px; background: rgba(255,255,255,0.4); border-radius: 50%; animation: typing-blink 1.4s infinite;"></div>
                                                                                        <div class="dot" style="width: 8px; height: 8px; background: rgba(255,255,255,0.4); border-radius: 50%; animation: typing-blink 1.4s infinite; animation-delay: 0.2s;"></div>
                                                                                        <div class="dot" style="width: 8px; height: 8px; background: rgba(255,255,255,0.4); border-radius: 50%; animation: typing-blink 1.4s infinite; animation-delay: 0.4s;"></div>
                                                                                    </div>
                                                                                    <span style="color: rgba(255,255,255,0.5); font-size: 13px; font-style: italic;">AI is thinking...</span>
                                                                                </div>
                                                                            `;

                                                                    fetch('{{ url("admin/ai-playground-chat") }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/json',
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                        },
                                                                        body: JSON.stringify({ prompt: prompt })
                                                                    })
                                                                        .then(response => response.json())
                                                                        .then(data => {
                                                                            const endTime = performance.now();
                                                                            const duration = ((endTime - startTime) / 1000).toFixed(1);

                                                                            sendPlaygroundBtn.disabled = false;
                                                                            sendPlaygroundBtn.innerHTML = '<span>Generate</span><i class="fas fa-sparkles ml-2"></i>';
                                                                            if (aiLabel) aiLabel.innerText = 'Active Engine: Ready';

                                                                            if (data.status === 'success') {
                                                                                playgroundResponse.innerHTML = `<div class="playground-msg-bubble">${data.answer}</div>`;
                                                                                if (copyWrap) copyWrap.style.display = 'block';
                                                                                if (modelInfo) {
                                                                                    modelInfo.style.display = 'flex';
                                                                                    if (modelName) modelName.innerText = data.model;
                                                                                    if (timeMeta) timeMeta.innerText = `Latency: ${duration}s`;
                                                                                }
                                                                            } else {
                                                                                playgroundResponse.innerHTML = `
                                                                                        <div class="alert alert-danger mx-3 my-3" style="border-radius:12px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: #ff808b;">
                                                                                            <i class="fas fa-exclamation-triangle mr-2"></i> ${data.message}
                                                                                        </div>`;
                                                                                if (copyWrap) copyWrap.style.display = 'none';
                                                                                if (modelInfo) modelInfo.style.display = 'none';
                                                                            }
                                                                        })
                                                                        .catch(error => {
                                                                            sendPlaygroundBtn.disabled = false;
                                                                            sendPlaygroundBtn.innerHTML = '<span>Generate</span><i class="fas fa-sparkles ml-2"></i>';
                                                                            if (aiLabel) aiLabel.innerText = 'Active Engine: Error';
                                                                            playgroundResponse.innerHTML = `<div class="alert alert-danger mx-3 my-3" style="border-radius:12px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: #ff808b;">Connection Error. Please check your credentials.</div>`;
                                                                        });
                                                                });
                                                            }

                                                            function setCardState(state) {
                                                                statusCard.classList.remove('ai-loading', 'ai-success', 'ai-error');
                                                                statusCard.classList.add('ai-' + state);
                                                            }

                                                            function formatErrorMessage(msg) {
                                                                if (msg.includes('API Error')) {
                                                                    const httpMatch = msg.match(/HTTP (\d+)/);
                                                                    const httpCode = httpMatch ? httpMatch[1] : '';
                                                                    let cleanMsg = msg.replace(/^API Error \(HTTP \d+\):\s*/, '');
                                                                    cleanMsg = cleanMsg.replace(/https?:\/\/[^\s]+/g, function (url) {
                                                                        return url.length > 60 ? url.substring(0, 57) + '...' : url;
                                                                    });
                                                                    return '<span style="display:inline-block;background:rgba(255,255,255,0.15);padding:2px 8px;border-radius:4px;font-size:11px;margin-bottom:6px;font-weight:600;">HTTP ' + httpCode + '</span><br>' + cleanMsg;
                                                                }
                                                                return msg;
                                                            }

                                                            // Eye toggle for Gemini API Key
                                                            const toggleGeminiBtn = document.getElementById('toggle_gemini_key');
                                                            const geminiKeyInput = document.getElementById('gemini_api_key_input');
                                                            const geminiEyeIcon = document.getElementById('gemini_key_eye');
                                                            if (toggleGeminiBtn && geminiKeyInput) {
                                                                toggleGeminiBtn.addEventListener('click', function () {
                                                                    if (geminiKeyInput.type === 'password') {
                                                                        geminiKeyInput.type = 'text';
                                                                        geminiEyeIcon.classList.remove('fa-eye');
                                                                        geminiEyeIcon.classList.add('fa-eye-slash');
                                                                    } else {
                                                                        geminiKeyInput.type = 'password';
                                                                        geminiEyeIcon.classList.remove('fa-eye-slash');
                                                                        geminiEyeIcon.classList.add('fa-eye');
                                                                    }
                                                                });
                                                            }

                                                            // Eye toggle for ChatGPT API Key
                                                            const toggleChatgptBtn = document.getElementById('toggle_chatgpt_key');
                                                            const chatgptKeyInput = document.getElementById('chatgpt_api_key_input');
                                                            const chatgptEyeIcon = document.getElementById('chatgpt_key_eye');
                                                            if (toggleChatgptBtn && chatgptKeyInput) {
                                                                toggleChatgptBtn.addEventListener('click', function () {
                                                                    if (chatgptKeyInput.type === 'password') {
                                                                        chatgptKeyInput.type = 'text';
                                                                        chatgptEyeIcon.classList.remove('fa-eye');
                                                                        chatgptEyeIcon.classList.add('fa-eye-slash');
                                                                    } else {
                                                                        chatgptKeyInput.type = 'password';
                                                                        chatgptEyeIcon.classList.remove('fa-eye-slash');
                                                                        chatgptEyeIcon.classList.add('fa-eye');
                                                                    }
                                                                });
                                                            }

                                                            if (testBtn) {
                                                                testBtn.addEventListener('click', function () {
                                                                    const provider = providerSelect.value;
                                                                    robotIcon.classList.add('pulse');
                                                                    testBtn.disabled = true;

                                                                    setCardState('loading');
                                                                    statusTitle.innerText = 'Testing Connection...';
                                                                    statusMsg.innerHTML = 'Connecting to ' + (provider === 'gemini' ? 'Gemini API' : (provider === 'chatgpt' ? 'ChatGPT' : 'Vertex AI')) + ', please wait...';
                                                                    statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#17a2b8"></i>';
                                                                    statusCard.classList.add('show');

                                                                    fetch('{{ url("admin/check-ai-connection") }}?provider=' + provider)
                                                                        .then(response => response.json())
                                                                        .then(data => {
                                                                            robotIcon.classList.remove('pulse');
                                                                            testBtn.disabled = false;

                                                                            if (data.status === 'success') {
                                                                                setCardState('success');
                                                                                statusTitle.innerText = '✓ Connected Successfully';
                                                                                statusIcon.innerHTML = '<i class="fas fa-check-circle" style="color:#28a745"></i>';
                                                                                statusMsg.innerHTML = data.message;
                                                                            } else {
                                                                                setCardState('error');
                                                                                statusTitle.innerText = '✕ Connection Failed';
                                                                                statusIcon.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#dc3545"></i>';
                                                                                statusMsg.innerHTML = formatErrorMessage(data.message);
                                                                            }

                                                                            setTimeout(() => {
                                                                                statusCard.classList.remove('show');
                                                                            }, 10000);
                                                                        })
                                                                        .catch(error => {
                                                                            robotIcon.classList.remove('pulse');
                                                                            testBtn.disabled = false;
                                                                            setCardState('error');
                                                                            statusTitle.innerText = '✕ Network Error';
                                                                            statusMsg.innerHTML = 'Could not reach the server. Please check your internet connection and try again.';
                                                                            statusIcon.innerHTML = '<i class="fas fa-wifi" style="color:#dc3545"></i>';
                                                                            statusCard.classList.add('show');
                                                                        });
                                                                });
                                                            }

                                                            if (closeBtn) {
                                                                closeBtn.addEventListener('click', () => statusCard.classList.remove('show'));
                                                            }
                                                        });
                                                    </script>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse2">
                                                    Ads Setting
                                                </h4>
                                            </div>
                                            <div id="collapse2" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/ads-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('ads_enable', 'Ads Enable', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9 mt-1">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="ads_enable" value="1"
                                                                            name="name[ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('ads_network', 'Ads Network', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    <select id="ads_network" name="name[ads_network]"
                                                                        class="form-control" required>
                                                                        <option value="Admob"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('ads_network') == "Admob")
                                                                            selected @endif>Admob</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('publisher_id', 'Publisher Id', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    {!! Form::text('name[publisher_id]', App\Models\AdsSetting::getAdsSetting('publisher_id'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="card card-success">
                                                                <div class="card-header">
                                                                    <span>Banner Ads</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="banner_ads_enable"
                                                                            value="1" name="name[banner_ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('banner_ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="banner_ads_id">Banner Ads ID</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[banner_ads_id]"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('banner_ads_id')}}"
                                                                            id="banner_ads_id"
                                                                            placeholder="Enter Banner Ads Id">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-success">
                                                                <div class="card-header">
                                                                    <span>APP Opens Ads</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="app_opens_ads_enable"
                                                                            value="1" name="name[app_opens_ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('app_opens_ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="app_open_ads_id">App Open Ads ID</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[app_open_ads_id]"
                                                                            id="app_open_ads_id"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('app_open_ads_id')}}"
                                                                            placeholder="Enter App Open Ads Id">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="card card-success">
                                                                <div class="card-header">
                                                                    <span>Native Ads</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="native_ads_enable"
                                                                            value="1" name="name[native_ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('native_ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="native_ads_id">Native Ads ID</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[native_ads_id]" id="native_ads_id"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('native_ads_id')}}"
                                                                            placeholder="Enter Native Ads Id">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-success">
                                                                <div class="card-header">
                                                                    <span>Rewarded Ads</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="rewarded_ads_enable"
                                                                            value="1" name="name[rewarded_ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('rewarded_ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="rewarded_ads_id">Rewarded Ads ID</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[rewarded_ads_id]"
                                                                            id="rewarded_ads_id"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('rewarded_ads_id')}}"
                                                                            placeholder="Enter Rewarded Ads Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="daily_limit_rewarded">Daily Limit in
                                                                            Rewarded</label>
                                                                        <input type="number" class="form-control"
                                                                            name="name[daily_limit_rewarded]"
                                                                            id="daily_limit_rewarded"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('daily_limit_rewarded')}}"
                                                                            placeholder="Enter Daily Limit in Rewarded">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="card card-success">
                                                                <div class="card-header">
                                                                    <span>Interstitial Ads</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="interstitial_ads_enable"
                                                                            value="1" name="name[interstitial_ads_enable]"
                                                                            @if(App\Models\AdsSetting::getAdsSetting('interstitial_ads_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="interstitial_ads_id">Interstitial Ads
                                                                            ID</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[interstitial_ads_id]"
                                                                            id="interstitial_ads_id"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('interstitial_ads_id')}}"
                                                                            placeholder="Enter Interstitial Ads Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="interstitial_ads_click">Interstitial Ads
                                                                            Click</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[interstitial_ads_click]"
                                                                            id="interstitial_ads_click"
                                                                            value="{{App\Models\AdsSetting::getAdsSetting('interstitial_ads_click')}}"
                                                                            placeholder="Enter Interstitial Ads Click">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading" data-toggle="collapse" data-parent="#accordion"
                                                data-target="#collapse3">
                                                <h4 class="panel-title">
                                                    Notification
                                                </h4>
                                            </div>
                                            <div id="collapse3" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/notification-setting', 'method' => 'POST', 'files' => true]) !!}
                                                    <div class="row mt-3">
                                                         <div class="col-12">
                                                             <div class="form-group row">
                                                                 {!! Form::label('firebase_service_account', 'Firebase Service Account (JSON)', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                 <div class="col-xl-5 col-md-9 col-9">
                                                                     <div class="input-group">
                                                                         <input type="file" name="firebase_service_account" class="form-control" accept=".json">
                                                                         @if(App\Models\NotificationSetting::getNotificationSetting('firebase_service_account_encrypted') || App\Models\NotificationSetting::getNotificationSetting('firebase_service_account'))
                                                                             <div class="input-group-append">
                                                                                 <button type="submit" name="remove_credentials" value="1" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove the Firebase Service Account credentials?')">
                                                                                     <i class="fas fa-trash-alt mr-1"></i> Remove
                                                                                 </button>
                                                                             </div>
                                                                         @endif
                                                                     </div>
                                                                     @if(App\Models\NotificationSetting::getNotificationSetting('firebase_service_account_encrypted') || App\Models\NotificationSetting::getNotificationSetting('firebase_service_account'))
                                                                         <small class="text-success mt-2 d-block"><i class="fas fa-check-circle"></i> Service Account JSON is currently uploaded and active.</small>
                                                                     @else
                                                                         <small class="text-danger mt-2 d-block"><i class="fas fa-times-circle"></i> No Service Account JSON uploaded yet.</small>
                                                                     @endif
                                                                 </div>
                                                             </div>
                                                         </div>
                                                     </div>

                                                     <div class="row">
                                                         <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                             @if(optional(Auth::user())->user_type == "Demo")
                                                                 <button type="button"
                                                                     class="btn btn-success ToastrButton">Save</button>
                                                             @else
                                                                 {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                                 {{-- Future: Add Test Connection Button --}}
                                                             @endif
                                                         </div>
                                                     </div>
                                                     {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse4">
                                                    Email Setting
                                                </h4>
                                            </div>
                                            <div id="collapse4" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/email-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('smtp_host', 'SMTP Host', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    {!! Form::text('name[smtp_host]', App\Models\EmailSetting::getEmailSetting('smtp_host'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('username', 'Username', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    {!! Form::text('name[username]', App\Models\EmailSetting::getEmailSetting('username'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('password', 'Password', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    {!! Form::text('name[password]', null, ['class' => 'form-control']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('password', 'SMTP Secure', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9">
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <select id="encryption" name="name[encryption]"
                                                                                class="form-control" required>
                                                                                <option value="ssl"
                                                                                    @if(App\Models\EmailSetting::getEmailSetting('encryption') == "ssl")
                                                                                    selected @endif>SSL</option>
                                                                                <option value="tls"
                                                                                    @if(App\Models\EmailSetting::getEmailSetting('encryption') == "tls")
                                                                                    selected @endif>TLS</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            {!! Form::number('name[port]', App\Models\EmailSetting::getEmailSetting('port'), ['class' => 'form-control', 'required']) !!}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse5">
                                                    Payment Setting
                                                </h4>
                                            </div>
                                            <div id="collapse5" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/payment-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Razorpay</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="razorpay_enable"
                                                                            value="1" name="name[razorpay_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('razorpay_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="razorpay_key_id">Razorpay Key Id</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[razorpay_key_id]"
                                                                            id="razorpay_key_id"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('razorpay_key_id')}}"
                                                                            placeholder="Enter Razorpay Key Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="razorpay_key_secret">Razorpay Secret
                                                                            Key</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[razorpay_key_secret]"
                                                                            id="razorpay_key_secret"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('razorpay_key_secret')}}"
                                                                            placeholder="Enter Razorpay Secret Key">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Stripe</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="stripe_enable" value="1"
                                                                            name="name[stripe_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('stripe_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="stripe_publishable_Key">Stripe
                                                                            Publishable Key</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[stripe_publishable_Key]"
                                                                            id="stripe_publishable_Key"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('stripe_publishable_Key')}}"
                                                                            placeholder="Enter Stripe Publishable Key">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="stripe_secret_key">Stripe Secret
                                                                            Key</label>
                                                                        <textarea rows="3" class="form-control"
                                                                            name="name[stripe_secret_key]"
                                                                            id="stripe_secret_key">{{App\Models\PaymentSetting::getPaymentSetting('stripe_secret_key')}}</textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Cashfree</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="cashfree_enable"
                                                                            value="1" name="name[cashfree_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('cashfree_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="cashfree_type">Cashfree Payment</label>
                                                                        <select class="form-control" id="cashfree_type"
                                                                            name="name[cashfree_type]" required>
                                                                            <option value="Test"
                                                                                @if(App\Models\PaymentSetting::getPaymentSetting('cashfree_type') == "Test")
                                                                                selected @endif>Test</option>
                                                                            <option value="Live"
                                                                                @if(App\Models\PaymentSetting::getPaymentSetting('cashfree_type') == "Live")
                                                                                selected @endif>Live</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="cashfree_key_id">Cashfree Key Id</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[cashfree_key_id]"
                                                                            id="cashfree_key_id"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('cashfree_key_id')}}"
                                                                            placeholder="Enter Cashfree Key Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="cashfree_key_secret">Cashfree Secret
                                                                            Key</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[cashfree_key_secret]"
                                                                            id="cashfree_key_secret"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('cashfree_key_secret')}}"
                                                                            placeholder="Enter Cashfree Secret Key">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Paytm</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="paytm_enable" value="1"
                                                                            name="name[paytm_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('paytm_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="paytm_type">Paytm Payment</label>
                                                                        <select class="form-control" id="paytm_type"
                                                                            name="name[paytm_type]" required>
                                                                            <option value="Test"
                                                                                @if(App\Models\PaymentSetting::getPaymentSetting('paytm_type') == "Test")
                                                                                selected @endif>Test</option>
                                                                            <option value="Live"
                                                                                @if(App\Models\PaymentSetting::getPaymentSetting('paytm_type') == "Live")
                                                                                selected @endif>Live</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="paytm_merchant_id">Paytm Merchant
                                                                            Id</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[paytm_merchant_id]"
                                                                            id="paytm_merchant_id"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('paytm_merchant_id')}}"
                                                                            placeholder="Enter Paytm Merchant Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="paytm_merchant_key">Paytm Merchant
                                                                            Key</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[paytm_merchant_key]"
                                                                            id="paytm_merchant_key"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('paytm_merchant_key')}}"
                                                                            placeholder="Enter Paytm Merchant Key">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Phonepe</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="phonepe_enable" value="1"
                                                                            name="name[phonepe_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('phonepe_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="phonepe_merchant_id">Phonepe Merchant
                                                                            Id</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[phonepe_merchant_id]"
                                                                            id="phonepe_merchant_id"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('phonepe_merchant_id')}}"
                                                                            placeholder="Enter Phonepe Merchant Id">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="phonepe_salt_key">Phonepe Salt
                                                                            Key</label>
                                                                        <input type="text" class="form-control"
                                                                            name="name[phonepe_salt_key]"
                                                                            id="phonepe_salt_key"
                                                                            value="{{App\Models\PaymentSetting::getPaymentSetting('phonepe_salt_key')}}"
                                                                            placeholder="Enter Phonepe Salt Key">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card card-primary">
                                                                <div class="card-header">
                                                                    <span>Offline Payment</span>
                                                                    <label class="cl-switch cl-switch-blue float-right">
                                                                        <input type="checkbox" id="offline_enable" value="1"
                                                                            name="name[offline_enable]"
                                                                            @if(App\Models\PaymentSetting::getPaymentSetting('offline_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="form-group">
                                                                        <label for="offline_payment_details">Offline Payment
                                                                            Details</label>
                                                                        <textarea rows="5" class="form-control"
                                                                            name="name[offline_payment_details]"
                                                                            id="offline_payment_details">{{App\Models\PaymentSetting::getPaymentSetting('offline_payment_details')}}</textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse6">
                                                    Api Setting
                                                </h4>
                                            </div>
                                            <div id="collapse6" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/api-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">1</th>
                                                                <td>Get Category Api</td>
                                                                <td>Order By</td>
                                                                <td>
                                                                    <select class="form-control" id="category_order_type"
                                                                        name="name[category_order_type]" required>
                                                                        <option value="name"
                                                                            @if(App\Models\ApiSetting::getApiSetting("category_order_type") == "name")
                                                                            selected @endif>Category Name</option>
                                                                        <option value="created_at"
                                                                            @if(App\Models\ApiSetting::getApiSetting("category_order_type") == "created_at")
                                                                            selected @endif>Added Date</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-control" id="category_order_by"
                                                                        name="name[category_order_by]" required>
                                                                        <option value="asc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("category_order_by") == "asc")
                                                                            selected @endif>Ascending</option>
                                                                        <option value="desc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("category_order_by") == "desc")
                                                                            selected @endif>Descending</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">2</th>
                                                                <td>Get Festivals Api</td>
                                                                <td>Order By</td>
                                                                <td>
                                                                    <select class="form-control" id="festival_order_type"
                                                                        name="name[festival_order_type]" required>
                                                                        <option value="title"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_type") == "name")
                                                                            selected @endif>Festival Title</option>
                                                                        <option value="festivals_date"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_type") == "festivals_date")
                                                                            selected @endif>Festivals Date</option>
                                                                        <option value="activation_date"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_type") == "activation_date")
                                                                            selected @endif>Activation Date</option>
                                                                        <option value="created_at"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_type") == "created_at")
                                                                            selected @endif>Added Date</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-control" id="festival_order_by"
                                                                        name="name[festival_order_by]" required>
                                                                        <option value="asc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_by") == "asc")
                                                                            selected @endif>Ascending</option>
                                                                        <option value="desc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("festival_order_by") == "desc")
                                                                            selected @endif>Descending</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">3</th>
                                                                <td>Get Custom Category Api</td>
                                                                <td>Order By</td>
                                                                <td>
                                                                    <select class="form-control" id="custom_order_type"
                                                                        name="name[custom_order_type]" required>
                                                                        <option value="name"
                                                                            @if(App\Models\ApiSetting::getApiSetting("custom_order_type") == "name")
                                                                            selected @endif>Custom Category Name</option>
                                                                        <option value="created_at"
                                                                            @if(App\Models\ApiSetting::getApiSetting("custom_order_type") == "created_at")
                                                                            selected @endif>Added Date</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-control" id="custom_order_by"
                                                                        name="name[custom_order_by]" required>
                                                                        <option value="asc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("custom_order_by") == "asc")
                                                                            selected @endif>Ascending</option>
                                                                        <option value="desc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("custom_order_by") == "desc")
                                                                            selected @endif>Descending</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">4</th>
                                                                <td>Get Business Category Api</td>
                                                                <td>Order By</td>
                                                                <td>
                                                                    <select class="form-control" id="business_order_type"
                                                                        name="name[business_order_type]" required>
                                                                        <option value="name"
                                                                            @if(App\Models\ApiSetting::getApiSetting("business_order_type") == "name")
                                                                            selected @endif>Business Category Name</option>
                                                                        <option value="created_at"
                                                                            @if(App\Models\ApiSetting::getApiSetting("business_order_type") == "created_at")
                                                                            selected @endif>Added Date</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-control" id="business_order_by"
                                                                        name="name[business_order_by]" required>
                                                                        <option value="asc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("business_order_by") == "asc")
                                                                            selected @endif>Ascending</option>
                                                                        <option value="desc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("business_order_by") == "desc")
                                                                            selected @endif>Descending</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">5</th>
                                                                <td>Get News Api</td>
                                                                <td>Order By</td>
                                                                <td>
                                                                    <select class="form-control" id="news_order_type"
                                                                        name="name[news_order_type]" required>
                                                                        <option value="title"
                                                                            @if(App\Models\ApiSetting::getApiSetting("news_order_type") == "title")
                                                                            selected @endif>News Title</option>
                                                                        <option value="created_at"
                                                                            @if(App\Models\ApiSetting::getApiSetting("news_order_type") == "created_at")
                                                                            selected @endif>Added Date</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-control" id="news_order_by"
                                                                        name="name[news_order_by]" required>
                                                                        <option value="asc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("news_order_by") == "asc")
                                                                            selected @endif>Ascending</option>
                                                                        <option value="desc"
                                                                            @if(App\Models\ApiSetting::getApiSetting("news_order_by") == "desc")
                                                                            selected @endif>Descending</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <!-- <tr>
                                                                                                                                                                    <th scope="row">6</th>
                                                                                                                                                                    <td>Get Stories Api</td>
                                                                                                                                                                    <td>Order By</td>
                                                                                                                                                                    <td>
                                                                                                                                                                        <select class="form-control" id="story_order_type" name="name[story_order_type]" required>
                                                                                                                                                                            <option value="name" @if(App\Models\ApiSetting::getApiSetting("story_order_type") == "name") selected @endif>Name</option>
                                                                                                                                                                            <option value="created_at" @if(App\Models\ApiSetting::getApiSetting("story_order_type") == "created_at") selected @endif>Added Date</option>
                                                                                                                                                                        </select>
                                                                                                                                                                    </td>
                                                                                                                                                                    <td>
                                                                                                                                                                        <select class="form-control" id="story_order_by" name="name[story_order_by]" required>
                                                                                                                                                                            <option value="asc" @if(App\Models\ApiSetting::getApiSetting("story_order_by") == "asc") selected @endif>Ascending</option>
                                                                                                                                                                            <option value="desc" @if(App\Models\ApiSetting::getApiSetting("story_order_by") == "desc") selected @endif>Descending</option>
                                                                                                                                                                        </select>
                                                                                                                                                                    </td>
                                                                                                                                                                </tr> -->
                                                        </tbody>
                                                    </table>

                                                    <hr>
                                                    <h5 class="mt-4 mb-3" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1e293b;"><i class="fas fa-magic mr-2 text-primary"></i>Photoroom API (Background Removal)</h5>
                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('photoroom_api_enable', 'Enable Photoroom API', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-9 col-9 mt-1">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="photoroom_api_enable" value="1"
                                                                            name="name[photoroom_api_enable]"
                                                                            @if(App\Models\ApiSetting::getApiSetting('photoroom_api_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                    <small class="text-muted d-block mt-1">If enabled, the system will use the paid Photoroom API for background removal based on subscription limits. If disabled, it will fallback to the free system default.</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('photoroom_api_key', 'Photoroom API Key', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-xl-10 col-md-9 col-9">
                                                                    {!! Form::text('name[photoroom_api_key]', App\Models\ApiSetting::getApiSetting('photoroom_api_key'), ['class' => 'form-control', 'placeholder' => 'Enter your Photoroom API Key here']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>


                                        <div class="vertab-content">
                                            <div class="panel-heading" data-toggle="collapse" data-parent="#accordion"
                                                data-target="#collapse61">
                                                <h4 class="panel-title">
                                                    WhatsApp Setting
                                                </h4>
                                            </div>
                                            <div id="collapse61" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/whatsapp-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('Whatsapp_auth_enable', 'Whatsapp Authentication', ['class' => 'col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-md-6 col-9 mt-1">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="whatsapp_auth_enable"
                                                                            value="1" name="name[whatsapp_auth_enable]"
                                                                            @if(App\Models\WhatsAppSetting::getWhatsAppSetting('whatsapp_auth_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                <label for="example-text-input"
                                                                    class="col-md-3 col-3 col-form-label">Send OTP
                                                                    Message</label>
                                                                <div class="col-md-6 col-9 mt-1">
                                                                    <textarea class="form-control"
                                                                        name="name[whatsapp_otp_message]" type="text"
                                                                        placeholder="Enter Send OTP message" rows=3
                                                                        required>{{App\Models\WhatsAppSetting::getWhatsAppSetting('whatsapp_otp_message')}}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('evolution_server_url', 'Evolution Server URL', ['class' => 'col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-md-6 col-9">
                                                                    {!! Form::url('name[evolution_server_url]', App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_server_url'), ['class' => 'form-control', 'placeholder' => 'e.g., http://localhost:8080']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('evolution_api_key', 'Global API Key', ['class' => 'col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-md-6 col-9">
                                                                    {!! Form::text('name[evolution_api_key]', App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_api_key'), ['class' => 'form-control', 'placeholder' => 'Evolution Global API Key']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('evolution_instance_name', 'Instance Name', ['class' => 'col-md-3 col-3 col-form-label']) !!}
                                                                <div class="col-md-6 col-9">
                                                                    {!! Form::text('name[evolution_instance_name]', App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_instance_name'), ['class' => 'form-control', 'placeholder' => 'e.g., ArteraInstance']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button" class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                    
                                                    <hr>
                                                    <h5 class="mt-4 mb-3" style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #1e293b;"><i class="fab fa-whatsapp mr-2 text-success"></i>Connect WhatsApp (Scan QR)</h5>
                                                    <div class="row mt-3">
                                                        <div class="col-md-3"></div>
                                                        <div class="col-md-6 text-center">
                                                            <div id="qrCodeContainer" style="border: 2px dashed #cbd5e1; padding: 20px; border-radius: 10px; background: #f8fafc; min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                                                <i class="fas fa-qrcode fa-4x text-muted mb-3" id="qrPlaceholderIcon"></i>
                                                                <img id="whatsappQrImage" src="" alt="WhatsApp QR Code" style="display: none; max-width: 200px;" />
                                                                <p id="qrStatusText" class="text-muted mt-2">Click below to generate a QR code for your instance.</p>
                                                                <button type="button" id="btnGenerateQr" class="btn btn-primary mt-2"><i class="fas fa-sync-alt"></i> Generate QR Code</button>
                                                            </div>
                                                            <div id="connectedStateContainer" style="display: none; border: 2px solid #28a745; padding: 20px; border-radius: 10px; background: #f4fff6; min-height: 250px; flex-direction: column; align-items: center; justify-content: center;">
                                                                <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                                                                <h4 class="text-success font-weight-bold">Connected Successfully</h4>
                                                                <p class="text-muted mt-2">Your WhatsApp instance is active and ready to send messages.</p>
                                                                <span class="badge badge-success p-2 mt-2" style="font-size: 14px;"><i class="fas fa-circle text-white mr-1" style="font-size: 10px;"></i> Online</span>
                                                                <button type="button" id="btnCheckStatus" class="btn btn-outline-success mt-4"><i class="fas fa-sync-alt"></i> Refresh Status</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse7">
                                                    Storage Setting
                                                </h4>
                                            </div>
                                            <div id="collapse7" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/storage-setting', 'method' => 'POST', 'files' => true, 'id' => 'storage_form']) !!}

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('storage', 'Select Storage', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <select class="form-control" id="storage"
                                                                        name="name[storage]" required>
                                                                        <option value="local"
                                                                            @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                            selected @endif>Local</option>
                                                                        <option value="DigitalOcean"
                                                                            @if(App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                                                                            selected @endif>Digital Ocean</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('digitalOcean_space_name', 'DigitalOcean Space Name', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <input type="text" id="digitalOcean_space_name"
                                                                        name="name[digitalOcean_space_name]"
                                                                        value="{{App\Models\StorageSetting::getStorageSetting('digitalOcean_space_name')}}"
                                                                        class="form-control"
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        readonly @else required @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('digitalOcean_key', 'DigitalOcean Key', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <input type="text" id="digitalOcean_key"
                                                                        name="name[digitalOcean_key]"
                                                                        value="{{App\Models\StorageSetting::getStorageSetting('digitalOcean_key')}}"
                                                                        class="form-control"
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        readonly @else required @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('digitalOcean_secret', 'DigitalOcean Secret', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <input type="text" id="digitalOcean_secret"
                                                                        name="name[digitalOcean_secret]"
                                                                        value="{{App\Models\StorageSetting::getStorageSetting('digitalOcean_secret')}}"
                                                                        class="form-control"
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        readonly @else required @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('digitalOcean_bucket_region', 'DigitalOcean Bucket Region', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <input type="text" id="digitalOcean_bucket_region"
                                                                        name="name[digitalOcean_bucket_region]"
                                                                        value="{{App\Models\StorageSetting::getStorageSetting('digitalOcean_bucket_region')}}"
                                                                        class="form-control"
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        readonly @else required @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('digitalOcean_endpoint', 'DigitalOcean Endpoint', ['class' => 'col-3 col-form-label']) !!}
                                                                <div class="col-9">
                                                                    <input type="text" id="digitalOcean_endpoint"
                                                                        name="name[digitalOcean_endpoint]"
                                                                        value="{{App\Models\StorageSetting::getStorageSetting('digitalOcean_endpoint')}}"
                                                                        class="form-control"
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        readonly @else required @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton digitalOcean_btn">Save</button>
                                                            @else
                                                                <button type="submit" class="btn btn-success digitalOcean_btn"
                                                                    id="save_digitalOcean">Save</button>
                                                                <!-- <a href="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {{url('admin/move-local-to-digitalOcean')}} @endif" type="button" class="btn btn-primary">Move Files From Local To Digital Ocean</a> -->
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('test_image', 'Test Image', ['class' => 'col-sm-3 col-form-label']) !!}
                                                                <div class="col-sm-4">
                                                                    <input class="form-control" type="file" id="test_image"
                                                                        name="test_image"
                                                                        accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG"
                                                                        required
                                                                        @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                        disabled @endif>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-3">
                                                        </div>
                                                        <div class="col-9" id="view_test_image">

                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton digitalOcean_test_btn"
                                                                    @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                    disabled @endif>Test</button>
                                                            @else
                                                                <button type="submit"
                                                                    class="btn btn-success digitalOcean_test_btn" id="test_btn"
                                                                    @if(App\Models\StorageSetting::getStorageSetting("storage") == "local")
                                                                    disabled @endif>Test</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse8">
                                                    App Update Popup
                                                </h4>
                                            </div>
                                            <div id="collapse8" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/app-update-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('update_popup_show', 'App Update Popup Show/Hide', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="update_popup_show"
                                                                            value="1" name="name[update_popup_show]"
                                                                            @if(App\Models\AppUpdateSetting::getAppUpdateSetting('update_popup_show') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('new_app_version_code', 'New App Version Code', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    {!! Form::text('name[new_app_version_code]', App\Models\AppUpdateSetting::getAppUpdateSetting('new_app_version_code'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('description', 'Description', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    {!! Form::textarea('name[description]', App\Models\AppUpdateSetting::getAppUpdateSetting('description'), ['class' => 'form-control', 'rows' => 7, 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('app_link', 'App Link', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    {!! Form::text('name[app_link]', App\Models\AppUpdateSetting::getAppUpdateSetting('app_link'), ['class' => 'form-control', 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('cancel_option', 'Cancel Option', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="cancel_option" value="1"
                                                                            name="name[cancel_option]"
                                                                            @if(App\Models\AppUpdateSetting::getAppUpdateSetting('cancel_option') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse9">
                                                    Privacy Policy
                                                </h4>
                                            </div>
                                            <div id="collapse9" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/other-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('privacy_policy', 'Privacy Policy', ['class' => 'col-sm-2 col-form-label']) !!}
                                                                <div class="col-sm-10">
                                                                    <textarea name="name[privacy_policy]"
                                                                        id="privacy_policy" class="form-control"
                                                                        required>{{App\Models\OtherSetting::getOtherSetting('privacy_policy')}}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse10">
                                                    Refund Policy
                                                </h4>
                                            </div>
                                            <div id="collapse10" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/other-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('refund_policy', 'Refund Policy', ['class' => 'col-sm-2 col-form-label']) !!}
                                                                <div class="col-sm-10">
                                                                    <textarea name="name[refund_policy]" id="refund_policy"
                                                                        class="form-control"
                                                                        required>{{App\Models\OtherSetting::getOtherSetting('refund_policy')}}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse11">
                                                    Terms & Condition
                                                </h4>
                                            </div>
                                            <div id="collapse11" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/other-setting', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('terms_condition', 'Terms & Condition', ['class' => 'col-sm-2 col-form-label']) !!}
                                                                <div class="col-sm-10">
                                                                    <textarea name="name[terms_condition]"
                                                                        id="terms_condition" class="form-control"
                                                                        required>{{App\Models\OtherSetting::getOtherSetting('terms_condition')}}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="vertab-content">
                                            <div class="panel-heading">
                                                <h4 class="panel-title" data-toggle="collapse" data-parent="#accordion"
                                                    data-target="#collapse12">
                                                    Whatsapp Contact
                                                </h4>
                                            </div>
                                            <div id="collapse12" class="panel-collapse collapse">
                                                <div class="panel-body">
                                                    {!! Form::open(['url' => 'admin/whatsapp-contact', 'method' => 'POST', 'files' => true]) !!}

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('whatsapp_contact_enable', 'Whatsapp Contact Enable', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    <label class="cl-switch cl-switch-blue">
                                                                        <input type="checkbox" id="whatsapp_contact_enable"
                                                                            value="1" name="name[whatsapp_contact_enable]"
                                                                            @if(App\Models\AppSetting::getAppSetting('whatsapp_contact_enable') == 1)
                                                                            checked @endif>
                                                                        <span class="switcher"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                {!! Form::label('whatsapp_number', 'Whatsapp Number', ['class' => 'col-xl-3 col-md-4 col-4 col-form-label']) !!}
                                                                <div class="col-xl-5 col-md-8 col-8">
                                                                    {!! Form::text('name[whatsapp_number]', App\Models\AppSetting::getAppSetting('whatsapp_number'), ['class' => 'form-control', 'pattern' => "[0-9]*", 'minlength' => 10, 'maxlength' => 10, 'required']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3"></div><div class="col-md-9 text-left">
                                                            @if(optional(Auth::user())->user_type == "Demo")
                                                                <button type="button"
                                                                    class="btn btn-success ToastrButton">Save</button>
                                                            @else
                                                                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    {!! Form::close() !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to Delete ?</p>
                </div>
                <div class="modal-footer">
                    <button id="del_btn" class="btn btn-danger" type="button" data-submit="">Delete</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
@endsection

@section("script")
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#timezone').select2();
            $('#currency').select2();
            $('#category_order_by').select2();
            $('#category_order_type').select2();
            $('#news_order_by').select2();
            $('#news_order_type').select2();
            $('#festival_order_by').select2();
            $('#festival_order_type').select2();
            $('#custom_order_by').select2();
            $('#custom_order_type').select2();
            $('#business_order_by').select2();
            $('#business_order_type').select2();
            $('#story_order_by').select2();
            $('#story_order_type').select2();
            $('#storage').select2();
            $('#ads_network').select2();
            $('#encryption').select2();
            $('#payment_gateway').select2();
        });

        var msg = "{{Session::get('alert')}}";
        var exist = "{{Session::has('alert')}}";
        if (exist) {
            alert(msg);
        }

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
        });

        $("#del_btn").on("click", function () {
            var id = $(this).data("submit");
            $("#form_" + id).submit();
        });

        $('#myModal').on('show.bs.modal', function (e) {
            var id = e.relatedTarget.dataset.id;
            $("#del_btn").attr("data-submit", id);
        });

        $('#desc_text').summernote({
            placeholder: '',
            tabsize: 2,
            height: 150
        });

        $('#privacy_policy').summernote({
            placeholder: '',
            tabsize: 2,
            height: 400
        });

        $('#refund_policy').summernote({
            placeholder: '',
            tabsize: 2,
            height: 400
        });

        $('#terms_condition').summernote({
            placeholder: '',
            tabsize: 2,
            height: 400
        });

        $("#payment_gateway").change(function () {
            $('#otherText').empty();
            if ($(this).find("option:selected").text() == "Razorpay") {
                $('#otherText').append('<div class="row"><div class="col-xl-2 col-md-3 col-3"><label class="col-form-label">Razorpay key Id</label></div><div class="col-xl-5 col-md-9 col-9"><input type="text" class="form-control" name="name[razorpay_key_id]" value="{{App\Models\PaymentSetting::getPaymentSetting("razorpay_key_id")}}"></div></div>');
                $('#otherText').append('<div class="row mt-3"><div class="col-xl-2 col-md-3 col-3"><label class="col-form-label">Razorpay Key Secret</label></div><div class="col-xl-5 col-md-9 col-9"><input type="text" class="form-control" name="name[razorpay_key_secret]" value="{{App\Models\PaymentSetting::getPaymentSetting("razorpay_key_secret")}}"></div></div>');
            }
            if ($(this).find("option:selected").text() == "Cashfree") {
                $('#otherText').append('<div class="row"><div class="col-xl-2 col-md-3 col-3"><label class="col-form-label">Cashfree key Id</label></div><div class="col-xl-5 col-md-9 col-9"><input type="text" class="form-control" name="name[cashfree_key_id]" value="{{App\Models\PaymentSetting::getPaymentSetting("cashfree_key_id")}}"></div></div>');
                $('#otherText').append('<div class="row mt-3"><div class="col-xl-2 col-md-3 col-3"><label class="col-form-label">Cashfree Key Secret</label></div><div class="col-xl-5 col-md-9 col-9"><input type="text" class="form-control" name="name[cashfree_key_secret]" value="{{App\Models\PaymentSetting::getPaymentSetting("cashfree_key_secret")}}"></div></div>');
            }
        });

        $("#storage").change(function () {
            if ($(this).find("option:selected").text() == "Local") {
                $("#digitalOcean_space_name").prop("readonly", true);
                $("#digitalOcean_key").prop("readonly", true);
                $("#digitalOcean_secret").prop("readonly", true);
                $("#digitalOcean_bucket_region").prop("readonly", true);
                $("#digitalOcean_endpoint").prop("readonly", true);
                $(".digitalOcean_test_btn").attr('disabled', 'disabled');
            }
            if ($(this).find("option:selected").text() == "Digital Ocean") {
                $("#digitalOcean_space_name").prop("readonly", false);
                $("#digitalOcean_space_name").attr("required", "true");
                $("#digitalOcean_key").prop("readonly", false);
                $("#digitalOcean_key").attr("required", "true");
                $("#digitalOcean_secret").prop("readonly", false);
                $("#digitalOcean_secret").attr("required", "true");
                $("#digitalOcean_bucket_region").prop("readonly", false);
                $("#digitalOcean_bucket_region").attr("required", "true");
                $("#digitalOcean_endpoint").prop("readonly", false);
                $("#digitalOcean_endpoint").attr("required", "true");
                $(".digitalOcean_btn").removeAttr('disabled');
            }
        });

        $('#save_digitalOcean').on('click', function () {
            var storage = $('#storage').val();
            var digitalOcean_space_name = $('#digitalOcean_space_name').val();
            var digitalOcean_key = $('#digitalOcean_key').val();
            var digitalOcean_secret = $('#digitalOcean_secret').val();
            var digitalOcean_bucket_region = $('#digitalOcean_bucket_region').val();
            var digitalOcean_endpoint = $('#digitalOcean_endpoint').val();

            var form = new FormData();
            form.append("name[storage]", storage);
            form.append("name[digitalOcean_space_name]", digitalOcean_space_name);
            form.append("name[digitalOcean_key]", digitalOcean_key);
            form.append("name[digitalOcean_secret]", digitalOcean_secret);
            form.append("name[digitalOcean_bucket_region]", digitalOcean_bucket_region);
            form.append("name[digitalOcean_endpoint]", digitalOcean_endpoint);

            $.ajax({
                type: "POST",
                url: "{{url('admin/storage-setting')}}",
                data: form,
                contentType: false,
                processData: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    if (data == 1) {
                        $("#storage_form").submit();
                    }
                    if (data == 0) {
                        location.reload(true);
                    }
                },
            });
        });

        $("#test_btn").on('click', function () {
            var file = $('#test_image').prop("files")[0];
            var form = new FormData();
            form.append("image", file);

            $.ajax({
                type: "POST",
                url: "{{url('admin/test-image-digitalOcean')}}",
                data: form,
                contentType: false,
                processData: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    if (data == 0) {
                        $('#view_test_image').html('<div class="alert alert-danger" role="alert">Digital Ocean Invalid Credentials!</div>');
                    }
                    else {
                        $('#view_test_image').html('<div class="alert alert-success" role="alert">Digital Ocean valid Credentials!</div>');
                    }
                },
            });
        });

        function imagePreview(fileInput) {
            if (fileInput.files && fileInput.files[0]) {
                var fileReader = new FileReader();
                fileReader.onload = function (event) {
                    $('#preview').html('<img src="' + event.target.result + '" class="shadow bg-white rounded" width="100px" alt="Select Image" height="100px"/>');
                };
                fileReader.readAsDataURL(fileInput.files[0]);
            }
        }

        $("#app_logo").change(function () {
            imagePreview(this);
        });

        function imagePreview1(fileInput) {
            if (fileInput.files && fileInput.files[0]) {
                var fileReader = new FileReader();
                fileReader.onload = function (event) {
                    $('#preview1').html('<img src="' + event.target.result + '" class="shadow bg-white rounded" width="40px" alt="Select Image" height="40px"/>');
                };
                fileReader.readAsDataURL(fileInput.files[0]);
            }
        }

        $("#admin_favicon").change(function () {
            imagePreview1(this);
        });
    </script>

    <script>
        // Screen-width breakpoint
        var tc_breakpoint = 767;

        jQuery(document).ready(function () {
            "use strict";

            // Switch tabs and update panels classes - Adjust container height
            jQuery(".vertab-container .vertab-menu .list-group a").click(function (e) {
                var index = jQuery(this).index();
                var container = jQuery(this).parents('.vertab-container');
                var accordion = container.find('.vertab-accordion');
                var contents = accordion.find(".vertab-content");

                e.preventDefault();

                jQuery(this).addClass("active");
                jQuery(this).siblings('a.active').removeClass("active");

                contents.removeClass("active");
                contents.eq(index).addClass("active");
                container.data('current', index);

                localStorage.setItem('settings_active_tab', index);

                    //Adjust container height
                    //jQuery(this).parents('.vertab-menu').css('min-height',jQuery(container).children('.vertab-accordion').height());
                });

                // Collapse accordion panels (except the one the user just opened) and add "active" class to the panel heading 
                jQuery('.vertab-accordion').on('show.bs.collapse', '.collapse', function () {
                    var accordion, container, current, index;

                    accordion = jQuery(this).parents('.vertab-accordion');
                    container = accordion.parents('.vertab-container');

                    accordion.find('.collapse.in').each(function () {
                        jQuery(this).collapse('hide');
                    });

                    jQuery(this).siblings('.panel-heading').addClass('active');

                    current = accordion.find('.panel-heading.active');
                    index = accordion.find('.panel-heading').index(current);

                    container.data('current', index);
                    localStorage.setItem('settings_active_tab', index);
                });

                // Remove "active" class from heading when collapsing the current panel 
                jQuery('.vertab-accordion .panel-collapse').on('hide.bs.collapse', function () {
                    jQuery(this).siblings('.panel-heading').removeClass('active');
                });

                // Manage resize / rotation events
                jQuery(window).on("resize orientationchange", function () {
                    resize_vertical_accordions();
                });

                // Scroll accordion to show the current panel
                jQuery(".vertab-accordion .panel-heading").click(function () {
                    var el = this;
                    setTimeout(function () { jQuery("html, body").animate({ scrollTop: jQuery(el).offset().top - 10 }, 1000); }, 500);

                    return true;
                });

                //Initial Panels setup
                resize_vertical_accordions();
            });

            function resize_vertical_accordions() {
                "use strict";
                jQuery('.vertab-container').each(function (i, e) {
                    var index, menu, contents;
                    var container = jQuery(this);

                    // Setup current tab/panel (default to first tab/panel)
                    index = jQuery(this).data('current');
                    if (index === undefined) {
                        index = localStorage.getItem('settings_active_tab');
                        if (index !== null) {
                            index = parseInt(index);
                            jQuery(this).data('current', index);
                        } else {
                            jQuery(this).data('current', 0);
                            index = 0;
                        }
                    }

                    // If using a desktop-size screen, manage as tabbed panels
                    if (jQuery(window).width() > tc_breakpoint) {
                        // Reset panels heights (Bootstrap's accordions sets heights to zero)
                        jQuery(this).find('.panel-collapse.collapse').css('height', 'auto');

                        // Clean tab-navigation styles
                        menu = jQuery(this).find('.vertab-menu .list-group a');
                        menu.removeClass("active");

                        // Clean tab-panels styles
                        contents = jQuery(this).find(".vertab-accordion .vertab-content");
                        contents.removeClass("active");

                        // Update tab navigation and panels styles
                        menu.eq(index).addClass('active');
                        contents.eq(index).addClass("active");

                        // Update tab navigation's height to match current tab
                        jQuery(this).children('.vertab-menu').css('min-height', jQuery(this).children('.vertab-accordion').height());
                    }
                    else // If using a mobile device (phone + tablets), manage as accordion
                    {
                        // Close all panels
                        jQuery(this).find('.vertab-content .panel-collapse.collapse').collapse('hide');

                        // Clean styles from headings
                        jQuery(this).find('.vertab-content .panel-heading').removeClass('active');

                        // Wait until all panels have collapsed and mark the one the user selected as active.
                        setTimeout(function () {
                            jQuery(container).find('.vertab-content .panel-heading').eq(index).addClass("active");
                            jQuery(container).find('.vertab-content .panel-collapse.collapse').eq(index).collapse('show');
                        }, 1000);

                    }
                });
            }

            let qrPollInterval = null;

            function checkWhatsappStatus(btnElement, isPolling = false) {
                var serverUrl = document.querySelector('input[name="name[evolution_server_url]"]').value;
                var apiKey = document.querySelector('input[name="name[evolution_api_key]"]').value;
                var instanceName = document.querySelector('input[name="name[evolution_instance_name]"]').value;

                if (!serverUrl || !apiKey || !instanceName) {
                    if (!isPolling) toastr.error('Please save your Server URL, API Key, and Instance Name first.');
                    return;
                }

                if (btnElement && !isPolling) {
                    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
                    btnElement.disabled = true;
                }
                
                if(!isPolling) document.getElementById('qrStatusText').innerText = 'Connecting to Evolution API...';

                fetch('{{ url("admin/whatsapp-generate-qr") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        server_url: serverUrl,
                        api_key: apiKey,
                        instance_name: instanceName
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.qr) {
                        document.getElementById('qrCodeContainer').style.display = 'flex';
                        document.getElementById('connectedStateContainer').style.display = 'none';
                        document.getElementById('qrPlaceholderIcon').style.display = 'none';
                        document.getElementById('whatsappQrImage').style.display = 'block';
                        document.getElementById('whatsappQrImage').src = data.qr;
                        document.getElementById('qrStatusText').innerText = 'Scan this QR code with your WhatsApp app.';
                        if(!isPolling) toastr.success('QR Code generated successfully!');
                        
                        // Start polling if not already
                        if(!qrPollInterval) {
                            qrPollInterval = setInterval(() => checkWhatsappStatus(null, true), 5000);
                        }
                    } else if (data.status === 'connected') {
                        document.getElementById('qrCodeContainer').style.display = 'none';
                        document.getElementById('connectedStateContainer').style.display = 'flex';
                        if(!isPolling && btnElement && btnElement.id !== 'btnCheckStatus') toastr.success('Instance is already connected!');
                        if(qrPollInterval) {
                            clearInterval(qrPollInterval);
                            qrPollInterval = null;
                            toastr.success('WhatsApp Connected Successfully!');
                        }
                    } else {
                        if(!isPolling) {
                            toastr.error(data.message || 'Failed to generate QR Code');
                            document.getElementById('qrStatusText').innerText = 'Failed. Try again.';
                        }
                    }
                })
                .catch(err => {
                    if(!isPolling) {
                        toastr.error('Network error occurred while fetching QR.');
                        document.getElementById('qrStatusText').innerText = 'Error connecting to server.';
                    }
                })
                .finally(() => {
                    if (btnElement && !isPolling) {
                        if(btnElement.id === 'btnGenerateQr') {
                            btnElement.innerHTML = '<i class="fas fa-sync-alt"></i> Generate QR Code';
                        } else {
                            btnElement.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
                        }
                        btnElement.disabled = false;
                    }
                });
            }

            document.getElementById('btnGenerateQr')?.addEventListener('click', function() {
                checkWhatsappStatus(this, false);
            });
            document.getElementById('btnCheckStatus')?.addEventListener('click', function() {
                checkWhatsappStatus(this, false);
            });

            // On load check if fields are filled and check status silently
            setTimeout(() => {
                var s = document.querySelector('input[name="name[evolution_server_url]"]');
                var a = document.querySelector('input[name="name[evolution_api_key]"]');
                var i = document.querySelector('input[name="name[evolution_instance_name]"]');
                if(s && a && i && s.value && a.value && i.value) {
                    checkWhatsappStatus(null, true);
                }
            }, 1500);
        </script>
@endsection
