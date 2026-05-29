<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Dashboard - {{App\Models\AppSetting::getAppSetting('app_title')}}</title>
  <link rel="icon"
    href="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @endif">
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('assets/plugins/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/fontawesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{asset('assets/css/ionicons.min.css')}}">
  <!-- fullCalendar 2.2.5-->
  <link rel="stylesheet" href="{{asset('assets/plugins/fullcalendar/fullcalendar.min.css')}}">
  <link rel="stylesheet" href="{{asset('assets/plugins/fullcalendar/fullcalendar.print.css')}}" media="print">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{asset('assets/plugins/datatables/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/cdn/buttons.dataTables.min.css')}}">
  <!-- Select2 -->
  <link rel="stylesheet" href="{{asset('assets/plugins/select2/select2.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('assets/css/dist/adminlte.min.css')}}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{asset('assets/plugins/iCheck/flat/blue.css')}}">
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="{{asset('assets/plugins/iCheck/all.css')}}">
  <!-- Morris chart -->
  <link rel="stylesheet" href="{{asset('assets/plugins/morris/morris.css')}}">
  <!-- jvectormap -->
  <link rel="stylesheet" href="{{asset('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.css')}}">
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="{{asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">
  <!-- Google Font: Source Sans Pro -->
  <link href="{{ asset('assets/css/fonts/fonts.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css"
    rel="stylesheet">
  <link href="{{ asset('assets/css/pnotify.custom.min.css')}}" media="all" rel="stylesheet" type="text/css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
  <!--toaster--alert -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
  @yield("extra_css")

  <!-- browser notification -->
  <!-- <script type="text/javascript" src="{{asset('assets/push_notification/app.js')}}"></script> -->
  <style type="text/css">
    tfoot input {
      width: 100%;
      padding: 3px;
      box-sizing: border-box;
      font-size: 0.6em;
      height: 35px !important;
    }

    body {
      font-family: 'Poppins', sans-serif;
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

    .main-sidebar {
      min-height: 100% !important;
    }

    .content-wrapper {
      min-height: 1000px !important;
    }

    .card-title {
      font-size: 1.4rem;
    }

    /* Modern PNotify Customization */
    .ui-pnotify {
      top: 25px !important;
      right: 25px !important;
      opacity: 0.98 !important;
    }

    .ui-pnotify-container {
      padding: 18px 25px !important;
      border-radius: 16px !important;
      background: rgba(255, 255, 255, 0.8) !important;
      backdrop-filter: blur(12px) saturate(180%) !important;
      -webkit-backdrop-filter: blur(12px) saturate(180%) !important;
      border: 1px solid rgba(255, 255, 255, 0.45) !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
      color: #2d3748 !important;
      font-family: 'Poppins', sans-serif !important;
    }

    .ui-pnotify-title {
      font-weight: 700 !important;
      font-size: 1.15rem !important;
      margin-bottom: 6px !important;
      display: block !important;
      color: #1a202c !important;
    }

    .ui-pnotify-text {
      font-size: 0.95rem !important;
      line-height: 1.5 !important;
      display: block !important;
      color: #4a5568 !important;
    }

    /* Success Theme Override */
    .ui-pnotify.ui-pnotify-type-success .ui-pnotify-container {
      background: rgba(232, 247, 237, 0.85) !important;
      border-left: 6px solid #48bb78 !important;
    }

    .ui-pnotify.ui-pnotify-type-success .ui-pnotify-title {
      color: #22543d !important;
    }

    .ui-pnotify.ui-pnotify-type-success .ui-pnotify-icon {
      color: #48bb78 !important;
      font-size: 1.4rem !important;
    }

    /* Error Theme Override */
    .ui-pnotify.ui-pnotify-type-error .ui-pnotify-container {
      background: rgba(255, 245, 245, 0.85) !important;
      border-left: 6px solid #f56565 !important;
    }

    .ui-pnotify.ui-pnotify-type-error .ui-pnotify-title {
      color: #742a2a !important;
    }

    .ui-pnotify.ui-pnotify-type-error .ui-pnotify-icon {
      color: #f56565 !important;
      font-size: 1.4rem !important;
    }

    /* Aggressive AdminLTE/Bootstrap Overlay Bug Fixes */
    #sidebar-overlay,
    .modal-backdrop,
    .modal-backdrop.show,
    .ui-widget-overlay,
    .os-scrollbar-vertical {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -9999 !important;
        visibility: hidden !important;
    }
    
    body.modal-open,
    body.sidebar-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }
    
    .wrapper, body, html {
        pointer-events: auto !important;
    }
    /* ULTRA-MODERN SIDEBAR Redesign */
    /* ULTRA-MODERN SIDEBAR Redesign V2 */
    .main-sidebar, .brand-link {
        background-color: #0f172a !important;
        border-right: 1px solid #1e293b;
    }
    .brand-link {
        border-bottom: 1px solid #1e293b !important;
        color: #f8fafc !important;
    }
    .nav-sidebar .nav-item {
        margin-bottom: 2px;
    }
    .nav-sidebar .nav-header {
        color: #64748b !important;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 15px 14px 5px 14px;
    }
    .nav-sidebar .nav-link {
        color: #94a3b8 !important; /* Subtle gray */
        border-radius: 6px !important;
        margin: 2px 10px;
        padding: 10px 14px !important;
        transition: all 0.2s ease !important;
        border-left: 3px solid transparent;
        display: block;
    }
    .nav-sidebar .nav-link i.nav-icon, 
    .nav-sidebar .nav-link p i.right {
        color: #64748b !important;
        transition: all 0.2s ease !important;
    }
    
    /* Hover State */
    .nav-sidebar .nav-link:hover {
        color: #f8fafc !important;
        background: rgba(255, 255, 255, 0.04) !important;
    }
    .nav-sidebar .nav-link:hover i.nav-icon,
    .nav-sidebar .nav-link:hover p i.right {
        color: #f8fafc !important;
    }

    /* Active State (Main Links) */
    .nav-sidebar .nav-link.active {
        background: rgba(56, 189, 248, 0.1) !important; /* Soft blue glass */
        color: #38bdf8 !important; /* Bright blue text */
        border-left: 3px solid #38bdf8;
        font-weight: 500;
        box-shadow: inset 0 0 10px rgba(56,189,248,0.05);
    }
    .nav-sidebar .nav-link.active i.nav-icon,
    .nav-sidebar .nav-link.active p i.right {
        color: #38bdf8 !important;
    }
    
    /* Sub-menu (Treeview) Styling */
    .nav-sidebar .nav-treeview {
        background: rgba(0, 0, 0, 0.25);
        border-radius: 0;
        margin: 0;
        padding: 5px 0;
    }
    .nav-sidebar .nav-treeview > .nav-item {
        margin-bottom: 0;
    }
    .nav-sidebar .nav-treeview > .nav-item > .nav-link {
        margin: 1px 10px;
        padding: 8px 10px 8px 18px !important;
        border-radius: 4px !important;
        border-left: none; 
        width: calc(100% - 20px) !important;
    }
    .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover {
        background: rgba(255, 255, 255, 0.08) !important;
    }
    /* Sub-menu Active */
    .nav-sidebar .nav-treeview > .nav-item > .nav-link.active {
        background: rgba(255, 255, 255, 0.06) !important;
        color: #e2e8f0 !important;
        box-shadow: none;
        border-left: none;
    }
    .nav-sidebar .nav-treeview > .nav-item > .nav-link.active i.nav-icon {
        color: #e2e8f0 !important;
    }
    
    /* Better Scrollbar for Sidebar */
    .main-sidebar ::-webkit-scrollbar {
        width: 6px;
    }
    .main-sidebar ::-webkit-scrollbar-track {
        background: transparent;
    }
    .main-sidebar ::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 4px;
    }
    .main-sidebar ::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-navbar-fixed layout-fixed">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand bg-white navbar-light border-bottom">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fa fa-bars"></i></a>
        </li>
      </ul>

      <a class="ml-3" href="{{url('admin/clear-cache')}}"><img src="{{asset('assets/images/icon/clean.png')}}"
          width="30px" height="30px" /></a>
      <!-- <a class="ml-4 text-success" href="{{url('admin/sql-database')}}"><i class="fa-solid fa-xl fa-database"></i></a> -->
      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <li class="dropdown mt-1 mr-3">
          <a data-toggle="dropdown" href="#">
            <img class="rounded-circle"
              src="@if(optional(Auth::user())->image) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . optional(Auth::user())->image)}} @else {{asset('uploads/' . optional(Auth::user())->image)}} @endif @else {{ asset('assets/images/user.png') }} @endif"
              width="40px" height="40px">
            <span class="badge badge-danger navbar-badge"></span>
          </a>
          <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item" href="{{url('admin/user-profile')}}">Profile</a>
            @if(optional(Auth::user())->user_type == 'A')<a class="dropdown-item"
            href="{{url('admin/')}}">Administration</a>@endif
            <a class="dropdown-item" href="{{ route('logout') }}"
              onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
          </div>
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
          </form>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar elevation-4">
      <!-- Brand Logo -->
      <a href="{{ url('admin/')}}" class="brand-link text-center" style="height: 60px;">
        <h3>{{App\Models\AppSetting::getAppSetting('app_title')}}</h3>
        <!-- <img src="{{asset('uploads/'.App\Models\AppSetting::getAppSetting('app_logo'))}}" class="mt-2" width="50px" height="50px"> -->
      </a>
      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <!-- <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
        <img src="{{asset('uploads/'.optional(Auth::user())->image)}}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="{{ url('admin/user/'.optional(Auth::user())->id.'/edit')}}" class="d-block">{{optional(Auth::user())->name}}</a>
        </div>
      </div> -->

        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
            <!-- user-type S or O -->


            <li class="nav-item">
            <a href="{{url('admin/dashboard')}}" class="nav-link {{ (Request::is('admin/dashboard*')) ? 'active' : '' }}" style="color: white;">
              <i class="nav-icon fas fa-tachometer-alt" style="color: white;"></i>
              <p style="color: white; font-weight: 500;">
                  Dashboard
              </p>
            </a>
          </li>

          <!-- The God View -->
          <li class="nav-item">
            <a href="{{ route('admin.god_view') }}" class="nav-link {{ (Request::is('admin/god-view*')) ? 'active' : '' }}" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(79, 70, 229, 0.4) 100%); border-radius: 8px;">
              <i class="nav-icon fa-solid fa-eye" style="color: #818cf8; text-shadow: 0 0 8px rgba(129, 140, 248, 0.5);"></i>
              <p style="color: white; font-weight: 700; letter-spacing: 0.5px;">
                  God View <span class="right badge badge-danger" style="background: #ef4444; font-size: 0.6rem; padding: 3px 5px;">LIVE</span>
              </p>
            </a>
          </li>

            <!-- @if(Request::is('admin/members*'))
              @php($class="menu-open")
              @php($active="active")
          @else
              @php($class="")
              @php($active="")
          @endif

          
          <li class="nav-item has-treeview {{$class}}">
            <a href="{{url('/admin/members')}}" class="nav-link {{$active}}" style="color: white;"> 
              <i class="nav-icon fa fa-user"></i>
              <p>
                Members
              </p>
            </a>
          </li> -->

            @can('Language')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('language.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-language"></i>
                  <p>
                    Language
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/category*') || Request::is('admin/category-post*') || Request::is('admin/category-get*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['Category', 'CategoryPost'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-sitemap"></i>
                  <p>
                    Category Post
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('Category')
                    <li class="nav-item">
                      <a href="{{route('category.index')}}"
                        class="nav-link @if(Request::is('admin/category*') && !Request::is('admin/category-post*') && !Request::is('admin/category-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('CategoryPost')
                    <li class="nav-item">
                      <a href="{{route('category-post.index')}}"
                        class="nav-link @if(Request::is('admin/category-post*') || Request::is('admin/category-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Category Post</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/festivals*') || Request::is('admin/festivals_post*') || Request::is('admin/festival*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['Festival', 'FestivalPost'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-gifts"></i>
                  <p>
                    Festivals Post
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('Festival')
                    <li class="nav-item">
                      <a href="{{route('festivals.index')}}"
                        class="nav-link @if(Request::is('admin/festivals*') && !Request::is('admin/festivals-post*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Festivals</p>
                      </a>
                    </li>
                  @endcan
                  @can('FestivalPost')
                    <li class="nav-item">
                      <a href="{{route('festivals-post.index')}}"
                        class="nav-link @if(Request::is('admin/festivals-post*') || Request::is('admin/festival*') && !Request::is('admin/festivals*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Festivals Post</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/custom-post*') || Request::is('admin/custom-post-frame*') || Request::is('admin/custom-post-get*') || Request::is('admin/magic-cloner'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['CustomCategory', 'CustomFrame'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-image"></i>
                  <p>
                    Magic Cloner <span class="badge badge-warning right mr-3" style="font-size: 0.6em;">Pkg 3 Gold</span>
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('CustomCategory')
                    <li class="nav-item">
                      <a href="{{route('custom-post.index')}}"
                        class="nav-link @if(Request::is('admin/custom-post*') && !Request::is('admin/custom-post-frame*') && !Request::is('admin/custom-post-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Magic Cloner Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('CustomFrame')
                    <li class="nav-item">
                      <a href="{{route('custom-post-frame.index')}}"
                        class="nav-link @if(Request::is('admin/custom-post-frame*') || Request::is('admin/custom-post-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Magic Cloner Frame</p>
                      </a>
                    </li>
                  @endcan
                  @can('CustomFrame')
                    <li class="nav-item">
                      <a href="{{route('admin.magic_cloner.index')}}"
                        class="nav-link @if(Request::is('admin/magic-cloner')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Logic Setup</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/business-category*') || Request::is('admin/business-frame*') || Request::is('admin/business-category-get*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('BusinessFrame')
              <li class="nav-item">
                <a href="{{route('business-frame.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-business-time"></i>
                  <p>
                    Custom Post <span class="badge badge-secondary right mr-3" style="font-size: 0.6em;">Pkg 2 Silver</span>
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/general-post*'))
            @php($gpClass = "menu-open")
            @php($gpActive = "active")
            @else
            @php($gpClass = "")
            @php($gpActive = "")
            @endif

            @can('BusinessFrame')
              <li class="nav-item">
                <a href="{{route('general-post.index')}}"
                  class="nav-link @if(Request::is('admin/general-post*')) active @endif" style="color: white;">
                  <i class="nav-icon fa-solid fa-newspaper"></i>
                  <p>General Posts</p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/sticker-category*') || Request::is('admin/sticker*') || Request::is('admin/sticker-category-get*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['StickerCategory', 'Sticker'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-face-smile"></i>
                  <p>
                    Stickers
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('StickerCategory')
                    <li class="nav-item">
                      <a href="{{route('sticker-category.index')}}"
                        class="nav-link @if(Request::is('admin/sticker-category*') && !Request::is('admin/sticker-category-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Sticker Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('Sticker')
                    <li class="nav-item">
                      <a href="{{route('sticker.index')}}"
                        class="nav-link @if(Request::is('admin/sticker*') && !Request::is('admin/sticker-category*') || Request::is('admin/sticker-category-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Stickers</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/product-category*') || Request::is('admin/product*') || Request::is('admin/inquiry*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['ProductCategory', 'Product', 'Inquiry'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-store"></i>
                  <p>
                    Product
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('ProductCategory')
                    <li class="nav-item">
                      <a href="{{route('product-category.index')}}"
                        class="nav-link @if(Request::is('admin/product-category*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Product Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('Product')
                    <li class="nav-item">
                      <a href="{{route('product.index')}}"
                        class="nav-link @if(Request::is('admin/product*') && !Request::is('admin/product-category*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Product</p>
                      </a>
                    </li>
                  @endcan
                  @can('Inquiry')
                    <li class="nav-item">
                      <a href="{{route('inquiry.index')}}" class="nav-link @if(Request::is('admin/inquiry*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Inquiry</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/referral-system*') || Request::is('admin/withdraw-request*') || Request::is('admin/subscription-plan*') || Request::is('admin/coupon-code*') || Request::is('admin/transaction*') || Request::is('admin/offer*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['ReferralSystem', 'WithdrawRequest', 'SubscriptionPlan', 'CouponCode', 'FinancialStatistics', 'Offer'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-landmark"></i>
                  <p>
                    Finance
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('ReferralSystem')
                    <li class="nav-item">
                      <a href="{{url('admin/referral-system')}}"
                        class="nav-link @if(Request::is('admin/referral-system*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Referral System</p>
                      </a>
                    </li>
                  @endcan
                  @can('WithdrawRequest')
                    <li class="nav-item">
                      <a href="{{url('admin/withdraw-request')}}"
                        class="nav-link @if(Request::is('admin/withdraw-request*') && !Request::is('admin/product-category*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Withdraw Request</p>
                      </a>
                    </li>
                  @endcan
                  @can('SubscriptionPlan')
                    <li class="nav-item">
                      <a href="{{route('subscription-plan.index')}}"
                        class="nav-link @if(Request::is('admin/subscription-plan*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Subscriptions Plan</p>
                      </a>
                    </li>
                  @endcan
                  @can('CouponCode')
                    <li class="nav-item">
                      <a href="{{route('coupon-code.index')}}"
                        class="nav-link @if(Request::is('admin/coupon-code*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Coupon Code</p>
                      </a>
                    </li>
                  @endcan
                  @can('FinancialStatistics')
                    <li class="nav-item">
                      <a href="{{url('admin/transaction')}}"
                        class="nav-link @if(Request::is('admin/transaction*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Transactions</p>
                      </a>
                    </li>
                  @endcan
                  @can('Offer')
                    <li class="nav-item">
                      <a href="{{url('admin/offer')}}" class="nav-link @if(Request::is('admin/offer*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Offer</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

                  @php($marketingClass = (Request::is('admin/blogs*') || Request::is('admin/leads*')) ? "menu-open" : "")
                  @php($marketingActive = (Request::is('admin/blogs*') || Request::is('admin/leads*')) ? "active" : "")
                  
                  <li class="nav-item has-treeview {{$marketingClass}}">
                    <a href="#" class="nav-link {{$marketingActive}}" style="color: white;">
                      <i class="nav-icon fa fa-bullhorn text-warning"></i>
                      <p>
                        Marketing
                        <i class="right fa fa-angle-right"></i>
                      </p>
                    </a>
                    <ul class="nav nav-treeview">
                      <li class="nav-item">
                        <a href="{{ url('admin/blogs') }}" class="nav-link @if(Request::is('admin/blogs*')) active @endif" style="color: white;">
                          <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Blogs</p>
                        </a>
                      </li>
                      <li class="nav-item">
                        <a href="{{ route('admin.leads') }}" class="nav-link @if(Request::is('admin/leads*')) active @endif" style="color: white;">
                          <p><i class="fa fa-angle-right ml-3 mr-1"></i> Lead Management</p>
                        </a>
                      </li>
                    </ul>
                  </li>

            @if(Request::is('admin/poster-category*') || Request::is('admin/poster-maker*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['PosterCategory', 'PosterMaker'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fas fa-images"></i>
                  <p>
                    Frame
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('PosterCategory')
                    <li class="nav-item">
                      <a href="{{route('poster-category.index')}}"
                        class="nav-link @if(Request::is('admin/poster-category*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Frame Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('PosterMaker')
                    <li class="nav-item">
                      <a href="{{route('poster-maker.index')}}"
                        class="nav-link @if(Request::is('admin/poster-maker*')) active @endif" style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Frame</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @if(Request::is('admin/video*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Video')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('video.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fas fa-video"></i>
                  <p>
                    Video
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/news*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('News')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('news.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-gift"></i>
                  <p>
                    News
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/story*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Stories')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('story.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-map"></i>
                  <p>
                    Stories
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/user*') && !Request::is('admin/user-profile'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Users')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('user.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-users"></i>
                  <p>
                    User
                  </p>
                </a>
              </li>
            @endcan


            @can('Users')
              <li class="nav-item has-treeview">
                <a href="{{route('admin.user_performance')}}" class="nav-link @if(Request::is('admin/user-performance*')) active @endif" style="color: white;">
                  <i class="nav-icon fa fa-chart-pie"></i>
                  <p>
                    User Performance
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/reported-errors'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Users')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('admin.reported_errors')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-bug"></i>
                  <p>
                    Reported Errors
                  </p>
                </a>
              </li>
            @endcan


            @if(Request::is('admin/business*') && !Request::is('admin/business-card*') && !Request::is('admin/business-frame*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Businesses')
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-briefcase"></i>
                  <p>
                    Businesses
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('business.index')}}"
                      class="nav-link @if(Request::is('admin/business') || Request::is('admin/business/*')) active @endif"
                      style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> All Businesses</p>
                    </a>
                  </li>
                  @can('BusinessCategory')
                    <li class="nav-item">
                      <a href="{{route('business-category.index')}}"
                        class="nav-link @if(Request::is('admin/business-category*') && !Request::is('admin/business-frame*') && !Request::is('admin/business-category-get*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Business Category</p>
                      </a>
                    </li>
                  @endcan
                  @can('BusinessSubCategory')
                    <li class="nav-item">
                      <a href="{{route('business-sub-category.index')}}"
                        class="nav-link @if(Request::is('admin/business-sub-category*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Sub Category</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcan

            @if(Request::is('admin/business-card*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('BusinessCard')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('business-card.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-address-card"></i>
                  <p>
                    Business Card
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/entry*') || Request::is('admin/subject*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @canany(['Entry', 'Subject'])
              <li class="nav-item has-treeview {{$class}}">
                <a href="" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-envelope"></i>
                  <p>
                    Contact List
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>

                <ul class="nav nav-treeview">
                  @can('Entry')
                    <li class="nav-item">
                      <a href="{{route('entry.index')}}" class="nav-link @if(Request::is('admin/entry*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Entry</p>
                      </a>
                    </li>
                  @endcan
                  @can('Subject')
                    <li class="nav-item">
                      <a href="{{route('subject.index')}}" class="nav-link @if(Request::is('admin/subject*')) active @endif"
                        style="color: white;">
                        <p><i class="fa fa-angle-right ml-3 mr-1"></i> Subject</p>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>
            @endcanany

            @php($notiClass = (Request::is('admin/notification*') || Request::is('admin/marketing-settings*') || Request::is('admin/ai-campaigns*')) ? "menu-open" : "")
            @php($notiActive = (Request::is('admin/notification*') || Request::is('admin/marketing-settings*') || Request::is('admin/ai-campaigns*')) ? "active" : "")

            @can('Notification')
              <li class="nav-item has-treeview {{$notiClass}}">
                <a href="#" class="nav-link {{$notiActive}}" style="color: white;">
                  <i class="nav-icon fa fa-paper-plane"></i>
                  <p>
                    Notification
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('admin.marketing_settings') }}" class="nav-link @if(Request::is('admin/marketing-settings*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Auto Notification</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.ai_campaigns') }}" class="nav-link @if(Request::is('admin/ai-campaigns*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Manual Notification</p>
                    </a>
                  </li>
                </ul>
              </li>
            @endcan

            @if(Request::is('admin/whatsapp-message*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('WhatsAppMessage')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{url('admin/whatsapp-message')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fab fa-whatsapp"></i>
                  <p>
                    WhatsApp Message
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/roles*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('UserRoleManagement')
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{ url('admin/roles')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-user-gear"></i>
                  <p>
                    Role Manager
                  </p>
                </a>
              </li>
            @endcan

            @if(Request::is('admin/settings*') || Request::is('admin/app-language*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @can('Settings')
              <li class="nav-item has-treeview {{$class}}">
                <a href="#" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-cog"></i>
                  <p>
                    Settings
                    <i class="right fa fa-angle-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ url('admin/settings')}}" class="nav-link @if(Request::is('admin/settings*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> General Settings</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('app-language.index') }}" class="nav-link @if(Request::is('admin/app-language*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> App Language</p>
                    </a>
                  </li>
                </ul>
              </li>
            @endcan

            @php($analyticsClass = (Request::is('admin/payment-analytics*') || Request::is('admin/churn*') || Request::is('admin/journey*') || Request::is('admin/ai-analytics*')) ? "menu-open" : "")
            @php($analyticsActive = (Request::is('admin/payment-analytics*') || Request::is('admin/churn*') || Request::is('admin/journey*') || Request::is('admin/ai-analytics*')) ? "active" : "")
            <li class="nav-item has-treeview {{$analyticsClass}}">
                <a href="#" class="nav-link {{$analyticsActive}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-chart-line text-info"></i>
                  <p>Analytics & Insights<i class="right fa fa-angle-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('admin.payment_analytics') }}" class="nav-link @if(Request::is('admin/payment-analytics*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Payment Analytics</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.churn_analytics') }}" class="nav-link @if(Request::is('admin/churn*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Churn Analytics</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.journey') }}" class="nav-link @if(Request::is('admin/journey*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Customer Journey</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.ai_analytics') }}" class="nav-link @if(Request::is('admin/ai-analytics*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Token Analytics</p>
                    </a>
                  </li>
                </ul>
            </li>

            @php($opsClass = (Request::is('*ai-monitor*') || Request::is('*tickets*') || Request::is('*knowledge-base*')) ? "menu-open" : "")
            @php($opsActive = (Request::is('*ai-monitor*') || Request::is('*tickets*') || Request::is('*knowledge-base*')) ? "active" : "")
            <li class="nav-item has-treeview {{$opsClass}}">
                <a href="#" class="nav-link {{$opsActive}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-headset text-danger"></i>
                  <p>Support & Ops<i class="right fa fa-angle-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('admin.ai_monitor') }}" class="nav-link @if(Request::is('*ai-monitor*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Monitor</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.tickets') }}" class="nav-link @if(Request::is('*tickets*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Support Tickets <span class="right badge badge-danger">AI</span></p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('admin.knowledge_base') }}" class="nav-link @if(Request::is('*knowledge-base*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Knowledge Base</p>
                    </a>
                  </li>
                </ul>
              </li>

            @if(Request::is('admin/backup*'))
            @php($class = "menu-open")
            @php($active = "active")
            @else
            @php($class = "")
            @php($active = "")
            @endif

            @php($partnerClass = (Request::is('*partner-leaderboard*') || Request::is('*partner/toolkit*') || Request::is('*partner/profile*')) ? "menu-open" : "")
            @php($partnerActive = (Request::is('*partner-leaderboard*') || Request::is('*partner/toolkit*') || Request::is('*partner/profile*')) ? "active" : "")
            <li class="nav-item has-treeview {{$partnerClass}}">
                <a href="#" class="nav-link {{$partnerActive}}" style="color: white;">
                  <i class="nav-icon fa-solid fa-handshake text-warning"></i>
                  <p>Partner Portal<i class="right fa fa-angle-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{ route('admin.partner_leaderboard') }}" class="nav-link @if(Request::is('*partner-leaderboard*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Leaderboard</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('partner.toolkit') }}" class="nav-link @if(Request::is('*partner/toolkit*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> AI Marketing Toolkit</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('partner.profile') }}" class="nav-link @if(Request::is('*partner/profile*')) active @endif" style="color: white;">
                      <p><i class="fa fa-angle-right ml-3 mr-1"></i> Compliance Profile</p>
                    </a>
                  </li>
                </ul>
            </li>

            @if(optional(Auth::user())->user_type == "Super Admin" || optional(Auth::user())->user_type == "Demo")
              <li class="nav-item has-treeview {{$class}}">
                <a href="{{route('backup.index')}}" class="nav-link {{$active}}" style="color: white;">
                  <i class="nav-icon fa fa-database"></i>
                  <p>
                    Backup
                  </p>
                </a>
              </li>
            @endif

              <!-- Missing Phase 7: Gamification -->
              <li class="nav-item">
                <a href="{{ route('admin.challenges') }}" class="nav-link @if(Request::is('admin/challenges*')) active @endif" style="color: white;">
                  <i class="nav-icon fa-solid fa-trophy text-warning"></i>
                  <p>Design Challenges</p>
                </a>
              </li>

              <li class="nav-header">SYSTEM DOCUMENTATION</li>
              <li class="nav-item mb-4">
                <a href="{{ route('admin.documentation') }}" class="nav-link @if(Request::is('admin/documentation*')) active @endif" style="background: rgba(255, 255, 255, 0.1); border-left: 3px solid #10b981; color: white;">
                  <i class="nav-icon fa-solid fa-book-open"></i>
                  <p>Master SaaS Docs</p>
                </a>
              </li>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h4 class="m-0 text-dark">@yield('heading') </h4>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <!-- <ol class="breadcrumb float-sm-right">
              @if(!(Request::is('admin')))
              <li class="breadcrumb-item"><a href="{{ url('admin/')}}"></a></li>
              @endif
              @yield('breadcrumb')
            </ol> -->
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>

      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          @yield('content')
        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer"
      style="background: url({{ asset('assets/images/cultrl-bottom-bg2.png') }}) no-repeat;background-size: cover; background-position: bottom; min-height: 333px; width: auto;">
      <div class="row d-flex justify-content-between text-light" style="margin-top: 270px;color:black;">
        <div class="float-left d-sm-inline-block text-dark">
          <strong>Power by {{App\Models\AppSetting::getAppSetting('app_title')}}</strong>
        </div>
        <div class="float-right d-none d-sm-inline-block text-dark">
          <b>© {{date("Y")}} All Rights Reserved.</b>
        </div>
      </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->
  @yield('script2')
  <!-- jQuery -->
  <script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="{{asset('assets/js/jquery-ui.min.js')}}"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
    $.widget.bridge('uibutton', $.ui.button)
  </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/fontawesome.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="{{asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <!-- Select2 -->
  <script src="{{asset('assets/plugins/select2/select2.full.min.js')}}"></script>
  <!-- iCheck 1.0.1 -->
  <script src="{{asset('assets/plugins/iCheck/icheck.min.js')}}"></script>
  <!-- FastClick -->
  <script src="{{asset('assets/plugins/fastclick/fastclick.js')}}"></script>
  <!-- DataTables -->
  <script src="{{asset('assets/js/cdn/jquery.dataTables.min.js')}}"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/js/bootstrap-colorpicker.min.js"></script>
  <script src="{{asset('assets/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/cdn/dataTables.buttons.min.js')}}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/cdn/buttons.print.min.js')}}"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <!-- AdminLTE App -->
  <script src="{{asset('assets/js/adminlte.js')}}"></script>
  <script src="{{asset('assets/js/fontawesome.js')}}"></script>
  <script type="text/javascript">
    $(document).ready(function () {
      // EMERGENCY OVERLAY CLEANUP
      setTimeout(function() {
          $('#sidebar-overlay').remove();
          $('.modal-backdrop').remove();
          $('body').removeClass('modal-open sidebar-open').css({'overflow': 'auto', 'pointer-events': 'auto'});
      }, 300);

      $('#data_table tfoot th').each(function () {
        // console.log($('#data_table tfoot th').length);
        if ($(this).index() != $('#data_table tfoot th').length - 1) {
          var title = $(this).text();
          $(this).html('<input type="text" placeholder="' + title + '" />');
        }
      });

      //alert($('.content').height());
      //alert($('.content-wrapper').height());

      var table = $('#data_table').DataTable({
        "order": [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [0] }],
        // individual column search
        "initComplete": function () {
          table.columns().every(function () {
            var that = this;
            $('input', this.footer()).on('keyup change', function () {
              // console.log($(this).parent().index());
              that.search(this.value).draw();
            });
          });
        }
      });

      $('[data-toggle="tooltip"]').tooltip();

      $('.ToastrButton').click(function () {
        toastr.error('This Action Not Available Demo User');
      });
    });
  </script>
  <script type="text/javascript" src="{{asset('assets/js/pnotify.custom.min.js')}}"></script>
  <!-- AdminLTE for demo purposes -->
  <!-- <script src="{{ asset('assets/js/demo.js') }}"></script> -->
  <script>
    // Auto-scroll sidebar to the active menu item so it's always visible
    $(document).ready(function() {
      setTimeout(function() {
        var activeLink = document.querySelector('.nav-sidebar .nav-link.active');
        if (activeLink) {
          activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 300); // Slight delay to let AdminLTE treeview initialize
    });
  </script>
  @yield('script')
</body>

</html>