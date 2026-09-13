import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../providers/cart_provider.dart';
import '../theme/app_theme.dart';
import 'checkout_screen.dart';

class CheckoutCartPage extends StatelessWidget {
  const CheckoutCartPage({super.key});

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return SafeArea(
      child: Consumer<CartProvider>(
        builder: (context, cart, _) => Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(18, 20, 18, 12),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text('Mon panier', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
              ),
            ),
            Expanded(
              child: cart.items.isEmpty
                  ? const Center(child: Text('Votre panier est vide.', style: TextStyle(color: HillColors.muted)))
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: cart.items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (_, index) {
                        final item = cart.items[index];
                        return Card(
                          child: ListTile(
                            title: Text(item.name, maxLines: 2, overflow: TextOverflow.ellipsis),
                            subtitle: Text('${money.format(item.price)} FCFA × ${item.quantity}'),
                            trailing: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                IconButton(onPressed: () => cart.decrement(item.id), icon: const Icon(Icons.remove_circle_outline_rounded)),
                                Text('${item.quantity}', style: const TextStyle(fontWeight: FontWeight.w900)),
                                IconButton(onPressed: () => cart.add(id: item.id, name: item.name, price: item.price), icon: const Icon(Icons.add_circle_rounded)),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
            if (cart.items.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
                child: Column(
                  children: [
                    Row(
                      children: [
                        const Expanded(child: Text('Total', style: TextStyle(fontWeight: FontWeight.w700))),
                        Text('${money.format(cart.total)} FCFA', style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w900)),
                      ],
                    ),
                    const SizedBox(height: 12),
                    ElevatedButton(
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => const CheckoutScreen()),
                      ),
                      child: const Text('Continuer vers la livraison'),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}
