import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/customer_account.dart';
import '../services/customer_account_service.dart';
import '../theme/app_theme.dart';

class OrderHistoryScreen extends StatefulWidget {
  const OrderHistoryScreen({super.key});

  @override
  State<OrderHistoryScreen> createState() => _OrderHistoryScreenState();
}

class _OrderHistoryScreenState extends State<OrderHistoryScreen> {
  final _service = CustomerAccountService();
  late Future<List<CustomerOrderSummary>> _future;

  @override
  void initState() {
    super.initState();
    _future = _service.orders();
  }

  Future<void> _refresh() async {
    setState(() => _future = _service.orders());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(18, 20, 18, 8),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text('Mes commandes', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
            ),
          ),
          const Padding(
            padding: EdgeInsets.fromLTRB(18, 0, 18, 14),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text('Suivez la préparation et la livraison en temps réel.', style: TextStyle(color: HillColors.muted)),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<CustomerOrderSummary>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.lock_outline_rounded, size: 54),
                          const SizedBox(height: 12),
                          const Text('Connectez-vous pour consulter vos commandes.', textAlign: TextAlign.center),
                          const SizedBox(height: 12),
                          OutlinedButton.icon(onPressed: _refresh, icon: const Icon(Icons.refresh_rounded), label: const Text('Réessayer')),
                        ],
                      ),
                    ),
                  );
                }
                final orders = snapshot.data ?? const [];
                if (orders.isEmpty) {
                  return RefreshIndicator(
                    onRefresh: _refresh,
                    child: ListView(children: const [SizedBox(height: 180), Icon(Icons.receipt_long_outlined, size: 56), SizedBox(height: 12), Center(child: Text('Aucune commande pour le moment.'))]),
                  );
                }
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 6, 16, 24),
                    itemCount: orders.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (_, index) => _OrderCard(order: orders[index]),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({required this.order});
  final CustomerOrderSummary order;

  static const _steps = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered'];

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    final current = _steps.indexOf(order.orderStatus);
    final cancelled = order.orderStatus == 'cancelled';
    final date = order.createdAt == null ? '' : DateFormat('dd/MM/yyyy · HH:mm').format(order.createdAt!.toLocal());

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(order.orderNumber, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900))),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(color: cancelled ? const Color(0xFFFFE7E7) : HillColors.yellow, borderRadius: BorderRadius.circular(99)),
                  child: Text(_statusLabel(order.orderStatus), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900)),
                ),
              ],
            ),
            if (date.isNotEmpty) ...[const SizedBox(height: 4), Text(date, style: const TextStyle(fontSize: 12, color: HillColors.muted))],
            const SizedBox(height: 14),
            Row(children: [
              Expanded(child: Text('${money.format(order.total)} FCFA', style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w900))),
              Text(_paymentLabel(order), style: const TextStyle(fontSize: 12, color: HillColors.muted, fontWeight: FontWeight.w700)),
            ]),
            const SizedBox(height: 16),
            if (cancelled)
              const Text('Cette commande a été annulée.', style: TextStyle(fontWeight: FontWeight.w700))
            else
              Row(
                children: List.generate(_steps.length, (index) {
                  final active = current >= index;
                  return Expanded(
                    child: Row(
                      children: [
                        Container(width: 14, height: 14, decoration: BoxDecoration(shape: BoxShape.circle, color: active ? HillColors.yellow : HillColors.line)),
                        if (index < _steps.length - 1) Expanded(child: Container(height: 3, color: current > index ? HillColors.yellow : HillColors.line)),
                      ],
                    ),
                  );
                }),
              ),
            if (!cancelled) ...[
              const SizedBox(height: 8),
              Text(_nextMessage(order.orderStatus), style: const TextStyle(fontSize: 12, color: HillColors.muted)),
            ],
          ],
        ),
      ),
    );
  }

  static String _statusLabel(String status) {
    switch (status) {
      case 'confirmed': return 'Confirmée';
      case 'preparing': return 'Préparation';
      case 'ready': return 'Prête';
      case 'out_for_delivery': return 'En livraison';
      case 'delivered': return 'Livrée';
      case 'cancelled': return 'Annulée';
      default: return 'Reçue';
    }
  }

  static String _nextMessage(String status) {
    switch (status) {
      case 'confirmed': return 'Votre commande a été confirmée par Hill Emballage.';
      case 'preparing': return 'Nos équipes préparent actuellement vos articles.';
      case 'ready': return 'Votre commande est prête pour la livraison.';
      case 'out_for_delivery': return 'Votre commande est en route vers vous.';
      case 'delivered': return 'Commande livrée. Merci pour votre confiance.';
      default: return 'Nous avons bien reçu votre commande.';
    }
  }

  static String _paymentLabel(CustomerOrderSummary order) {
    if (order.paymentMethod == 'cash_on_delivery') return 'Paiement à la livraison';
    final channel = order.paymentChannel?.trim();
    return channel == null || channel.isEmpty ? 'Paiement Pro' : 'Paiement Pro · $channel';
  }
}
