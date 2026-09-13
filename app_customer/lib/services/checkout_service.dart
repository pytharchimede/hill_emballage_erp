import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../providers/cart_provider.dart';

class CheckoutResult {
  const CheckoutResult({
    required this.orderNumber,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.orderStatus,
    required this.requiresOnlinePayment,
  });

  final String orderNumber;
  final String paymentMethod;
  final String paymentStatus;
  final String orderStatus;
  final bool requiresOnlinePayment;

  factory CheckoutResult.fromJson(Map<String, dynamic> json) => CheckoutResult(
        orderNumber: json['order_number'] as String,
        paymentMethod: json['payment_method'] as String,
        paymentStatus: json['payment_status'] as String,
        orderStatus: json['order_status'] as String,
        requiresOnlinePayment: json['requires_online_payment'] == true,
      );
}

class PaymentInitResult {
  const PaymentInitResult({required this.paymentUrl});
  final String paymentUrl;

  factory PaymentInitResult.fromJson(Map<String, dynamic> json) =>
      PaymentInitResult(paymentUrl: json['payment_url'] as String);
}

class CheckoutService {
  CheckoutService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const _baseUrl = String.fromEnvironment(
    'HILL_API_BASE_URL',
    defaultValue: 'http://10.0.2.2/hill_emballage_pos',
  );

  Future<Map<String, String>> _headers() async {
    final headers = <String, String>{'Content-Type': 'application/json; charset=utf-8'};
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('hill_customer_token');
    if (token != null && token.isNotEmpty) headers['Authorization'] = 'Bearer $token';
    return headers;
  }

  Future<CheckoutResult> createOrder({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String address,
    required String city,
    required String deliveryNote,
    required String paymentMethod,
    String? paymentChannel,
    required List<CartItem> items,
  }) async {
    final response = await _client.post(
      Uri.parse('$_baseUrl/storefront_api/orders.php'),
      headers: await _headers(),
      body: jsonEncode({
        'first_name': firstName,
        'last_name': lastName,
        'email': email,
        'phone': phone,
        'address': address,
        'city': city,
        'delivery_note': deliveryNote,
        'payment_method': paymentMethod,
        'payment_channel': paymentChannel,
        'items': items
            .map((item) => {
                  'product_id': item.id,
                  'quantity': item.quantity,
                })
            .toList(),
      }),
    );

    final body = _decode(response.body);
    if (response.statusCode != 201) {
      throw CheckoutException(_errorMessage(body));
    }
    return CheckoutResult.fromJson(body);
  }

  Future<PaymentInitResult> initializePaiementPro(String orderNumber) async {
    final response = await _client.post(
      Uri.parse('$_baseUrl/storefront_api/payment_init.php'),
      headers: await _headers(),
      body: jsonEncode({'order_number': orderNumber}),
    );

    final body = _decode(response.body);
    if (response.statusCode != 200 || body['success'] != true) {
      throw CheckoutException(_errorMessage(body));
    }
    return PaymentInitResult.fromJson(body);
  }

  Future<Map<String, dynamic>> getOrderStatus(String orderNumber) async {
    final response = await _client.get(
      Uri.parse('$_baseUrl/storefront_api/order_status.php?order=${Uri.encodeQueryComponent(orderNumber)}'),
      headers: await _headers(),
    );
    final body = _decode(response.body);
    if (response.statusCode != 200) {
      throw CheckoutException(_errorMessage(body));
    }
    return body;
  }

  Map<String, dynamic> _decode(String raw) {
    final decoded = jsonDecode(raw);
    if (decoded is! Map<String, dynamic>) {
      throw const CheckoutException('Réponse serveur invalide.');
    }
    return decoded;
  }

  String _errorMessage(Map<String, dynamic> body) {
    final message = body['message']?.toString().trim();
    if (message != null && message.isNotEmpty) return message;
    switch (body['error']) {
      case 'invalid_customer':
        return 'Veuillez vérifier vos informations de livraison.';
      case 'payment_channel_required':
        return 'Choisissez un moyen de paiement Paiement Pro.';
      case 'payment_not_configured':
        return 'Paiement Pro n’est pas encore configuré sur le serveur.';
      case 'unsupported_payment_channel':
        return 'Ce moyen de paiement n’est pas activé.';
      case 'payment_initialization_failed':
        return 'Impossible d’initialiser le paiement.';
      default:
        return 'Une erreur est survenue. Réessayez.';
    }
  }
}

class CheckoutException implements Exception {
  const CheckoutException(this.message);
  final String message;

  @override
  String toString() => message;
}
