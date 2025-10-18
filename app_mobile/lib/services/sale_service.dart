import '../models/sale.dart';
import 'api_service.dart';

class SaleService {
  // Récupérer toutes les ventes
  static Future<List<Sale>> getAllSales() async {
    final data = await ApiService.get('sales.php');

    if (data['success'] == true && data['sales'] != null) {
      return (data['sales'] as List)
          .map((saleJson) => Sale.fromJson(saleJson))
          .toList();
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération des ventes',
      );
    }
  }

  // Récupérer une vente par ID
  static Future<Sale> getSale(int id) async {
    final data = await ApiService.get('sales.php?id=$id');

    if (data['success'] == true && data['sale'] != null) {
      return Sale.fromJson(data['sale']);
    } else {
      throw Exception(data['message'] ?? 'Vente non trouvée');
    }
  }

  // Créer une nouvelle vente
  static Future<Sale> createSale(Sale sale) async {
    final data = await ApiService.post('sales.php', sale.toJson());

    if (data['success'] == true && data['sale'] != null) {
      return Sale.fromJson(data['sale']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la création de la vente',
      );
    }
  }

  // Mettre à jour une vente
  static Future<Sale> updateSale(Sale sale) async {
    final data = await ApiService.put('sales.php', sale.toJson());

    if (data['success'] == true && data['sale'] != null) {
      return Sale.fromJson(data['sale']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour de la vente',
      );
    }
  }

  // Supprimer une vente
  static Future<bool> deleteSale(int id) async {
    final data = await ApiService.delete('sales.php?id=$id');

    return data['success'] == true;
  }

  // Récupérer les ventes par client
  static Future<List<Sale>> getSalesByClient(int clientId) async {
    final data = await ApiService.get('sales.php?client_id=$clientId');

    if (data['success'] == true && data['sales'] != null) {
      return (data['sales'] as List)
          .map((saleJson) => Sale.fromJson(saleJson))
          .toList();
    } else {
      return [];
    }
  }

  // Récupérer les ventes par période
  static Future<List<Sale>> getSalesByPeriod(
    DateTime startDate,
    DateTime endDate,
  ) async {
    final start = startDate.toIso8601String().split('T')[0];
    final end = endDate.toIso8601String().split('T')[0];

    final data = await ApiService.get(
      'sales.php?start_date=$start&end_date=$end',
    );

    if (data['success'] == true && data['sales'] != null) {
      return (data['sales'] as List)
          .map((saleJson) => Sale.fromJson(saleJson))
          .toList();
    } else {
      return [];
    }
  }

  // Récupérer les ventes en crédit
  static Future<List<Sale>> getCreditSales() async {
    final data = await ApiService.get('sales.php?type=credit');

    if (data['success'] == true && data['sales'] != null) {
      return (data['sales'] as List)
          .map((saleJson) => Sale.fromJson(saleJson))
          .toList();
    } else {
      return [];
    }
  }

  // Valider une vente
  static Future<bool> validateSale(int saleId) async {
    final data = await ApiService.post('sales.php', {
      'action': 'validate',
      'sale_id': saleId,
    });

    return data['success'] == true;
  }

  // Livrer une vente
  static Future<bool> deliverSale(int saleId) async {
    final data = await ApiService.post('sales.php', {
      'action': 'deliver',
      'sale_id': saleId,
    });

    return data['success'] == true;
  }

  // Annuler une vente
  static Future<bool> cancelSale(int saleId, String reason) async {
    final data = await ApiService.post('sales.php', {
      'action': 'cancel',
      'sale_id': saleId,
      'reason': reason,
    });

    return data['success'] == true;
  }

  // Générer un rapport de ventes
  static Future<Map<String, dynamic>> getSalesReport(
    DateTime startDate,
    DateTime endDate,
  ) async {
    final start = startDate.toIso8601String().split('T')[0];
    final end = endDate.toIso8601String().split('T')[0];

    final data = await ApiService.get(
      'sales.php?action=report&start_date=$start&end_date=$end',
    );

    if (data['success'] == true) {
      return data['report'];
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la génération du rapport',
      );
    }
  }
}
