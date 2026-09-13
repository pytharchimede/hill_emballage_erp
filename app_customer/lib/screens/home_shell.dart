import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../providers/cart_provider.dart';
import '../theme/app_theme.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      const StoreHomeScreen(),
      const CategoriesScreen(),
      const OrdersScreen(),
      const CartScreen(),
      const AccountScreen(),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, _) {
          return NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: (value) => setState(() => _index = value),
            destinations: [
              const NavigationDestination(
                icon: Icon(Icons.home_outlined),
                selectedIcon: Icon(Icons.home_rounded),
                label: 'Accueil',
              ),
              const NavigationDestination(
                icon: Icon(Icons.grid_view_outlined),
                selectedIcon: Icon(Icons.grid_view_rounded),
                label: 'Catégories',
              ),
              const NavigationDestination(
                icon: Icon(Icons.receipt_long_outlined),
                selectedIcon: Icon(Icons.receipt_long_rounded),
                label: 'Commandes',
              ),
              NavigationDestination(
                icon: Badge(
                  isLabelVisible: cart.count > 0,
                  label: Text('${cart.count}'),
                  child: const Icon(Icons.shopping_bag_outlined),
                ),
                selectedIcon: Badge(
                  isLabelVisible: cart.count > 0,
                  label: Text('${cart.count}'),
                  child: const Icon(Icons.shopping_bag_rounded),
                ),
                label: 'Panier',
              ),
              const NavigationDestination(
                icon: Icon(Icons.person_outline_rounded),
                selectedIcon: Icon(Icons.person_rounded),
                label: 'Compte',
              ),
            ],
          );
        },
      ),
    );
  }
}

class StoreHomeScreen extends StatelessWidget {
  const StoreHomeScreen({super.key});

  static const _products = [
    (1, 'Pot alimentaire kraft avec couvercle', 3500, 'Emballages alimentaires'),
    (2, 'Gobelets premium 50 pièces', 4200, 'Gobelets'),
    (3, 'Sacs kraft poignées torsadées', 6500, 'Sacs'),
    (4, 'Boîtes pâtissières blanches', 7200, 'Pâtisserie'),
  ];

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: _topHeader(context)),
          SliverToBoxAdapter(child: _searchBar()),
          SliverToBoxAdapter(child: _hero()),
          SliverToBoxAdapter(child: _sectionTitle('Nos catégories', action: 'Voir tout')),
          SliverToBoxAdapter(child: _categories()),
          SliverToBoxAdapter(child: _sectionTitle('Populaires cette semaine', action: 'Découvrir')),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
            sliver: SliverGrid.builder(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: .67,
              ),
              itemCount: _products.length,
              itemBuilder: (context, index) {
                final p = _products[index];
                return ProductCard(
                  id: p.$1,
                  name: p.$2,
                  price: p.$3,
                  category: p.$4,
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _topHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 12, 8),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: HillColors.yellow,
              borderRadius: BorderRadius.circular(15),
            ),
            alignment: Alignment.center,
            child: const Text('H', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('HILL EMBALLAGE', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
                SizedBox(height: 2),
                Text('Tout pour emballer mieux', style: TextStyle(color: HillColors.muted, fontSize: 12)),
              ],
            ),
          ),
          IconButton.filledTonal(
            onPressed: () {},
            icon: const Badge(smallSize: 8, child: Icon(Icons.notifications_none_rounded)),
          ),
        ],
      ),
    );
  }

  Widget _searchBar() {
    return const Padding(
      padding: EdgeInsets.fromLTRB(16, 6, 16, 10),
      child: TextField(
        decoration: InputDecoration(
          hintText: 'Rechercher un produit, une catégorie…',
          prefixIcon: Icon(Icons.search_rounded),
          suffixIcon: Icon(Icons.tune_rounded),
        ),
      ),
    );
  }

  Widget _hero() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 18),
      child: Container(
        height: 190,
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          color: HillColors.yellow,
          borderRadius: BorderRadius.circular(28),
        ),
        child: Row(
          children: [
            const Expanded(
              flex: 3,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  DecoratedBox(
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.all(Radius.circular(20))),
                    child: Padding(
                      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      child: Text('NOUVEAUTÉS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900)),
                    ),
                  ),
                  SizedBox(height: 12),
                  Text('Des emballages qui valorisent vos produits.', style: TextStyle(fontSize: 23, height: 1.08, fontWeight: FontWeight.w900)),
                  SizedBox(height: 10),
                  Text('Commandez simplement. Nous nous occupons du reste.', style: TextStyle(fontSize: 12, height: 1.35)),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              flex: 2,
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .72),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: const Center(child: Icon(Icons.inventory_2_rounded, size: 72, color: HillColors.ink)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title, {required String action}) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 2, 10, 12),
      child: Row(
        children: [
          Expanded(child: Text(title, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w900))),
          TextButton(onPressed: () {}, child: Text(action)),
        ],
      ),
    );
  }

  Widget _categories() {
    const items = [
      (Icons.takeout_dining_rounded, 'Alimentaire'),
      (Icons.local_cafe_rounded, 'Gobelets'),
      (Icons.shopping_bag_rounded, 'Sacs'),
      (Icons.cake_rounded, 'Pâtisserie'),
      (Icons.cleaning_services_rounded, 'Entretien'),
    ];

    return SizedBox(
      height: 102,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (_, index) {
          final item = items[index];
          return SizedBox(
            width: 78,
            child: Column(
              children: [
                Container(
                  width: 62,
                  height: 62,
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: HillColors.line)),
                  child: Icon(item.$1, color: HillColors.ink),
                ),
                const SizedBox(height: 7),
                Text(item.$2, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
              ],
            ),
          );
        },
      ),
    );
  }
}

