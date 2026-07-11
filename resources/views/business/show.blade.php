@extends('layouts.app')

@section('heading')
  <div class="d-flex align-items-center">
      <a href="{{ url('admin/business') }}" class="btn btn-sm btn-light rounded-circle shadow-sm mr-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; border: 1px solid #e2e8f0;">
          <i class="fa-solid fa-arrow-left text-primary"></i>
      </a>
      <div>
          <h4 class="font-weight-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Business Details</h4>
          <small class="text-muted" style="font-size: 0.85rem; font-weight: 500;">Overview and management</small>
      </div>
  </div>
@endsection

@section('extra_css')
  <style type="text/css">
    .business-card-premium {
      border: none;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      background: #fff;
      position: relative;
    }
    
    .business-header-gradient {
      height: 140px;
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
      position: relative;
    }
    
    .business-profile-container {
      position: absolute;
      left: 40px;
      bottom: -45px;
      display: flex;
      align-items: flex-end;
    }
    
    .business-profile-img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 4px solid #fff;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      background-color: white;
      object-fit: cover;
    }
    
    .business-title-container {
      margin-left: 20px;
      margin-bottom: 55px; /* Push above the border */
      color: #fff;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .business-name {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
    }
    
    .business-badge {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(4px);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.3);
      display: inline-block;
      margin-top: 5px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      padding: 20px;
    }
    
    .info-card {
      background: #f8fafc;
      border-radius: 12px;
      padding: 16px;
      display: flex;
      align-items: flex-start;
      border: 1px solid #edf2f7;
      transition: all 0.3s ease;
    }
    
    .info-card:hover {
      background: #fff;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border-color: #e2e8f0;
      transform: translateY(-2px);
    }
    
    .info-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      flex-shrink: 0;
    }
    
    .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
    .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .icon-orange { background: rgba(249, 115, 22, 0.1); color: #f97316; }
    .icon-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .icon-indigo { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
    
    .info-content {
      overflow: hidden;
    }
    
    .info-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 4px;
    }
    
    .info-value {
      font-size: 0.95rem;
      color: #1e293b;
      font-weight: 500;
      word-break: break-word;
    }

    .extra-items-list {
      margin-top: 8px;
      padding-top: 8px;
      border-top: 1px dashed #cbd5e1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .extra-item {
      font-size: 0.85rem;
      color: #475569;
      background: #f1f5f9;
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
    }
    
    .extra-item i {
      font-size: 0.75rem;
      margin-right: 6px;
      opacity: 0.7;
    }
  </style>
@endsection

@section('content')
  <div class="row">
    <div class="col-xl-8 col-lg-10 mx-auto mt-4">
      
      <div class="business-card-premium mb-5">
        <!-- Header Gradient & Profile -->
        <div class="business-header-gradient">
          <div class="business-profile-container">
            <img 
              src="@if($data->logo) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $data->logo)}} @else {{asset('uploads/' . $data->logo)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif" 
              class="business-profile-img" 
              alt="Business Logo">
            
            <div class="business-title-container">
              <h2 class="business-name">{{$data->name}}</h2>
              @if($data->business_category_id)
                <div class="business-badge">
                  <i class="fa-solid fa-layer-group mr-1"></i> {{$data->business_category->name}}
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Body Content -->
        <div style="padding: 60px 20px 20px 20px;">
          
          <div class="info-grid">
            <!-- Email -->
            <div class="info-card">
              <div class="info-icon icon-blue">
                <i class="fa-solid fa-envelope"></i>
              </div>
              <div class="info-content">
                <div class="info-label">Primary Email</div>
                <div class="info-value">{{$data->email ?: 'N/A'}}</div>
                @if($data->extra_emails && count($data->extra_emails) > 0)
                  <div class="extra-items-list">
                    @foreach($data->extra_emails as $extraEmail)
                      <div class="extra-item"><i class="fa-solid fa-at"></i> {{ $extraEmail }}</div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>

            <!-- Mobile -->
            <div class="info-card">
              <div class="info-icon icon-purple">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div class="info-content">
                <div class="info-label">Primary Mobile</div>
                <div class="info-value">{{$data->mobile_no ?: 'N/A'}}</div>
                @if($data->extra_mobile_numbers && count($data->extra_mobile_numbers) > 0)
                  <div class="extra-items-list">
                    @foreach($data->extra_mobile_numbers as $extraPhone)
                      <div class="extra-item"><i class="fa-solid fa-mobile-screen"></i> {{ $extraPhone }}</div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>

            <!-- Website -->
            <div class="info-card">
              <div class="info-icon icon-green">
                <i class="fa-solid fa-globe"></i>
              </div>
              <div class="info-content">
                <div class="info-label">Primary Website</div>
                <div class="info-value">{{$data->website ?: 'N/A'}}</div>
                @if($data->extra_websites && count($data->extra_websites) > 0)
                  <div class="extra-items-list">
                    @foreach($data->extra_websites as $extraWeb)
                      <div class="extra-item"><i class="fa-solid fa-link"></i> {{ $extraWeb }}</div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>

            <!-- Address -->
            <div class="info-card" style="grid-column: 1 / -1;">
              <div class="info-icon icon-orange">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div class="info-content">
                <div class="info-label">Primary Address</div>
                <div class="info-value">{{$data->address ?: 'N/A'}}</div>
                @if($data->extra_addresses && count($data->extra_addresses) > 0)
                  <div class="extra-items-list" style="flex-direction: row; flex-wrap: wrap;">
                    @foreach($data->extra_addresses as $extraAddr)
                      <div class="extra-item"><i class="fa-solid fa-map-pin"></i> {{ $extraAddr }}</div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>

            <!-- Created At -->
            <div class="info-card">
              <div class="info-icon icon-indigo">
                <i class="fa-solid fa-calendar-plus"></i>
              </div>
              <div class="info-content">
                <div class="info-label">Created On</div>
                <div class="info-value">{{$data->created_at->format('d M, Y')}}</div>
              </div>
            </div>

          </div>

        </div>
      </div>
      
    </div>
  </div>
@endsection

@section("script")
  <script type="text/javascript">
    $('.datepicker').datepicker({
      format: 'dd/mm/yyyy',
    });
  </script>
@endsection