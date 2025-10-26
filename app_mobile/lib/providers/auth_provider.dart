import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';
import '../utils/storage.dart';

class AuthProvider extends ChangeNotifier {
  User? _user;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  bool _initialized = false;
  String? _errorMessage;

  User? get user => _user;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  bool get isInitialized => _initialized;
  String? get errorMessage => _errorMessage;

  AuthProvider() {
    _initialize();
  }

  Future<void> _initialize() async {
    // Initialisation au démarrage: restaurer session locale (token + user)
    _isLoading = true;
    notifyListeners();

    try {
      final savedToken = await Storage.getToken();
      final savedUser = await Storage.getUser();
      if (savedToken != null && savedToken.isNotEmpty && savedUser != null) {
        ApiService.setAuthToken(savedToken);
        _user = User.fromJson(savedUser);
        _isAuthenticated = true;
      } else {
        _user = null;
        _isAuthenticated = false;
      }
    } finally {
      _initialized = true;
      _isLoading = false;
      notifyListeners();
    }
  }

  // Ancienne vérification via endpoint token: non utilisée (backend non requis)

  // Connexion
  Future<bool> login(String email, String password) async {
    _setLoading(true);
    _clearError();

    try {
      final response = await AuthService.login(email, password);

      if (response['success'] == true) {
        _user = User.fromJson(response['user']);
        _isAuthenticated = true;
        // Persister token et user
        final token = response['token'] as String?;
        if (token != null && token.isNotEmpty) {
          await Storage.saveToken(token);
        }
        await Storage.saveUser(_user!.toJson());
        notifyListeners();
        return true;
      } else {
        _setError(response['message'] ?? 'Erreur de connexion');
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // Déconnexion
  Future<void> logout() async {
    try {
      await AuthService.logout();
    } finally {
      _user = null;
      _isAuthenticated = false;
      _clearError();
      // Nettoyer le stockage
      await Storage.clearToken();
      await Storage.clearUser();
      notifyListeners();
    }
  }

  // Vérification du token au démarrage
  Future<void> checkAuthStatus() async {
    // Optionnel: pourrait appeler une API /me si disponible
  }

  // Mise à jour du profil
  Future<bool> updateProfile(User updatedUser) async {
    _setLoading(true);
    _clearError();

    try {
      _user = await AuthService.updateProfile(updatedUser);
      notifyListeners();
      return true;
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // Changement de mot de passe
  Future<bool> changePassword(
    String currentPassword,
    String newPassword,
  ) async {
    _setLoading(true);
    _clearError();

    try {
      final response = await AuthService.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
      );

      if (response['success'] == true) {
        return true;
      } else {
        _setError(
          response['message'] ?? 'Erreur lors du changement de mot de passe',
        );
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  void _setError(String error) {
    _errorMessage = error;
    notifyListeners();
  }

  void _clearError() {
    _errorMessage = null;
  }

  void clearError() {
    _clearError();
    notifyListeners();
  }
}
