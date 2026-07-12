import 'package:get/get.dart';
void main() {
  var businesses = [].obs;
  List data = [{"id": 1}];
  businesses.value = data;
  print("Is not empty: ${businesses.isNotEmpty}");
}
