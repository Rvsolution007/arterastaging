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
        'category' => 'Events & Entertainment',
        'sub_category' => 'Movie Theatre / Multiplex',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Movie Ticket Booking', 'type' => 'Service', 'brands' => 'PVR INOX'],
            ['name' => 'Online Ticket Booking', 'type' => 'Service', 'brands' => 'BookMyShow'],
            ['name' => 'IMAX Experience', 'type' => 'Service', 'brands' => 'IMAX'],
            ['name' => '4DX Experience', 'type' => 'Service', 'brands' => 'CJ 4DPLEX'],
            ['name' => 'Recliner Seats', 'type' => 'Service', 'brands' => 'PVR INOX'],
            ['name' => 'Premium Lounge', 'type' => 'Service', 'brands' => 'PVR Director\'s Cut'],
            ['name' => 'Gold Class Seating', 'type' => 'Service', 'brands' => 'Cinepolis'],
            ['name' => 'Dolby Atmos Experience', 'type' => 'Service', 'brands' => 'Dolby'],
            ['name' => '3D Movie Screening', 'type' => 'Service', 'brands' => 'RealD 3D'],
            ['name' => '2D Movie Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Private Screening', 'type' => 'Service', 'brands' => 'PVR INOX'],
            ['name' => 'Corporate Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'School Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Film Festival Hosting', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Live Sports Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Concert Live Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Popcorn Combo', 'type' => 'Physical Product', 'brands' => 'PVR INOX'],
            ['name' => 'Nachos Combo', 'type' => 'Physical Product', 'brands' => 'Cinepolis'],
            ['name' => 'Soft Drinks', 'type' => 'Physical Product', 'brands' => 'Coca-Cola'],
            ['name' => 'Coffee & Beverages', 'type' => 'Physical Product', 'brands' => 'Café'],
            ['name' => 'Candy & Chocolates', 'type' => 'Physical Product', 'brands' => 'Cadbury'],
            ['name' => 'Ice Cream', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Food Court', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Parking Facility', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Wheelchair Assistance', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Online Seat Selection', 'type' => 'Service', 'brands' => 'BookMyShow'],
            ['name' => 'Gift Cards', 'type' => 'Physical Product', 'brands' => 'PVR INOX'],
            ['name' => 'Membership Program', 'type' => 'Service', 'brands' => 'PVR Passport'],
            ['name' => 'Loyalty Rewards', 'type' => 'Service', 'brands' => 'Club Cinepolis'],
            ['name' => 'Movie Merchandise', 'type' => 'Physical Product', 'brands' => 'Theatre Store'],
            ['name' => 'Birthday Party Screening', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Kids Movie Events', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Couple Movie Package', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Student Discount', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Senior Citizen Discount', 'type' => 'Service', 'brands' => 'Multiplex'],
            ['name' => 'Mobile App Booking', 'type' => 'Software', 'brands' => 'PVR INOX'],
            ['name' => 'Self Check-in Kiosk', 'type' => 'Software', 'brands' => 'Multiplex'],
            ['name' => 'Digital Ticket', 'type' => 'Service', 'brands' => 'BookMyShow'],
            ['name' => 'Snack Pre-Order', 'type' => 'Service', 'brands' => 'PVR INOX'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Multiplex'],
        ]
    ],
    [
        'category' => 'Events & Entertainment',
        'sub_category' => 'Live Music & Concert Organizer',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Concert Planning', 'type' => 'Service', 'brands' => 'Wizcraft'],
            ['name' => 'Live Music Event', 'type' => 'Service', 'brands' => 'BookMyShow Live'],
            ['name' => 'Singer Booking', 'type' => 'Service', 'brands' => 'Artist Management'],
            ['name' => 'Band Booking', 'type' => 'Service', 'brands' => 'Artist Management'],
            ['name' => 'Orchestra Arrangement', 'type' => 'Service', 'brands' => 'Artist Management'],
            ['name' => 'DJ Concert', 'type' => 'Service', 'brands' => 'Pioneer DJ'],
            ['name' => 'EDM Festival', 'type' => 'Service', 'brands' => 'Sunburn'],
            ['name' => 'Classical Music Concert', 'type' => 'Service', 'brands' => 'SPIC MACAY'],
            ['name' => 'Folk Music Event', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Cultural Music Festival', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Corporate Concert', 'type' => 'Service', 'brands' => 'Percept'],
            ['name' => 'College Concert', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'School Music Event', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Artist Management', 'type' => 'Service', 'brands' => 'Collective Artists Network'],
            ['name' => 'Celebrity Performance', 'type' => 'Service', 'brands' => 'Celebrity Manager'],
            ['name' => 'Stage Design', 'type' => 'Service', 'brands' => 'Event Vendor'],
            ['name' => 'Sound System', 'type' => 'Service', 'brands' => 'JBL Professional'],
            ['name' => 'LED Wall Setup', 'type' => 'Service', 'brands' => 'Event Vendor'],
            ['name' => 'Lighting Setup', 'type' => 'Service', 'brands' => 'Philips Entertainment'],
            ['name' => 'Laser Show', 'type' => 'Service', 'brands' => 'Laserworld'],
            ['name' => 'Smoke Effects', 'type' => 'Service', 'brands' => 'Chauvet DJ'],
            ['name' => 'Fireworks Show', 'type' => 'Service', 'brands' => 'MagicFX'],
            ['name' => 'Ticket Booking', 'type' => 'Service', 'brands' => 'BookMyShow'],
            ['name' => 'VIP Pass', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Backstage Pass', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Security Management', 'type' => 'Service', 'brands' => 'Event Security'],
            ['name' => 'Crowd Management', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Food Court Management', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Merchandise Stall', 'type' => 'Physical Product', 'brands' => 'Event Merchandise'],
            ['name' => 'Sponsor Management', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Live Streaming', 'type' => 'Service', 'brands' => 'YouTube Live'],
            ['name' => 'Photography', 'type' => 'Service', 'brands' => 'Canon Professionals'],
            ['name' => 'Videography', 'type' => 'Service', 'brands' => 'Sony'],
            ['name' => 'Drone Coverage', 'type' => 'Service', 'brands' => 'DJI'],
            ['name' => 'Venue Booking', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Online Registration', 'type' => 'Service', 'brands' => 'Eventbrite'],
            ['name' => 'Event Promotion', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Customized Concert Package', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => 'Artist Hospitality', 'type' => 'Service', 'brands' => 'Organizer'],
            ['name' => '24×7 Event Support', 'type' => 'Service', 'brands' => 'Organizer'],
        ]
    ],
    [
        'category' => 'Events & Entertainment',
        'sub_category' => 'Kids Play Area / Indoor Play Zone',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Indoor Play Area', 'type' => 'Service', 'brands' => 'Fun City'],
            ['name' => 'Soft Play Zone', 'type' => 'Service', 'brands' => 'KidZania'],
            ['name' => 'Toddler Play Area', 'type' => 'Service', 'brands' => 'Play \'N\' Learn'],
            ['name' => 'Ball Pool', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Trampoline Park', 'type' => 'Service', 'brands' => 'SkyJumper'],
            ['name' => 'Obstacle Course', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Adventure Play Zone', 'type' => 'Service', 'brands' => 'Fun City'],
            ['name' => 'Climbing Wall', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Slides & Tunnels', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Interactive Games', 'type' => 'Service', 'brands' => 'Timezone'],
            ['name' => 'Arcade Games', 'type' => 'Service', 'brands' => 'Timezone'],
            ['name' => 'VR Games', 'type' => 'Service', 'brands' => 'Zero Latency'],
            ['name' => 'Kids Birthday Party', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Theme Party Package', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'School Group Visit', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Summer Camp', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Creative Workshops', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Art & Craft Activity', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Storytelling Session', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Puppet Show', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Magic Show', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Face Painting', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Kids Dance Activity', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Mini Theatre', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Café for Parents', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Kids Snack Counter', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Membership Program', 'type' => 'Service', 'brands' => 'Fun City'],
            ['name' => 'Daily Entry Pass', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Monthly Pass', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Annual Membership', 'type' => 'Service', 'brands' => 'Fun City'],
            ['name' => 'Gift Voucher', 'type' => 'Physical Product', 'brands' => 'Play Zone'],
            ['name' => 'Party Decoration', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Photography', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Return Gift Packages', 'type' => 'Physical Product', 'brands' => 'Play Zone'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Mobile App Booking', 'type' => 'Software', 'brands' => 'Play Zone'],
            ['name' => 'CCTV Monitoring', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Child Safety Supervision', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => 'Parent Waiting Lounge', 'type' => 'Service', 'brands' => 'Play Zone'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Play Zone'],
        ]
    ],
    [
        'category' => 'Events & Entertainment',
        'sub_category' => 'Escape Room',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Mystery Escape Room', 'type' => 'Service', 'brands' => 'Mystery Rooms'],
            ['name' => 'Horror Escape Room', 'type' => 'Service', 'brands' => 'Mystery Rooms'],
            ['name' => 'Detective Escape Game', 'type' => 'Service', 'brands' => 'Escape Room India'],
            ['name' => 'Prison Break Escape', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Zombie Escape Challenge', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Adventure Escape Room', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Sci-Fi Escape Room', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Treasure Hunt Escape', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'VR Escape Room', 'type' => 'Service', 'brands' => 'Zero Latency'],
            ['name' => 'Family Escape Room', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Kids Escape Room', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Corporate Team Building', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Birthday Party Package', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'School Group Booking', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'College Group Booking', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Couple Challenge', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Friends Group Challenge', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Time Challenge Game', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Puzzle Solving Experience', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Live Actor Experience', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Themed Game Rooms', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Multi-Level Escape Challenge', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Photo Booth', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Digital Scoreboard', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Winner Certificate', 'type' => 'Physical Product', 'brands' => 'Escape Room'],
            ['name' => 'Gift Voucher', 'type' => 'Physical Product', 'brands' => 'Escape Room'],
            ['name' => 'Membership Program', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Tournament Events', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Corporate Competition', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Mobile App Booking', 'type' => 'Software', 'brands' => 'Escape Room'],
            ['name' => 'Event Hosting', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Snacks & Beverages', 'type' => 'Physical Product', 'brands' => 'Café'],
            ['name' => 'Merchandise', 'type' => 'Physical Product', 'brands' => 'Escape Room Store'],
            ['name' => 'Puzzle Merchandise', 'type' => 'Physical Product', 'brands' => 'Escape Room'],
            ['name' => 'CCTV Security', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Game Master Assistance', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Customized Escape Game', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => 'Group Discount Package', 'type' => 'Service', 'brands' => 'Escape Room'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Escape Room'],
        ]
    ],
    [
        'category' => 'Events & Entertainment',
        'sub_category' => 'VR Gaming Center',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'VR Gaming Session', 'type' => 'Service', 'brands' => 'Meta Quest'],
            ['name' => 'Multiplayer VR Games', 'type' => 'Service', 'brands' => 'Zero Latency'],
            ['name' => 'VR Zombie Game', 'type' => 'Service', 'brands' => 'Zero Latency'],
            ['name' => 'VR Shooting Game', 'type' => 'Service', 'brands' => 'HTC Vive'],
            ['name' => 'VR Racing Simulator', 'type' => 'Service', 'brands' => 'Playseat'],
            ['name' => 'VR Flight Simulator', 'type' => 'Service', 'brands' => 'Meta Quest'],
            ['name' => 'VR Roller Coaster', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Escape Room', 'type' => 'Service', 'brands' => 'Zero Latency'],
            ['name' => 'VR Adventure Experience', 'type' => 'Service', 'brands' => 'HTC Vive'],
            ['name' => 'VR Sports Games', 'type' => 'Service', 'brands' => 'PlayStation VR'],
            ['name' => 'VR Cricket', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Football', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Boxing', 'type' => 'Service', 'brands' => 'Meta Quest'],
            ['name' => 'VR Fitness', 'type' => 'Service', 'brands' => 'Supernatural VR'],
            ['name' => 'VR Education Experience', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Space Exploration', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Horror Experience', 'type' => 'Service', 'brands' => 'HTC Vive'],
            ['name' => 'VR Kids Zone', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Birthday Party Package', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Corporate Team Building', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'School Group Booking', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'College Group Booking', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Gaming Tournament', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Esports Event', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Private Gaming Room', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Hourly Gaming Pass', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Daily Gaming Pass', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Membership Program', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'VR Headset Rental', 'type' => 'Physical Product', 'brands' => 'Meta Quest'],
            ['name' => 'VR Controller Rental', 'type' => 'Physical Product', 'brands' => 'Meta Quest'],
            ['name' => 'Motion Platform Experience', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Haptic Gaming Experience', 'type' => 'Service', 'brands' => 'bHaptics'],
            ['name' => 'Gaming Café', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Snacks & Beverages', 'type' => 'Physical Product', 'brands' => 'Café'],
            ['name' => 'Gift Voucher', 'type' => 'Physical Product', 'brands' => 'VR Center'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Mobile App Booking', 'type' => 'Software', 'brands' => 'VR Center'],
            ['name' => 'Game Recording', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => 'Photo & Video Capture', 'type' => 'Service', 'brands' => 'VR Center'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'VR Center'],
        ]
    ],
    [
        'category' => 'Events & Entertainment',
        'sub_category' => 'Gaming Café / Esports Arena',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'PC Gaming', 'type' => 'Service', 'brands' => 'Alienware'],
            ['name' => 'Console Gaming', 'type' => 'Service', 'brands' => 'Sony PlayStation'],
            ['name' => 'PS5 Gaming', 'type' => 'Service', 'brands' => 'Sony PlayStation'],
            ['name' => 'Xbox Gaming', 'type' => 'Service', 'brands' => 'Microsoft Xbox'],
            ['name' => 'Nintendo Switch Gaming', 'type' => 'Service', 'brands' => 'Nintendo'],
            ['name' => 'High-End Gaming PC', 'type' => 'Service', 'brands' => 'ASUS ROG'],
            ['name' => 'VR Gaming', 'type' => 'Service', 'brands' => 'Meta Quest'],
            ['name' => 'Racing Simulator', 'type' => 'Service', 'brands' => 'Logitech G'],
            ['name' => 'Flight Simulator', 'type' => 'Service', 'brands' => 'Thrustmaster'],
            ['name' => 'Esports Tournament', 'type' => 'Service', 'brands' => 'ESL'],
            ['name' => 'LAN Tournament', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'PUBG Tournament', 'type' => 'Service', 'brands' => 'Krafton'],
            ['name' => 'BGMI Tournament', 'type' => 'Service', 'brands' => 'Krafton'],
            ['name' => 'Valorant Tournament', 'type' => 'Service', 'brands' => 'Riot Games'],
            ['name' => 'Counter-Strike Tournament', 'type' => 'Service', 'brands' => 'Valve'],
            ['name' => 'FIFA Tournament', 'type' => 'Service', 'brands' => 'EA Sports'],
            ['name' => 'DOTA 2 Tournament', 'type' => 'Service', 'brands' => 'Valve'],
            ['name' => 'League of Legends Tournament', 'type' => 'Service', 'brands' => 'Riot Games'],
            ['name' => 'Streaming Booth', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Game Streaming Setup', 'type' => 'Service', 'brands' => 'Elgato'],
            ['name' => 'Content Creator Room', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Coaching Sessions', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Gaming Membership', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Hourly Gaming Pass', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Daily Pass', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Monthly Membership', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Private Gaming Room', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Team Practice Room', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Gaming Accessories', 'type' => 'Physical Product', 'brands' => 'Logitech G, Razer'],
            ['name' => 'Gaming Keyboard', 'type' => 'Physical Product', 'brands' => 'Logitech G'],
            ['name' => 'Gaming Mouse', 'type' => 'Physical Product', 'brands' => 'Razer'],
            ['name' => 'Gaming Headset', 'type' => 'Physical Product', 'brands' => 'HyperX'],
            ['name' => 'Snacks & Beverages', 'type' => 'Physical Product', 'brands' => 'Café'],
            ['name' => 'Merchandise', 'type' => 'Physical Product', 'brands' => 'Gaming Store'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Mobile App Booking', 'type' => 'Software', 'brands' => 'Gaming Café'],
            ['name' => 'Loyalty Rewards', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Birthday Gaming Party', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => 'Corporate Gaming Event', 'type' => 'Service', 'brands' => 'Gaming Café'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Gaming Café'],
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

echo "Successfully seeded Data for Events & Entertainment Sub Categories 31.15 to 31.20.\n";
