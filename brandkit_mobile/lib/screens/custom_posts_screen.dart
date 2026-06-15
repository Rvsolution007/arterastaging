import 'package:flutter/material.dart';
import '../widgets/coming_soon_widget.dart';

class CustomPostsScreen extends StatelessWidget {
  final int? initialCategoryId;

  const CustomPostsScreen({
    super.key,
    this.initialCategoryId,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Custom Posts', style: TextStyle(color: Colors.black)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: const ComingSoonWidget(title: 'Custom Posts'),
    );
  }
}
