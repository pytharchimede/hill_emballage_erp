import '../models/user.dart';
import 'api_service.dart';

class AuthService {
  // Connexion utilisateur
  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    try {
      // L'API attend un POST JSON avec { action: 'login', username, password }
      final resp = await ApiService.post('auth.php', {
        'action': 'login',
        'username': email, // accepte email ou username côté backend
        'password': password,
      });

      // Succès: l'API renvoie { message, user, token }
      final token = resp['token'];
      final user = resp['user'];
      if (token != null && user != null) {
        ApiService.setAuthToken(token);
        return {
          'success': true,
          'message': resp['message'] ?? 'Connexion réussie',
          'user': user,
          'token': token,
        };
      }
      return {
        'success': false,
        'message': resp['message'] ?? 'Réponse inattendue du serveur',
      };
    } catch (e) {
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  // Déconnexion
  static Future<void> logout() async {
    // Pas d'endpoint logout côté API: on supprime juste le token localement
    ApiService.clearAuthToken();
  }

  // Vérification du token
  static Future<Map<String, dynamic>> verifyToken() async {
    try {
      // Extraire le token du header Authorization
      final auth = ApiService.headers['Authorization'];
      if (auth == null || !auth.startsWith('Bearer ')) {
        return {'success': false, 'message': 'Aucun token'};
      }
      final token = auth.substring('Bearer '.length);
      final data = await ApiService.get('auth.php?token=$token');
      // GET /auth.php?token=... renvoie directement l'objet utilisateur
      if (data['id'] != null) {
        return {'success': true, 'user': data};
      }
      return {'success': false, 'message': 'Token invalide'};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  // Récupération du profil utilisateur
  static Future<User> getUserProfile() async {
    final check = await verifyToken();
    if (check['success'] == true && check['user'] != null) {
      return User.fromJson(check['user']);
    }
    throw Exception(
        check['message'] ?? 'Impossible de récupérer le profil utilisateur');
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
    // Cette route côté backend attend un POST JSON avec { action, user_id, current_password, new_password, (username) }
    // Ici on envoie ce que l'on peut; l'API côté serveur valide l'identité.
    return await ApiService.post('auth.php', {
      'action': 'change_password',
      'current_password': currentPassword,
      'new_password': newPassword,
    });
  }
}
