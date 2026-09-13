class CustomerAccount {
  const CustomerAccount({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.email,
    this.phone,
  });

  final int id;
  final String firstName;
  final String lastName;
  final String? email;
  final String? phone;

  String get fullName => '$firstName $lastName'.trim();

  factory CustomerAccount.fromJson(Map<String, dynamic> json) => CustomerAccount(
        id: (json['id'] as num?)?.toInt() ?? 0,
        firstName: (json['first_name'] ?? '').toString(),
        lastName: (json['last_name'] ?? '').toString(),
        email: json['email']?.toString(),
        phone: json['phone']?.toString(),
      );
}

class CustomerOrderSummary {
  const CustomerOrderSummary({
    required this.orderNumber,
    required this.total,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.orderStatus,
    required this.createdAt,
    this.paymentChannel,
  });

  final String orderNumber;
  final int total;
  final String paymentMethod;
  final String? paymentChannel;
  final String paymentStatus;
  final String orderStatus;
  final DateTime? createdAt;

  factory CustomerOrderSummary.fromJson(Map<String, dynamic> json) => CustomerOrderSummary(
        orderNumber: (json['order_number'] ?? '').toString(),
        total: (json['total'] as num?)?.toInt() ?? 0,
        paymentMethod: (json['payment_method'] ?? '').toString(),
        paymentChannel: json['payment_channel']?.toString(),
        paymentStatus: (json['payment_status'] ?? 'unpaid').toString(),
        orderStatus: (json['order_status'] ?? 'pending').toString(),
        createdAt: DateTime.tryParse((json['created_at'] ?? '').toString()),
      );
}
