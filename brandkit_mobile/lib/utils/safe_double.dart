double safeDouble(dynamic value, [double defaultValue = 0.0]) {
  if (value == null) return defaultValue;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is num) return value.toDouble();
  if (value is String) {
    if (value.toLowerCase() == 'auto') return defaultValue;
    return double.tryParse(value) ?? defaultValue;
  }
  return defaultValue;
}
