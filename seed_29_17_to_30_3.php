<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessProduct;
use App\Models\ProductType;
use App\Models\Brand;
use Illuminate\Support\Str;

$data = [
    [
        'category' => 'Education',
        'sub_category' => 'Exam Preparation Institute',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'UPSC Coaching', 'type' => 'Service', 'brands' => 'Vajiram & Ravi, Drishti IAS'],
            ['name' => 'GPSC Coaching', 'type' => 'Service', 'brands' => 'Chahal Academy'],
            ['name' => 'SSC Coaching', 'type' => 'Service', 'brands' => 'Career Power'],
            ['name' => 'Banking Exam Coaching', 'type' => 'Service', 'brands' => 'Mahendra\'s, Career Power'],
            ['name' => 'IBPS Preparation', 'type' => 'Service', 'brands' => 'Oliveboard'],
            ['name' => 'SBI PO Coaching', 'type' => 'Service', 'brands' => 'Career Power'],
            ['name' => 'RBI Grade B Coaching', 'type' => 'Service', 'brands' => 'Oliveboard'],
            ['name' => 'Railway Exam Coaching', 'type' => 'Service', 'brands' => 'Career Power'],
            ['name' => 'Police Exam Coaching', 'type' => 'Service', 'brands' => 'Chahal Academy'],
            ['name' => 'Defence Exam Coaching', 'type' => 'Service', 'brands' => 'Major Kalshi Classes'],
            ['name' => 'NDA Coaching', 'type' => 'Service', 'brands' => 'Centurion Defence Academy'],
            ['name' => 'CDS Coaching', 'type' => 'Service', 'brands' => 'Major Kalshi Classes'],
            ['name' => 'AFCAT Coaching', 'type' => 'Service', 'brands' => 'Cavalier India'],
            ['name' => 'CAPF Coaching', 'type' => 'Service', 'brands' => 'Vajiram & Ravi'],
            ['name' => 'CUET Coaching', 'type' => 'Service', 'brands' => 'Career Launcher'],
            ['name' => 'CLAT Coaching', 'type' => 'Service', 'brands' => 'LegalEdge'],
            ['name' => 'CAT Coaching', 'type' => 'Service', 'brands' => 'TIME, IMS'],
            ['name' => 'MAT Coaching', 'type' => 'Service', 'brands' => 'IMS'],
            ['name' => 'XAT Coaching', 'type' => 'Service', 'brands' => 'IMS'],
            ['name' => 'GMAT Coaching', 'type' => 'Service', 'brands' => 'Jamboree'],
            ['name' => 'GRE Coaching', 'type' => 'Service', 'brands' => 'Manya'],
            ['name' => 'IELTS Coaching', 'type' => 'Service', 'brands' => 'IDP Education'],
            ['name' => 'TOEFL Coaching', 'type' => 'Service', 'brands' => 'ETS'],
            ['name' => 'PTE Coaching', 'type' => 'Service', 'brands' => 'Pearson'],
            ['name' => 'NEET Coaching', 'type' => 'Service', 'brands' => 'Allen, Aakash'],
            ['name' => 'JEE Main Coaching', 'type' => 'Service', 'brands' => 'Allen, FIITJEE'],
            ['name' => 'JEE Advanced Coaching', 'type' => 'Service', 'brands' => 'Allen'],
            ['name' => 'Olympiad Coaching', 'type' => 'Service', 'brands' => 'SOF'],
            ['name' => 'Foundation Course', 'type' => 'Service', 'brands' => 'Allen'],
            ['name' => 'Test Series', 'type' => 'Service', 'brands' => 'Testbook, Allen'],
            ['name' => 'Mock Tests', 'type' => 'Service', 'brands' => 'Oliveboard'],
            ['name' => 'Online Classes', 'type' => 'Service', 'brands' => 'Unacademy'],
            ['name' => 'Recorded Lectures', 'type' => 'Service', 'brands' => 'Physics Wallah'],
            ['name' => 'Doubt Solving Sessions', 'type' => 'Service', 'brands' => 'PW'],
            ['name' => 'Study Material', 'type' => 'Physical Product', 'brands' => 'Arihant, Disha Publications'],
            ['name' => 'Current Affairs Classes', 'type' => 'Service', 'brands' => 'Drishti IAS'],
            ['name' => 'Interview Guidance', 'type' => 'Service', 'brands' => 'Chahal Academy'],
            ['name' => 'Career Counseling', 'type' => 'Service', 'brands' => 'Career Launcher'],
            ['name' => 'Scholarship Test', 'type' => 'Service', 'brands' => 'Allen'],
            ['name' => 'Admission Counseling', 'type' => 'Service', 'brands' => 'Institute'],
        ]
    ],
    [
        'category' => 'Education',
        'sub_category' => 'Robotics & STEM Academy',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Robotics for Kids', 'type' => 'Service', 'brands' => 'LEGO Education'],
            ['name' => 'Robotics for Teens', 'type' => 'Service', 'brands' => 'LEGO Education'],
            ['name' => 'STEM Education', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Coding for Kids', 'type' => 'Service', 'brands' => 'WhiteHat Jr, Code.org'],
            ['name' => 'Scratch Programming', 'type' => 'Service', 'brands' => 'MIT Scratch'],
            ['name' => 'Python for Kids', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Arduino Programming', 'type' => 'Service', 'brands' => 'Arduino'],
            ['name' => 'Raspberry Pi Projects', 'type' => 'Service', 'brands' => 'Raspberry Pi Foundation'],
            ['name' => 'IoT Training', 'type' => 'Service', 'brands' => 'Arduino'],
            ['name' => 'Artificial Intelligence for Kids', 'type' => 'Service', 'brands' => 'Google AI'],
            ['name' => 'Machine Learning Basics', 'type' => 'Service', 'brands' => 'IBM SkillsBuild'],
            ['name' => 'Electronics Fundamentals', 'type' => 'Service', 'brands' => 'Arduino'],
            ['name' => 'Sensor Programming', 'type' => 'Service', 'brands' => 'Arduino'],
            ['name' => 'Embedded Systems', 'type' => 'Service', 'brands' => 'Arduino'],
            ['name' => 'Drone Programming', 'type' => 'Service', 'brands' => 'DJI Education'],
            ['name' => 'Drone Building', 'type' => 'Service', 'brands' => 'DJI Education'],
            ['name' => '3D Printing', 'type' => 'Service', 'brands' => 'Creality'],
            ['name' => 'CAD Design', 'type' => 'Service', 'brands' => 'Autodesk'],
            ['name' => 'LEGO Robotics', 'type' => 'Service', 'brands' => 'LEGO Education'],
            ['name' => 'VEX Robotics', 'type' => 'Service', 'brands' => 'VEX Robotics'],
            ['name' => 'Coding Competition Training', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Robotics Competition Training', 'type' => 'Service', 'brands' => 'FIRST Robotics'],
            ['name' => 'Maker Lab', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Science Experiments', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Innovation Lab', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Project-Based Learning', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Summer STEM Camp', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Winter Camp', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Robotics Kit', 'type' => 'Physical Product', 'brands' => 'LEGO Education, STEMpedia'],
            ['name' => 'Arduino Kit', 'type' => 'Physical Product', 'brands' => 'Arduino'],
            ['name' => 'Raspberry Pi Kit', 'type' => 'Physical Product', 'brands' => 'Raspberry Pi Foundation'],
            ['name' => 'Electronics Kit', 'type' => 'Physical Product', 'brands' => 'STEMpedia'],
            ['name' => '3D Printer', 'type' => 'Physical Product', 'brands' => 'Creality'],
            ['name' => 'Online STEM Classes', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Teacher Training', 'type' => 'Service', 'brands' => 'LEGO Education'],
            ['name' => 'Parent Workshop', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Student Assessment', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Certification Program', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Career Counseling', 'type' => 'Service', 'brands' => 'STEM Academy'],
            ['name' => 'Admission Counseling', 'type' => 'Service', 'brands' => 'STEM Academy'],
        ]
    ],
    [
        'category' => 'Education',
        'sub_category' => 'Educational NGO / Training Foundation',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Free Education Program', 'type' => 'Service', 'brands' => 'Pratham, Teach For India'],
            ['name' => 'Literacy Program', 'type' => 'Service', 'brands' => 'Pratham'],
            ['name' => 'Adult Education', 'type' => 'Service', 'brands' => 'National Literacy Mission'],
            ['name' => 'Child Education Support', 'type' => 'Service', 'brands' => 'CRY'],
            ['name' => 'Underprivileged Student Support', 'type' => 'Service', 'brands' => 'Pratham'],
            ['name' => 'Scholarship Program', 'type' => 'Service', 'brands' => 'Vidyasaarathi'],
            ['name' => 'Girl Child Education', 'type' => 'Service', 'brands' => 'Educate Girls'],
            ['name' => 'Digital Literacy Program', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Computer Training', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Spoken English Training', 'type' => 'Service', 'brands' => 'British Council'],
            ['name' => 'Skill Development Program', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Vocational Training', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Entrepreneurship Training', 'type' => 'Service', 'brands' => 'Startup India'],
            ['name' => 'Career Counseling', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Teacher Training', 'type' => 'Service', 'brands' => 'British Council'],
            ['name' => 'Student Mentorship', 'type' => 'Service', 'brands' => 'Mentor Together'],
            ['name' => 'Career Guidance Workshops', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'Personality Development', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'Soft Skills Training', 'type' => 'Service', 'brands' => 'Dale Carnegie'],
            ['name' => 'Financial Literacy', 'type' => 'Service', 'brands' => 'RBI Financial Education'],
            ['name' => 'STEM Education', 'type' => 'Service', 'brands' => 'Atal Tinkering Labs'],
            ['name' => 'Robotics Workshops', 'type' => 'Service', 'brands' => 'STEMpedia'],
            ['name' => 'Coding Workshops', 'type' => 'Service', 'brands' => 'Code.org'],
            ['name' => 'Environmental Education', 'type' => 'Service', 'brands' => 'WWF India'],
            ['name' => 'Health Awareness Program', 'type' => 'Service', 'brands' => 'Red Cross'],
            ['name' => 'Women Empowerment Training', 'type' => 'Service', 'brands' => 'SEWA'],
            ['name' => 'Rural Education Program', 'type' => 'Service', 'brands' => 'Pratham'],
            ['name' => 'Community Learning Center', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'Library Development Program', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'School Adoption Program', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'Volunteer Program', 'type' => 'Service', 'brands' => 'Teach For India'],
            ['name' => 'Teacher Volunteer Training', 'type' => 'Service', 'brands' => 'NGO Program'],
            ['name' => 'Donation Management', 'type' => 'Service', 'brands' => 'NGO Foundation'],
            ['name' => 'CSR Education Program', 'type' => 'Service', 'brands' => 'NGO Foundation'],
            ['name' => 'Online Learning Support', 'type' => 'Service', 'brands' => 'Khan Academy'],
            ['name' => 'Educational Material Distribution', 'type' => 'Physical Product', 'brands' => 'NCERT'],
            ['name' => 'School Kit Distribution', 'type' => 'Physical Product', 'brands' => 'NGO Foundation'],
            ['name' => 'Awareness Campaign', 'type' => 'Service', 'brands' => 'NGO Foundation'],
            ['name' => 'Educational Events', 'type' => 'Service', 'brands' => 'NGO Foundation'],
            ['name' => 'Parent Counseling', 'type' => 'Service', 'brands' => 'NGO Foundation'],
        ]
    ],
    [
        'category' => 'Education',
        'sub_category' => 'Educational Testing & Assessment Center',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'IQ Assessment', 'type' => 'Service', 'brands' => 'Mensa India'],
            ['name' => 'Aptitude Test', 'type' => 'Service', 'brands' => 'Pearson TalentLens'],
            ['name' => 'Psychometric Assessment', 'type' => 'Service', 'brands' => 'SHL'],
            ['name' => 'Personality Assessment', 'type' => 'Service', 'brands' => 'SHL'],
            ['name' => 'Career Assessment', 'type' => 'Service', 'brands' => 'Mindler'],
            ['name' => 'Interest Assessment', 'type' => 'Service', 'brands' => 'Mindler'],
            ['name' => 'Learning Ability Test', 'type' => 'Service', 'brands' => 'Pearson'],
            ['name' => 'Skill Assessment', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'English Proficiency Test', 'type' => 'Service', 'brands' => 'Cambridge English'],
            ['name' => 'IELTS Test Registration', 'type' => 'Service', 'brands' => 'IDP Education'],
            ['name' => 'TOEFL Registration', 'type' => 'Service', 'brands' => 'ETS'],
            ['name' => 'PTE Registration', 'type' => 'Service', 'brands' => 'Pearson'],
            ['name' => 'Computer-Based Testing', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Online Proctored Examination', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Mock Test Series', 'type' => 'Service', 'brands' => 'Testbook'],
            ['name' => 'Competitive Exam Assessment', 'type' => 'Service', 'brands' => 'Oliveboard'],
            ['name' => 'School Assessment', 'type' => 'Service', 'brands' => 'ASSET (Educational Initiatives)'],
            ['name' => 'College Entrance Assessment', 'type' => 'Service', 'brands' => 'Pearson'],
            ['name' => 'Coding Skill Assessment', 'type' => 'Service', 'brands' => 'HackerRank'],
            ['name' => 'Technical Assessment', 'type' => 'Service', 'brands' => 'Mercer Mettl'],
            ['name' => 'Corporate Assessment', 'type' => 'Service', 'brands' => 'Mercer Mettl'],
            ['name' => 'Employee Skill Evaluation', 'type' => 'Service', 'brands' => 'Mercer Mettl'],
            ['name' => 'Interview Assessment', 'type' => 'Service', 'brands' => 'SHL'],
            ['name' => 'Leadership Assessment', 'type' => 'Service', 'brands' => 'SHL'],
            ['name' => 'Behavioral Assessment', 'type' => 'Service', 'brands' => 'SHL'],
            ['name' => 'Student Counseling', 'type' => 'Service', 'brands' => 'Mindler'],
            ['name' => 'Career Guidance', 'type' => 'Service', 'brands' => 'Mindler'],
            ['name' => 'Report Generation', 'type' => 'Service', 'brands' => 'Assessment Center'],
            ['name' => 'Digital Scorecard', 'type' => 'Service', 'brands' => 'Assessment Platform'],
            ['name' => 'Certification Examination', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Exam Registration', 'type' => 'Service', 'brands' => 'Assessment Center'],
            ['name' => 'Exam Scheduling', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Identity Verification', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Secure Examination Center', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Remote Examination Support', 'type' => 'Service', 'brands' => 'Pearson VUE'],
            ['name' => 'Result Analysis', 'type' => 'Service', 'brands' => 'Assessment Center'],
            ['name' => 'Performance Analytics', 'type' => 'Service', 'brands' => 'Assessment Center'],
            ['name' => 'Parent Consultation', 'type' => 'Service', 'brands' => 'Assessment Center'],
            ['name' => 'Institution Assessment Solutions', 'type' => 'Service', 'brands' => 'Mercer Mettl'],
            ['name' => 'Online Assessment Platform', 'type' => 'Software', 'brands' => 'Mercer Mettl'],
        ]
    ],
    [
        'category' => 'Travel & Tourism',
        'sub_category' => 'Travel Agency',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Domestic Tour Package', 'type' => 'Service', 'brands' => 'MakeMyTrip, Yatra'],
            ['name' => 'International Tour Package', 'type' => 'Service', 'brands' => 'Thomas Cook, SOTC'],
            ['name' => 'Honeymoon Package', 'type' => 'Service', 'brands' => 'Veena World'],
            ['name' => 'Family Tour Package', 'type' => 'Service', 'brands' => 'Kesari Tours'],
            ['name' => 'Group Tour Package', 'type' => 'Service', 'brands' => 'Veena World'],
            ['name' => 'Customized Holiday Package', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => 'Flight Ticket Booking', 'type' => 'Service', 'brands' => 'MakeMyTrip, EaseMyTrip'],
            ['name' => 'Hotel Booking', 'type' => 'Service', 'brands' => 'Booking.com, MakeMyTrip'],
            ['name' => 'Bus Ticket Booking', 'type' => 'Service', 'brands' => 'redBus'],
            ['name' => 'Train Ticket Booking', 'type' => 'Service', 'brands' => 'IRCTC'],
            ['name' => 'Cruise Booking', 'type' => 'Service', 'brands' => 'Royal Caribbean'],
            ['name' => 'Visa Assistance', 'type' => 'Service', 'brands' => 'VFS Global'],
            ['name' => 'Passport Assistance', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Travel Insurance', 'type' => 'Service', 'brands' => 'Tata AIG, ICICI Lombard'],
            ['name' => 'Foreign Exchange', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'Forex Card', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'International SIM Card', 'type' => 'Service', 'brands' => 'Matrix'],
            ['name' => 'Airport Transfer', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Car Rental', 'type' => 'Service', 'brands' => 'Zoomcar, Avis'],
            ['name' => 'Taxi Booking', 'type' => 'Service', 'brands' => 'Ola Outstation'],
            ['name' => 'Adventure Tour', 'type' => 'Service', 'brands' => 'Thrillophilia'],
            ['name' => 'Wildlife Tour', 'type' => 'Service', 'brands' => 'Jungle Lodges'],
            ['name' => 'Pilgrimage Tour', 'type' => 'Service', 'brands' => 'IRCTC Tourism'],
            ['name' => 'Educational Tour', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Corporate Travel Management', 'type' => 'Service', 'brands' => 'American Express GBT'],
            ['name' => 'MICE Tour', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'Cruise Holiday', 'type' => 'Service', 'brands' => 'Norwegian Cruise Line'],
            ['name' => 'Travel Itinerary Planning', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Hotel & Resort Packages', 'type' => 'Service', 'brands' => 'Club Mahindra'],
            ['name' => 'Tour Guide Booking', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Travel EMI', 'type' => 'Service', 'brands' => 'Bajaj Finserv'],
            ['name' => 'Student Tour Package', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Senior Citizen Tour', 'type' => 'Service', 'brands' => 'Veena World'],
            ['name' => 'Weekend Getaway', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => 'Bike Tour', 'type' => 'Service', 'brands' => 'Royal Enfield Rides'],
            ['name' => 'Trekking Package', 'type' => 'Service', 'brands' => 'Indiahikes'],
            ['name' => 'Camping Package', 'type' => 'Service', 'brands' => 'Thrillophilia'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => '24x7 Travel Support', 'type' => 'Service', 'brands' => 'Travel Agency'],
            ['name' => 'Travel Consultation', 'type' => 'Service', 'brands' => 'Travel Agency'],
        ]
    ],
    [
        'category' => 'Travel & Tourism',
        'sub_category' => 'Tour Operator',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Domestic Tour Operations', 'type' => 'Service', 'brands' => 'Kesari Tours'],
            ['name' => 'International Tour Operations', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'Customized Tour Planning', 'type' => 'Service', 'brands' => 'SOTC'],
            ['name' => 'Fixed Departure Tours', 'type' => 'Service', 'brands' => 'Veena World'],
            ['name' => 'Group Tours', 'type' => 'Service', 'brands' => 'Kesari Tours'],
            ['name' => 'Family Tours', 'type' => 'Service', 'brands' => 'MakeMyTrip Holidays'],
            ['name' => 'Honeymoon Tours', 'type' => 'Service', 'brands' => 'SOTC'],
            ['name' => 'Luxury Tours', 'type' => 'Service', 'brands' => 'Abercrombie & Kent'],
            ['name' => 'Budget Tours', 'type' => 'Service', 'brands' => 'Veena World'],
            ['name' => 'Adventure Tours', 'type' => 'Service', 'brands' => 'Thrillophilia'],
            ['name' => 'Trekking Tours', 'type' => 'Service', 'brands' => 'Indiahikes'],
            ['name' => 'Wildlife Safari Tours', 'type' => 'Service', 'brands' => 'Jungle Lodges'],
            ['name' => 'Desert Safari', 'type' => 'Service', 'brands' => 'Rajasthan Tourism'],
            ['name' => 'Pilgrimage Tours', 'type' => 'Service', 'brands' => 'IRCTC Tourism'],
            ['name' => 'Heritage Tours', 'type' => 'Service', 'brands' => 'Incredible India'],
            ['name' => 'Cultural Tours', 'type' => 'Service', 'brands' => 'Travel Operator'],
            ['name' => 'Eco Tourism', 'type' => 'Service', 'brands' => 'Kerala Tourism'],
            ['name' => 'Educational Tours', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Corporate Tours', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'Incentive Tours', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'MICE Tours', 'type' => 'Service', 'brands' => 'SOTC'],
            ['name' => 'Cruise Tours', 'type' => 'Service', 'brands' => 'Royal Caribbean'],
            ['name' => 'River Cruise Tours', 'type' => 'Service', 'brands' => 'Cordelia Cruises'],
            ['name' => 'Hotel Reservation', 'type' => 'Service', 'brands' => 'Booking.com'],
            ['name' => 'Flight Reservation', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => 'Bus Reservation', 'type' => 'Service', 'brands' => 'redBus'],
            ['name' => 'Train Reservation', 'type' => 'Service', 'brands' => 'IRCTC'],
            ['name' => 'Local Sightseeing', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Tour Guide Services', 'type' => 'Service', 'brands' => 'Licensed Guides'],
            ['name' => 'Airport Transfers', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Vehicle Arrangement', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Visa Assistance', 'type' => 'Service', 'brands' => 'VFS Global'],
            ['name' => 'Travel Insurance', 'type' => 'Service', 'brands' => 'Tata AIG'],
            ['name' => 'Forex Assistance', 'type' => 'Service', 'brands' => 'Thomas Cook'],
            ['name' => 'Event Tour Management', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Festival Tour Packages', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Religious Yatra Management', 'type' => 'Service', 'brands' => 'IRCTC Tourism'],
            ['name' => '24x7 Tour Support', 'type' => 'Service', 'brands' => 'Tour Operator'],
            ['name' => 'Online Tour Booking', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => 'Tour Consultation', 'type' => 'Service', 'brands' => 'Tour Operator'],
        ]
    ],
    [
        'category' => 'Travel & Tourism',
        'sub_category' => 'Hotel',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Deluxe Room', 'type' => 'Service', 'brands' => 'Taj Hotels, Marriott'],
            ['name' => 'Executive Room', 'type' => 'Service', 'brands' => 'Radisson Blu'],
            ['name' => 'Premium Room', 'type' => 'Service', 'brands' => 'Hyatt'],
            ['name' => 'Luxury Suite', 'type' => 'Service', 'brands' => 'The Oberoi'],
            ['name' => 'Presidential Suite', 'type' => 'Service', 'brands' => 'Taj Hotels'],
            ['name' => 'Family Room', 'type' => 'Service', 'brands' => 'Holiday Inn'],
            ['name' => 'Twin Room', 'type' => 'Service', 'brands' => 'ibis'],
            ['name' => 'King Room', 'type' => 'Service', 'brands' => 'Marriott'],
            ['name' => 'Single Room', 'type' => 'Service', 'brands' => 'Ginger Hotels'],
            ['name' => 'Double Room', 'type' => 'Service', 'brands' => 'Lemon Tree Hotels'],
            ['name' => 'Room Booking', 'type' => 'Service', 'brands' => 'Booking.com'],
            ['name' => 'Online Reservation', 'type' => 'Service', 'brands' => 'MakeMyTrip'],
            ['name' => 'Early Check-In', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Late Check-Out', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => '24-Hour Front Desk', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Room Service', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Housekeeping', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Laundry Service', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Dry Cleaning', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Restaurant', 'type' => 'Service', 'brands' => 'Hotel Restaurant'],
            ['name' => 'Multi-Cuisine Dining', 'type' => 'Service', 'brands' => 'Hotel Restaurant'],
            ['name' => 'Complimentary Breakfast', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Banquet Hall', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Conference Room', 'type' => 'Service', 'brands' => 'Marriott'],
            ['name' => 'Meeting Room', 'type' => 'Service', 'brands' => 'Radisson Blu'],
            ['name' => 'Wedding Venue', 'type' => 'Service', 'brands' => 'Taj Hotels'],
            ['name' => 'Swimming Pool', 'type' => 'Service', 'brands' => 'Hotel Facility'],
            ['name' => 'Spa & Wellness', 'type' => 'Service', 'brands' => 'Jiva Spa'],
            ['name' => 'Gym / Fitness Center', 'type' => 'Service', 'brands' => 'Hotel Facility'],
            ['name' => 'Free Wi-Fi', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Airport Pickup', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Airport Drop', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Car Rental', 'type' => 'Service', 'brands' => 'Avis'],
            ['name' => 'Travel Desk', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Concierge Service', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Business Center', 'type' => 'Service', 'brands' => 'Marriott'],
            ['name' => 'Valet Parking', 'type' => 'Service', 'brands' => 'Hotel Service'],
            ['name' => 'Pet Friendly Stay', 'type' => 'Service', 'brands' => 'Selected Hotels'],
            ['name' => 'Long Stay Packages', 'type' => 'Service', 'brands' => 'Marriott Executive Apartments'],
            ['name' => 'Corporate Booking', 'type' => 'Service', 'brands' => 'Hotel Sales Team'],
        ]
    ]
];

foreach ($data as $block) {
    // 1. Get/Create Category
    $category = BusinessCategory::firstOrCreate(
        ['name' => $block['category']],
        ['slug' => Str::slug($block['category']), 'status' => 1]
    );

    // 2. Get/Create Sub Category
    $subCategory = BusinessSubCategory::firstOrCreate(
        [
            'name' => $block['sub_category'],
            'business_category_id' => $category->id
        ],
        [
            'slug' => Str::slug($block['sub_category']),
            'has_business_type' => $block['has_business_type'],
            'status' => 1
        ]
    );

    // 3. Insert Products
    foreach ($block['products'] as $p) {
        $productType = ProductType::firstOrCreate(
            ['name' => $p['type']],
            ['slug' => Str::slug($p['type']), 'status' => 1]
        );

        $brandIds = [];
        if (!empty($p['brands']) && $p['brands'] !== 'None') {
            $brandsArr = array_map('trim', explode(',', $p['brands']));
            foreach ($brandsArr as $b) {
                $brandObj = Brand::firstOrCreate(
                    ['name' => $b],
                    ['slug' => Str::slug($b), 'status' => 1]
                );
                $brandIds[] = (string)$brandObj->id;
            }
        }

        BusinessProduct::updateOrCreate(
            [
                'name' => $p['name'],
                'business_category_id' => $category->id,
                'business_sub_category_id' => $subCategory->id,
            ],
            [
                'slug' => Str::slug($p['name']),
                'product_type_id' => $productType->id,
                'brand_id' => $brandIds, // the cast handles array to json automatically!
                'status' => 1,
            ]
        );
    }
}

echo "Successfully seeded Data for Education Sub Categories 29.17 to 29.26, and Travel & Tourism 30.1 to 30.3.\n";
