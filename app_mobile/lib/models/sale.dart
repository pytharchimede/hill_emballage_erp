class Sale {
  final int? id;
  final int clientId;
  final int userId;
  final String numeroVente;
  final DateTime dateVente;
  final String typeVente; // 'comptant' ou 'credit'
  final double montantTotal;
  final double montantPaye;
  final String statut; // 'en_attente', 'validee', 'livree', 'annulee'
  final DateTime? dateEcheance;
  final String? commentaire;
  final List<SaleItem> items;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Sale({
    this.id,
    required this.clientId,
    required this.userId,
    required this.numeroVente,
    required this.dateVente,
    this.typeVente = 'comptant',
    required this.montantTotal,
    this.montantPaye = 0.0,
    this.statut = 'en_attente',
    this.dateEcheance,
    this.commentaire,
    this.items = const [],
    this.createdAt,
    this.updatedAt,
  });

  factory Sale.fromJson(Map<String, dynamic> json) {
    return Sale(
      id: json['id'],
      clientId: json['client_id'],
      userId: json['user_id'],
      numeroVente: json['numero_vente'],
      dateVente: DateTime.parse(json['date_vente']),
      typeVente: json['type_vente'] ?? 'comptant',
      montantTotal: double.tryParse(json['montant_total'].toString()) ?? 0.0,
      montantPaye: double.tryParse(json['montant_paye'].toString()) ?? 0.0,
      statut: json['statut'] ?? 'en_attente',
      dateEcheance: json['date_echeance'] != null
          ? DateTime.parse(json['date_echeance'])
          : null,
      commentaire: json['commentaire'],
      items: json['items'] != null
          ? (json['items'] as List)
                .map((item) => SaleItem.fromJson(item))
                .toList()
          : [],
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
      'client_id': clientId,
      'user_id': userId,
      'numero_vente': numeroVente,
      'date_vente': dateVente.toIso8601String().split('T')[0],
      'type_vente': typeVente,
      'montant_total': montantTotal,
      'montant_paye': montantPaye,
      'statut': statut,
      'date_echeance': dateEcheance?.toIso8601String().split('T')[0],
      'commentaire': commentaire,
      'items': items.map((item) => item.toJson()).toList(),
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }

  double get montantRestant => montantTotal - montantPaye;

  bool get isPayee => montantRestant <= 0;

  bool get isEnRetard {
    if (dateEcheance == null) return false;
    return DateTime.now().isAfter(dateEcheance!) && !isPayee;
  }

  Sale copyWith({
    int? id,
    int? clientId,
    int? userId,
    String? numeroVente,
    DateTime? dateVente,
    String? typeVente,
    double? montantTotal,
    double? montantPaye,
    String? statut,
    DateTime? dateEcheance,
    String? commentaire,
    List<SaleItem>? items,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Sale(
      id: id ?? this.id,
      clientId: clientId ?? this.clientId,
      userId: userId ?? this.userId,
      numeroVente: numeroVente ?? this.numeroVente,
      dateVente: dateVente ?? this.dateVente,
      typeVente: typeVente ?? this.typeVente,
      montantTotal: montantTotal ?? this.montantTotal,
      montantPaye: montantPaye ?? this.montantPaye,
      statut: statut ?? this.statut,
      dateEcheance: dateEcheance ?? this.dateEcheance,
      commentaire: commentaire ?? this.commentaire,
      items: items ?? this.items,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}

class SaleItem {
  final int? id;
  final int produitId;
  final String nomProduit;
  final double quantite;
  final double prixUnitaire;
  final double montant;

  SaleItem({
    this.id,
    required this.produitId,
    required this.nomProduit,
    required this.quantite,
    required this.prixUnitaire,
    required this.montant,
  });

  factory SaleItem.fromJson(Map<String, dynamic> json) {
    return SaleItem(
      id: json['id'],
      produitId: json['produit_id'],
      nomProduit: json['nom_produit'],
      quantite: double.tryParse(json['quantite'].toString()) ?? 0.0,
      prixUnitaire: double.tryParse(json['prix_unitaire'].toString()) ?? 0.0,
      montant: double.tryParse(json['montant'].toString()) ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'produit_id': produitId,
      'nom_produit': nomProduit,
      'quantite': quantite,
      'prix_unitaire': prixUnitaire,
      'montant': montant,
    };
  }

  SaleItem copyWith({
    int? id,
    int? produitId,
    String? nomProduit,
    double? quantite,
    double? prixUnitaire,
    double? montant,
  }) {
    return SaleItem(
      id: id ?? this.id,
      produitId: produitId ?? this.produitId,
      nomProduit: nomProduit ?? this.nomProduit,
      quantite: quantite ?? this.quantite,
      prixUnitaire: prixUnitaire ?? this.prixUnitaire,
      montant: montant ?? this.montant,
    );
  }
}
