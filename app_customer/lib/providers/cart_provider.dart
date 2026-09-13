import 'package:flutter/foundation.dart';

class CartItem {
  const CartItem({
    required this.id,
    required this.name,
    required this.price,
    required this.quantity,
  });

  final int id;
  final String name;
  final int price;
  final int quantity;

  CartItem copyWith({int? quantity}) => CartItem(
        id: id,
        name: name,
        price: price,
        quantity: quantity ?? this.quantity,
      );
}

class CartProvider extends ChangeNotifier {
  final Map<int, CartItem> _items = {};

  List<CartItem> get items => _items.values.toList(growable: false);
  int get count => _items.values.fold(0, (sum, item) => sum + item.quantity);
  int get total => _items.values.fold(0, (sum, item) => sum + item.price * item.quantity);

  void add({required int id, required String name, required int price}) {
    final current = _items[id];
    _items[id] = current == null
        ? CartItem(id: id, name: name, price: price, quantity: 1)
        : current.copyWith(quantity: current.quantity + 1);
    notifyListeners();
  }

  void decrement(int id) {
    final current = _items[id];
    if (current == null) return;
    if (current.quantity <= 1) {
      _items.remove(id);
    } else {
      _items[id] = current.copyWith(quantity: current.quantity - 1);
    }
    notifyListeners();
  }

  void clear() {
    _items.clear();
    notifyListeners();
  }
}
