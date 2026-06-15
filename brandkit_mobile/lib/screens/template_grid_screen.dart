import 'package:flutter/material.dart';
import '../widgets/shared_header.dart';
import '../widgets/coming_soon_widget.dart';

class TemplateGridScreen extends StatelessWidget {
  const TemplateGridScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        backgroundColor: Colors.white,
        body: Column(
          children: [
            const SharedHeader(),
            Expanded(
              child: const ComingSoonWidget(title: 'Custom Posts'),
            ),
          ],
        ),
      ),
    );
  }
}
