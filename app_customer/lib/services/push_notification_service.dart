import 'package:flutter/foundation.dart';

/// Point d'intégration des notifications grand public.
///
/// Le déclenchement des campagnes sera piloté par le back-office Hill Emballage.
/// Firebase Cloud Messaging sera uniquement le transport vers Android/iOS :
/// aucune campagne ne devra être créée manuellement dans la console Firebase.
class PushNotificationService {
  PushNotificationService._();

  static final PushNotificationService instance = PushNotificationService._();

  Future<void> initialize() async {
    // Activation prévue dès que les fichiers de configuration Firebase propres
    // aux applications Android/iOS seront fournis pour les builds Play Store.
    debugPrint('PushNotificationService ready for FCM configuration.');
  }

  Future<void> registerDeviceToken(String token) async {
    // TODO(customer-api): POST /api/v1/customer/devices
    // { token, platform, app_version, locale }
    debugPrint('Device token registration pending backend endpoint: $token');
  }

  Future<void> unregisterDeviceToken(String token) async {
    // TODO(customer-api): DELETE /api/v1/customer/devices/{token}
    debugPrint('Device token removal pending backend endpoint: $token');
  }
}
