class Depot {
  final int? id;
  final String nom;
  final String? adresse;
  final String? responsable;
  final String? telephone;
  final bool isActive;
  final DateTime? createdAt;

  Depot({
    this.id,
    required this.nom,
    this.adresse,
    this.responsable,
    this.telephone,
    this.isActive = true,
    this.createdAt,
  });

  factory Depot.fromJson(Map<String, dynamic> json) {
    return Depot(
      id: json['id'],
      nom: json['nom'],
      adresse: json['adresse'],
      responsable: json['responsable'],
      telephone: json['telephone'],
      isActive: json['is_active'] == 1,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'nom': nom,
      'adresse': adresse,
      'responsable': responsable,
      'telephone': telephone,
      'is_active': isActive ? 1 : 0,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
    };
  }

  Depot copyWith({
    int? id,
    String? nom,
    String? adresse,
    String? responsable,
    String? telephone,
    bool? isActive,
    DateTime? createdAt,
  }) {
    return Depot(
      id: id ?? this.id,
      nom: nom ?? this.nom,
      adresse: adresse ?? this.adresse,
      responsable: responsable ?? this.responsable,
      telephone: telephone ?? this.telephone,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
