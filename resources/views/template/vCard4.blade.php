<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <link rel="stylesheet" href="{{asset('assets/Blue Boat/base.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/Blue Boat/fancy.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/Blue Boat/main.css')}}"/>
    <title>Business Card 4</title>
    <style>
        .main_title{
            line-height: 3;
            height:80px !important;
            left: 0px !important;
            width:100%;
            text-align:center;
        }
        .main_sub_title{
            height:20px !important;
            left: 0px !important;
            font-weight: 900;
            width:100%;
            text-align:center;
        }
        .main_phone{
            height:20px !important;
            left: 160px !important;
        }
        .main_email{
            height:20px !important;
            left: 160px !important;
        }
        .main_website{
            height:20px !important;
            left: 160px !important;
        }
        .main_address{
            height:50px !important;
            left: 160px !important;
            top:750px;
            white-space: break-spaces;
        }
        .main_whatsapp{
            height:20px !important;
            left: 160px !important;
        }
        .main_link{
            height:20px !important;
            left: 160px !important;
        }
        a{
            color: #FFFFFF;
            text-decoration: none;
        }
        .user_image{
            border-radius:100%;
            height:160px;
            width:160px;
            top: 108px !important;
            left: 190px !important;
            background-color:white;
        }
        
    </style>
</head>
<body>
    <div id="sidebar">
        <div id="outline">
        </div>
    </div>
    <div id="page-container">
        <div id="pf1" class="pf w0 h0" data-page-no="1">
            <div class="pc pc1 w0 h0">
                <img class="bi x0 y0 w0 h0" alt="image" src="{{asset('assets/Blue Boat/bg1.png')}}"/>
                <div class="t m0 x1 h1 y1 ff1 fs0 fc0 sc0 ls0 ws0 main_phone" style="font-size:20px;"><a href="tel:{{$phone}}">+91 {{$phone}}</a></div>
                <div class="t m0 x1 h1 y2 ff1 fs0 fc0 sc0 ls0 ws0 main_email" style="font-size:20px;"><a href="mailto:{{$email}}">{{$email}}</a></div>
                <div class="t m0 x1 h1 y3 ff1 fs0 fc0 sc0 ls0 ws0 main_website" style="font-size:20px;"><a href="{{$website}}">{{$website}}</a></div>
                <div class="t m0 x1 h2 y4 ff1 fs1 fc0 sc0 ls0 ws0 main_address" style="font-size:20px;"><a href="https://www.google.com/maps/place/{{$address}}" target="_blank">{{$address}}</a></div>
                <div class="t m0 x2 h3 y6 ff1 fs2 fc1 sc0 ls0 ws0 main_title" style="font-size:30px;"><b>{{$name}}</b></div>
                <div class="t m0 x3 h4 y7 ff2 fs1 fc1 sc0 ls0 ws0 main_sub_title" style="font-size:20px;">{{$designation}}</div>
                <div class="t m0 x1 h5 y8 ff1 fs3 fc0 sc0 ls0 ws0 main_whatsapp" style="font-size:20px;"><a href="https://wa.me/{{$whatsapp}}" target="_blank">Click to Whatsapp</a></div>
                <img class="t user_image" alt="image" src="{{$image}}"/>
                
            </div>
            <a href="https://www.facebook.com/{{$facebook}}" target="_blank" class="t" style="position:absolute;top:843px;left:99px;"><img alt="image" src="{{asset('assets/profile-card/img/social/facebook.svg')}}" style="height:45px;width:45px;"></a>
            <a href="https://www.instagram.com/{{$instagram}}" target="_blank" class="t" style="position:absolute;top:843px;left:159px;"><img alt="image" src="{{asset('assets/profile-card/img/social/Instagram.png')}}" style="height:45px;width:45px;border-radius:50%;"></a>
            <a href="https://twitter.com/{{$twitter}}" target="_blank" class="t" style="position:absolute;top:843px;left:217px;"><img alt="image" src="{{asset('assets/profile-card/img/social/twitter.png')}}" style="height:45px;width:45px;"></a>
            <a href="https://www.linkedin.com/{{$linkedin}}" target="_blank" class="t" style="position:absolute;top:843px;left:275px;"><img alt="image" src="{{asset('assets/profile-card/img/social/linkedin.svg')}}" style="height:45px;width:45px;"></a>
            <a href="https://www.google.com/maps/place/{{$address}}" target="_blank" class="t" style="position:absolute;top:843px;left:335px;"><img alt="image" src="{{asset('assets/profile-card/img/social/google_map.png')}}" style="height:45px;width:45px;border-radius:50%;"></a>
            <a href="https://www.youtube.com/c/{{$youtube}}" target="_blank" class="t" style="position:absolute;top:843px;left:396px;"><img alt="image" src="{{asset('assets/profile-card/img/social/youtube.svg')}}" style="height:45px;width:45px;"></a>
            <div class="pi" data-data='{"ctm":[1.000000,0.000000,0.000000,1.000000,0.000000,0.000000]}'></div>
        </div>
    </div>
    <div class="loading-indicator">
    </div>
    <script src="{{asset('assets/Blue Boat/compatibility.min.js')}}"></script>
    <script src="{{asset('assets/Blue Boat/theViewer.min.js')}}"></script>
    <script>
    try{
    theViewer.defaultViewer = new theViewer.Viewer({});
    }catch(e){}
    </script>
</body>
</html>
