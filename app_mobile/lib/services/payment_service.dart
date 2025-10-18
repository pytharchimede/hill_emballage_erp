import '../models/payment.dart';
import 'api_service.dart';

class PaymentService {
  // Récupérer tous les paiements
  static Future<List<Payment>> getAllPayments() async {
    final data = await ApiService.get('payments.php');

    if (data['success'] == true && data['payments'] != null) {
      return (data['payments'] as List)
          .map((paymentJson) => Payment.fromJson(paymentJson))
          .toList();
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération des paiements',
      );
    }
  }

  // Récupérer un paiement par ID
  static Future<Payment> getPayment(int id) async {
    final data = await ApiService.get('payments.php?id=$id');

    if (data['success'] == true && data['payment'] != null) {
      return Payment.fromJson(data['payment']);
    } else {
      throw Exception(data['message'] ?? 'Paiement non trouvé');
    }
  }

  // Créer un nouveau paiement
  static Future<Payment> createPayment(Payment payment) async {
    final data = await ApiService.post('payments.php', payment.toJson());

    if (data['success'] == true && data['payment'] != null) {
      return Payment.fromJson(data['payment']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la création du paiement',
      );
    }
  }

  // Mettre à jour un paiement
  static Future<Payment> updatePayment(Payment payment) async {
    final data = await ApiService.put('payments.php', payment.toJson());

    if (data['success'] == true && data['payment'] != null) {
      return Payment.fromJson(data['payment']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour du paiement',
      );
    }
  }

  // Supprimer un paiement
  static Future<bool> deletePayment(int id) async {
    final data = await ApiService.delete('payments.php?id=$id');

    return data['success'] == true;
  }

  // Récupérer les paiements par vente
  static Future<List<Payment>> getPaymentsBySale(int venteId) async {
    final data = await ApiService.get('payments.php?vente_id=$venteId');

    if (data['success'] == true && data['payments'] != null) {
      return (data['payments'] as List)
          .map((paymentJson) => Payment.fromJson(paymentJson))
          .toList();
    } else {
      return [];
    }
  }

  // Récupérer les paiements par période
  static Future<List<Payment>> getPaymentsByPeriod(
    DateTime startDate,
    DateTime endDate,
  ) async {
    final start = startDate.toIso8601String().split('T')[0];
    final end = endDate.toIso8601String().split('T')[0];

    final data = await ApiService.get(
      'payments.php?start_date=$start&end_date=$end',
    );

    if (data['success'] == true && data['payments'] != null) {
      return (data['payments'] as List)
          .map((paymentJson) => Payment.fromJson(paymentJson))
          .toList();
    } else {
      return [];
    }
  }

  // Valider un paiement
  static Future<bool> validatePayment(int paymentId) async {
    final data = await ApiService.post('payments.php', {
      'action': 'validate',
      'payment_id': paymentId,
    });

    return data['success'] == true;
  }

  // Rejeter un paiement
  static Future<bool> rejectPayment(int paymentId, String reason) async {
    final data = await ApiService.post('payments.php', {
      'action': 'reject',
      'payment_id': paymentId,
      'reason': reason,
    });

    return data['success'] == true;
  }

  // Générer un reçu
  static Future<Map<String, dynamic>> generateReceipt(int paymentId) async {
    final data = await ApiService.get(
      'payments.php?action=receipt&payment_id=$paymentId',
    );

    if (data['success'] == true) {
      return data['receipt'];
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la génération du reçu',
      );
    }
  }

  // Statistiques des paiements
  static Future<Map<String, dynamic>> getPaymentStats(
    DateTime startDate,
    DateTime endDate,
  ) async {
    final start = startDate.toIso8601String().split('T')[0];
    final end = endDate.toIso8601String().split('T')[0];

    final data = await ApiService.get(
      'payments.php?action=stats&start_date=$start&end_date=$end',
    );

    if (data['success'] == true) {
      return data['stats'];
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération des statistiques',
      );
    }
  }
}
