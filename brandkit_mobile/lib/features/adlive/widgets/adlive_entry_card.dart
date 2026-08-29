import 'package:brandkit_mobile/controllers/home_controller.dart';
import 'package:brandkit_mobile/features/adlive/controllers/adlive_session_controller.dart';
import 'package:brandkit_mobile/features/adlive/screens/adlive_shell_screen.dart';
import 'package:brandkit_mobile/features/adlive/services/adlive_api_service.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class AdLiveEntryCard extends StatelessWidget {
  const AdLiveEntryCard({super.key});

  @override
  Widget build(BuildContext context) {
    final home = Get.isRegistered<HomeController>()
        ? Get.find<HomeController>()
        : Get.put(HomeController());

    return Obx(() {
      final businessName = home.businessName.value.trim();
      return Container(
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 20),
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF047857), Color(0xFF059669), Color(0xFF10B981)],
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF047857).withValues(alpha: 0.24),
              blurRadius: 18,
              offset: const Offset(0, 9),
            ),
          ],
        ),
        child: Stack(
          children: [
            Positioned(
              right: -22,
              top: -28,
              child: _decorativeCircle(120, 0.13),
            ),
            Positioned(
              right: 28,
              bottom: -42,
              child: _decorativeCircle(96, 0.12),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.16),
                    borderRadius: BorderRadius.circular(99),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.24),
                    ),
                  ),
                  child: const Text(
                    'ADLIVE',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 10,
                      letterSpacing: 1.1,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                const Text(
                  'Grow your business\nwith Meta ads',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 23,
                    height: 1.14,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Launch, track and improve campaigns with AdLive.',
                  style: TextStyle(
                    color: Color(0xFFD1FAE5),
                    fontSize: 12.5,
                    height: 1.35,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 16),
                InkWell(
                  onTap: () => AdLiveActivationSheet.show(context),
                  borderRadius: BorderRadius.circular(13),
                  child: Ink(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 11,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(13),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.10),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.campaign_outlined,
                          color: Color(0xFF047857),
                          size: 18,
                        ),
                        const SizedBox(width: 7),
                        Text(
                          businessName.isEmpty
                              ? 'Open AdLive'
                              : 'Open AdLive for $businessName',
                          style: const TextStyle(
                            color: Color(0xFF047857),
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.arrow_forward,
                          color: Color(0xFF047857),
                          size: 17,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      );
    });
  }
}

class AdLiveEntryTile extends StatelessWidget {
  const AdLiveEntryTile({super.key});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => AdLiveActivationSheet.show(context),
      child: const Padding(
        padding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        child: Row(
          children: [
            _TileIcon(),
            SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'AdLive',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                  SizedBox(height: 2),
                  Text(
                    'Campaigns, leads and ad wallet',
                    style: TextStyle(fontSize: 13, color: Color(0xFF6B7280)),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
          ],
        ),
      ),
    );
  }
}

class _TileIcon extends StatelessWidget {
  const _TileIcon();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: const Color(0xFFD1FAE5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Icon(Icons.campaign_outlined, color: Color(0xFF047857)),
    );
  }
}

class AdLiveActivationSheet extends StatefulWidget {
  const AdLiveActivationSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const AdLiveActivationSheet(),
    );
  }

  @override
  State<AdLiveActivationSheet> createState() => _AdLiveActivationSheetState();
}

class _AdLiveActivationSheetState extends State<AdLiveActivationSheet> {
  late final HomeController _home;
  late Map<String, dynamic> _business;
  late final AdLiveSessionController _adLiveSession;

  @override
  void initState() {
    super.initState();
    _home = Get.isRegistered<HomeController>()
        ? Get.find<HomeController>()
        : Get.put(HomeController());
    _adLiveSession = Get.isRegistered<AdLiveSessionController>()
        ? Get.find<AdLiveSessionController>()
        : Get.put(AdLiveSessionController());
    _business = _initialBusiness();
  }

  Map<String, dynamic> _initialBusiness() {
    final activeId = _home.businessId.value;
    for (final rawBusiness in _home.businesses) {
      if (rawBusiness is Map && rawBusiness['id']?.toString() == activeId) {
        return Map<String, dynamic>.from(rawBusiness);
      }
    }

    return {
      'id': activeId,
      'name': _home.businessName.value,
      'logo': _home.businessLogo.value,
      'address': _home.businessAddress.value,
    };
  }

