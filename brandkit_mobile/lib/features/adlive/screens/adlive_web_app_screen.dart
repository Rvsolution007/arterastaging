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
    this.initialPath,
  });

  final Map<String, dynamic> business;
  final String? initialPath;

  @override
  State<AdLiveWebAppScreen> createState() => _AdLiveWebAppScreenState();
}

class _AdLiveWebAppScreenState extends State<AdLiveWebAppScreen> {
  late final WebViewController _webView;
  var _loading = true;
  String? _error;
  bool _directPathLoaded = false;
  String? _adLiveHost;

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
          onPageFinished: (url) async {
            final path = widget.initialPath;
            if (!_directPathLoaded &&
                path != null &&
                path.startsWith('/') &&
                !path.startsWith('//')) {
              final current = Uri.tryParse(url);
              final destination = current?.resolve(path);
              if (current != null &&
                  destination != null &&
                  current.host == _adLiveHost &&
                  destination.host == _adLiveHost &&
                  destination.scheme == 'https') {
                _directPathLoaded = true;
                await _webView.loadRequest(destination);
                return;
              }
            }
            if (mounted) setState(() => _loading = false);
          },
          onWebResourceError: (error) {
            if (!mounted || error.isForMainFrame != true) return;
            setState(() {
              _loading = false;
              _error =
                  'AdLive could not load. Check your connection and try again.';
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
    _directPathLoaded = false;

    try {
      final url = await AdLiveApiService.launchBrowserApp(
        businessId: widget.business['id']?.toString(),
      );
      _adLiveHost = Uri.parse(url).host;
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
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        if (await _webView.canGoBack()) {
          await _webView.goBack();
        }
        if (!mounted) return;
        Navigator.of(this.context).pop();
      },
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SafeArea(
          bottom: false,
          child: Stack(
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
        ),
      ),
    );
  }
}
