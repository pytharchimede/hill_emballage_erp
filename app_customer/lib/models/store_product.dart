class StoreProduct {
  const StoreProduct({
    required this.id,
    required this.name,
    required this.code,
    required this.description,
    required this.unit,
    required this.price,
    required this.imageUrl,
    required this.category,
  });

  final int id;
  final String name;
  final String code;
  final String description;
  final String unit;
  final int price;
  final String imageUrl;
  final String category;

  factory StoreProduct.fromJson(Map<String, dynamic> json) {
    return StoreProduct(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? '').toString(),
      code: (json['code'] ?? '').toString(),
      description: (json['description'] ?? '').toString(),
      unit: (json['unit'] ?? 'pièce').toString(),
      price: (json['price'] as num?)?.round() ?? 0,
      imageUrl: (json['image_url'] ?? '').toString(),
      category: (json['category'] ?? 'Catalogue').toString(),
    );
  }
}
