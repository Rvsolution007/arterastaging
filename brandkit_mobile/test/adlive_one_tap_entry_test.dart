import 'package:brandkit_mobile/adlive_one_tap_entry.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('opens in one tap only for the activated Pixel account and business', () {
    expect(
      AdLiveScopedSelection.canOpen(
        currentUserId: '42',
        storedUserId: '42',
        selectedBusinessId: '9',
        businessAvailable: true,
        hasToken: true,
      ),
      isTrue,
    );
  });

  test('requires activation again after logout, account switch, or removal', () {
    expect(
      AdLiveScopedSelection.canOpen(
        currentUserId: '',
        storedUserId: '42',
        selectedBusinessId: '9',
        businessAvailable: true,
        hasToken: true,
      ),
      isFalse,
    );
    expect(
      AdLiveScopedSelection.canOpen(
        currentUserId: '99',
        storedUserId: '42',
        selectedBusinessId: '9',
        businessAvailable: true,
        hasToken: true,
      ),
      isFalse,
    );
    expect(
      AdLiveScopedSelection.canOpen(
        currentUserId: '42',
        storedUserId: '42',
        selectedBusinessId: '9',
        businessAvailable: false,
        hasToken: true,
      ),
      isFalse,
    );
  });
}
