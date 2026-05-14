<!doctype html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0 minimal-ui">
    <title>Business Card 5</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <link href="{{asset('assets/template/card5/template5.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/fonts.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/star-rating.css')}}" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper" id="home">
        <div class="firstpagetop">
            <div class="profilepic">
                <img src="{{$image}}" class="img-responsive" alt="{{$name}}">
            </div>
            <div class="companyname" style="font-size:20px;">{{$comapany_name}}</div>
            <div class="name">
                {{$name}}<br>
                <span>
                    {{$designation}}
                </span>
            </div>
            <div class="actionbtn"> 
                <a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone iconbtn" style="line-height:1.5;"></i> </a> 
                <a target="_blank" href="https://wa.me/{{$whatsapp}}"> <i class="fa-brands fa-whatsapp iconbtn" style="line-height:1.5;"></i> </a> 
                <a target="_blank" href="https://www.google.com/maps/place/{{$address}}6"> <i class="fas fa-map-marker iconbtn" style="line-height:1.5;"></i> </a> 
                <a target="_blank" href="mailto:{{$email}}"> <i class="fas fa-envelope fa-flip-horizontal iconbtn" style="line-height:1.5;"></i> </a> 
            </div>
        </div>

        <div class="firstpagebottom">
            <table class="contact-table">
                <tbody>
                    <tr>
                        <td><a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone contact-icon"></i> </a></td>
                        <td><a target="_blank" href="tel:{{$phone}}" class="contact-text" > +91 {{$phone}} </a></td>
                    </tr>
                    <tr>
                        <td><a href="mailto:{{$email}}"> <i class="fas fa-envelope contact-icon"></i> </a></td>
                        <td><a href="mailto:{{$email}}" class="contact-text"> {{$email}} </a></td>
                    </tr>
                    <tr>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fa-solid fa-location-dot contact-icon"></i> </a></td>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}" class="contact-text"> {{$address}} </a></td>
                    </tr>
                    <tr>
                        <td><a target="_blank" href="{{$website}}"> <i class="fas fa-globe contact-icon"></i> </a></td>
                        <td><a target="_blank" href="{{$website}}" class="contact-text"> {{$website}} </a></td>
                    </tr>
                </tbody>
            </table>

            <div class="firstpage share-btn">
                <a href="https://www.facebook.com/{{$facebook}}" style="display: inline-block;line-height:40px; margin-top:20px;"><i class="share-btn-facebook fa-brands fa-facebook"></i></a>
                <a href="https://www.twitter.com/{{$twitter}}" style="display: inline-block;line-height:40px;"><i class="share-btn-twitter fa-brands fa-twitter"></i></a> 
                <a href="https://www.instagram.com/{{$instagram}}" style="display: inline-block;line-height:40px;"><i class="share-btn-instagram fa-brands fa-instagram"></i></a>
                <a href="https://www.youtube.com/c/{{$youtube}}" style="display: inline-block;line-height:40px;"><i class="share-btn-youtube fa-brands fa-youtube"></i></a>
                <a href="https://www.linkedin.com/{{$linkedin}}" style="display: inline-block;line-height:40px;"><i class="share-btn-linkedin fa-brands fa-linkedin"></i></a>
            </div>
        </div>

        <div class="page-container" id="aboutus" style="margin-top:-30px;margin-bottom:-140px;">
            <h2 class="section-heading">ABOUT US</h2>
            <table class="about-tbl">
                <tbody>
                <tr>
                    <td class="td-label"><h3>Business Name</h3></td>
                    <td>:</td>
                    <td>{{$comapany_name}}</td>
                </tr>
                </tbody>
            </table>
            <p class="about-txt">{{$about_us}}</p>
        </div>
    </div>
</body>
</html>

