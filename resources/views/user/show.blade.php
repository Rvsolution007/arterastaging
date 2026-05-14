@extends('layouts.app')

@section('extra_css')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .analytics-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.5rem;
        letter-spacing: -0.025em;
    }

    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .premium-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .premium-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .user-profile-header {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        padding: 3rem 2rem;
        border-radius: 16px;
        color: white;
        margin-bottom: 2rem;
        position: relative;
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .user-profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 24px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    .user-profile-info h2 {
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .user-profile-info .badge-premium {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(4px);
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .nav-pills-premium {
        background: white;
        padding: 0.5rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .nav-pills-premium .nav-link {
        border-radius: 8px;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        color: #64748b;
        transition: all 0.2s;
    }

    .nav-pills-premium .nav-link.active {
        background: #6366f1;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .form-label-premium {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .form-control-premium {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 0.7rem 1rem;
        background: #f8fafc;
        transition: all 0.2s;
    }

    .form-control-premium:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background: white;
        outline: none;
    }

    .stat-box {
        background: #f8fafc;
        padding: 1.25rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        display: block;
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
    }

    .business-mini-card {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .business-mini-card:hover {
        border-color: #6366f1;
        background: #f8fafc;
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .switch input { display:none; }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px; width: 14px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider { background-color: #6366f1; }
    input:checked + .slider:before { transform: translateX(20px); }

    .premium-card-body {
        padding: 1.5rem;
    }

    .btn-premium {
        background: #6366f1;
        color: white;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
    }

    .btn-premium:hover {
        background: #4f46e5;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
    }

    .custom-table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
        font-size: 0.875rem;
    }

    .badge-soft {
        background: #e0e7ff;
        color: #4338ca;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="user-profile-header shadow-lg">
        <img class="user-profile-avatar" src="@if($data->image) @if(substr($data->image, 0, 4)=="http") {{$data->image}} @else @if(App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean"){{\Storage::disk('spaces')->url('uploads/'.$data->image)}} @else {{asset('uploads/'.$data->image)}} @endif @endif @else {{asset('assets/images/no-user.jpg')}} @endif">
        <div class="user-profile-info">
            <h2 class="mb-1">{{ $data->name }}</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="badge-premium"><i class="fa-regular fa-envelope mr-1"></i> {{ $data->email }}</span>
                <span class="badge-premium"><i class="fa-solid fa-phone mr-1"></i> {{ $data->mobile_no }}</span>
                @if($data->is_subscribe)
                <span class="badge badge-warning text-dark font-weight-bold ml-2 px-3 py-2" style="border-radius: 20px;">PREMIUM MEMBER</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="nav flex-column nav-pills nav-pills-premium shadow-sm mb-4" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <a class="nav-link active mb-1" id="v-pills-profile-tab" data-toggle="pill" href="#v-pills-profile" role="tab"><i class="fa-solid fa-user-pen mr-2"></i> Edit Profile</a>
                <a class="nav-link mb-1" id="v-pills-business-tab" data-toggle="pill" href="#v-pills-business" role="tab"><i class="fa-solid fa-briefcase mr-2"></i> Businesses</a>
                <a class="nav-link mb-1" id="v-pills-subscription-tab" data-toggle="pill" href="#v-pills-subscription" role="tab"><i class="fa-solid fa-gem mr-2"></i> Subscription</a>
                <a class="nav-link mb-1" id="v-pills-transactions-tab" data-toggle="pill" href="#v-pills-transactions" role="tab"><i class="fa-solid fa-receipt mr-2"></i> Transactions</a>
                <a class="nav-link" id="v-pills-earning-tab" data-toggle="pill" href="#v-pills-earning" role="tab"><i class="fa-solid fa-sack-dollar mr-2"></i> Referral & Earnings</a>
            </div>

            <div class="premium-card p-4">
                <h6 class="font-weight-bold mb-3 text-muted small uppercase">Quick Stats</h6>
                <div class="row g-3">
                    <div class="col-6 mb-3">
                        <div class="stat-box">
                            <span class="stat-value">{{ count($business) }}</span>
                            <span class="stat-label">Businesses</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-box">
                            <span class="stat-value">₹{{ number_format($data->total_balance, 0) }}</span>
                            <span class="stat-label">Earnings</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted d-block mb-1">Account Created:</small>
                    <span class="font-weight-bold text-dark">{{ $data->created_at->format('d M, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content Panel -->
        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> Please check the form below for errors.
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="premium-card">
                        <div class="premium-card-header">
                            <h5 class="premium-card-title">Personal Information</h5>
                        </div>
                        <div class="premium-card-body">
                            {!! Form::open(['route' => ['user.update',$data->id],'method'=>'PATCH','files'=>true]) !!}
                            {!! Form::hidden('id',$data->id) !!}
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Full Name</label>
                                    {!! Form::text('name',$data->name,['class' => 'form-control-premium w-100','required']) !!}
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Email Address</label>
                                    {!! Form::email('email',$data->email,['class'=>'form-control-premium w-100','required']) !!}
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Mobile Number</label>
                                    {!! Form::number('mobile_no',$data->mobile_no,['class' => 'form-control-premium w-100','required']) !!}
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Change Password (Optional)</label>
                                    {!! Form::password('password', ['class' => 'form-control-premium w-100', 'placeholder' => 'Enter new password']) !!}
                                </div>
                                <div class="col-md-12 mb-4">
                                    <label class="form-label-premium">Update Profile Photo</label>
                                    <input type="file" name="image" class="form-control-premium w-100" style="padding: 0.5rem 1rem;">
                                </div>
                                <div class="col-md-12 mt-3 mb-2">
                                    <h6 class="font-weight-bold border-bottom pb-2 text-success">Partner / Affiliate Settings</h6>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium d-block">Is Partner?</label>
                                    <label class="switch my-auto">
                                        <input type="checkbox" name="is_partner" value="1" @if($data->is_partner==1) checked @endif>
                                        <span class="slider"></span>
                                    </label>
                                    <small class="text-muted d-block mt-1">Allow this user to earn commissions.</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Custom Commission (%)</label>
                                    {!! Form::number('partner_commission_percent',$data->partner_commission_percent,['class' => 'form-control-premium w-100', 'placeholder' => 'Leave blank for global default', 'step' => '0.01']) !!}
                                    <small class="text-muted d-block mt-1">Overrides the global default commission.</small>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-premium px-5">Save Profile Changes</button>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>

                <!-- Business Tab -->
                <div class="tab-pane fade" id="v-pills-business" role="tabpanel">
                    <div class="premium-card">
                        <div class="premium-card-header">
                            <h5 class="premium-card-title">Registered Businesses</h5>
                            <a href="{{ url('admin/user-business/'.$data->id) }}" class="btn btn-sm btn-outline-primary px-3" style="border-radius: 8px;"><i class="fa-solid fa-plus mr-1"></i> Add Business</a>
                        </div>
                        <div class="premium-card-body">
                            <div class="row">
                                @forelse($business as $b)
                                <div class="col-md-6">
                                    <div class="business-mini-card">
                                        <img class="rounded shadow-sm mr-3" src="@if($b->logo) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$b->logo)}} @else {{asset('uploads/'.$b->logo)}} @endif @else {{asset('assets/images/user-noimage.png')}} @endif" width="55" height="55" style="object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="font-weight-bold mb-1 text-dark">{{ $b->name }}</h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="btn-group">
                                                    <a href="{{url('admin/business/'.$b->id) }}" class="text-primary mr-3"><i class="fa fa-eye"></i></a>
                                                    <a href="{{url('admin/business/'.$b->id.'/edit') }}" class="text-success mr-3"><i class="fa fa-edit"></i></a>
                                                    <a href="#" class="text-danger" data-id="{{$b->id}}" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></a>
                                                </div>
                                                <label class="switch my-auto">
                                                    <input type="checkbox" name="status" data-id="{{$b->id}}" value="1" class="status" @if($b->status==1) checked @endif>
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="col-12 text-center py-5 text-muted">No businesses found for this user.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Tab -->
                <div class="tab-pane fade" id="v-pills-subscription" role="tabpanel">
                    <div class="premium-card">
                        <div class="premium-card-header">
                            <h5 class="premium-card-title">Subscription Management</h5>
                        </div>
                        <div class="premium-card-body">
                            {!! Form::open(['url' => 'admin/subscription-update','method'=>'post']) !!}
                            {!! Form::hidden('id',$data->id)!!}
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <label class="form-label-premium">Active Plan</label>
                                    <select name="plan" class="form-control-premium w-100" required>
                                        <option value="">Select Plan</option>
                                        @foreach($subscription as $sub)
                                        <option value="{{$sub->id}}" @if($sub->id == $data->subscription_id) selected @endif>{{$sub->plan_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Start Date</label>
                                    {!! Form::text('subscription_start_from',($data->subscription_start_date)?date('d M, y',strtotime($data->subscription_start_date)):"",['class' => 'form-control-premium datepicker w-100','required',"autocomplete"=>"off"]) !!}
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label-premium">Expiry Date</label>
                                    {!! Form::text('subscription_start_to',($data->subscription_end_date)?date('d M, y',strtotime($data->subscription_end_date)):"",['class' => 'form-control-premium datepicker w-100','required',"autocomplete"=>"off"]) !!}
                                </div>
                            </div>
                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn-premium px-5">Update Subscription</button>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>

                <!-- Transaction Tab -->
                <div class="tab-pane fade" id="v-pills-transactions" role="tabpanel">
                    <div class="premium-card">
                        <div class="premium-card-header">
                            <h5 class="premium-card-title">Billing History</h5>
                        </div>
                        <div class="premium-card-body">
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th># ID</th>
                                            <th>Plan Name</th>
                                            <th>Total Paid</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transaction as $t)
                                        <tr>
                                            <td>#{{$t->id}}</td>
                                            <td><span class="badge-soft">{{$t->subscription->plan_name ?? 'N/A'}}</span></td>
                                            <td class="font-weight-bold text-dark">₹{{$t->total_paid}}</td>
                                            <td>{{date('d M, Y',strtotime($t->date))}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earning Tab -->
                <div class="tab-pane fade" id="v-pills-earning" role="tabpanel">
                    <div class="premium-card">
                        <div class="premium-card-header">
                            <h5 class="premium-card-title">Referral & Balance</h5>
                        </div>
                        <div class="premium-card-body">
                             <div class="row mb-5">
                                <div class="col-md-4">
                                    <div class="stat-box" style="background: #eff6ff; border-color: #bfdbfe;">
                                        <span class="stat-value text-primary">{{$data->referral_code ?? '---'}}</span>
                                        <span class="stat-label">Your Referral Code</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                                        <span class="stat-value text-success">₹{{$data->current_balance}}</span>
                                        <span class="stat-label">Current Balance</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box" style="background: #fffbeb; border-color: #fef3c7;">
                                        <span class="stat-value text-warning">₹{{$data->total_balance}}</span>
                                        <span class="stat-label">Lifetime Earnings</span>
                                    </div>
                                </div>
                            </div>

                            <h6 class="font-weight-bold mb-3"><i class="fa-solid fa-users-viewfinder mr-2 text-primary"></i> Referred Users</h6>
                            <div class="table-responsive mb-5">
                                <table class="custom-table" style="border: 1px solid #f1f5f9; border-radius: 12px;">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>User Name</th>
                                            <th>Join Date</th>
                                            <th>Subscribed?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($referralRegister as $r)
                                        <tr>
                                            <td>#{{$r->user->id}}</td>
                                            <td class="font-weight-bold text-dark">{{$r->user->name}}</td>
                                            <td>{{date('d M, Y',strtotime($r->created_at))}}</td>
                                            <td>
                                                @if($r->subscription == 1)
                                                <span class="badge badge-success px-2 py-1">YES ({{$r->user->subscription->plan_name ?? 'Plan'}})</span>
                                                @else
                                                <span class="badge badge-light px-2 py-1 text-muted">NO</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="font-weight-bold mb-3"><i class="fa-solid fa-clock-rotate-left mr-2 text-primary"></i> Earning History</h6>
                            <div class="table-responsive">
                                <table class="custom-table" style="border: 1px solid #f1f5f9; border-radius: 12px;">
                                    <thead>
                                        <tr>
                                            <th>Source User</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($earningHistory as $h)
                                        <tr>
                                            <td>{{$h->referUser->name ?? 'System'}}</td>
                                            <td class="@if($h->amount_type == 1) text-success @else text-danger @endif font-weight-bold">
                                                @if($h->amount_type == 1)+@else-@endif₹{{$h->amount}}
                                            </td>
                                            <td>{{date('d M, Y',strtotime($h->created_at))}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Standard Modals -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-4 text-center">
                <p class="text-muted">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4" style="border-radius: 8px;" data-dismiss="modal">Cancel</button>
                <button id="del_btn" class="btn btn-danger px-4" style="border-radius: 8px;" type="button">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // Handle direct linking to tabs if needed
        var url = window.location.href;
        if (url.indexOf("#") != -1) {
            var activeTab = url.substring(url.indexOf("#") + 1);
            $('#v-pills-tab a[href="#' + activeTab + '"]').tab('show');
        }

        $(".status").change(function(){
            var checked = $(this).is(':checked');
            var id = $(this).data("id");
            $.ajax({
                type: "POST",
                url: "{{url('admin/business-status')}}",
                data: { checked : checked , id : id},
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    toastr.success("Business Status Changed");
                },
            });
        });
    });

    $('.ToastrButton').click(function() {
      toastr.error('This Action Not Available Demo User');
    });
</script>
@endsection