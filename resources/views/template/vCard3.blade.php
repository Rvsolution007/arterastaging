<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=divice-width, initial-scale=1.0">
  <title>Business Card 3</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
  <link rel="stylesheet" type="text/css" href="{{asset('assets/profile-card/css/main.css')}}">
  <style>
    .vcard.vcard-premium-03 .vcard-header .description p {
        max-height: 500px;
    }
    .social-icons{
        margin-top: 25px;
    }
    .social-icons a {
        display:inline-block;
        padding: 5px;
        height:2rem;
        width:2rem;
        background-color: #eaedff !important;
        border-radius : 100%;
        justify-content:center;
        font-size: 1.25rem;
        line-height: 1.5;
    }
    .subtitle{
        padding: 5px;
        background: #fff;
        border-radius: 10px !important;
        display: grid;
        width:88%;
        margin-left:20px;
    }
    .subtitle i {
        font-size: 1.25rem;
        border-radius: 100%;
        height: 50px;
        width: 50px;
        display: inline-block;
        text-align: center;
        justify-content: center;
        line-height: 1.8;
        background-color: #304CFD !important;
        color: #fff;
        margin-left:20px;
        margin-top:15px;
    }
  </style>
</head>
<body>
<div id="vcard-box" style="margin-top:-45px;margin-left:-45px;margin-right:-45px;margin-bottom:-45px;">
    <div class="vcard vcard-premium-03" style="max-width:700px;">
        <div class="vcard-details">
            <div class="vcard-header">
                <div class="bg-light overflow-hidden">
                    <div class="cover">
                        <div class="cover-img">
                            <span></span>
                        </div>
                    </div>
                    <div class="row row-0 profile">
                        <img src="https://demo-vcard-v2.dbcsoft.net/public/dist/images/premium-03/profile_bg.jpg?v=20221007221404165722" class="img-fluid" style="width: 100%;">
                        <div class="profile-image" style="margin-top:-60px;z-index:100;">
                            <img src="{{$image}}" alt="{{$name}}" class="rounded img-thumbnail">
                        </div>
                        <div class="mx-3" style="width:93%;margin-top:-60px;">
                            <div class="profile-title">
                                <h4 class="mt-0 mb-0 company-name">{{$name}}</h4>
                                <span class="user-status">{{$designation}}<br/> ({{$comapany_name}}) </span>
                                <div class="social-icons">
                                    <a href="https://twitter.com/{{$twitter}}" target="_blank">
                                        <i class="fa-brands fa-twitter"></i>
                                    </a>
                        
                                    <a href="https://wa.me/{{$whatsapp}}" target="_blank">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>

                                    <a href="https://www.facebook.com/{{$facebook}}" target="_blank">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>

                                    <a href="https://www.linkedin.com/{{$linkedin}}" target="_blank">
                                        <i class="fa-brands fa-linkedin"></i>
                                    </a>

                                    <a href="https://www.instagram.com/{{$instagram}}}" target="_blank">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light description">
                        <div class="vcard-block-title">
                            <h2 class="title"><b>About</b></h2>
                        </div>
                        <p>{{$about_us}}</p>
                    </div>
            </div>
        </div>
        <div class="vcard-contact__item" style="margin-bottom:-10px;">
            <div class="subtitle" style="width:91%;">
                <i class="fas fa-location-dot"></i>
                <div style="display:inline-block;line-height: 1.5;margin-left:10px;width:330px;justify-content: center;margin-top:20px;">
                    <span><b>Address</b></span>
                    <a href="https://www.google.com/maps/place/{{$address}}" target="_blank" class="media vcard-contact__link">{{$address}}</a>
                </div>
            </div>

            <div class="subtitle" style="margin-top:5px;width:91%;">
                <i class="fas fa-phone"></i>
                <div style="display:inline-block;line-height: 1.5;margin-left:10px;">
                    <span><b>Phone</b></span>
                    <a href="tel:{{$phone}}" class="media vcard-contact__link">{{$phone}}</a>
                </div>
            </div>
        
            <div class="subtitle" style="margin-top:5px;width:91%;">
                <i class="fas fa-envelope"></i>
                <div style="display:inline-block;line-height: 1.5;margin-left:10px;">
                    <span><b>Email</b></span>
                    <a href="mailto:{{$email}}" class="media vcard-contact__link">{{$email}}</a>
                </div>
            </div>
        
            <div class="subtitle" style="margin-top:5px;width:91%;">
                <i class="fas fa-link"></i>
                <div style="display:inline-block;line-height: 1.5;margin-left:10px;">
                    <span><b>Website</b></span>
                    <a href="{{$website}}" target="_blank" class="media vcard-contact__link">{{$website}}</a>
                </div>
            </div>
        </div>
    </div>
</div>              
</body>
</html>