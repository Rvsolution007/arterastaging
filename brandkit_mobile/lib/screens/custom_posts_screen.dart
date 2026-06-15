import 'package:flutter/material.dart';
import '../widgets/coming_soon_widget.dart';

class CustomPostsScreen extends StatelessWidget {
  final int categoryId;
  final String categoryName;

  const CustomPostsScreen({
    super.key,
    required this.categoryId,
    required this.categoryName,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(categoryName, style: const TextStyle(color: Colors.black)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: const ComingSoonWidget(title: 'Custom Posts'),
    );
  }
}
