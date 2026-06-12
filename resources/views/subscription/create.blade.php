@extends('layouts.app')

@section('heading')
<div class="mt-5">Add Subscription Plan</div>
@endsection

@section('extra_css')
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
<style type="text/css">
    .rule-summary {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.82rem;
        line-height: 1.7;
    }
    .rule-summary h6 {
        color: #f97316;
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
    }
    .rule-summary .rule-item {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }
    .rule-summary .rule-icon {
        flex-shrink: 0;
        width: 18px;
        text-align: center;
    }
    .rule-summary .rule-divider {
        border-top: 1px solid rgba(255,255,255,0.1);
        margin: 0.6rem 0;
    }
    .feature-card {
        background-color: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        transition: box-shadow 0.2s ease;
    }
    .feature-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .feature-card .feature-header {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 0.5rem;
        margin-bottom: 0.75rem;
    }
</style>
@endsection

@section('content')
    {!! Form::open(['route' => 'subscription-plan.store','method'=>'post','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    <div class="row mt-5">
        <div class="col-12">
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('plan_name','Plan Name', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::text('plan_name', null,['class' => 'form-control','required','placeholder'=>'Enter Plan Name']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="text-primary mb-3 mt-4"><i class="fas fa-calendar-alt mr-2"></i> Monthly Pricing</h5>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('monthly_price','Monthly Base Price', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::number('monthly_price', 0,['class' => 'form-control','required','placeholder'=>'Enter Monthly Base Price']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('monthly_discount_price','Monthly Discount Price', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::number('monthly_discount_price', 0,['class' => 'form-control','required','placeholder'=>'Enter Monthly Discount Price']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="text-primary mb-3 mt-4"><i class="fas fa-calendar-check mr-2"></i> Yearly Pricing</h5>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('yearly_price','Yearly Base Price', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::number('yearly_price', 0,['class' => 'form-control','required','placeholder'=>'Enter Yearly Base Price']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('yearly_discount_price','Yearly Discount Price', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::number('yearly_discount_price', 0,['class' => 'form-control','required','placeholder'=>'Enter Yearly Discount Price']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group row">
                                {!! Form::label('business_limit','Business Limit', ['class' => 'col-sm-3 col-form-label']) !!}
                                <div class="col-sm-9">
                                    {!! Form::number('business_limit', null,['class' => 'form-control','required','placeholder'=>'Enter Business Limit']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="text-primary mb-3 mt-4"><i class="fas fa-sliders-h mr-2"></i> SaaS Feature Limits (Per Month)</h5>

                    <!-- Ad Strategy Rules Summary -->
                    <div class="rule-summary">
                        <h6><i class="fas fa-info-circle mr-1"></i> Ad Monetization Rules</h6>
                        <div class="rule-item">
                            <span class="rule-icon">✅</span>
                            <span><strong>Base Limit ≥ 1</strong> → No ads until limit reached. After that, <strong>Rewarded + Interstitial</strong> ads shown (up to Max Ad Uses).</span>
                        </div>
                        <div class="rule-item">
                            <span class="rule-icon">🔴</span>
                            <span><strong>Base Limit = 0</strong> → All ads from start (Banner, Native, Interstitial, Rewarded). After Max Ad Uses → feature locked.</span>
                        </div>
                        <div class="rule-item">
                            <span class="rule-icon">📦</span>
                            <span>If <strong>ALL</strong> features have Base Limit = 0 → Banner & Native ads shown globally on all screens.</span>
                        </div>
                        <div class="rule-divider"></div>
                        <div class="rule-item">
                            <span class="rule-icon">🎬</span>
                            <span><strong>Festival & Category Post:</strong> Paid posts → Rewarded Video + Interstitial. Free posts → Interstitial only.</span>
                        </div>
                        <div class="rule-item">
                            <span class="rule-icon">🚫</span>
                            <span><strong>Max Ad Uses = 0</strong> → No ad extension. Feature locked immediately after base limit.</span>
                        </div>
                    </div>
                    
                    <!-- Custom Post Limit -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="feature-card p-3">
                                <div class="feature-header">
                                    <h6 class="font-weight-bold text-dark mb-0">Custom Post (Edit)</h6>
                                    <small class="text-muted">Max edits per month</small>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Base Limit</label>
                                        {!! Form::number('custom_post_edit_limit', 5,['class' => 'form-control form-control-sm','required','min'=>0]) !!}
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Max Ad Uses</label>
                                        {!! Form::number('custom_post_ad_reward_limit', 5, ['class' => 'form-control form-control-sm', 'min'=>0]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>





                    <!-- Festival Post Limit -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="feature-card p-3">
                                <div class="feature-header">
                                    <h6 class="font-weight-bold text-dark mb-0">Festival Post</h6>
                                    <small class="text-muted">Max festival posts · <span class="text-info">Supports Paid/Free post rules</span></small>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Base Limit</label>
                                        {!! Form::number('festival_post_limit', 30,['class' => 'form-control form-control-sm','required','min'=>0]) !!}
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Max Ad Uses</label>
                                        {!! Form::number('festival_post_ad_reward_limit', 5, ['class' => 'form-control form-control-sm', 'min'=>0]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Post Limit -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="feature-card p-3">
                                <div class="feature-header">
                                    <h6 class="font-weight-bold text-dark mb-0">Category Post</h6>
                                    <small class="text-muted">Max category posts · <span class="text-info">Supports Paid/Free post rules</span></small>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Base Limit</label>
                                        {!! Form::number('category_post_limit', 10,['class' => 'form-control form-control-sm','required','min'=>0]) !!}
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Max Ad Uses</label>
                                        {!! Form::number('category_ad_reward_limit', 5, ['class' => 'form-control form-control-sm', 'min'=>0]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photoroom API Limit -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="feature-card p-3">
                                <div class="feature-header">
                                    <h6 class="font-weight-bold text-dark mb-0">Photoroom BG</h6>
                                    <small class="text-muted">Max API uses</small>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Base Limit</label>
                                        {!! Form::number('photoroom_bg_limit', 5,['class' => 'form-control form-control-sm','required','min'=>0]) !!}
                                    </div>
                                    <div class="col-sm-6 pt-2 pt-sm-0">
                                        <small class="text-muted"><i class="fas fa-info-circle text-info"></i> Paid API — no AdMob extension.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Image Generation Limit -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="feature-card p-3">
                                <div class="feature-header">
                                    <h6 class="font-weight-bold text-dark mb-0">AI Image Generate</h6>
                                    <small class="text-muted">Max Imagen 3 API uses</small>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm-6">
                                        <label class="small text-muted font-weight-bold mb-1">Base Limit</label>
                                        {!! Form::number('ai_image_limit', 5,['class' => 'form-control form-control-sm','required','min'=>0]) !!}
                                    </div>
                                    <div class="col-sm-6 pt-2 pt-sm-0">
                                        <small class="text-muted"><i class="fas fa-info-circle text-info"></i> Paid API — no AdMob extension.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <b>Plan Details:</b>
                    <div class="card mt-3">
                        <div class="card-body text-center">
                            <span id="add_plan"><i class="fa-solid fa-plus fa-xl"></i></span>
                        </div>
                    </div>
                    <div id="add_text"></div>
                </div>
            </div> 
        </div>
    </div>

    <div class="row mt-4 mb-5 pb-5">
        <div class="col-md-12 text-left">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill font-weight-bold ToastrButton">Save Subscription Plan</button>
        @else
        {!! Form::submit('Save Subscription Plan', ['class' => 'btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill font-weight-bold']) !!}
        @endif
        </div>
    </div>
    {!! Form::close() !!}
@endsection

@section("script")
<script type="text/javascript">
    $(document).ready(function() {
        $('#duration_type').select2();

        $('#google_product_enable').change(function(){
            if($(this).is(':checked'))
            {
                $(".google-product").css("display","block");
                $("#google_product_id").prop('required',true);
            }
            else
            {
                $(".google-product").css("display","none");
                $("#google_product_id").prop('required',false);
            }
        });
    });

    $(function() {
        var count = 1;
        $('#add_plan').on('click', function(e){
            e.preventDefault();
            $('#add_text').append('<div class="row mb-2"><div class="col-11"><input type="text" class="form-control" name="detail[data' + count +']" placeholder="Enter Detail"></div><div class="col-1"><button type="button" class="btn btn-danger remove"><i class="fa fa-xmark"></i></button></div></div>');
            count++;
        });
        $(document).on('click', 'button.remove', function( e ) {
            e.preventDefault();
            $(this).closest( 'div.row' ).remove();
        });
    });
</script>
@endsection
