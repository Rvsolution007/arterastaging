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
        'category' => 'Health & Medical',
        'sub_category' => 'Plastic Surgery & Cosmetic Surgery Clinic',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Plastic Surgeon Consultation', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics, Kaya Clinic'],
            ['name' => 'Cosmetic Surgery Consultation', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Rhinoplasty (Nose Surgery)', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Septoplasty', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Blepharoplasty (Eyelid Surgery)', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Facelift Surgery', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Neck Lift Surgery', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Brow Lift Surgery', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Chin Augmentation', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Lip Augmentation', 'type' => 'Service', 'brands' => 'Juvederm'],
            ['name' => 'Botox Treatment', 'type' => 'Service', 'brands' => 'Allergan'],
            ['name' => 'Dermal Fillers', 'type' => 'Service', 'brands' => 'Juvederm, Restylane'],
            ['name' => 'Thread Lift', 'type' => 'Service', 'brands' => 'Aptos'],
            ['name' => 'Chemical Peel', 'type' => 'Service', 'brands' => 'Kaya Clinic'],
            ['name' => 'HydraFacial', 'type' => 'Service', 'brands' => 'HydraFacial'],
            ['name' => 'Laser Skin Resurfacing', 'type' => 'Service', 'brands' => 'Candela'],
            ['name' => 'Scar Revision Surgery', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Burn Reconstruction', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Hand Surgery', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Ear Correction Surgery (Otoplasty)', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Gynecomastia Surgery', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Liposuction', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Tummy Tuck (Abdominoplasty)', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Mommy Makeover', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Breast Augmentation', 'type' => 'Service', 'brands' => 'Motiva, Mentor'],
            ['name' => 'Breast Reduction', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Breast Lift', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Hair Transplant (FUE)', 'type' => 'Service', 'brands' => 'Eugenix Hair Sciences'],
            ['name' => 'Hair Transplant (FUT)', 'type' => 'Service', 'brands' => 'Eugenix Hair Sciences'],
            ['name' => 'PRP Hair Therapy', 'type' => 'Service', 'brands' => 'QR678'],
            ['name' => 'GFC Hair Therapy', 'type' => 'Service', 'brands' => 'QR678'],
            ['name' => 'Laser Hair Removal', 'type' => 'Service', 'brands' => 'Candela'],
            ['name' => 'Mole Removal', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Wart Removal', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Tattoo Removal', 'type' => 'Service', 'brands' => 'Candela'],
            ['name' => 'Skin Tightening', 'type' => 'Service', 'brands' => 'Ultherapy'],
            ['name' => 'Body Contouring', 'type' => 'Service', 'brands' => 'CoolSculpting'],
            ['name' => 'Cosmetic Follow-up Consultation', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
            ['name' => 'Online Cosmetic Consultation', 'type' => 'Service', 'brands' => 'Apollo 24/7'],
            ['name' => 'Cosmetic Surgery Package', 'type' => 'Service', 'brands' => 'Apollo Cosmetic Clinics'],
        ]
    ],
    [
        'category' => 'Health & Medical',
        'sub_category' => 'Medical Laboratory',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Complete Blood Count (CBC)', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs, SRL Diagnostics'],
            ['name' => 'Blood Sugar Test', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'HbA1c Test', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Lipid Profile', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Liver Function Test (LFT)', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Kidney Function Test (KFT)', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Thyroid Function Test', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Vitamin D Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Vitamin B12 Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Iron Profile', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Urine Routine Test', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Urine Culture', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Stool Examination', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Pregnancy Test', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Dengue Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Malaria Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Typhoid Test', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'COVID-19 Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'HIV Test', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Hepatitis Profile', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Allergy Test', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Hormone Profile', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Cancer Marker Test', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Cardiac Marker Test', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Coagulation Profile', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Histopathology', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Cytology', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Biopsy Analysis', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Semen Analysis', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Microbiology Test', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Culture & Sensitivity Test', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Home Sample Collection', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Corporate Health Checkup', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Preventive Health Package', 'type' => 'Service', 'brands' => 'Metropolis Healthcare'],
            ['name' => 'Full Body Checkup', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Senior Citizen Health Package', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Women\'s Health Package', 'type' => 'Service', 'brands' => 'Thyrocare'],
            ['name' => 'Online Report Access', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Tele Consultation Referral', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Follow-up Test Consultation', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
        ]
    ],
    [
        'category' => 'Health & Medical',
        'sub_category' => 'Health Insurance Advisor / TPA',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Individual Health Insurance', 'type' => 'Service', 'brands' => 'Star Health, Niva Bupa, HDFC ERGO'],
            ['name' => 'Family Floater Health Insurance', 'type' => 'Service', 'brands' => 'Care Health, ICICI Lombard'],
            ['name' => 'Senior Citizen Health Insurance', 'type' => 'Service', 'brands' => 'Star Health, Care Health'],
            ['name' => 'Critical Illness Insurance', 'type' => 'Service', 'brands' => 'HDFC ERGO, ICICI Lombard'],
            ['name' => 'Cancer Insurance', 'type' => 'Service', 'brands' => 'HDFC Life'],
            ['name' => 'Personal Accident Insurance', 'type' => 'Service', 'brands' => 'Tata AIG'],
            ['name' => 'Cashless Hospital Assistance', 'type' => 'Service', 'brands' => 'Medi Assist, Vidal Health'],
            ['name' => 'Health Insurance Consultation', 'type' => 'Service', 'brands' => 'Policybazaar'],
            ['name' => 'Policy Comparison', 'type' => 'Service', 'brands' => 'Policybazaar'],
            ['name' => 'Policy Purchase Assistance', 'type' => 'Service', 'brands' => 'Ditto Insurance'],
            ['name' => 'Policy Renewal', 'type' => 'Service', 'brands' => 'Star Health'],
            ['name' => 'Policy Portability', 'type' => 'Service', 'brands' => 'IRDAI Guidelines'],
            ['name' => 'Cashless Claim Assistance', 'type' => 'Service', 'brands' => 'Medi Assist'],
            ['name' => 'Reimbursement Claim Assistance', 'type' => 'Service', 'brands' => 'Vidal Health'],
            ['name' => 'Claim Documentation', 'type' => 'Service', 'brands' => 'Medi Assist'],
            ['name' => 'Pre-Authorization Support', 'type' => 'Service', 'brands' => 'Vidal Health'],
            ['name' => 'Hospital Network Assistance', 'type' => 'Service', 'brands' => 'Star Health'],
            ['name' => 'TPA Coordination', 'type' => 'Service', 'brands' => 'Medi Assist, Vidal Health'],
            ['name' => 'Claim Status Tracking', 'type' => 'Service', 'brands' => 'Medi Assist'],
            ['name' => 'Health Card Assistance', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'OPD Cover Consultation', 'type' => 'Service', 'brands' => 'Niva Bupa'],
            ['name' => 'Maternity Cover Consultation', 'type' => 'Service', 'brands' => 'Care Health'],
            ['name' => 'Corporate Health Insurance', 'type' => 'Service', 'brands' => 'ICICI Lombard'],
            ['name' => 'Group Mediclaim', 'type' => 'Service', 'brands' => 'New India Assurance'],
            ['name' => 'Top-Up Health Insurance', 'type' => 'Service', 'brands' => 'HDFC ERGO'],
            ['name' => 'Super Top-Up Health Insurance', 'type' => 'Service', 'brands' => 'Care Health'],
            ['name' => 'Wellness Benefits Guidance', 'type' => 'Service', 'brands' => 'Niva Bupa'],
            ['name' => 'Preventive Health Checkup Benefits', 'type' => 'Service', 'brands' => 'Star Health'],
            ['name' => 'Tax Benefit Consultation', 'type' => 'Service', 'brands' => 'Insurance Advisor'],
            ['name' => 'Nominee Update', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'Policy Endorsement', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'Online Claim Submission', 'type' => 'Service', 'brands' => 'Medi Assist'],
            ['name' => 'Digital Policy Download', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'E-Card Generation', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'Health Insurance Review', 'type' => 'Service', 'brands' => 'Ditto Insurance'],
            ['name' => 'Family Health Plan Consultation', 'type' => 'Service', 'brands' => 'Policybazaar'],
            ['name' => 'Telephonic Insurance Support', 'type' => 'Service', 'brands' => 'Insurance Provider'],
            ['name' => 'Video Consultation', 'type' => 'Service', 'brands' => 'Policybazaar'],
            ['name' => 'Annual Policy Review', 'type' => 'Service', 'brands' => 'Insurance Advisor'],
            ['name' => 'Renewal Reminder Service', 'type' => 'Service', 'brands' => 'Insurance Advisor'],
        ]
    ],
    [
        'category' => 'Health & Medical',
        'sub_category' => 'Organ Donation & Transplant Center',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Organ Transplant Consultation', 'type' => 'Service', 'brands' => 'Apollo Hospitals, Fortis'],
            ['name' => 'Kidney Transplant', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Liver Transplant', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Heart Transplant', 'type' => 'Service', 'brands' => 'Fortis'],
            ['name' => 'Lung Transplant', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Pancreas Transplant', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Bone Marrow Transplant', 'type' => 'Service', 'brands' => 'Tata Memorial Hospital'],
            ['name' => 'Stem Cell Transplant', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Cornea Transplant', 'type' => 'Service', 'brands' => 'LV Prasad Eye Institute'],
            ['name' => 'Skin Graft Surgery', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Organ Donation Registration', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'Living Donor Evaluation', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Deceased Donor Program', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'Tissue Donation', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'Organ Matching', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'HLA Typing', 'type' => 'Service', 'brands' => 'Apollo Diagnostics'],
            ['name' => 'Cross Match Testing', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Blood Group Matching', 'type' => 'Service', 'brands' => 'Dr. Lal PathLabs'],
            ['name' => 'Immunology Testing', 'type' => 'Service', 'brands' => 'SRL Diagnostics'],
            ['name' => 'Transplant Coordinator Support', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Donor Counseling', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Recipient Counseling', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Psychological Assessment', 'type' => 'Service', 'brands' => 'Amaha'],
            ['name' => 'Nutrition Counseling', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Pre-Transplant Evaluation', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Post-Transplant Follow-up', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Immunosuppressive Therapy', 'type' => 'Service', 'brands' => 'Roche'],
            ['name' => 'Infection Monitoring', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Rehabilitation After Transplant', 'type' => 'Service', 'brands' => 'Apollo Rehabilitation'],
            ['name' => 'Home Care After Transplant', 'type' => 'Service', 'brands' => 'Apollo HomeCare'],
            ['name' => 'Teleconsultation', 'type' => 'Service', 'brands' => 'Apollo 24/7'],
            ['name' => 'Medication Management', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Long-Term Transplant Care', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Organ Donation Awareness Program', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'Community Outreach Program', 'type' => 'Service', 'brands' => 'NOTTO'],
            ['name' => 'Family Counseling', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Medical Record Review', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Second Opinion Consultation', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => 'Emergency Transplant Coordination', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
            ['name' => '24×7 Transplant Support', 'type' => 'Service', 'brands' => 'Apollo Hospitals'],
        ]
    ],
    [
        'category' => 'Education',
        'sub_category' => 'School',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Nursery Admission', 'type' => 'Service', 'brands' => 'EuroKids, Kidzee'],
            ['name' => 'Playgroup Admission', 'type' => 'Service', 'brands' => 'EuroKids, Hello Kids'],
            ['name' => 'Preschool Education', 'type' => 'Service', 'brands' => 'Kidzee, Bachpan'],
            ['name' => 'Primary School Education', 'type' => 'Service', 'brands' => 'Delhi Public School, Podar International'],
            ['name' => 'Secondary School Education', 'type' => 'Service', 'brands' => 'Kendriya Vidyalaya, DPS'],
            ['name' => 'Higher Secondary Education', 'type' => 'Service', 'brands' => 'DPS, Podar International'],
            ['name' => 'CBSE Curriculum', 'type' => 'Service', 'brands' => 'CBSE Schools'],
            ['name' => 'ICSE Curriculum', 'type' => 'Service', 'brands' => 'CISCE Schools'],
            ['name' => 'State Board Curriculum', 'type' => 'Service', 'brands' => 'State Board Schools'],
            ['name' => 'English Medium Education', 'type' => 'Service', 'brands' => 'DPS, Podar'],
            ['name' => 'Smart Classroom', 'type' => 'Service', 'brands' => 'Next Education'],
            ['name' => 'Digital Learning', 'type' => 'Service', 'brands' => 'Extramarks, LEAD School'],
            ['name' => 'Science Laboratory', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Computer Laboratory', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Library Facility', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Sports Training', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Music Classes', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Dance Classes', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Art & Craft Classes', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Robotics Classes', 'type' => 'Service', 'brands' => 'LEGO Education'],
            ['name' => 'Coding Classes', 'type' => 'Service', 'brands' => 'WhiteHat Jr, LEAD'],
            ['name' => 'Spoken English', 'type' => 'Service', 'brands' => 'School Program'],
            ['name' => 'Personality Development', 'type' => 'Service', 'brands' => 'School Program'],
            ['name' => 'Olympiad Training', 'type' => 'Service', 'brands' => 'SOF'],
            ['name' => 'NTSE Preparation', 'type' => 'Service', 'brands' => 'School Program'],
            ['name' => 'Career Counseling', 'type' => 'Service', 'brands' => 'School Counselor'],
            ['name' => 'School Transport', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Hostel Facility', 'type' => 'Service', 'brands' => 'Residential Schools'],
            ['name' => 'Mid-Day Meal', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Parent Teacher Meeting', 'type' => 'Service', 'brands' => 'School Facility'],
            ['name' => 'Online Classes', 'type' => 'Service', 'brands' => 'Google Classroom, Microsoft Teams'],
            ['name' => 'Student Portal', 'type' => 'Service', 'brands' => 'Entab, Fedena'],
            ['name' => 'Attendance Management', 'type' => 'Service', 'brands' => 'Entab'],
            ['name' => 'Fee Management', 'type' => 'Service', 'brands' => 'Entab'],
            ['name' => 'Examination Management', 'type' => 'Service', 'brands' => 'School ERP'],
            ['name' => 'Report Card', 'type' => 'Service', 'brands' => 'School ERP'],
            ['name' => 'Annual Function', 'type' => 'Service', 'brands' => 'School Activity'],
            ['name' => 'Educational Tours', 'type' => 'Service', 'brands' => 'School Activity'],
            ['name' => 'Scholarship Program', 'type' => 'Service', 'brands' => 'School Program'],
            ['name' => 'Admission Counseling', 'type' => 'Service', 'brands' => 'School Administration'],
        ]
    ],
    [
        'category' => 'Education',
        'sub_category' => 'College',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Undergraduate Programs', 'type' => 'Service', 'brands' => 'Parul University, Nirma University'],
            ['name' => 'Postgraduate Programs', 'type' => 'Service', 'brands' => 'Amity University, LPU'],
            ['name' => 'Diploma Courses', 'type' => 'Service', 'brands' => 'Government Colleges'],
            ['name' => 'Engineering Courses', 'type' => 'Service', 'brands' => 'IIT, NIT'],
            ['name' => 'Medical Courses', 'type' => 'Service', 'brands' => 'AIIMS, CMC Vellore'],
            ['name' => 'Pharmacy Courses', 'type' => 'Service', 'brands' => 'Parul University'],
            ['name' => 'Nursing Courses', 'type' => 'Service', 'brands' => 'Apollo College of Nursing'],
            ['name' => 'Law Courses', 'type' => 'Service', 'brands' => 'NLU, GLS University'],
            ['name' => 'Management (MBA/BBA)', 'type' => 'Service', 'brands' => 'IIM, NMIMS'],
            ['name' => 'Commerce Courses', 'type' => 'Service', 'brands' => 'H.L. College'],
            ['name' => 'Arts Courses', 'type' => 'Service', 'brands' => 'St. Xavier\'s College'],
            ['name' => 'Science Courses', 'type' => 'Service', 'brands' => 'Christ University'],
            ['name' => 'Computer Science Programs', 'type' => 'Service', 'brands' => 'LPU, VIT'],
            ['name' => 'Polytechnic Programs', 'type' => 'Service', 'brands' => 'Government Polytechnic'],
            ['name' => 'Distance Learning', 'type' => 'Service', 'brands' => 'IGNOU'],
            ['name' => 'Online Degree Programs', 'type' => 'Service', 'brands' => 'Amity Online, Manipal Online'],
            ['name' => 'Skill Development Programs', 'type' => 'Service', 'brands' => 'NSDC'],
            ['name' => 'Internship Assistance', 'type' => 'Service', 'brands' => 'College Placement Cell'],
            ['name' => 'Campus Placement', 'type' => 'Service', 'brands' => 'Training & Placement Cell'],
            ['name' => 'Career Counseling', 'type' => 'Service', 'brands' => 'College Career Cell'],
            ['name' => 'Industrial Training', 'type' => 'Service', 'brands' => 'College Program'],
            ['name' => 'Research Programs', 'type' => 'Service', 'brands' => 'Universities'],
            ['name' => 'Innovation Lab', 'type' => 'Service', 'brands' => 'College Facility'],
            ['name' => 'Incubation Center', 'type' => 'Service', 'brands' => 'Startup India, College Incubator'],
            ['name' => 'Library', 'type' => 'Service', 'brands' => 'College Facility'],
            ['name' => 'Computer Lab', 'type' => 'Service', 'brands' => 'College Facility'],
            ['name' => 'Hostel', 'type' => 'Service', 'brands' => 'College Facility'],
            ['name' => 'Transportation', 'type' => 'Service', 'brands' => 'College Facility'],
            ['name' => 'Scholarship Assistance', 'type' => 'Service', 'brands' => 'Government Scholarships'],
            ['name' => 'Education Loan Guidance', 'type' => 'Service', 'brands' => 'SBI, HDFC Bank'],
            ['name' => 'Student Exchange Program', 'type' => 'Service', 'brands' => 'Partner Universities'],
            ['name' => 'Certificate Courses', 'type' => 'Service', 'brands' => 'Coursera Campus, NPTEL'],
            ['name' => 'Workshop & Seminars', 'type' => 'Service', 'brands' => 'College Program'],
            ['name' => 'Alumni Network', 'type' => 'Service', 'brands' => 'College Association'],
            ['name' => 'Online Learning Portal', 'type' => 'Service', 'brands' => 'Moodle, Google Classroom'],
            ['name' => 'Examination Services', 'type' => 'Service', 'brands' => 'College Administration'],
            ['name' => 'Degree Verification', 'type' => 'Service', 'brands' => 'University Administration'],
            ['name' => 'Admission Counseling', 'type' => 'Service', 'brands' => 'College Admission Cell'],
            ['name' => 'Fee Payment Portal', 'type' => 'Service', 'brands' => 'College ERP'],
            ['name' => 'Student ERP', 'type' => 'Service', 'brands' => 'Fedena, Entab'],
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

echo "Successfully seeded Data for Health & Medical Sub Categories 28.23 to 28.26, and Education 29.1 to 29.2.\n";
