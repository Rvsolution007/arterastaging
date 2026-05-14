<!doctype html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0 minimal-ui">
        <title>Business Card 6</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
        <link href="{{asset('assets/template/card5/template6.css')}}" rel="stylesheet">
        <link href="{{asset('assets/template/card5/fonts.css')}}" rel="stylesheet">
        <link href="{{asset('assets/template/card5/star-rating.css')}}" rel="stylesheet">
    </head>
    <body>
        <div class="main-wrapper" id="home">
            <div class="headerbg">
                <div class="personface">
                    <img src="{{$image}}" class="img-responsive" alt="{{$name}}">
                </div>
                <div class="text-center">
                    <div class="personname">
                        <span>{{$name}}</span>
                    </div>
		            <div class="companyname">
                        <span class="designation">{{$designation}}</span>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="contact-row"> 
                    <a class="contact-icon red" href="tel:{{$phone}}" style="line-height:1.8;"> 
                        <i class="fas fa-phone"></i>
                    </a> 
                    <a class="contact-icon green" target="_blank" href="https://wa.me/{{$whatsapp}}" style="line-height:1.8;"> 
                        <i class="fa-brands fa-whatsapp"></i> 
                    </a> 
                    <a class="contact-icon blue" target="_blank" href="https://www.google.com/maps/place/{{$address}}" style="line-height:1.8;"> 
                        <i class="fa-solid fa-location-dot"></i> 
                    </a> 
                    <a class="contact-icon yellow" target="_blank" href="mailto:{{$email}}" style="line-height:1.8;"> 
                        <i class="fas fa-envelope"></i> 
                    </a> 
                </div>
            </div>
            <div class="firstpagebottom">
                <div class="firstpage share-btn">
                    <a href="https://www.linkedin.com/{{$linkedin}}" style="display: inline-block;line-height:40px; margin-top:20px;"><i class="share-btn-linkedin fa-brands fa-linkedin"></i></a>
                    <a href="https://www.facebook.com/{{$facebook}}" style="display: inline-block;line-height:40px;"><i class="share-btn-facebook fa-brands fa-facebook"></i></a>
                    <a href="https://www.twitter.com/{{$twitter}}" style="display: inline-block;line-height:40px;"><i class="share-btn-twitter fa-brands fa-twitter"></i></a>
                </div>
                <table class="contact-table" style="margin-left:20px;">
                    <tbody>
                        <tr>
                            <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}"> <i class="fa-solid fa-location-dot inside-icon blue"></i> </a></td>
                            <td><a target="_blank" href="https://www.google.com/maps/place/{{$address}}" class="contact-text">{{$address}}</a></td>
                        </tr>
                        <tr>
                            <td><a href="mailto:{{$email}}"> <i class="fas fa-envelope inside-icon yellow"></i> </a></td>
                            <td><a href="mailto:{{$email}}" class="contact-text">{{$email}}</a></td>
                        </tr>
                        <tr>
                            <td><a target="_blank" href="{{$website}}"> <i class="fas fa-globe inside-icon green"></i> </a></td>
                            <td><a target="_blank" href="{{$website}}" class="contact-text">{{$website}}</a></td>
                        </tr>
                        <tr>
                            <td><a target="_blank" href="tel:{{$phone}}"> <i class="fas fa-phone inside-icon red"></i> </a></td>
                            <td><a target="_blank" href="tel:{{$phone}}" class="contact-text" >+91 {{$phone}} </a></td>
                        </tr>
                    </tbody>
                </table>
                <div class="page-container" id="aboutus" style="margin-left:10px;">
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
        </div>
    </body>
</html>

