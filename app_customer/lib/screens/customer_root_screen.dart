import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../models/store_product.dart';
import '../providers/cart_provider.dart';
import '../providers/catalog_provider.dart';
import '../theme/app_theme.dart';

class CustomerRootScreen extends StatefulWidget {
  const CustomerRootScreen({super.key});

  @override
  State<CustomerRootScreen> createState() => _CustomerRootScreenState();
}

class _CustomerRootScreenState extends State<CustomerRootScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      StorefrontScreen(onOpenCart: () => setState(() => _index = 3)),
      const CategoriesPage(),
      const OrdersPage(),
      const CustomerCartPage(),
      const AccountPage(),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, _) => NavigationBar(
          selectedIndex: _index,
          onDestinationSelected: (value) => setState(() => _index = value),
          destinations: [
            const NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded), label: 'Accueil'),
            const NavigationDestination(icon: Icon(Icons.grid_view_outlined), selectedIcon: Icon(Icons.grid_view_rounded), label: 'Catégories'),
            const NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long_rounded), label: 'Commandes'),
            NavigationDestination(
              icon: Badge(isLabelVisible: cart.count > 0, label: Text('${cart.count}'), child: const Icon(Icons.shopping_bag_outlined)),
              selectedIcon: Badge(isLabelVisible: cart.count > 0, label: Text('${cart.count}'), child: const Icon(Icons.shopping_bag_rounded)),
              label: 'Panier',
            ),
            const NavigationDestination(icon: Icon(Icons.person_outline_rounded), selectedIcon: Icon(Icons.person_rounded), label: 'Compte'),
          ],
        ),
      ),
    );
  }
}

class StorefrontScreen extends StatefulWidget {
  const StorefrontScreen({super.key, required this.onOpenCart});
  final VoidCallback onOpenCart;

  @override
  State<StorefrontScreen> createState() => _StorefrontScreenState();
}

class _StorefrontScreenState extends State<StorefrontScreen> {
  final _search = TextEditingController();

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final catalog = context.watch<CatalogProvider>();
    return SafeArea(
      child: RefreshIndicator(
        onRefresh: () => context.read<CatalogProvider>().load(query: _search.text),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(child: _header(context)),
            SliverToBoxAdapter(child: _searchField(context)),
            const SliverToBoxAdapter(child: _HeroBanner()),
            const SliverToBoxAdapter(child: _SectionTitle(title: 'Notre catalogue')),
            if (catalog.isLoading)
              const SliverFillRemaining(hasScrollBody: false, child: Center(child: CircularProgressIndicator()))
            else if (catalog.error != null)
              SliverFillRemaining(
                hasScrollBody: false,
                child: _ErrorState(message: catalog.error!, onRetry: () => context.read<CatalogProvider>().load(query: _search.text)),
              )
            else if (catalog.products.isEmpty)
              const SliverFillRemaining(hasScrollBody: false, child: _EmptyState())
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 28),
                sliver: SliverGrid.builder(
                  itemCount: catalog.products.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 12,
                    crossAxisSpacing: 12,
                    childAspectRatio: .68,
                  ),
                  itemBuilder: (context, index) => ProductTile(product: catalog.products[index]),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _header(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 10, 8),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(color: HillColors.yellow, borderRadius: BorderRadius.circular(16)),
            alignment: Alignment.center,
            child: const Text('H', style: TextStyle(fontSize: 25, fontWeight: FontWeight.w900)),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('HILL EMBALLAGE', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
              Text('Tout pour emballer mieux', style: TextStyle(fontSize: 12, color: HillColors.muted)),
            ]),
          ),
          Consumer<CartProvider>(
            builder: (context, cart, _) => IconButton.filledTonal(
              onPressed: widget.onOpenCart,
              icon: Badge(isLabelVisible: cart.count > 0, label: Text('${cart.count}'), child: const Icon(Icons.shopping_bag_outlined)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _searchField(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 6, 16, 12),
      child: TextField(
        controller: _search,
        textInputAction: TextInputAction.search,
        onSubmitted: (value) => context.read<CatalogProvider>().load(query: value),
        decoration: InputDecoration(
          hintText: 'Rechercher un produit…',
          prefixIcon: const Icon(Icons.search_rounded),
          suffixIcon: IconButton(
            onPressed: () {
              _search.clear();
              context.read<CatalogProvider>().load();
            },
            icon: const Icon(Icons.close_rounded),
          ),
        ),
      ),
    );
  }
}

