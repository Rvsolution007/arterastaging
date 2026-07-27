# Mobile Google Sign-In setup

The app and API now use Firebase Authentication ID tokens. The mobile app sends
only the Firebase token to `POST /google-sign-in`; the API verifies Google's
signature, expiry, Firebase project, and `google.com` provider before it
creates a user or starts a session.

## One-time Firebase Console setup

1. In the Firebase project used by this app, enable **Authentication → Sign-in
   method → Google**.
2. Add or correct the Android app whose package name is
   `com.arterapixel.pro`. Add the SHA-1 and SHA-256 fingerprints for every
   signing key that can install the app: debug, release, and Google Play App
   Signing (if Play is used).
3. Download the updated `google-services.json` and replace
   `brandkit_mobile/android/app/google-services.json`.
4. Add the iOS app in the same Firebase project, download
   `GoogleService-Info.plist`, and add it to the Runner target. Add its
   `REVERSED_CLIENT_ID` as a URL scheme in `ios/Runner/Info.plist`.
5. Set `FIREBASE_PROJECT_ID` on the Laravel environment to the Firebase
   project ID. If the Firebase and existing Google Cloud project are the same,
   the application also falls back to `GOOGLE_CLOUD_PROJECT_ID`.
6. Deploy the backend code, run `php artisan migrate --force`, then rebuild
   the mobile app. Do not put a Firebase service-account private key in the
   mobile app or API `.env` for this flow.

## Account behaviour

- A first-time verified Google account automatically creates an Artera user.
- An existing Firebase-linked Google account signs in normally.
- A legacy account marked as `login_type = google` is securely migrated the
  first time it signs in.
- An email/password account is not auto-linked merely because its email matches
  a Google account. This prevents account takeover; it should continue using
  its existing sign-in method until a separately authenticated account-linking
  flow is added.

## Acceptance test

1. Install a debug/release build that matches a Firebase Console SHA key.
2. Tap **Continue with Google** on Login and sign in with a new account.
   Confirm that an Artera session is created and the user reaches Dashboard.
3. Log out, then repeat with the same account from Login and Register. Confirm
   the same Artera user ID is used and only one account exists.
4. Tamper with the request by replacing `firebase_id_token` with random text;
   the API must return 401 and never create a user.
5. Try Google sign-in with an email already used by an email/password account.
   It must be rejected and must not join the two accounts automatically.
