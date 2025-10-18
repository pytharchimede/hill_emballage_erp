import '../models/client.dart';
import 'api_service.dart';

class ClientService {
  // Récupérer tous les clients
  static Future<List<Client>> getAllClients() async {
    final data = await ApiService.get('clients.php');

    if (data['success'] == true && data['clients'] != null) {
      return (data['clients'] as List)
          .map((clientJson) => Client.fromJson(clientJson))
          .toList();
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la récupération des clients',
      );
    }
  }

  // Récupérer un client par ID
  static Future<Client> getClient(int id) async {
    final data = await ApiService.get('clients.php?id=$id');

    if (data['success'] == true && data['client'] != null) {
      return Client.fromJson(data['client']);
    } else {
      throw Exception(data['message'] ?? 'Client non trouvé');
    }
  }

  // Créer un nouveau client
  static Future<Client> createClient(Client client) async {
    final data = await ApiService.post('clients.php', client.toJson());

    if (data['success'] == true && data['client'] != null) {
      return Client.fromJson(data['client']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la création du client',
      );
    }
  }

  // Mettre à jour un client
  static Future<Client> updateClient(Client client) async {
    final data = await ApiService.put('clients.php', client.toJson());

    if (data['success'] == true && data['client'] != null) {
      return Client.fromJson(data['client']);
    } else {
      throw Exception(
        data['message'] ?? 'Erreur lors de la mise à jour du client',
      );
    }
  }

  // Supprimer un client
  static Future<bool> deleteClient(int id) async {
    final data = await ApiService.delete('clients.php?id=$id');

    return data['success'] == true;
  }

  // Rechercher des clients
  static Future<List<Client>> searchClients(String query) async {
    final data = await ApiService.get(
      'clients.php?search=${Uri.encodeComponent(query)}',
    );

    if (data['success'] == true && data['clients'] != null) {
      return (data['clients'] as List)
          .map((clientJson) => Client.fromJson(clientJson))
          .toList();
    } else {
      return [];
    }
  }

  // Obtenir les clients avec crédit
  static Future<List<Client>> getClientsWithCredit() async {
    final data = await ApiService.get('clients.php?filter=credit');

    if (data['success'] == true && data['clients'] != null) {
      return (data['clients'] as List)
          .map((clientJson) => Client.fromJson(clientJson))
          .toList();
    } else {
      return [];
    }
  }

  // Mettre à jour les points de fidélité
  static Future<bool> updateFidelityPoints(int clientId, int points) async {
    final data = await ApiService.post('clients.php', {
      'action': 'update_points',
      'client_id': clientId,
      'points': points,
    });

    return data['success'] == true;
  }
}
