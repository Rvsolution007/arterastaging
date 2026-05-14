import 'package:flutter/material.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: Scaffold(
        body: Center(
          child: Container(
            width: 300,
            height: 300,
            color: Colors.blue,
            child: Stack(
              children: [
                Positioned(
                  right: 10,
                  top: 100,
                  child: Container(
                    color: Colors.red,
                    child: Text(
                      'WARDROBE',
                      style: TextStyle(fontSize: 60, color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
