<!doctype html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0 minimal-ui">
    <title>Business Card 8</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <link href="{{asset('assets/template/card5/template8.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/fonts.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/star-rating.css')}}" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper" id="home" style="background:#143160;">
        <div class="firstpagetop">
            <div class="profilepic">
                <img src="{{$image}}" class="img-responsive" alt="{{$name}}">
            </div>
            <div class="companyname" style="font-size:18px;">{{$comapany_name}}</div>
            <div class="name">
                {{$name}} <br>
                <span>({{$designation}})</span>
            </div>
            <div class="actionbtn"> 
                <a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone iconbtn"></i> <br><span class="icontxt">Call</span> </a> 
                <a target="_blank" href="https://wa.me/{{$whatsapp}}"> <i class="fab fa-whatsapp iconbtn"></i> <br><span class="icontxt">Whatsapp</span> </a> 
                <a target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fas fa-location-dot iconbtn"></i> <br><span class="icontxt">Direction</span> </a> 
                <a target="_blank" href="mailto:{{$email}}"> <i class="fas fa-envelope fa-flip-horizontal iconbtn"></i> <br><span class="icontxt">Mail</span> </a> 
            </div>
        </div>

        <div class="firstpagebottom">
            <table class="contact-table">
                <tbody>
                    <tr>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fas fa-location-dot contact-icon"></i> </a></td>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}" class="contact-text"> {{$address}}</a></td>
                    </tr>

                    <tr>
                        <td><a href="mailto:{{$email}}"> <i class="fas fa-envelope contact-icon"></i> </a></td>
                        <td><a href="mailto:{{$email}}" class="contact-text"> {{$email}}</a></td>
                    </tr>

                    <tr>
                        <td><a target="_blank" href="{{$website}}"> <i class="fas fa-globe contact-icon"></i> </a></td>
                        <td><a target="_blank" href="{{$website}}" class="contact-text"> {{$website}} </a></td>
                    </tr>

                    <tr>
                        <td><a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone contact-icon"></i> </a></td>
                        <td><a target="_blank" href="tel:{{$phone}}" class="contact-text" > +91 {{$phone}} </a></td>
                    </tr>
                </tbody>
            </table>

            <ul class="firstpage share-btn">
                <li> <a href="https://www.facebook.com/{{$facebook}}"><i class="share-btn-facebook fab fa-facebook"></i></a> </li>
                <li> <a href="https://twitter.com/{{$twitter}}"><i class="share-btn-twitter fab fa-twitter"></i></a> </li>
                <li> <a href="https://www.instagram.com/{{$instagram}}"><i class="share-btn-instagram fab fa-instagram"></i></a> </li>
                <li> <a href="https://www.youtube.com/c/{{$youtube}}"><i class="share-btn-youtube fab fa-youtube"></i></a> </li>
                <li> <a href="https://www.linkedin.com/{{$linkedin}}" ><i class="share-btn-linkedin fab fa-linkedin"></i></a> </li>
            </ul>
        </div>

        <div class="page-container" id="aboutus">
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

