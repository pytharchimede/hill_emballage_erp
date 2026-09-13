import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../models/customer_account.dart';

class CustomerAccountService {
  static const _baseUrl = String.fromEnvironment(
    'HILL_API_BASE_URL',
    defaultValue: 'http://10.0.2.2/hill_emballage_pos',
  );
  static const _tokenKey = 'hill_customer_token';

  Future<String?> get token async => (await SharedPreferences.getInstance()).getString(_tokenKey);

  Future<void> saveToken(String value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, value);
  }

  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<Map<String, String>> _headers({bool auth = false}) async {
    final headers = <String, String>{'Accept': 'application/json', 'Content-Type': 'application/json'};
    if (auth) {
      final value = await token;
      if (value != null && value.isNotEmpty) headers['Authorization'] = 'Bearer $value';
    }
    return headers;
  }

  Future<CustomerAccount> login({required String identifier, required String password}) async {
    final response = await http.post(
      Uri.parse('$_baseUrl/storefront_api/auth.php?action=login'),
      headers: await _headers(),
      body: jsonEncode({'identifier': identifier.trim(), 'password': password}),
    );
    if (response.statusCode != 200) throw Exception(_message(response, 'Connexion impossible'));
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    await saveToken((data['token'] ?? '').toString());
    return CustomerAccount.fromJson(Map<String, dynamic>.from(data['customer'] as Map));
  }

  Future<CustomerAccount> register({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$_baseUrl/storefront_api/auth.php?action=register'),
      headers: await _headers(),
      body: jsonEncode({
        'first_name': firstName.trim(),
        'last_name': lastName.trim(),
        'email': email.trim(),
        'phone': phone.trim(),
        'password': password,
      }),
    );
    if (response.statusCode != 201) throw Exception(_message(response, 'Inscription impossible'));
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    await saveToken((data['token'] ?? '').toString());
    return CustomerAccount.fromJson(Map<String, dynamic>.from(data['customer'] as Map));
  }

  Future<CustomerAccount?> me() async {
    final value = await token;
    if (value == null || value.isEmpty) return null;
    final response = await http.get(
      Uri.parse('$_baseUrl/storefront_api/auth.php?action=me'),
      headers: await _headers(auth: true),
    );
    if (response.statusCode == 401) {
      await clearToken();
      return null;
    }
    if (response.statusCode != 200) throw Exception(_message(response, 'Profil indisponible'));
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return CustomerAccount.fromJson(Map<String, dynamic>.from(data['customer'] as Map));
  }

  Future<List<CustomerOrderSummary>> orders() async {
    final response = await http.get(
      Uri.parse('$_baseUrl/storefront_api/customer_orders.php'),
      headers: await _headers(auth: true),
    );
    if (response.statusCode != 200) throw Exception(_message(response, 'Commandes indisponibles'));
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return (data['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(CustomerOrderSummary.fromJson)
        .toList(growable: false);
  }

  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$_baseUrl/storefront_api/auth.php?action=logout'),
        headers: await _headers(auth: true),
      );
    } finally {
      await clearToken();
    }
  }

  String _message(http.Response response, String fallback) {
    try {
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      return (data['message'] ?? data['error'] ?? fallback).toString();
    } catch (_) {
      return fallback;
    }
  }
}
