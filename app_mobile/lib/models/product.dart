class Product {
  final int? id;
  final String nom;
  final String codeProduit;
  final String? description;
  final String unite;
  final double prixUnitaire;
  final double? prixCredit;
  final int pointsFidelite;
  final bool isActive;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Product({
    this.id,
    required this.nom,
    required this.codeProduit,
    this.description,
    this.unite = 'pièce',
    required this.prixUnitaire,
    this.prixCredit,
    this.pointsFidelite = 1,
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      nom: json['nom'],
      codeProduit: json['code_produit'],
      description: json['description'],
      unite: json['unite'] ?? 'pièce',
      prixUnitaire: double.tryParse(json['prix_unitaire'].toString()) ?? 0.0,
      prixCredit: json['prix_credit'] != null
          ? double.tryParse(json['prix_credit'].toString())
          : null,
      pointsFidelite: int.tryParse(json['points_fidelite'].toString()) ?? 1,
      isActive: json['is_active'] == 1,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'nom': nom,
      'code_produit': codeProduit,
      'description': description,
      'unite': unite,
      'prix_unitaire': prixUnitaire,
      'prix_credit': prixCredit,
      'points_fidelite': pointsFidelite,
      'is_active': isActive ? 1 : 0,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }

  double getPrixByType(String typeVente) {
    return typeVente == 'credit' && prixCredit != null
        ? prixCredit!
        : prixUnitaire;
  }

  Product copyWith({
    int? id,
    String? nom,
    String? codeProduit,
    String? description,
    String? unite,
    double? prixUnitaire,
    double? prixCredit,
    int? pointsFidelite,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Product(
      id: id ?? this.id,
      nom: nom ?? this.nom,
      codeProduit: codeProduit ?? this.codeProduit,
      description: description ?? this.description,
      unite: unite ?? this.unite,
      prixUnitaire: prixUnitaire ?? this.prixUnitaire,
      prixCredit: prixCredit ?? this.prixCredit,
      pointsFidelite: pointsFidelite ?? this.pointsFidelite,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}
