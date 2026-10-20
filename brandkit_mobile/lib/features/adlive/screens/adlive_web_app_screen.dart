import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../services/adlive_api_service.dart';

/// Hosts the established AdLive browser application after exchanging an
/// opaque, one-time mobile handoff. The mobile bearer token and Pixel
/// credential never enter the WebView URL or its browser session.
class AdLiveWebAppScreen extends StatefulWidget {
  const AdLiveWebAppScreen({
    super.key,
    required this.business,
  });

  final Map<String, dynamic> business;

  @override
  State<AdLiveWebAppScreen> createState() => _AdLiveWebAppScreenState();
}

class _AdLiveWebAppScreenState extends State<AdLiveWebAppScreen> {
  late final WebViewController _webView;
  var _loading = true;
  String? _error;

  String get _businessName {
    final name = widget.business['name']?.toString().trim() ?? '';
    return name.isEmpty ? 'Selected business' : name;
  }

  @override
  void initState() {
    super.initState();
    _webView = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() => _loading = true);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => _loading = false);
          },
          onWebResourceError: (error) {
            if (!mounted || error.isForMainFrame != true) return;
            setState(() {
              _loading = false;
              _error = 'AdLive could not load. Check your connection and try again.';
            });
          },
        ),
      );
    _open();
  }

  Future<void> _open() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final url = await AdLiveApiService.launchBrowserApp(
        businessId: widget.business['id']?.toString(),
      );
      await _webView.loadRequest(Uri.parse(url));
    } on AdLiveApiException catch (error) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = error.message;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = 'Could not open AdLive. Please try again.';
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        titleSpacing: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'AdLive',
              style: TextStyle(
                color: Color(0xFF047857),
                fontSize: 19,
                fontWeight: FontWeight.w900,
              ),
            ),
            Text(
              _businessName,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Color(0xFF64748B),
                fontSize: 11,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Reload',
            onPressed: _loading ? null : _open,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _webView),
          if (_loading) const LinearProgressIndicator(minHeight: 3),
          if (_error != null)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.cloud_off_outlined,
                      color: Color(0xFF92400E),
                      size: 42,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFF334155),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _open,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Try again'),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}