class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.id,
    required this.name,
    required this.price,
    required this.category,
  });

  final int id;
  final String name;
  final int price;
  final String category;

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Stack(
                children: [
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF3F3F3),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(Icons.inventory_2_outlined, size: 58, color: Color(0xFFB4B4B4)),
                  ),
                  Positioned(
                    right: 7,
                    top: 7,
                    child: CircleAvatar(
                      radius: 17,
                      backgroundColor: Colors.white,
                      child: IconButton(padding: EdgeInsets.zero, iconSize: 18, onPressed: () {}, icon: const Icon(Icons.favorite_border_rounded)),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            Text(category.toUpperCase(), style: const TextStyle(fontSize: 9, color: HillColors.muted, fontWeight: FontWeight.w800)),
            const SizedBox(height: 4),
            Text(name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w800, height: 1.2)),
            const Spacer(),
            Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Expanded(child: Text('${money.format(price)} F', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900))),
                InkWell(
                  onTap: () {
                    context.read<CartProvider>().add(id: id, name: name, price: price);
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('$name ajouté au panier'), duration: const Duration(milliseconds: 900)),
                    );
                  },
                  borderRadius: BorderRadius.circular(13),
                  child: Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(color: HillColors.yellow, borderRadius: BorderRadius.circular(13)),
                    child: const Icon(Icons.add_rounded),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class CategoriesScreen extends StatelessWidget {
  const CategoriesScreen({super.key});

  @override
  Widget build(BuildContext context) => const _PlaceholderPage(
        title: 'Catégories',
        subtitle: 'Parcourez tout le catalogue Hill Emballage.',
        icon: Icons.grid_view_rounded,
      );
}

class OrdersScreen extends StatelessWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context) => const _PlaceholderPage(
        title: 'Mes commandes',
        subtitle: 'Suivi en temps réel, historique et détails de livraison.',
        icon: Icons.receipt_long_rounded,
      );
}

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return SafeArea(
      child: Consumer<CartProvider>(
        builder: (context, cart, _) {
          return Column(
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(18, 20, 18, 12),
                child: Align(alignment: Alignment.centerLeft, child: Text('Mon panier', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900))),
              ),
              Expanded(
                child: cart.items.isEmpty
                    ? const Center(child: _EmptyCart())
                    : ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: cart.items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (_, index) {
                          final item = cart.items[index];
                          return Card(
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Row(
                                children: [
                                  Container(width: 62, height: 62, decoration: BoxDecoration(color: const Color(0xFFF2F2F2), borderRadius: BorderRadius.circular(15)), child: const Icon(Icons.inventory_2_outlined)),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                      Text(item.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w800)),
                                      const SizedBox(height: 4),
                                      Text('${money.format(item.price)} F', style: const TextStyle(fontWeight: FontWeight.w900)),
                                    ]),
                                  ),
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
                Container(
                  padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
                  decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: HillColors.line))),
                  child: Column(
                    children: [
                      Row(children: [
                        const Expanded(child: Text('Total', style: TextStyle(color: HillColors.muted, fontWeight: FontWeight.w700))),
                        Text('${money.format(cart.total)} FCFA', style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w900)),
                      ]),
                      const SizedBox(height: 12),
                      ElevatedButton(onPressed: () {}, child: const Text('Passer la commande')),
                    ],
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) => const _PlaceholderPage(
        title: 'Mon compte',
        subtitle: 'Profil, adresses, favoris, notifications et assistance.',
        icon: Icons.person_rounded,
      );
}

class _PlaceholderPage extends StatelessWidget {
  const _PlaceholderPage({required this.title, required this.subtitle, required this.icon});

  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
            const SizedBox(height: 28),
            Expanded(
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(width: 88, height: 88, decoration: BoxDecoration(color: const Color(0xFFFFF2A5), borderRadius: BorderRadius.circular(28)), child: Icon(icon, size: 42)),
                    const SizedBox(height: 18),
                    Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(color: HillColors.muted, height: 1.5)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyCart extends StatelessWidget {
  const _EmptyCart();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.shopping_bag_outlined, size: 70, color: HillColors.muted),
          SizedBox(height: 16),
          Text('Votre panier est vide', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
          SizedBox(height: 7),
          Text('Ajoutez vos emballages préférés depuis l’accueil.', textAlign: TextAlign.center, style: TextStyle(color: HillColors.muted)),
        ],
      ),
    );
  }
}