class ProductTile extends StatelessWidget {
  const ProductTile({super.key, required this.product});
  final StoreProduct product;

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductDetailsPage(product: product))),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(color: const Color(0xFFF4F4F4), borderRadius: BorderRadius.circular(16)),
                clipBehavior: Clip.antiAlias,
                child: product.imageUrl.isEmpty
                    ? const Icon(Icons.inventory_2_outlined, size: 58, color: Color(0xFFB6B6B6))
                    : CachedNetworkImage(imageUrl: product.imageUrl, fit: BoxFit.cover, errorWidget: (_, __, ___) => const Icon(Icons.inventory_2_outlined, size: 58)),
              ),
            ),
            const SizedBox(height: 10),
            Text(product.category.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 9, color: HillColors.muted, fontWeight: FontWeight.w800)),
            const SizedBox(height: 4),
            Text(product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w800, height: 1.2)),
            const Spacer(),
            Row(children: [
              Expanded(child: Text('${money.format(product.price)} F', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900))),
              InkWell(
                onTap: () => _addToCart(context, product),
                borderRadius: BorderRadius.circular(13),
                child: Container(width: 38, height: 38, decoration: BoxDecoration(color: HillColors.yellow, borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.add_rounded)),
              ),
            ]),
          ]),
        ),
      ),
    );
  }
}

class ProductDetailsPage extends StatelessWidget {
  const ProductDetailsPage({super.key, required this.product});
  final StoreProduct product;

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return Scaffold(
      appBar: AppBar(title: const Text('Détail du produit')),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          AspectRatio(
            aspectRatio: 1.15,
            child: Container(
              decoration: BoxDecoration(color: const Color(0xFFF4F4F4), borderRadius: BorderRadius.circular(28)),
              clipBehavior: Clip.antiAlias,
              child: product.imageUrl.isEmpty
                  ? const Icon(Icons.inventory_2_outlined, size: 110, color: Color(0xFFB6B6B6))
                  : CachedNetworkImage(imageUrl: product.imageUrl, fit: BoxFit.cover, errorWidget: (_, __, ___) => const Icon(Icons.inventory_2_outlined, size: 110)),
            ),
          ),
          const SizedBox(height: 22),
          Text(product.category.toUpperCase(), style: const TextStyle(fontSize: 11, color: HillColors.muted, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text(product.name, style: const TextStyle(fontSize: 25, fontWeight: FontWeight.w900, height: 1.12)),
          if (product.code.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text('Réf. ${product.code}', style: const TextStyle(color: HillColors.muted)),
          ],
          const SizedBox(height: 16),
          Text('${money.format(product.price)} FCFA', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
          const SizedBox(height: 18),
          Text(product.description.isEmpty ? 'Produit Hill Emballage disponible à la commande.' : product.description, style: const TextStyle(fontSize: 15, height: 1.5)),
          const SizedBox(height: 24),
          ElevatedButton.icon(onPressed: () => _addToCart(context, product), icon: const Icon(Icons.shopping_bag_outlined), label: const Text('Ajouter au panier')),
        ],
      ),
    );
  }
}

void _addToCart(BuildContext context, StoreProduct product) {
  context.read<CartProvider>().add(id: product.id, name: product.name, price: product.price);
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${product.name} ajouté au panier'), duration: const Duration(milliseconds: 850)));
}

class CustomerCartPage extends StatelessWidget {
  const CustomerCartPage({super.key});

