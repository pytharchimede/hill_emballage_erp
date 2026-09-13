import 'package:flutter/foundation.dart';

import '../models/store_product.dart';
import '../services/catalog_service.dart';

class CatalogProvider extends ChangeNotifier {
  CatalogProvider(this._service);

  final CatalogService _service;

  bool _isLoading = false;
  String? _error;
  String _query = '';
  List<StoreProduct> _products = const [];

  bool get isLoading => _isLoading;
  String? get error => _error;
  String get query => _query;
  List<StoreProduct> get products => _products;

  List<String> get categories {
    final values = _products
        .map((product) => product.category.trim())
        .where((category) => category.isNotEmpty)
        .toSet()
        .toList();
    values.sort();
    return values;
  }

  Future<void> load({String query = ''}) async {
    _isLoading = true;
    _error = null;
    _query = query;
    notifyListeners();

    try {
      _products = await _service.fetchProducts(query: query);
    } catch (e) {
      _error = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
