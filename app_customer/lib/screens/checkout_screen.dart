import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../providers/cart_provider.dart';
import '../services/checkout_service.dart';
import '../theme/app_theme.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _address = TextEditingController();
  final _city = TextEditingController(text: 'Abidjan');
  final _note = TextEditingController();
  final _service = CheckoutService();

  String _paymentMethod = 'paiement_pro';
  String _paymentChannel = 'WAVECI';
  bool _submitting = false;

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _email.dispose();
    _phone.dispose();
    _address.dispose();
    _city.dispose();
    _note.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Livraison & paiement')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(18),
            children: [
              const _SectionTitle('Informations de livraison'),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(child: _field(_firstName, 'Prénom')),
                const SizedBox(width: 10),
                Expanded(child: _field(_lastName, 'Nom')),
              ]),
              const SizedBox(height: 10),
              _field(_phone, 'Téléphone', keyboardType: TextInputType.phone),
              const SizedBox(height: 10),
              _field(_email, 'E-mail', keyboardType: TextInputType.emailAddress, email: true),
              const SizedBox(height: 10),
              _field(_city, 'Ville / commune'),
              const SizedBox(height: 10),
              _field(_address, 'Adresse de livraison', maxLines: 2),
              const SizedBox(height: 10),
              TextFormField(
                controller: _note,
                maxLines: 2,
                decoration: const InputDecoration(labelText: 'Indications pour le livreur (facultatif)'),
              ),
              const SizedBox(height: 26),
              const _SectionTitle('Mode de paiement'),
              const SizedBox(height: 10),
              _PaymentMethodCard(
                selected: _paymentMethod == 'paiement_pro',
                icon: Icons.account_balance_wallet_rounded,
                title: 'Paiement en ligne',
                subtitle: 'Paiement Pro — Mobile Money ou carte',
                onTap: () => setState(() => _paymentMethod = 'paiement_pro'),
              ),
              const SizedBox(height: 10),
              _PaymentMethodCard(
                selected: _paymentMethod == 'cash_on_delivery',
                icon: Icons.payments_outlined,
                title: 'Paiement à la livraison',
                subtitle: 'La commande est créée sans débit en ligne.',
                onTap: () => setState(() => _paymentMethod = 'cash_on_delivery'),
              ),
              if (_paymentMethod == 'paiement_pro') ...[
                const SizedBox(height: 14),
                const Text('Choisissez votre moyen Paiement Pro', style: TextStyle(fontWeight: FontWeight.w800)),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: const [
                    ('WAVECI', 'Wave'),
                    ('OMCIV2', 'Orange Money'),
                    ('MOMOCI', 'Mobile Money'),
                    ('CARD', 'Carte bancaire'),
                  ].map((entry) {
                    return const SizedBox.shrink();
                  }).toList(),
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _channelChip('WAVECI', 'Wave'),
                    _channelChip('OMCIV2', 'Orange Money'),
                    _channelChip('MOMOCI', 'Mobile Money'),
                    _channelChip('CARD', 'Carte bancaire'),
                  ],
                ),
              ],
              const SizedBox(height: 26),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: HillColors.line),
                ),
                child: Row(children: [
                  const Expanded(child: Text('Total commande', style: TextStyle(fontWeight: FontWeight.w700))),
                  Text('${cart.total} FCFA', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
                ]),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _submitting || cart.items.isEmpty ? null : _submit,
                icon: _submitting
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : Icon(_paymentMethod == 'paiement_pro' ? Icons.lock_rounded : Icons.check_circle_outline_rounded),
                label: Text(_paymentMethod == 'paiement_pro' ? 'Payer avec Paiement Pro' : 'Confirmer la commande'),
              ),
              const SizedBox(height: 12),
              const Text(
                'Le montant final est recalculé par le serveur à partir des prix du catalogue avant toute création de paiement.',
                textAlign: TextAlign.center,
                style: TextStyle(color: HillColors.muted, fontSize: 12, height: 1.35),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(TextEditingController controller, String label,
      {TextInputType? keyboardType, int maxLines = 1, bool email = false}) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      decoration: InputDecoration(labelText: label),
      validator: (value) {
        final text = value?.trim() ?? '';
        if (text.isEmpty) return 'Champ requis';
        if (email && (!text.contains('@') || !text.contains('.'))) return 'E-mail invalide';
        return null;
      },
    );
  }

  Widget _channelChip(String value, String label) => ChoiceChip(
        label: Text(label),
        selected: _paymentChannel == value,
        onSelected: (_) => setState(() => _paymentChannel = value),
      );

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final cart = context.read<CartProvider>();
    setState(() => _submitting = true);

    try {
      final result = await _service.createOrder(
        firstName: _firstName.text.trim(),
        lastName: _lastName.text.trim(),
        email: _email.text.trim(),
        phone: _phone.text.trim(),
        address: _address.text.trim(),
        city: _city.text.trim(),
        deliveryNote: _note.text.trim(),
        paymentMethod: _paymentMethod,
        paymentChannel: _paymentMethod == 'paiement_pro' ? _paymentChannel : null,
        items: cart.items,
      );

      if (!mounted) return;

      if (!result.requiresOnlinePayment) {
        cart.clear();
        await _showOrderSuccess(result.orderNumber, paid: false);
        return;
      }

      final init = await _service.initializePaiementPro(result.orderNumber);
      final uri = Uri.parse(init.paymentUrl);
      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) throw const CheckoutException('Impossible d’ouvrir la page Paiement Pro.');

      if (!mounted) return;
      await _showPaymentWaiting(result.orderNumber);
    } on CheckoutException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Impossible de finaliser la commande.')));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _showPaymentWaiting(String orderNumber) async {
    bool checking = false;
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Paiement en cours'),
          content: Text('Terminez le paiement Paiement Pro puis revenez ici.\n\nCommande : $orderNumber'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(dialogContext), child: const Text('Vérifier plus tard')),
            FilledButton.icon(
              onPressed: checking
                  ? null
                  : () async {
                      setDialogState(() => checking = true);
                      try {
                        final status = await _service.getOrderStatus(orderNumber);
                        if (!mounted) return;
                        if (status['payment_status'] == 'paid') {
                          context.read<CartProvider>().clear();
                          Navigator.pop(dialogContext);
                          await _showOrderSuccess(orderNumber, paid: true);
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Statut actuel : ${status['payment_status']}')),
                          );
                        }
                      } finally {
                        if (context.mounted) setDialogState(() => checking = false);
                      }
                    },
              icon: checking
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.refresh_rounded),
              label: const Text('Vérifier le paiement'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showOrderSuccess(String orderNumber, {required bool paid}) async {
    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        icon: const Icon(Icons.check_circle_rounded, color: Colors.green, size: 48),
        title: Text(paid ? 'Paiement confirmé' : 'Commande enregistrée'),
        content: Text(
          paid
              ? 'Votre paiement a été confirmé et la commande $orderNumber est enregistrée.'
              : 'Votre commande $orderNumber est enregistrée. Le paiement sera traité selon le mode choisi.',
        ),
        actions: [FilledButton(onPressed: () => Navigator.pop(context), child: const Text('Terminer'))],
      ),
    );
    if (mounted) Navigator.pop(context);
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Text(text, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900));
}

class _PaymentMethodCard extends StatelessWidget {
  const _PaymentMethodCard({
    required this.selected,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final bool selected;
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.all(15),
          decoration: BoxDecoration(
            color: selected ? HillColors.yellow.withValues(alpha: .16) : Colors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: selected ? HillColors.yellow : HillColors.line, width: selected ? 2 : 1),
          ),
          child: Row(children: [
            CircleAvatar(backgroundColor: selected ? HillColors.yellow : const Color(0xFFF2F2F2), child: Icon(icon, color: HillColors.ink)),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w900)),
              const SizedBox(height: 3),
              Text(subtitle, style: const TextStyle(color: HillColors.muted, fontSize: 12)),
            ])),
            Icon(selected ? Icons.radio_button_checked_rounded : Icons.radio_button_off_rounded),
          ]),
        ),
      );
}
