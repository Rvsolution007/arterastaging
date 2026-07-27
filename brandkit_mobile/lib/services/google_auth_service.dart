import 'package:firebase_auth/firebase_auth.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:google_sign_in/google_sign_in.dart';

/// Obtains a Firebase-signed ID token after the native Google account picker.
/// The token is sent to the Artera API; no Google profile field is trusted by
/// the API unless it appears in that verified Firebase token.
class GoogleAuthService {
  static final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: const <String>['email', 'profile'],
  );

  static Future<String?> signInAndGetIdToken() async {
    if (Firebase.apps.isEmpty) {
      await Firebase.initializeApp();
    }

    final googleUser = await _googleSignIn.signIn();
    if (googleUser == null) {
      return null;
    }

    final googleAuthentication = await googleUser.authentication;
    if (googleAuthentication.idToken == null ||
        googleAuthentication.idToken!.isEmpty) {
      throw StateError('Google did not return an ID token.');
    }

    final credential = GoogleAuthProvider.credential(
      accessToken: googleAuthentication.accessToken,
      idToken: googleAuthentication.idToken,
    );
    final userCredential =
        await FirebaseAuth.instance.signInWithCredential(credential);
    final firebaseUser = userCredential.user;
    if (firebaseUser == null) {
      throw StateError('Firebase did not create a signed-in user.');
    }

    final firebaseIdToken = await firebaseUser.getIdToken(true);
    if (firebaseIdToken == null || firebaseIdToken.isEmpty) {
      throw StateError('Firebase did not return an ID token.');
    }

    return firebaseIdToken;
  }

  static Future<void> signOut() async {
    await Future.wait<void>([
      FirebaseAuth.instance.signOut(),
      _googleSignIn.signOut(),
    ]);
  }
}
