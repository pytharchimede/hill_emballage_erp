class Payment {
  final int? id;
  final int venteId;
  final String numeroRecu;
  final DateTime datePayment;
  final double montant;
  final String modePayment; // 'espece', 'cheque', 'virement', 'mobile'
  final String statut; // 'valide', 'attente', 'rejete'
  final String? reference;
  final String? commentaire;
  final DateTime? createdAt;

  Payment({
    this.id,
    required this.venteId,
    required this.numeroRecu,
    required this.datePayment,
    required this.montant,
    this.modePayment = 'espece',
    this.statut = 'valide',
    this.reference,
    this.commentaire,
    this.createdAt,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'],
      venteId: json['vente_id'],
      numeroRecu: json['numero_recu'],
      datePayment: DateTime.parse(json['date_payment']),
      montant: double.tryParse(json['montant'].toString()) ?? 0.0,
      modePayment: json['mode_payment'] ?? 'espece',
      statut: json['statut'] ?? 'valide',
      reference: json['reference'],
      commentaire: json['commentaire'],
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'vente_id': venteId,
      'numero_recu': numeroRecu,
      'date_payment': datePayment.toIso8601String().split('T')[0],
      'montant': montant,
      'mode_payment': modePayment,
      'statut': statut,
      'reference': reference,
      'commentaire': commentaire,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
    };
  }

  Payment copyWith({
    int? id,
    int? venteId,
    String? numeroRecu,
    DateTime? datePayment,
    double? montant,
    String? modePayment,
    String? statut,
    String? reference,
    String? commentaire,
    DateTime? createdAt,
  }) {
    return Payment(
      id: id ?? this.id,
      venteId: venteId ?? this.venteId,
      numeroRecu: numeroRecu ?? this.numeroRecu,
      datePayment: datePayment ?? this.datePayment,
      montant: montant ?? this.montant,
      modePayment: modePayment ?? this.modePayment,
      statut: statut ?? this.statut,
      reference: reference ?? this.reference,
      commentaire: commentaire ?? this.commentaire,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
