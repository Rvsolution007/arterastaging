import 'package:flutter/material.dart';
import '../widgets/shared_header.dart';
import '../widgets/coming_soon_widget.dart';

class MiniWebsiteDashboardScreen extends StatelessWidget {
  const MiniWebsiteDashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: const Text('Mini Website', style: TextStyle(color: Colors.black)),
          backgroundColor: Colors.white,
          elevation: 0,
          iconTheme: const IconThemeData(color: Colors.black),
        ),
        body: Column(
          children: [
            Expanded(
              child: const ComingSoonWidget(title: 'Mini Website'),
            ),
          ],
        ),
      ),
    );
  }
}
