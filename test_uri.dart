void main() {
  try {
    var u = Uri.parse('http://example.com/skins/Service List_103/Rectangle_1.png');
    print('SUCCESS: ' + u.toString());
  } catch(e) {
    print('ERROR: $e');
  }
}
