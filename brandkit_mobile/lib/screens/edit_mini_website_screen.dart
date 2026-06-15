import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import '../controllers/mini_website_controller.dart';
import '../controllers/home_controller.dart';

class EditMiniWebsiteScreen extends StatefulWidget {
  final Map<String, dynamic> siteData;

  const EditMiniWebsiteScreen({Key? key, required this.siteData}) : super(key: key);

  @override
  State<EditMiniWebsiteScreen> createState() => _EditMiniWebsiteScreenState();
}

class _EditMiniWebsiteScreenState extends State<EditMiniWebsiteScreen> {
  final controller = Get.find<MiniWebsiteController>();
  final _formKey = GlobalKey<FormState>();

  late TextEditingController nameCtrl;
  late TextEditingController emailCtrl;
  late TextEditingController phoneCtrl;
  late TextEditingController websiteCtrl;
  late TextEditingController addressCtrl;
  late TextEditingController aboutCtrl;
  late TextEditingController servicesCtrl;
  late TextEditingController facebookCtrl;
  late TextEditingController instagramCtrl;
  late TextEditingController twitterCtrl;
  late TextEditingController youtubeCtrl;
  late TextEditingController linkedinCtrl;

  late TextEditingController mapUrlCtrl;
  late TextEditingController whatsappCtrl;
  late TextEditingController clientsCtrl;
  late TextEditingController yearsCtrl;

  XFile? _selectedLogo;

  @override
  void initState() {
    super.initState();
    final d = widget.siteData;
    
    String defaultName = '';
    String defaultEmail = '';
    String defaultPhone = '';
    String defaultWebsite = '';
    String defaultAddress = '';
    
    if (Get.isRegistered<HomeController>()) {
      final hc = Get.find<HomeController>();
      defaultName = hc.businessName.value;
      defaultEmail = hc.businessEmail.value;
      defaultPhone = hc.businessPhone.value;
      defaultWebsite = hc.businessWebsite.value;
      defaultAddress = hc.businessAddress.value;
    }

    String getVal(String key, String fallback) {
      final val = d[key]?.toString() ?? '';
      return val.isNotEmpty ? val : fallback;
    }

    nameCtrl = TextEditingController(text: getVal('business_name', defaultName));
    emailCtrl = TextEditingController(text: getVal('email', defaultEmail));
    phoneCtrl = TextEditingController(text: getVal('mobile_no', defaultPhone));
    websiteCtrl = TextEditingController(text: getVal('website', defaultWebsite));
    addressCtrl = TextEditingController(text: getVal('address', defaultAddress));
    
    aboutCtrl = TextEditingController(text: d['about_us'] ?? '');
    servicesCtrl = TextEditingController(text: d['products_services'] ?? '');
    facebookCtrl = TextEditingController(text: d['facebook'] ?? '');
    instagramCtrl = TextEditingController(text: d['instagram'] ?? '');
    twitterCtrl = TextEditingController(text: d['twitter'] ?? '');
    youtubeCtrl = TextEditingController(text: d['youtube'] ?? '');
    linkedinCtrl = TextEditingController(text: d['linkedin'] ?? '');

    mapUrlCtrl = TextEditingController(text: d['map_url'] ?? '');
    whatsappCtrl = TextEditingController(text: d['whatsapp_number'] ?? '');
    clientsCtrl = TextEditingController(text: d['clients_count'] ?? '');
    yearsCtrl = TextEditingController(text: d['years_experience'] ?? '');
  }

  Future<void> _pickLogo() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      setState(() {
        _selectedLogo = pickedFile;
      });
    }
  }

  void _save() async {
    if (_formKey.currentState!.validate()) {
      Map<String, String> fields = {
        'business_name': nameCtrl.text,
        'email': emailCtrl.text,
        'mobile_no': phoneCtrl.text,
        'website': websiteCtrl.text,
        'address': addressCtrl.text,
        'about_us': aboutCtrl.text,
        'products_services': servicesCtrl.text,
        'facebook': facebookCtrl.text,
        'instagram': instagramCtrl.text,
        'twitter': twitterCtrl.text,
        'youtube': youtubeCtrl.text,
        'linkedin': linkedinCtrl.text,
        'map_url': mapUrlCtrl.text,
        'whatsapp_number': whatsappCtrl.text,
        'clients_count': clientsCtrl.text,
        'years_experience': yearsCtrl.text,
      };

      String? logoPath;
      List<int>? logoBytes;
      if (_selectedLogo != null) {
        if (kIsWeb) {
          logoBytes = await _selectedLogo!.readAsBytes();
        } else {
          logoPath = _selectedLogo!.path;
        }
      }

      bool success = await controller.updateWebsite(
        widget.siteData['id'],
        fields,
        logoPath: logoPath,
        logoBytes: logoBytes,
      );

      if (success) {
        Get.back();
        Get.snackbar('Success', 'Website details updated!',
            backgroundColor: Colors.green, colorText: Colors.white);
      } else {
        Get.snackbar('Error', 'Failed to update details.',
            backgroundColor: Colors.red, colorText: Colors.white);
      }
    }
  }

  Widget _buildField(String label, TextEditingController ctrl, {int lines = 1}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: TextFormField(
        controller: ctrl,
        maxLines: lines,
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          filled: true,
          fillColor: Colors.white,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF3F4F6),
      appBar: AppBar(
        title: const Text('Edit Mini Website'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 1,
      ),
      body: Obx(() {
        if (controller.isGenerating.value) {
          return const Center(child: CircularProgressIndicator());
        }
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Business Logo', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: _pickLogo,
                  child: Container(
                    height: 100,
                    width: 100,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: _selectedLogo != null
                        ? (kIsWeb
                            ? Image.network(_selectedLogo!.path, fit: BoxFit.cover)
                            : Image.file(File(_selectedLogo!.path), fit: BoxFit.cover))
                        : const Icon(Icons.add_a_photo, color: Colors.grey, size: 40),
                  ),
                ),
                const SizedBox(height: 24),
                const Text('Basic Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                const SizedBox(height: 12),
                _buildField('Business Name', nameCtrl),
                _buildField('Phone Number', phoneCtrl),
                _buildField('WhatsApp Number', whatsappCtrl),
                _buildField('Email Address', emailCtrl),
                _buildField('Website URL', websiteCtrl),
                _buildField('Physical Address', addressCtrl, lines: 2),
                _buildField('Google Maps URL', mapUrlCtrl),
                
                const SizedBox(height: 24),
                const Text('Description', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                const SizedBox(height: 12),
                _buildField('About Us', aboutCtrl, lines: 4),
                _buildField('Products & Services', servicesCtrl, lines: 4),
                
                const SizedBox(height: 24),
                const Text('Stats / Highlights', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                const SizedBox(height: 12),
                _buildField('Happy Clients (e.g. 5000+)', clientsCtrl),
                _buildField('Years Experience (e.g. 10+)', yearsCtrl),

                const SizedBox(height: 24),
                const Text('Social Links', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                const SizedBox(height: 12),
                _buildField('Facebook URL', facebookCtrl),
                _buildField('Instagram URL', instagramCtrl),
                _buildField('Twitter URL', twitterCtrl),
                _buildField('YouTube URL', youtubeCtrl),
                _buildField('LinkedIn URL', linkedinCtrl),

                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _save,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3B28CC),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text('Save Details', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(height: 40),
              ],
            ),
          ),
        );
      }),
    );
  }
}