  @override
  Widget build(BuildContext context) {
    final money = NumberFormat.decimalPattern('fr_FR');
    return SafeArea(
      child: Consumer<CartProvider>(
        builder: (context, cart, _) => Column(children: [
          const Padding(padding: EdgeInsets.fromLTRB(18, 20, 18, 12), child: Align(alignment: Alignment.centerLeft, child: Text('Mon panier', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)))),
          Expanded(
            child: cart.items.isEmpty
                ? const Center(child: Text('Votre panier est vide.', style: TextStyle(color: HillColors.muted)))
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: cart.items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (_, index) {
                      final item = cart.items[index];
                      return Card(child: ListTile(
                        leading: const CircleAvatar(child: Icon(Icons.inventory_2_outlined)),
                        title: Text(item.name, maxLines: 2, overflow: TextOverflow.ellipsis),
                        subtitle: Text('${money.format(item.price)} FCFA × ${item.quantity}'),
                        trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                          IconButton(onPressed: () => cart.decrement(item.id), icon: const Icon(Icons.remove_circle_outline_rounded)),
                          Text('${item.quantity}', style: const TextStyle(fontWeight: FontWeight.w900)),
                          IconButton(onPressed: () => cart.add(id: item.id, name: item.name, price: item.price), icon: const Icon(Icons.add_circle_rounded)),
                        ]),
                      ));
                    },
                  ),
          ),
          if (cart.items.isNotEmpty)
            Container(
              padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
              decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: HillColors.line))),
              child: Column(children: [
                Row(children: [const Expanded(child: Text('Total', style: TextStyle(color: HillColors.muted, fontWeight: FontWeight.w700))), Text('${money.format(cart.total)} FCFA', style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w900))]),
                const SizedBox(height: 12),
                ElevatedButton(onPressed: () {}, child: const Text('Continuer vers la livraison')),
              ]),
            ),
        ]),
      ),
    );
  }
}

class CategoriesPage extends StatelessWidget {
  const CategoriesPage({super.key});
  @override
  Widget build(BuildContext context) {
    final categories = context.watch<CatalogProvider>().categories;
    return SafeArea(child: ListView(padding: const EdgeInsets.all(18), children: [
      const Text('Catégories', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
      const SizedBox(height: 16),
      if (categories.isEmpty) const Text('Les catégories apparaîtront dès qu’elles seront renseignées dans le catalogue.'),
      ...categories.map((category) => Card(child: ListTile(leading: const CircleAvatar(backgroundColor: HillColors.yellow, child: Icon(Icons.category_outlined)), title: Text(category, style: const TextStyle(fontWeight: FontWeight.w800)), trailing: const Icon(Icons.chevron_right_rounded)))),
    ]));
  }
}

class OrdersPage extends StatelessWidget {
  const OrdersPage({super.key});
  @override
  Widget build(BuildContext context) => const _SimplePage(title: 'Mes commandes', subtitle: 'Vos commandes et leur suivi apparaîtront ici.', icon: Icons.receipt_long_rounded);
}

class AccountPage extends StatelessWidget {
  const AccountPage({super.key});
  @override
  Widget build(BuildContext context) => const _SimplePage(title: 'Mon compte', subtitle: 'Profil, adresses, favoris, notifications et assistance.', icon: Icons.person_rounded);
}

class _SimplePage extends StatelessWidget {
  const _SimplePage({required this.title, required this.subtitle, required this.icon});
  final String title;
  final String subtitle;
  final IconData icon;
  @override
  Widget build(BuildContext context) => SafeArea(child: Center(child: Padding(padding: const EdgeInsets.all(28), child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(icon, size: 64), const SizedBox(height: 16), Text(title, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900)), const SizedBox(height: 8), Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(color: HillColors.muted, height: 1.4))]))));
}

class _HeroBanner extends StatelessWidget {
  const _HeroBanner();
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
    child: Container(
      height: 180,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(color: HillColors.yellow, borderRadius: BorderRadius.circular(28)),
      child: const Row(children: [
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [Text('EMBALLEZ MIEUX', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900)), SizedBox(height: 10), Text('Tout ce qu’il faut pour valoriser vos produits.', style: TextStyle(fontSize: 23, height: 1.08, fontWeight: FontWeight.w900)), SizedBox(height: 8), Text('Commandez directement depuis votre téléphone.', style: TextStyle(fontSize: 12))])),
        SizedBox(width: 10),
        Icon(Icons.inventory_2_rounded, size: 76),
      ]),
    ),
  );
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});
  final String title;
  @override
  Widget build(BuildContext context) => Padding(padding: const EdgeInsets.fromLTRB(16, 2, 16, 12), child: Text(title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900)));
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(28), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off_rounded, size: 56), const SizedBox(height: 12), Text(message, textAlign: TextAlign.center), const SizedBox(height: 14), OutlinedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: const Text('Réessayer'))])));
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();
  @override
  Widget build(BuildContext context) => const Center(child: Padding(padding: EdgeInsets.all(28), child: Text('Aucun produit trouvé.', style: TextStyle(color: HillColors.muted))));
}
