<!doctype html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0 minimal-ui">
    <title>Business Card 10</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <link href="{{asset('assets/template/card5/template10.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/fonts.css')}}" rel="stylesheet">
    <link href="{{asset('assets/template/card5/star-rating.css')}}" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper" id="home" style="max-height:100%;">
        <!-- <div class="companylogo"><img src="demo-templates/images/edesignguru-logo.jpg" class="img-responsive" alt="Edesignguru"></div>
        <div class="views"><i class="fa fa-eye"></i> Views: <b>1100</b></div> -->
        <div class="clearfix"></div>
        <div class="headerbg"></div>
        <div class="personface">
            <img src="{{$image}}" class="img-responsive" alt="{{$name}}" style="background-color:white;"/>
        </div>

        <div class="text-center">
            <div class="personname">
                {{$name}}<br>
                <span class="designation">{{$designation}}</span>
            </div>

            <div class="companyname">{{$comapany_name}}</div>

            <div class="clearfix"></div>

            <div class="contact-row"> 
                <a class="contact-icon" href="tel:{{$phone}}"> <i class="fas fa-phone"></i> </a> 
                <a class="contact-icon" target="_blank" href="https://wa.me/{{$whatsapp}}"> <i class="fab fa-whatsapp"></i> </a> 
                <a class="contact-icon" target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fas fa-location-dot"></i> </a> 
                <a class="contact-icon" target="_blank" href="mailto:{{$email}}"> <i class="fas fa-envelope"></i> </a> 
            </div>
        </div>

        <div class="firstpagebottom">
            <table class="contact-table">
                <tbody>
                    <tr>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fas fa-location-dot inside-icon"></i> </a></td>
                        <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}" class="contact-text"> {{$address}} </a></td>
                    </tr>

                    <tr>
                        <td><a href="mailto:{{$email}}"> <i class="fas fa-envelope inside-icon"></i> </a></td>
                        <td><a href="mailto:{{$email}}" class="contact-text"> {{$email}}</a></td>
                    </tr>

                    <tr>
                        <td><a target="_blank" href="{{$website}}"> <i class="fas fa-globe inside-icon"></i> </a></td>
                        <td><a target="_blank" href="{{$website}}" class="contact-text"> {{$website}} </a></td>
                    </tr>

                    <tr>
                        <td><a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone inside-icon"></i> </a></td>
                        <td><a target="_blank" href="tel:{{$phone}}" class="contact-text"> +91 {{$phone}} </a></td>
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
                        <td> {{$comapany_name}} </td>
                    </tr>
                </tbody>
            </table>

            <p class="about-txt">{{$about_us}}</p>
        </div>
    </div>
</body>
</html>

