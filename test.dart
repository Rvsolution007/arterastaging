void main() { 
  print(Uri.parse("http://192.168.1.34/Artera/uploads/template/e98e8410-0558-4cc8-b9f4-66ed80bb8ac1/../../../uploads/background_images/img.png").toString()); 
  print(Uri.parse("http://192.168.1.34/Artera/uploads/template/e98e8410-0558-4cc8-b9f4-66ed80bb8ac1/../../../uploads/background_images/img.png").normalizePath().toString()); 
}
