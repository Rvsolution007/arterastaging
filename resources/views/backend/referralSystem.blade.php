@extends('layouts.app')

@section('extra_css')
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
            <h3 class="card-title">Referral System</h3>
        </div>

        <div class="card-body">
            @if (count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {!! Form::open(['url' => 'admin/referral-system','method'=>'post']) !!}
            {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('referral_system_enable', 'Referral System', ['class' => 'col-xl-2 col-md-3 col-3 col-form-label']) !!}
                        <div class="col-xl-6 col-md-4 col-4">
                            <label class="cl-switch cl-switch-blue">
                                <input type="checkbox" id="referral_system_enable" value="1" name="name[referral_system_enable]" @if(App\Models\ReferralSystem::getReferralSystem('referral_system_enable')==1) checked @endif>
                                <span class="switcher"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('register_point','Register Point', ['class' => 'col-sm-2 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[register_point]',App\Models\ReferralSystem::getReferralSystem('register_point'),['class' => 'form-control','placeholder'=>'Enter Register Point','required','autocomplete'=>"off"]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('subscription_point','Subscription Point', ['class' => 'col-sm-2 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[subscription_point]',App\Models\ReferralSystem::getReferralSystem('subscription_point'),['class' => 'form-control','placeholder'=>'Enter Subscription Point','required','autocomplete'=>"off"]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('withdrawal_limit','Withdrawal Limit', ['class' => 'col-sm-2 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[withdrawal_limit]',App\Models\ReferralSystem::getReferralSystem('withdrawal_limit'),['class' => 'form-control','placeholder'=>'Enter Withdrawal Limit','required','autocomplete'=>"off"]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="text-primary border-bottom pb-2">B2C Referral System (Invite & Earn)</h5>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('referral_invite_count','Required Invites', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[referral_invite_count]',App\Models\ReferralSystem::getReferralSystem('referral_invite_count') ?? 5,['class' => 'form-control','placeholder'=>'e.g. 5','required','autocomplete'=>"off"]) !!}
                            <small class="text-muted">Number of friends user needs to invite</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('referral_reward_days','Reward Days', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[referral_reward_days]',App\Models\ReferralSystem::getReferralSystem('referral_reward_days') ?? 15,['class' => 'form-control','placeholder'=>'e.g. 15','required','autocomplete'=>"off"]) !!}
                            <small class="text-muted">Days of free package to be given</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('referral_reward_package_id','Reward Package', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::select('name[referral_reward_package_id]', $subscriptions, App\Models\ReferralSystem::getReferralSystem('referral_reward_package_id'), ['class' => 'form-control', 'required']) !!}
                            <small class="text-muted">Package to give as reward</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="text-success border-bottom pb-2">B2B Partner Program (Affiliates)</h5>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('partner_refund_hold_days','Refund Hold Days', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[partner_refund_hold_days]',App\Models\ReferralSystem::getReferralSystem('partner_refund_hold_days') ?? 7,['class' => 'form-control','placeholder'=>'e.g. 7','required','autocomplete'=>"off"]) !!}
                            <small class="text-muted">Days to hold commission before it becomes withdrawable</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('partner_default_commission_percent','Default Commission (%)', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            {!! Form::number('name[partner_default_commission_percent]',App\Models\ReferralSystem::getReferralSystem('partner_default_commission_percent') ?? 20,['class' => 'form-control','placeholder'=>'e.g. 20','required','autocomplete'=>"off", 'step' => '0.01']) !!}
                            <small class="text-muted">Default percentage of sale amount to give to partners</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 m-3 text-center">
                @if(optional(Auth::user())->user_type == "Demo")
                <button type="button" class="btn btn-success ToastrButton">Save</button>
                @else
                {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
                @endif
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@section("script")
<script type="text/javascript">
    $(document).ready(function() {
        
    });
</script>
@endsection