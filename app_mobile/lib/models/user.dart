import 'dart:convert';

class User {
  final int id;
  final String username;
  final String email;
  final String fullName;
  final String role;
  final String? phone;
  final int? depotId;
  final String? depotNom;
  final bool isActive;
  final DateTime? lastLogin;
  final DateTime createdAt;

  User({
    required this.id,
    required this.username,
    required this.email,
    required this.fullName,
    required this.role,
    this.phone,
    this.depotId,
    this.depotNom,
    this.isActive = true,
    this.lastLogin,
    required this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      username: json['username'],
      email: json['email'],
      fullName: json['full_name'],
      role: json['role'],
      phone: json['phone'],
      depotId: json['depot_id'],
      depotNom: json['depot_nom'],
      isActive: json['is_active'] == 1,
      lastLogin: json['last_login'] != null
          ? DateTime.parse(json['last_login'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'email': email,
      'full_name': fullName,
      'role': role,
      'phone': phone,
      'depot_id': depotId,
      'depot_nom': depotNom,
      'is_active': isActive ? 1 : 0,
      'last_login': lastLogin?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
    };
  }

  String toJsonString() => json.encode(toJson());

  factory User.fromJsonString(String jsonString) =>
      User.fromJson(json.decode(jsonString));

  bool get isAdmin => role == 'admin';
  bool get isVendeur => role == 'vendeur';
  bool get isLivreur => role == 'livreur';
  bool get isComptable => role == 'comptable';

  String get roleDisplayName {
    switch (role) {
      case 'admin':
        return 'Administrateur';
      case 'vendeur':
        return 'Vendeur';
      case 'livreur':
        return 'Livreur';
      case 'comptable':
        return 'Comptable';
      default:
        return role;
    }
  }
}
