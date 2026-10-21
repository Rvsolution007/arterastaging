import 'package:brandkit_mobile/adlive_one_tap_entry.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'opens in one tap only for the activated Pixel account and business',
    () {
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
    },
  );

  test(
    'requires activation again after logout, account switch, or removal',
    () {
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
    },
  );

  test('activates the only business after an asynchronous load', () {
    expect(
      AdLiveBusinessSelection.resolve(
        businesses: const [],
        currentBusinessId: '',
      ),
      isNull,
    );
    expect(
      AdLiveBusinessSelection.resolve(
        businesses: const [
          {'id': 21, 'name': 'Nexora Solutions'},
        ],
        currentBusinessId: '',
      )?['id'],
      21,
    );
  });

  test(
    'uses the current or explicitly selected business from a loaded list',
    () {
      const businesses = [
        {'id': 21, 'name': 'First'},
        {'id': 22, 'name': 'Second'},
      ];
      expect(
        AdLiveBusinessSelection.resolve(
          businesses: businesses,
          currentBusinessId: '',
        ),
        isNull,
      );
      expect(
        AdLiveBusinessSelection.resolve(
          businesses: businesses,
          currentBusinessId: '22',
        )?['id'],
        22,
      );
      expect(
        AdLiveBusinessSelection.resolve(
          businesses: businesses,
          currentBusinessId: '22',
          selectedBusiness: const {'id': 21},
        )?['id'],
        21,
      );
    },
  );
}
