@extends('layouts.app')

@section('extra_css')
<style type="text/css">
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

.poppins-font { font-family: 'Poppins', sans-serif; }

.dash-card {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    font-family: 'Poppins', sans-serif;
}
.dash-card.bg-white { background-color: #ffffff; }
.dash-card.bg-gradient-blue { background: linear-gradient(135deg, #0EA5E9, #3B82F6); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2); }
.dash-card.bg-gradient-pink { background: linear-gradient(135deg, #EC4899, #BE185D); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(236, 72, 153, 0.2); }
.dash-card.bg-gradient-green { background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2); }
.dash-card.bg-gradient-orange { background: linear-gradient(135deg, #F59E0B, #EA580C); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2); }

.dash-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    transform: translateY(-2px);
}
.dash-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}
.bg-white .dash-icon-box { background-color: #E0F2FE; }
.dash-card[class*="bg-gradient-"] .dash-icon-box { background-color: rgba(255,255,255,0.2); }

.dash-icon { width: 30px; height: 30px; }
.bg-white .dash-icon { filter: invert(49%) sepia(87%) saturate(1637%) hue-rotate(167deg) brightness(97%) contrast(96%); }
.dash-card[class*="bg-gradient-"] .dash-icon { filter: brightness(0) invert(1); }

.dash-content { display: flex; flex-direction: column; }
.dash-title { font-size: 11px; font-weight: 600; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
.bg-white .dash-title { color: #64748b; }
.dash-card[class*="bg-gradient-"] .dash-title { color: rgba(255,255,255,0.9); }

.dash-number { font-size: 24px; font-weight: 700; margin: 0; line-height: 1.1; }
.bg-white .dash-number { color: #0f172a; }

.dash-link { text-decoration: none !important; }

/* Panel Styles for Charts and Lists */
.dash-panel {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
}
.dash-panel-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background-color: #ffffff;
}
.dash-panel-title {
    font-size: 15px;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}
.dash-panel-body { padding: 0; }
.dash-panel-body .table { margin-bottom: 0; }
.dash-panel-body .table thead th {
    border-top: none;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    padding: 12px 16px;
}
.dash-panel-body .table tbody td {
    padding: 12px 16px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
    color: #334155;
}
.list-group-item {
    border-color: #f1f5f9;
    font-size: 14px;
    color: #334155;
    padding: 12px 16px;
}
</style>
@endsection

@section('content')
<div class="poppins-font">
    <!-- Breadcrumbs -->
    <div style="font-size: 14px; color: #64748b; margin-bottom: 24px; margin-top: 10px;">
        <span>Home</span> <span style="margin: 0 8px; color: #cbd5e1;">/</span> <span style="color: #64748b;">Dashboard</span>
    </div>

    <!-- Title Row -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="font-weight:700; color:#0f172a; margin:0 0 6px 0; font-size:28px; letter-spacing: -0.5px;">Dashboard</h2>
            <p style="color:#6b7280; margin:0; font-size:15px; font-weight:400;">Overview and statistics</p>
        </div>
    </div>
</div>

<div class="row poppins-font">
    <div class="col-lg-3 col-xs-6">
        <a href="{{route('user.index')}}" class="dash-link">
            <div class="dash-card bg-gradient-green">
                <div class="dash-icon-box">
                    <img src="{{asset('assets/images/icon/User.svg')}}" class="dash-icon"/>
                </div>
                <div class="dash-content">
                    <span class="dash-title">Total User</span>
                    <h2 class="dash-number">{{$user_count-1}}</h2>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-xs-6">
        <a href="{{route('category.index')}}" class="dash-link">
            <div class="dash-card bg-gradient-pink">
                <div class="dash-icon-box">
                    <img src="{{asset('assets/images/icon/Category.svg')}}" class="dash-icon"/>
                </div>
                <div class="dash-content">
                    <span class="dash-title">Total Category</span>
                    <h2 class="dash-number">{{$category_count}}</h2>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-xs-6">
        <a href="{{route('festivals.index')}}" class="dash-link">
            <div class="dash-card bg-gradient-blue">
                <div class="dash-icon-box">
                    <img src="{{asset('assets/images/icon/Festival.svg')}}" class="dash-icon"/>
                </div>
                <div class="dash-content">
                    <span class="dash-title">Total Festival</span>
                    <h2 class="dash-number">{{$festivals_count}}</h2>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-xs-6">
        <a href="{{route('business.index')}}" class="dash-link">
            <div class="dash-card bg-gradient-orange">
                <div class="dash-icon-box">
                    <img src="{{asset('assets/images/icon/Business.svg')}}" class="dash-icon"/>
                </div>
                <div class="dash-content">
                    <span class="dash-title">Total Business</span>
                    <h2 class="dash-number">{{$business_count}}</h2>
                </div>
            </div>
        </a>
    </div>
</div>

@can('FinancialStatistics')
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="dash-card bg-white">
            <div class="dash-icon-box">
                <img src="{{asset('assets/images/icon/Today Payment.svg')}}" class="dash-icon"/>
            </div>
            <div class="dash-content">
                <span class="dash-title">Today Payment</span>
                <h2 class="dash-number" style="font-size:22px;">{{App\Models\AppSetting::getAppSetting('currency')}} {{$today_payment}}</h2>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="dash-card bg-white">
            <div class="dash-icon-box">
                <img src="{{asset('assets/images/icon/Weekly Payment.svg')}}" class="dash-icon"/>
            </div>
            <div class="dash-content">
                <span class="dash-title">Weekly Payment</span>
                <h2 class="dash-number" style="font-size:22px;">{{App\Models\AppSetting::getAppSetting('currency')}} {{$weekly_payment}}</h2>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="dash-card bg-white">
            <div class="dash-icon-box">
                <img src="{{asset('assets/images/icon/Monthly Payment.svg')}}" class="dash-icon"/>
            </div>
            <div class="dash-content">
                <span class="dash-title">Monthly Payment</span>
                <h2 class="dash-number" style="font-size:22px;">{{App\Models\AppSetting::getAppSetting('currency')}} {{$monthly_payment}}</h2>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="dash-card bg-white">
            <div class="dash-icon-box">
                <img src="{{asset('assets/images/icon/Total Payment.svg')}}" class="dash-icon"/>
            </div>
            <div class="dash-content">
                <span class="dash-title">Total Payment</span>
                <h2 class="dash-number" style="font-size:22px;">{{App\Models\AppSetting::getAppSetting('currency')}} {{$transaction_count}}</h2>
            </div>
        </div>
    </div>
</div>
@endcan

<div class="row">
    <div class="col-md-12">
        <div class="row">
            @can('FinancialStatistics')
            <div class="col-md-6">
                <div class="dash-panel">
                    <div class="dash-panel-header"><h5 class="dash-panel-title">Monthly Payment Report</h5></div>
                    <div class="dash-panel-body">
                        <canvas id="myChart" style="width:100%;max-width:800px"></canvas>
                    </div>
                </div>
            </div>
            @endcan
            <div class="col-md-6">
                <div class="dash-panel">
                    <div class="dash-panel-header"><h5 class="dash-panel-title">Monthly User Report</h5></div>
                    <div class="dash-panel-body">
                        <canvas id="myChart1" style="width:100%;max-width:800px"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h5 class="dash-panel-title">Today Event</h5>
    </div>
    <div class="dash-panel-body">
        <div class="row grid7">
            @foreach($today_event as $e)
            <div class="col-xl-1 col-md-4 col-6 text-center mb-3">
                <img class="rounded-circle border border-primary mb-2 shadow-sm" src="@if($e->image) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$e->image)}} @else {{asset('uploads/'.$e->image)}} @endif @else {{asset('assets/images/noimage.png')}} @endif" width="100px" height="100px"></br>
                <span class="text-secondary font-weight-bold" style="font-size:14px;">{{$e->title}}</span><br>
                @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="btn btn-sm btn-primary rounded-pill mt-2 px-4 shadow-sm">Send</button>
                @else
                    <button type="button" class="btn btn-sm btn-primary rounded-pill mt-2 px-4 shadow-sm" data-id="{{$e->id}}">Send</button>
                @endif       
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h5 class="dash-panel-title">User Subscription Plan Expire</h5>
    </div>
    <div class="dash-panel-body">
        <div class="row grid7">
            @foreach($subscription_end_user as $subscription_user)
            <div class="col-xl-1 col-md-4 col-6 text-center mb-3">
                <img class="rounded-circle border border-primary mb-2 shadow-sm" src="@if($subscription_user->image) @if(substr($subscription_user->image, 0, 4)=='http') {{$subscription_user->image}} @else @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$subscription_user->image)}} @else {{asset('uploads/'.$subscription_user->image)}} @endif @endif @else {{asset('assets/images/no-user.jpg')}} @endif" width="100px" height="100px"></br>
                <span class="font-weight-bold" style="font-size:14px;"><a href="{{url('admin/user/'.$subscription_user->id) }}" class="text-secondary">{{$subscription_user->name}}</a></span><br>
                <span class="text-danger font-weight-bold" style="font-size:12px;"><a href="{{url('admin/user/'.$subscription_user->id) }}" class="text-danger">{{date('d M, y',strtotime($subscription_user->subscription_end_date))}}</a></span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4">
        <div class="dash-panel">
            <div class="dash-panel-header text-center">
                <h5 class="dash-panel-title">Recent Register User</h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($user as $u)
                    @if($u->email != "demo2023@gmail.com")
                    <li class="list-group-item">
                        <div class="d-flex flex-row">
                            <img class="rounded-circle border border-primary" src="@if($u->image) @if(substr($u->image, 0, 4)=='http') {{$u->image}} @else @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$u->image)}} @else {{asset('uploads/'.$u->image)}} @endif @endif @else {{asset('assets/images/no-user.jpg')}} @endif" width="60px" height="60px">
                            <div class="d-flex flex-column ml-3">
                                <span class="d-block font-weight-bold my-auto name"><a href="{{url('admin/user/'.$u->id) }}" class="text-dark" style="font-size:18px;">{{$u->name}}</a></span>
                                <span class="date text-muted" style="font-size:15px;">
                                @php 
                                    $time_ago = strtotime($u->created_at);
                                    $cur_time   = time();
                                    $time_elapsed   = $cur_time - $time_ago;
                                    $seconds    = $time_elapsed ;
                                    $minutes    = round($time_elapsed / 60 );
                                    $hours      = round($time_elapsed / 3600);
                                    $days       = round($time_elapsed / 86400 );
                                    $weeks      = round($time_elapsed / 604800);
                                    $months     = round($time_elapsed / 2600640 );
                                    $years      = round($time_elapsed / 31207680 );
                                    // Seconds
                                    if($seconds <= 60){
                                        echo "just now";
                                    }
                                    //Minutes
                                    else if($minutes <=60){
                                        if($minutes==1){
                                            echo "1 minute ago";
                                        }
                                        else{
                                            echo "$minutes minutes ago";
                                        }
                                    }
                                    //Hours
                                    else if($hours <=24){
                                        if($hours==1){
                                            echo "1 hour ago";
                                        }else{
                                            echo "$hours hrs ago";
                                        }
                                    }
                                    //Days
                                    else if($days <= 7){
                                        if($days==1){
                                            echo "yesterday";
                                        }else{
                                            echo "$days days ago";
                                        }
                                    }
                                    //Weeks
                                    else if($weeks <= 4.3){
                                        if($weeks==1){
                                            echo "1 week ago";
                                        }else{
                                            echo "$weeks weeks ago";
                                        }
                                    }
                                    //Months
                                    else if($months <=12){
                                        if($months==1){
                                            echo "1 month ago";
                                        }else{
                                            echo "$months months ago";
                                        }
                                    }
                                    //Years
                                    else{
                                        if($years==1){
                                            echo "1 year ago";
                                        }else{
                                            echo "$years years ago";
                                        }
                                    }
                                @endphp
                                </span>
                            </div>
                        </div>
                    </li>
                    @endif
                @endforeach
            </ul>
            <div class="card-footer text-center">
                <a href="{{route('user.index')}}" class="btn btn-primary">View More</a>
            </div>
        </div>
    </div>

    @can('FinancialStatistics')
    <div class="col-xl-4">
        <div class="dash-panel">
            <div class="dash-panel-header text-center">
                <h5 class="dash-panel-title">Recent Purchase</h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($transaction as $key=>$t)
                @if($t->user)
                <li class="list-group-item d-flex justify-content-between">
                    <div class="d-flex flex-row">
                        <img class="rounded-circle border border-primary" src="@if($t->user->image) @if(substr($t->user->image, 0, 4)=='http') {{$t->user->image}} @else @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$t->user->image)}} @else {{asset('uploads/'.$t->user->image)}} @endif @endif @else {{asset('assets/images/no-user.jpg')}} @endif" width="60px" height="60px">
                        <div class="d-flex flex-column ml-2">
                            <span class="d-block font-weight-bold my-auto name"><a href="{{url('admin/user/'.$t->user->id) }}" class="text-dark" style="font-size:18px;">{{$t->user->name}}</a></span>
                            <span class="date text-muted" style="font-size:15px;">
                            @php 
                                $time_ago = strtotime($t->created_at);
                                $cur_time   = time();
                                $time_elapsed   = $cur_time - $time_ago;
                                $seconds    = $time_elapsed ;
                                $minutes    = round($time_elapsed / 60 );
                                $hours      = round($time_elapsed / 3600);
                                $days       = round($time_elapsed / 86400 );
                                $weeks      = round($time_elapsed / 604800);
                                $months     = round($time_elapsed / 2600640 );
                                $years      = round($time_elapsed / 31207680 );
                                // Seconds
                                if($seconds <= 60){
                                    echo "just now";
                                }
                                //Minutes
                                else if($minutes <=60){
                                    if($minutes==1){
                                        echo "1 minute ago";
                                    }
                                    else{
                                        echo "$minutes minutes ago";
                                    }
                                }
                                //Hours
                                else if($hours <=24){
                                    if($hours==1){
                                        echo "1 hour ago";
                                    }else{
                                        echo "$hours hrs ago";
                                    }
                                }
                                //Days
                                else if($days <= 7){
                                    if($days==1){
                                        echo "yesterday";
                                    }else{
                                        echo "$days days ago";
                                    }
                                }
                                //Weeks
                                else if($weeks <= 4.3){
                                    if($weeks==1){
                                        echo "1 week ago";
                                    }else{
                                        echo "$weeks weeks ago";
                                    }
                                }
                                //Months
                                else if($months <=12){
                                    if($months==1){
                                        echo "1 month ago";
                                    }else{
                                        echo "$months months ago";
                                    }
                                }
                                //Years
                                else{
                                    if($years==1){
                                        echo "1 year ago";
                                    }else{
                                        echo "$years years ago";
                                    }
                                }
                            @endphp
                            </span>
                        </div>
                    </div>
                    @php $color =["primary","secondary","success","danger","warning","info"] @endphp
                    <div class="d-flex flex-column my-auto">
                        <div><button type="button" class="btn btn-{{$color[$key]}}">{{$t->total_paid}}</button></div>
                    </div>
                </li>
                @endif
                @endforeach
            </ul>
            <div class="card-footer text-center">
                <a href="{{url('admin/transaction')}}" class="btn btn-primary">View More</a>
            </div>
        </div>
    </div>
    @endcan

    <div class="col-xl-4">
        <div class="dash-panel">
            <div class="dash-panel-header text-center">
                <h5 class="dash-panel-title">Recent Contact User</h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($contact_user as $contact)
                <li class="list-group-item d-flex justify-content-between">
                    <div class="d-flex flex-row">
                        <img class="rounded-circle border border-primary" src="{{asset('assets/images/no-user.jpg')}}" width="60px" height="60px">
                        <div class="d-flex flex-column ml-2">
                            <span class="d-block font-weight-bold my-auto name text-dark" style="font-size:18px;">{{$contact->name}}</span>
                            <span class="date text-muted" style="font-size:15px;">
                            {{$contact->message}}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex flex-column my-auto text-muted">
                        <div>
                        @php 
                            $time_ago = strtotime($contact->created_at);
                            $cur_time   = time();
                            $time_elapsed   = $cur_time - $time_ago;
                            $seconds    = $time_elapsed ;
                            $minutes    = round($time_elapsed / 60 );
                            $hours      = round($time_elapsed / 3600);
                            $days       = round($time_elapsed / 86400 );
                            $weeks      = round($time_elapsed / 604800);
                            $months     = round($time_elapsed / 2600640 );
                            $years      = round($time_elapsed / 31207680 );
                            // Seconds
                            if($seconds <= 60){
                                echo "just now";
                            }
                            //Minutes
                            else if($minutes <=60){
                                if($minutes==1){
                                    echo "1 minute ago";
                                }
                                else{
                                    echo "$minutes minutes ago";
                                }
                            }
                            //Hours
                            else if($hours <=24){
                                if($hours==1){
                                    echo "1 hour ago";
                                }else{
                                    echo "$hours hrs ago";
                                }
                            }
                            //Days
                            else if($days <= 7){
                                if($days==1){
                                    echo "yesterday";
                                }else{
                                    echo "$days days ago";
                                }
                            }
                            //Weeks
                            else if($weeks <= 4.3){
                                if($weeks==1){
                                    echo "1 week ago";
                                }else{
                                    echo "$weeks weeks ago";
                                }
                            }
                            //Months
                            else if($months <=12){
                                if($months==1){
                                    echo "1 month ago";
                                }else{
                                    echo "$months months ago";
                                }
                            }
                            //Years
                            else{
                                if($years==1){
                                    echo "1 year ago";
                                }else{
                                    echo "$years years ago";
                                }
                            }
                        @endphp
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
            <div class="card-footer text-center">
                <a href="{{route('entry.index')}}" class="btn btn-primary">View More</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section("script")
<script src="{{ asset('assets/js/cdn/Chart.bundle.min.js')}}"></script>
<script src="{{ asset('assets/js/cdn/canvasjs.min.js')}}"></script>
<script>
$(document).ready(function() {
    $(".notification_btn").on("click",function(){
        var id = $(this).data("id");

        $.ajax({
            type: "POST",
            url: "{{url('admin/today-event-notification')}}",
            data: {id : id},
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                if(data == "Success")
                {
                    new PNotify({
                        title: 'Success!',
                        text: "Notification Send!",
                        type: 'success'
                    });
                }
                else
                {
                    new PNotify({
                        title: 'Error!',
                        text: data,
                        type: 'error'
                    });
                }
            },
        });
    });
});
</script>
<script>
$( document ).ready(function() {
    new Chart("myChart", {
        type: "line",
        data: {
            labels: [@foreach($payment_chart as $data) "{{$data['month']}}", @endforeach],
            datasets: [{
                data: [@foreach($payment_chart as $data) {{$data['count']}}, @endforeach],
                borderColor: "blue",
                fill: false,
            }]
        },
        options: {
            legend: {display: false},
            scales : {
                yAxes : [ {
                    gridLines : {
                        display : false
                    }
                } ]
            },
            tooltips: {
                yPadding: 10,
                xPadding: 10,
                backgroundColor: 'white',
                titleFontColor: 'black',
                bodyFontColor: 'black',
                cornerRadius: 5,
                borderColor: '#ced5e1',
                borderWidth: 1,
                callbacks: {
                    title: function(tooltipItems, data) {
                        @foreach($payment_chart as $data)
                            if(tooltipItems[0].xLabel == "{{$data['month']}}")
                            {
                                return "{{$data['fullMonth']}}";
                            }
                        @endforeach
                    },
                    label: function (tooltipItems, data) {
                        if(tooltipItems.yLabel >= 1000)
                        {
                            return Number((tooltipItems.yLabel / 1000)).toFixed(1) + 'K';
                        }
                        if(tooltipItems.yLabel >= 1000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000)).toFixed(1) + 'M';
                        }
                        if(tooltipItems.yLabel >= 1000000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000000)).toFixed(1) + 'B';
                        }
                        if(tooltipItems.yLabel >= 1000000000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000000000)).toFixed(1) + 'T';
                        }
                        if(tooltipItems.yLabel < 1000)
                        {
                            return tooltipItems.yLabel;
                        }  
                    }
                }
            },
        }
    });

    window.Apex = {
        dataLabels: {
            enabled: false
        }
    };

    new Chart("myChart1", {
        type: "bar",
        data: {
            labels:[@foreach($user_chart as $data) "{{$data['month']}}", @endforeach],
            datasets: [{
                data: [@foreach($user_chart as $data) {{$data['count']}}, @endforeach],
                backgroundColor: "#ced5e1",
                fill: false,
                hoverBackgroundColor:"#147ad6",
            }]
        },
        options: {
            legend: {display: false},
            scales : {
                yAxes : [ {
                    gridLines : {
                        display : false
                    }
                } ]
            },
            tooltips: {
                yPadding: 10,
                xPadding: 10,
                backgroundColor: 'white',
                titleFontColor: 'black',
                bodyFontColor: 'black',
                cornerRadius: 5,
                borderColor: '#ced5e1',
                borderWidth: 1,
                callbacks: {
                    title: function(tooltipItems, data) {
                        @foreach($payment_chart as $data)
                            if(tooltipItems[0].xLabel == "{{$data['month']}}")
                            {
                                return "{{$data['fullMonth']}}";
                            }
                        @endforeach
                    },
                    label: function (tooltipItems, data) {
                        if(tooltipItems.yLabel >= 1000)
                        {
                            return Number((tooltipItems.yLabel / 1000).toString()) + 'K';
                        }
                        if(tooltipItems.yLabel >= 1000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000).toString()) + 'M';
                        }
                        if(tooltipItems.yLabel >= 1000000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000000).toString()) + 'B';
                        }
                        if(tooltipItems.yLabel >= 1000000000000)
                        {
                            return Number((tooltipItems.yLabel / 1000000000000).toString()) + 'T';
                        }
                        if(tooltipItems.yLabel < 1000)
                        {
                            return tooltipItems.yLabel;
                        }  
                    },
                }
            },
        }
    });

    window.Apex = {
        dataLabels: {
            enabled: false
        }
    };
});
</script>
@endsection