  Future<void> _chooseBusiness() async {
    final selected = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => _AdLiveBusinessPicker(
        businesses: _home.businesses,
        selectedId: _business['id']?.toString(),
      ),
    );
    if (selected != null && mounted) setState(() => _business = selected);
  }

  Future<void> _continue() async {
    if ((_business['id']?.toString() ?? '').isEmpty) {
      Get.snackbar(
        'Business required',
        'Add or select an Artera business before opening AdLive.',
        backgroundColor: const Color(0xFFFEF3C7),
        colorText: const Color(0xFF92400E),
      );
      return;
    }

    try {
      final bootstrap = await _adLiveSession.openForBusiness(_business);
      if (!mounted) return;

      final serverBusiness = bootstrap['business'];
      final selectedBusiness = serverBusiness is Map
          ? Map<String, dynamic>.from(serverBusiness)
          : _business;

      Navigator.of(context).pop();
      Get.to(
        () => AdLiveShellScreen(
          initialBusiness: selectedBusiness,
          bootstrap: bootstrap,
        ),
      );
    } on AdLiveApiException catch (error) {
      if (!mounted) return;
      Get.snackbar(
        'Could not open AdLive',
        error.message,
        backgroundColor: const Color(0xFFFEF3C7),
        colorText: const Color(0xFF92400E),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final name = _business['name']?.toString().trim();
    final displayName = (name == null || name.isEmpty) ? 'your business' : name;
    final category = _categoryLabel(_business);
    final location = _business['address']?.toString().trim() ?? '';

    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.only(top: 72),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
              const SizedBox(height: 24),
              Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(
                  Icons.campaign_outlined,
                  color: Color(0xFF047857),
                  size: 28,
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'Run Meta ads for',
                style: TextStyle(
                  color: Color(0xFF475569),
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                displayName,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF0F172A),
                  fontSize: 23,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 20),
              _BusinessPreview(
                business: _business,
                uploadsBaseUrl: _home.uploadsBaseUrl,
                category: category,
                location: location,
                onChange: _chooseBusiness,
              ),
              const SizedBox(height: 18),
              const Text(
                'Your business name, logo and category will prefill campaign details. Campaign data and advertising payments are managed separately by AdLive.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Color(0xFF64748B),
                  fontSize: 12.5,
                  height: 1.45,
                ),
              ),
              const SizedBox(height: 20),
              const _SecureConnectionGraphic(),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: Obx(
                  () => ElevatedButton(
                    onPressed: _adLiveSession.isOpening.value
                        ? null
                        : _continue,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF047857),
                      foregroundColor: Colors.white,
                      disabledBackgroundColor: const Color(0xFF6EE7B7),
                      disabledForegroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: _adLiveSession.isOpening.value
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'Continue to AdLive',
                            style: TextStyle(fontWeight: FontWeight.w800),
                          ),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: _chooseBusiness,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF047857),
                    side: const BorderSide(color: Color(0xFFA7F3D0)),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Text(
                    'Choose another business',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BusinessPreview extends StatelessWidget {
  const _BusinessPreview({
    required this.business,
    required this.uploadsBaseUrl,
    required this.category,
    required this.location,
    required this.onChange,
  });

  final Map<String, dynamic> business;
  final String uploadsBaseUrl;
  final String category;
  final String location;
  final VoidCallback onChange;

  @override
  Widget build(BuildContext context) {
    final name = business['name']?.toString().trim();
    final displayName = (name == null || name.isEmpty)
        ? 'Business profile'
        : name;
    final logo = business['logo']?.toString() ?? '';
    final imageUrl = logo.isEmpty
        ? ''
        : (logo.startsWith('http') ? logo : '$uploadsBaseUrl/$logo');
    final details = [
      if (category.isNotEmpty) category,
      if (location.isNotEmpty) location,
    ].join(' · ');

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDFA),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFA7F3D0)),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFF047857),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
            ),
            clipBehavior: Clip.antiAlias,
            child: imageUrl.isNotEmpty
                ? CachedNetworkImage(
                    imageUrl: imageUrl,
                    fit: BoxFit.cover,
                    errorWidget: (context, error, stackTrace) =>
                        _BusinessInitials(name: displayName),
                  )
                : _BusinessInitials(name: displayName),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  displayName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Color(0xFF134E4A),
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (details.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    details,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Color(0xFF0F766E),
                      fontSize: 11.5,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ],
            ),
          ),
          TextButton(
            onPressed: onChange,
            child: const Text(
              'Change',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }
}

