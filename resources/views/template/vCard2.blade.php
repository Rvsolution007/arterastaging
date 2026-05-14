<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=divice-width, initial-scale=1.0">
  <title>Business Card 2</title>
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
  <style media="screen">
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        /* background-color: #EA9684; */
        font-family: Arial, Helvetica, sans-serif;
        background: #d5e1ef;

    }

    main {
        overflow: hidden;
        background-color: #fdfcfc;
        font-size: 16px;
        line-height: 22px;
        color: #000;
        border-radius: 7px;
        box-shadow: -4px -4px 20px rgba(0, 0, 0, 0.505);
        margin-bottom: -20px;
    }

    .top-card {
        width: 100%;
        /* height: 200px; */
    }

    .top-card img {
        width: 150px;
        height: 150px;
        margin-left: 180px;
        margin-top: 20px;
        border-radius:50%;
    }

    .top-card .menu-icon {
        position: relative;

        bottom: 13.7em;
        font-size: 17px;
        cursor: pointer;
    }



    .top-card .menu-icon .item1 {
        margin-left: 10px;
        margin-top: 15px;
        color: red;
    }

    .top-card .menu-icon .item2 {
    float: right;
    margin-right: 20px;
    margin-top: -10px;
    color: blue;

    }

    

    h1 {
        font-size: 22px;
        color: #000;
        font-weight: 600;
        margin: 12px 0;
        text-align: center;

    }


    .middle-card, footer {
        margin: 5px 25px;
    }

    .middle-card {
        text-align: justify;
    }

    footer {
        text-align: center;
    }

    footer .social-icon {
        /* padding: 7%; */
        font-size: 20px;
        color: rgba(0, 0, 0, .9);
    }

    .facebook:hover {color:#3b5999;}
    .twitter:hover {color: #55acee;}
    .google:hover {color: #dd4b39;}
    .github:hover {color: #302f2f;}
    .linkedin:hover {color: #0077B5;}

    footer .links {
        border-top: 2px solid rgba(0, 0, 0, .1);
        text-align: center;
        margin-top: 10px;
        padding: 8px 0;

    }

    button{
    width: 110px;
    height: 37px;
    margin-top: 10px;
    border-radius: 2px;
    background: #1990cc;
    font-size: 15px;
    border: none;
    color: white;
    }
    button:last-child{
    margin-left: 40px;
    border: 2px solid #1990cc;
    background: white;
    color: black;
    }
  </style>
</head>
<body>
  <main style="background-color:#ccc2ba;">
    <section class="top-card">
      <img src="{{$image}}" alt="{{$name}}" style="background-color:white;">
    </section>

    <section class="middle-card">
      <div style="width:100%;text-align:center;font-size:18px;">{{$comapany_name}}</div>
      <h1>{{$name}}</h1>
      <div style="width:100%;text-align:center;font-size:15px;margin-top:-10px;margin-bottom:20px;">({{$designation}})</div>
      <p style="line-height:25px;">{{$about_us}}</p>
    </section>

    <footer class="pb-3">
      <a href="https://www.facebook.com/{{$facebook}}" class="social-icon facebook"><img src="{{asset('assets/profile-card/img/social/facebook.svg')}}" alt="Facebook" height="30" width="30"></a>
      <a href="https://www.linkedin.com/{{$linkedin}}" class="social-icon facebook"><img src="{{asset('assets/profile-card/img/social/linkedin.svg')}}" alt="Linkedin" height="30" width="30"></a>
      <a href="https://www.youtube.com/c/{{$youtube}}" class="social-icon facebook"><img src="{{asset('assets/profile-card/img/social/youtube.svg')}}" alt="Youtube" height="30" width="30"></a>
      <a href="https://www.twitter.com/{{$twitter}}" class="social-icon facebook"><img src="{{asset('assets/profile-card/img/social/twitter.png')}}" alt="Twitter" height="30" width="30"></a>
      <a href="https://www.instagram.com/{{$instagram}}" class="social-icon facebook"><img src="{{asset('assets/profile-card/img/social/Instagram.png')}}" alt="Instagram" height="30" width="30"></a>
    </footer>

    <section class="middle-card">
      <div class="text-left mb-4">
        <div class="mb-3"><i class="fa fa-envelope fa-lg mr-2 text-primary"></i> <a target="_blank" href="mailto:{{$email}}" style="text-decoration:none;color:black;">{{$email}}</a></div>
        <div class="mb-3"><i class="fa fa-phone mr-2 fa-lg text-primary"></i> <a href="tel:{{$phone}}" target="_blank" style="text-decoration:none;color:black;">+91 {{$phone}}</a></div>
        <div class="mb-3"><i class="fa fa-globe mr-2 fa-lg text-primary"></i><a href="{{$website}}" target="_blank" style="text-decoration:none;color:black;"> {{$website}}</a></div>
        <div class="mb-3"><i class="fa fa-map-marker-alt fa-lg mr-2 text-primary"></i> <a target="_blank" href="https://www.google.com/maps/place/{{$address}}" style="text-decoration:none;color:black;">{{$address}}</a></div>
      </div>
    </section>
  </main>
</body>
</html>