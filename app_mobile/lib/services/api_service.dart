import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'http_client_stub.dart' if (dart.library.html) 'http_client_web.dart';

class ApiService {
  // URL fournie par l'utilisateur
  static const String baseUrl = 'https://app.hillemballage.ci/backend/requests';
  static Map<String, String> _defaultHeaders = {
    'Content-Type': 'application/json',
  };
  static final http.Client _client = createHttpClient();

  static void setAuthToken(String token) {
    _defaultHeaders['Authorization'] = 'Bearer $token';
  }

  static void clearAuthToken() {
    _defaultHeaders.remove('Authorization');
  }

  static Map<String, String> get headers =>
      Map<String, String>.from(_defaultHeaders);

  // Méthode générique pour les requêtes GET
  static Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final response = await _client
          .get(
            Uri.parse('$baseUrl/$endpoint'),
            headers: headers,
          )
          .timeout(const Duration(seconds: 15));
      if (kDebugMode) {
        debugPrint('[GET] $baseUrl/$endpoint => ${response.statusCode}');
        debugPrint(response.body);
      }

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  // Méthode générique pour les requêtes POST
  static Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _client
          .post(
            Uri.parse('$baseUrl/$endpoint'),
            headers: headers,
            body: jsonEncode(data),
          )
          .timeout(const Duration(seconds: 15));
      if (kDebugMode) {
        debugPrint('[POST JSON] $baseUrl/$endpoint');
        debugPrint('Body: ${jsonEncode(data)}');
        debugPrint('=> ${response.statusCode}');
        debugPrint(response.body);
      }

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  // Méthode POST (form-urlencoded) pour compatibilité PHP ($_POST)
  static Future<Map<String, dynamic>> postForm(
    String endpoint,
    Map<String, String> data,
  ) async {
    try {
      final formHeaders = Map<String, String>.from(_defaultHeaders);
      formHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
      formHeaders['Accept'] = 'application/json';

      final response = await _client
          .post(
            Uri.parse('$baseUrl/$endpoint'),
            headers: formHeaders,
            body: Uri(queryParameters: data).query, // clé=valeur&...
          )
          .timeout(const Duration(seconds: 15));
      if (kDebugMode) {
        debugPrint('[POST FORM] $baseUrl/$endpoint');
        debugPrint('Body: ${Uri(queryParameters: data).query}');
        debugPrint('=> ${response.statusCode}');
        debugPrint(response.body);
      }

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  // Méthode générique pour les requêtes PUT
  static Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await _client
          .put(
            Uri.parse('$baseUrl/$endpoint'),
            headers: headers,
            body: jsonEncode(data),
          )
          .timeout(const Duration(seconds: 15));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  // Méthode générique pour les requêtes DELETE
  static Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final response = await _client
          .delete(
            Uri.parse('$baseUrl/$endpoint'),
            headers: headers,
          )
          .timeout(const Duration(seconds: 15));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }

  // Gestion des réponses HTTP
  static Map<String, dynamic> _handleResponse(http.Response response) {
    Map<String, dynamic> data;
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      } else {
        data = {'success': false, 'message': 'Réponse invalide du serveur'};
      }
    } catch (_) {
      data = {
        'success': false,
        'message': response.body.isNotEmpty
            ? response.body
            : 'Réponse non JSON du serveur',
      };
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else {
      String errorMessage = data['message']?.toString() ?? 'Erreur inconnue';
      throw Exception('Erreur ${response.statusCode}: $errorMessage');
    }
  }

  // Vérification de la connectivité
  static Future<bool> checkConnection() async {
    try {
      final response = await _client.get(
        Uri.parse('$baseUrl/auth.php'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 5));

      return response.statusCode == 200 || response.statusCode == 401;
    } catch (e) {
      return false;
    }
  }
}