class _BusinessInitials extends StatelessWidget {
  const _BusinessInitials({required this.name});

  final String name;

  @override
  Widget build(BuildContext context) {
    final words = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((word) => word.isNotEmpty)
        .take(2);
    final initials = words.map((word) => word[0].toUpperCase()).join();
    return Center(
      child: Text(
        initials.isEmpty ? 'A' : initials,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _SecureConnectionGraphic extends StatelessWidget {
  const _SecureConnectionGraphic();

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const _ConnectionNode(
          label: 'Artera\nPixel',
          background: Color(0xFFEEF2FF),
          foreground: Color(0xFF4338CA),
        ),
        Container(width: 42, height: 1.5, color: const Color(0xFFA7F3D0)),
        Container(
          width: 30,
          height: 30,
          decoration: const BoxDecoration(
            color: Color(0xFF047857),
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.lock_outline, color: Colors.white, size: 15),
        ),
        Container(width: 42, height: 1.5, color: const Color(0xFFA7F3D0)),
        const _ConnectionNode(
          label: 'AdLive',
          background: Color(0xFFD1FAE5),
          foreground: Color(0xFF047857),
        ),
      ],
    );
  }
}

class _ConnectionNode extends StatelessWidget {
  const _ConnectionNode({
    required this.label,
    required this.background,
    required this.foreground,
  });

  final String label;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 66,
      height: 48,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(13),
      ),
      child: Text(
        label,
        textAlign: TextAlign.center,
        style: TextStyle(
          color: foreground,
          fontSize: 10,
          fontWeight: FontWeight.w800,
          height: 1.05,
        ),
      ),
    );
  }
}

class _AdLiveBusinessPicker extends StatelessWidget {
  const _AdLiveBusinessPicker({
    required this.businesses,
    required this.selectedId,
  });

  final List<dynamic> businesses;
  final String? selectedId;

  @override
  Widget build(BuildContext context) {
    final availableBusinesses = businesses
        .whereType<Map>()
        .map((business) => Map<String, dynamic>.from(business))
        .toList();
    return SafeArea(
      top: false,
      child: Container(
        constraints: const BoxConstraints(maxHeight: 470),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 22),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFE2E8F0),
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            const SizedBox(height: 18),
            const Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Choose a business for AdLive',
                style: TextStyle(
                  color: Color(0xFF0F172A),
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
            const SizedBox(height: 4),
            const Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'This does not change your active Artera business.',
                style: TextStyle(color: Color(0xFF64748B), fontSize: 12),
              ),
            ),
            const SizedBox(height: 14),
            Expanded(
              child: availableBusinesses.isEmpty
                  ? const Center(
                      child: Text(
                        'No business profile is available yet.',
                        style: TextStyle(color: Color(0xFF64748B)),
                      ),
                    )
                  : ListView.separated(
                      itemCount: availableBusinesses.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 10),
                      itemBuilder: (context, index) {
                        final business = availableBusinesses[index];
                        final selected =
                            business['id']?.toString() == selectedId;
                        final name = business['name']?.toString() ?? 'Business';
                        return InkWell(
                          onTap: () => Navigator.of(context).pop(business),
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: selected
                                  ? const Color(0xFFF0FDFA)
                                  : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: selected
                                    ? const Color(0xFF34D399)
                                    : const Color(0xFFE2E8F0),
                                width: selected ? 1.5 : 1,
                              ),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 38,
                                  height: 38,
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFD1FAE5),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(
                                    Icons.storefront_outlined,
                                    color: Color(0xFF047857),
                                    size: 20,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    name,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Color(0xFF1F2937),
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                                if (selected)
                                  const Icon(
                                    Icons.check_circle,
                                    color: Color(0xFF059669),
                                  ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

String _categoryLabel(Map<String, dynamic> business) {
  final nested = business['businessCategory'];
  if (nested is Map) {
    final name = nested['businessCategoryName']?.toString().trim();
    if (name != null && name.isNotEmpty) return name;
  }
  return business['category_name']?.toString().trim() ??
      business['businessCategoryName']?.toString().trim() ??
      '';
}

Widget _decorativeCircle(double size, double opacity) {
  return Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      color: Colors.white.withValues(alpha: opacity),
      shape: BoxShape.circle,
    ),
  );
}
