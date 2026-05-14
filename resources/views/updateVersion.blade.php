<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/installer/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/installer/css/font-awesome.css') }}">
    <link rel="icon" href="{{asset('assets/images/Brandpeak_7.jpg')}}">
    <link rel="stylesheet" href="{{asset('assets/css/dist/adminlte.min.css')}}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Update Version - {{App\Models\AppSetting::getAppSetting('app_title')}}</title>
    <style>
        .master {
            background-image: url('assets/installer/img/background2.png');
            background-size: cover;
            background-position: top;
            min-height: 100vh;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-justify-content: center;
            -ms-flex-pack: center;
            justify-content: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center;
        }
        .box {
            border-radius: 0 0 3px 3px;
            overflow: hidden;
            box-sizing: border-box;
            box-shadow: 0 10px 10px rgba(0, 0, 0, .19), 0 6px 3px rgba(0, 0, 0, .23);
        }
    </style>
  </head>
  <body>
    <div class="master">
        <div class="card card-primary" style="width: 30% !important;">
            <div class="card-header text-center">
                <span style="font-size:20px;">Update Version - {{App\Models\AppSetting::getAppSetting('app_title')}}</span>
            </div>
            <div class="card-body text-center">
                <form action="{{url('update-version')}}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="{{ asset('assets/installer/js/jquery-3.2.1.slim.min.js') }}"></script>
    <script src="{{ asset('assets/installer/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/installer/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/installer/js/font-awesome.js') }}"></script>
    <script src="{{asset('assets/js/adminlte.js')}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    
  </body>
</html>