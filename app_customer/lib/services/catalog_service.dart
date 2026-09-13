import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/store_product.dart';

class CatalogService {
  static const String _baseUrl = String.fromEnvironment(
    'HILL_API_BASE_URL',
    defaultValue: 'http://10.0.2.2/hill_emballage_pos',
  );

  Uri _catalogUri([String query = '']) {
    final params = <String, String>{'limit': '100'};
    if (query.trim().isNotEmpty) params['q'] = query.trim();
    return Uri.parse('$_baseUrl/storefront_api/catalog.php').replace(
      queryParameters: params,
    );
  }

  Future<List<StoreProduct>> fetchProducts({String query = ''}) async {
    final response = await http.get(
      _catalogUri(query),
      headers: const {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw Exception('Catalogue indisponible (${response.statusCode})');
    }

    final decoded = jsonDecode(response.body);
    final list = decoded is Map<String, dynamic>
        ? decoded['data'] as List<dynamic>? ?? const []
        : decoded as List<dynamic>? ?? const [];

    return list
        .whereType<Map<String, dynamic>>()
        .map(StoreProduct.fromJson)
        .where((product) => product.id > 0 && product.name.isNotEmpty)
        .toList(growable: false);
  }
}
