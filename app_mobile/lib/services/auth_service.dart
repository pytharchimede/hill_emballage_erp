import '../models/user.dart';
import 'api_service.dart';

class AuthService {
  // Connexion utilisateur
  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    final data = await ApiService.post('auth.php', {
      'action': 'login',
      'email': email,
      'password': password,
    });

    if (data['success'] == true && data['token'] != null) {
      ApiService.setAuthToken(data['token']);
    }

    return data;
  }

  // Déconnexion
  static Future<void> logout() async {
    try {
      await ApiService.post('auth.php', {'action': 'logout'});
    } finally {
      ApiService.clearAuthToken();
    }
  }

  // Vérification du token
  static Future<Map<String, dynamic>> verifyToken() async {
    return await ApiService.post('auth.php', {'action': 'verify'});
  }

  // Récupération du profil utilisateur
  static Future<User> getUserProfile() async {
    final data = await ApiService.get('auth.php?action=profile');

    if (data['success'] == true && data['user'] != null) {
      return User.fromJson(data['user']);
    } else {
      throw Exception('Impossible de récupérer le profil utilisateur');
    }
  }

  // Mise à jour du profil
  static Future<User> updateProfile(User user) async {
    final data = await ApiService.put('auth.php', {
      'action': 'update_profile',
      ...user.toJson(),
    });

    if (data['success'] == true && data['user'] != null) {
      return User.fromJson(data['user']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour du profil',
      );
    }
  }

  // Changement de mot de passe
  static Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    return await ApiService.post('auth.php', {
      'action': 'change_password',
      'current_password': currentPassword,
      'new_password': newPassword,
    });
  }
}
