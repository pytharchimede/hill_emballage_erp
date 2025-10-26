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
    DateTime? _parseDate(dynamic v) {
      if (v == null) return null;
      if (v is DateTime) return v;
      final s = v.toString().trim();
      if (s.isEmpty) return null;
      // Accepte formats MySQL "YYYY-MM-DD HH:MM:SS" ou ISO8601
      final iso = s.contains('T') ? s : s.replaceFirst(' ', 'T');
      try {
        return DateTime.parse(iso);
      } catch (_) {
        return null;
      }
    }

    bool _toBool(dynamic v) {
      if (v is bool) return v;
      final s = v?.toString() ?? '0';
      return s == '1' || s.toLowerCase() == 'true';
    }

    int? _toInt(dynamic v) {
      if (v == null) return null;
      if (v is int) return v;
      if (v is double) return v.toInt();
      final s = v.toString().trim();
      if (s.isEmpty) return null;
      final parsed = int.tryParse(s);
      return parsed;
    }

    return User(
      id: _toInt(json['id']) ?? 0,
      username: json['username'],
      email: json['email'],
      fullName: json['full_name'],
      role: json['role'],
      phone: json['phone'],
      depotId: _toInt(json['depot_id']),
      depotNom: json['depot_nom'],
      isActive: _toBool(json['is_active']),
      lastLogin: _parseDate(json['last_login']),
      createdAt: _parseDate(json['created_at']) ?? DateTime.now(),
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
