<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Installation - Brand Peak</title>
    <link rel="icon" href="{{asset('assets/installer/img/Brandpeak_7.jpg')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('assets/installer/css/style.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/installer/froiden-helper/helper.css') }}" rel="stylesheet"/>
    <style>
        .form-control{
            height: 14px;
            width: 100%;
        }
        .has-error{
            color: red;
        }
        .has-error input{
            color: black;
            border:1px solid red;
        }
    </style>
</head>
<body>
    <div class="master">
        <div class="box" style="width: 30% !important;">
            <div class="header">
                <img src="{{asset('assets/installer/img/Brandpeak_7.jpg')}}" style="border-radius: 50px;" height="85px" alt="logo">
                <h1 class="header__title">Welcome to Brand Peak Installation</h1>
            </div>

            <div class="main">
                @if(session('message')!="" || session('response')!="" || session('database')!="" )
                    <ul style="list-style-type: none;">
                        <div class="alert alert-danger">
                                <li> {{session('message')}} </li>
                        </div>
                    </ul>
                @endif
                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ url('installation') }}" id="env-form">
                    {!! csrf_field() !!}
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="font-size:17px;">Purchase Code:</label>

                        <div class="col-sm-10">
                            <input type="text" name="purchase_code" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="font-size:17px;">Hostname</label>

                        <div class="col-sm-10">
                            <input type="text" name="hostname" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="font-size:17px;">Username</label>
                        <div class="col-sm-10">
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label  class="col-sm-2 control-label" style="font-size:17px;">Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="font-size:17px;">Database Name</label>
                        <div class="col-sm-10">
                            <input type="text" name="database" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="buttons">
                            <button class="button" type="submit">Setup</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script src="{{ asset('assets/installer/js/jQuery-2.2.0.min.js') }}"></script>
<script src="{{ asset('assets/installer/froiden-helper/helper.js')}}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
</html>
