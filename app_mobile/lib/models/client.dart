class Client {
  final int? id;
  final String codeClient;
  final String nom;
  final String? prenoms;
  final String? telephone;
  final String? email;
  final String? adresse;
  final String? zone;
  final String typeClient;
  final double creditLimite;
  final double soldeCredit;
  final int pointsFidelite;
  final String? qrCode;
  final String? photoUrl;
  final bool isActive;
  final int? createdBy;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Client({
    this.id,
    required this.codeClient,
    required this.nom,
    this.prenoms,
    this.telephone,
    this.email,
    this.adresse,
    this.zone,
    this.typeClient = 'particulier',
    this.creditLimite = 0.0,
    this.soldeCredit = 0.0,
    this.pointsFidelite = 0,
    this.qrCode,
    this.photoUrl,
    this.isActive = true,
    this.createdBy,
    this.createdAt,
    this.updatedAt,
  });

  factory Client.fromJson(Map<String, dynamic> json) {
    return Client(
      id: json['id'],
      codeClient: json['code_client'],
      nom: json['nom'],
      prenoms: json['prenoms'],
      telephone: json['telephone'],
      email: json['email'],
      adresse: json['adresse'],
      zone: json['zone'],
      typeClient: json['type_client'] ?? 'particulier',
      creditLimite: double.tryParse(json['credit_limite'].toString()) ?? 0.0,
      soldeCredit: double.tryParse(json['solde_credit'].toString()) ?? 0.0,
      pointsFidelite: int.tryParse(json['points_fidelite'].toString()) ?? 0,
      qrCode: json['qr_code'],
      photoUrl: json['photo_url'],
      isActive: json['is_active'] == 1,
      createdBy: json['created_by'],
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
      'code_client': codeClient,
      'nom': nom,
      'prenoms': prenoms,
      'telephone': telephone,
      'email': email,
      'adresse': adresse,
      'zone': zone,
      'type_client': typeClient,
      'credit_limite': creditLimite,
      'solde_credit': soldeCredit,
      'points_fidelite': pointsFidelite,
      'qr_code': qrCode,
      'photo_url': photoUrl,
      'is_active': isActive ? 1 : 0,
      'created_by': createdBy,
      if (createdAt != null) 'created_at': createdAt!.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
    };
  }

  String get nomComplet => '$nom${prenoms != null ? ' $prenoms' : ''}';

  bool get isEntreprise => typeClient == 'entreprise';
  bool get isParticulier => typeClient == 'particulier';

  double get creditDisponible => creditLimite - soldeCredit;

  String get statut {
    if (!isActive) return 'Inactif';
    if (soldeCredit > 0) return 'Débiteur';
    return 'Actif';
  }

  Client copyWith({
    int? id,
    String? codeClient,
    String? nom,
    String? prenoms,
    String? telephone,
    String? email,
    String? adresse,
    String? zone,
    String? typeClient,
    double? creditLimite,
    double? soldeCredit,
    int? pointsFidelite,
    String? qrCode,
    String? photoUrl,
    bool? isActive,
    int? createdBy,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Client(
      id: id ?? this.id,
      codeClient: codeClient ?? this.codeClient,
      nom: nom ?? this.nom,
      prenoms: prenoms ?? this.prenoms,
      telephone: telephone ?? this.telephone,
      email: email ?? this.email,
      adresse: adresse ?? this.adresse,
      zone: zone ?? this.zone,
      typeClient: typeClient ?? this.typeClient,
      creditLimite: creditLimite ?? this.creditLimite,
      soldeCredit: soldeCredit ?? this.soldeCredit,
      pointsFidelite: pointsFidelite ?? this.pointsFidelite,
      qrCode: qrCode ?? this.qrCode,
      photoUrl: photoUrl ?? this.photoUrl,
      isActive: isActive ?? this.isActive,
      createdBy: createdBy ?? this.createdBy,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}
