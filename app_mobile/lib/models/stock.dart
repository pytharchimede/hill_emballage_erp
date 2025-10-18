class Stock {
  final int? id;
  final int produitId;
  final int depotId;
  final double quantite;
  final double quantiteReservee;
  final double seuillAlerte;
  final DateTime? lastUpdate;

  Stock({
    this.id,
    required this.produitId,
    required this.depotId,
    required this.quantite,
    this.quantiteReservee = 0.0,
    this.seuillAlerte = 0.0,
    this.lastUpdate,
  });

  factory Stock.fromJson(Map<String, dynamic> json) {
    return Stock(
      id: json['id'],
      produitId: json['produit_id'],
      depotId: json['depot_id'],
      quantite: double.tryParse(json['quantite'].toString()) ?? 0.0,
      quantiteReservee:
          double.tryParse(json['quantite_reservee'].toString()) ?? 0.0,
      seuillAlerte: double.tryParse(json['seuill_alerte'].toString()) ?? 0.0,
      lastUpdate: json['last_update'] != null
          ? DateTime.parse(json['last_update'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'produit_id': produitId,
      'depot_id': depotId,
      'quantite': quantite,
      'quantite_reservee': quantiteReservee,
      'seuill_alerte': seuillAlerte,
      if (lastUpdate != null) 'last_update': lastUpdate!.toIso8601String(),
    };
  }

  double get quantiteDisponible => quantite - quantiteReservee;

  bool get isEnRupture => quantiteDisponible <= 0;

  bool get isEnAlerte => quantiteDisponible <= seuillAlerte;

  Stock copyWith({
    int? id,
    int? produitId,
    int? depotId,
    double? quantite,
    double? quantiteReservee,
    double? seuillAlerte,
    DateTime? lastUpdate,
  }) {
    return Stock(
      id: id ?? this.id,
      produitId: produitId ?? this.produitId,
      depotId: depotId ?? this.depotId,
      quantite: quantite ?? this.quantite,
      quantiteReservee: quantiteReservee ?? this.quantiteReservee,
      seuillAlerte: seuillAlerte ?? this.seuillAlerte,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }
}
