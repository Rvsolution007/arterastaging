<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Business Card 1</title>
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
	<link rel="stylesheet" type="text/css" href="{{asset('assets/profile-card/css/style.css')}}">
</head>
<body style="margin-top:20px;margin-right:-93px;margin-bottom:-80px;">
	<div class="profile-card card rounded-lg shadow p-4 text-center position-relative overflow-hidden" style="margin-bottom:-80px;">
		<div class="banner"></div>
		<img src="{{$image}}" alt="{{$name}}" class="img-circle mx-auto mb-3" style="background-color:white;">
		<div class="companyname" style="font-size:18px;">{{$comapany_name}}</div> 
		<span style="font-size:20px;" class="text-primary"><b>{{$name}}</b></span><br>
		<span style="margin-top:-20px;">({{$designation}})</span>
		<div class="text-left mb-4">
			<p class="mb-2"><i class="fa fa-envelope mr-2 text-primary"></i> <a target="_blank" href="mailto:{{$email}}" style="text-decoration:none;color:black;">{{$email}}</a></p>
			<p class="mb-2"><i class="fa fa-phone mr-2 text-primary"></i> <a href="tel:{{$phone}}" target="_blank" style="text-decoration:none;color:black;">+91 {{$phone}}</a></p>
			<p class="mb-2"><i class="fa fa-globe mr-2 text-primary"></i><a href="{{$website}}" target="_blank" style="text-decoration:none;color:black;"> {{$website}}</a></p>
			<p class="mb-2"><i class="fa fa-map-marker-alt mr-2 text-primary"></i> <a target="_blank" href="https://www.google.com/maps/place/{{$address}}" style="text-decoration:none;color:black;">{{$address}}</a></p>
		</div>
		<div class="social-links d-flex justify-content-center">
			<a href="https://www.facebook.com/{{$facebook}}" class="mx-2"><img src="{{asset('assets/profile-card/img/social/facebook.svg')}}" alt="Facebook"></a>
			<a href="https://www.instagram.com/{{$instagram}}" class="mx-2"><img src="{{asset('assets/profile-card/img/social/Instagram.png')}}" class="rounded-circle" alt="Instagram"></a>
			<a href="https://www.linkedin.com/{{$linkedin}}" class="mx-2"><img src="{{asset('assets/profile-card/img/social/linkedin.svg')}}" alt="Linkedin"></a>
			<a href="https://www.youtube.com/c/{{$youtube}}" class="mx-2"><img src="{{asset('assets/profile-card/img/social/youtube.svg')}}" alt="Youtube"></a>
			<a href="https://twitter.com/{{$twitter}}" class="mx-2"><img src="{{asset('assets/profile-card/img/social/twitter.png')}}" alt="Twitter"></a>
		</div>

		<p class="text-justify mt-3">{{$about_us}}</p>
	</div>
</body>
</html>