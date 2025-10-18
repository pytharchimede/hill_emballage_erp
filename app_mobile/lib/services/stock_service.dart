import '../models/stock.dart';
import '../models/product.dart';
import 'api_service.dart';

class StockService {
  // Récupérer tout le stock
  static Future<List<Stock>> getAllStock() async {
    final data = await ApiService.get('stock.php');

    if (data['success'] == true && data['stock'] != null) {
      return (data['stock'] as List)
          .map((stockJson) => Stock.fromJson(stockJson))
          .toList();
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération du stock',
      );
    }
  }

  // Récupérer le stock par dépôt
  static Future<List<Stock>> getStockByDepot(int depotId) async {
    final data = await ApiService.get('stock.php?depot_id=$depotId');

    if (data['success'] == true && data['stock'] != null) {
      return (data['stock'] as List)
          .map((stockJson) => Stock.fromJson(stockJson))
          .toList();
    } else {
      return [];
    }
  }

  // Récupérer le stock d'un produit
  static Future<Stock?> getProductStock(int produitId, int depotId) async {
    final data = await ApiService.get(
      'stock.php?produit_id=$produitId&depot_id=$depotId',
    );

    if (data['success'] == true && data['stock'] != null) {
      return Stock.fromJson(data['stock']);
    } else {
      return null;
    }
  }

  // Mettre à jour le stock
  static Future<Stock> updateStock(Stock stock) async {
    final data = await ApiService.put('stock.php', stock.toJson());

    if (data['success'] == true && data['stock'] != null) {
      return Stock.fromJson(data['stock']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour du stock',
      );
    }
  }

  // Ajuster le stock
  static Future<bool> adjustStock(
    int produitId,
    int depotId,
    double quantite,
    String motif,
  ) async {
    final data = await ApiService.post('stock.php', {
      'action': 'adjust',
      'produit_id': produitId,
      'depot_id': depotId,
      'quantite': quantite,
      'motif': motif,
    });

    return data['success'] == true;
  }

  // Transférer du stock
  static Future<bool> transferStock({
    required int produitId,
    required int depotSource,
    required int depotDestination,
    required double quantite,
    String? motif,
  }) async {
    final data = await ApiService.post('stock.php', {
      'action': 'transfer',
      'produit_id': produitId,
      'depot_source': depotSource,
      'depot_destination': depotDestination,
      'quantite': quantite,
      'motif': motif,
    });

    return data['success'] == true;
  }

  // Récupérer les produits en rupture
  static Future<List<Map<String, dynamic>>> getOutOfStock() async {
    final data = await ApiService.get('stock.php?filter=out_of_stock');

    if (data['success'] == true && data['products'] != null) {
      return List<Map<String, dynamic>>.from(data['products']);
    } else {
      return [];
    }
  }

  // Récupérer les produits en alerte
  static Future<List<Map<String, dynamic>>> getLowStock() async {
    final data = await ApiService.get('stock.php?filter=low_stock');

    if (data['success'] == true && data['products'] != null) {
      return List<Map<String, dynamic>>.from(data['products']);
    } else {
      return [];
    }
  }

  // Récupérer tous les produits
  static Future<List<Product>> getAllProducts() async {
    final data = await ApiService.get('stock.php?action=products');

    if (data['success'] == true && data['products'] != null) {
      return (data['products'] as List)
          .map((productJson) => Product.fromJson(productJson))
          .toList();
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération des produits',
      );
    }
  }

  // Créer un nouveau produit
  static Future<Product> createProduct(Product product) async {
    final data = await ApiService.post('stock.php', {
      'action': 'create_product',
      ...product.toJson(),
    });

    if (data['success'] == true && data['product'] != null) {
      return Product.fromJson(data['product']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la création du produit',
      );
    }
  }

  // Mettre à jour un produit
  static Future<Product> updateProduct(Product product) async {
    final data = await ApiService.put('stock.php', {
      'action': 'update_product',
      ...product.toJson(),
    });

    if (data['success'] == true && data['product'] != null) {
      return Product.fromJson(data['product']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour du produit',
      );
    }
  }

  // Récupérer l'historique des mouvements de stock
  static Future<List<Map<String, dynamic>>> getStockHistory(
    int produitId,
    int depotId,
  ) async {
    final data = await ApiService.get(
      'stock.php?action=history&produit_id=$produitId&depot_id=$depotId',
    );

    if (data['success'] == true && data['history'] != null) {
      return List<Map<String, dynamic>>.from(data['history']);
    } else {
      return [];
    }
  }
}
