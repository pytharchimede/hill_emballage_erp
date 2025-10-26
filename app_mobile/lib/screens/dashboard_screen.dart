import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import '../providers/auth_provider.dart';
import 'clients_screen.dart';
import 'sales_screen.dart';
import 'stock_screen.dart';
import 'payments_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _selectedIndex = 0;

  static final List<Widget> _screens = <Widget>[
    const DashboardTab(),
    const ClientsScreen(),
    const SalesScreen(),
    const StockScreen(),
    const PaymentsScreen(),
  ];

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final depotNom = auth.user?.depotNom;
    const pageTitles = <String>[
      'Tableau de bord',
      'Clients',
      'Ventes',
      'Stock',
      'Paiements',
    ];
    return Scaffold(
      appBar: AppBar(
        title: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            const FaIcon(FontAwesomeIcons.boxesStacked,
                color: Colors.black87, size: 18),
            const SizedBox(width: 6),
            Text('HILL EMBALLAGE — ${pageTitles[_selectedIndex]}'),
            if (depotNom != null && depotNom.isNotEmpty) ...[
              const SizedBox(width: 8),
              Chip(
                label: Text('Dépôt: $depotNom'),
                backgroundColor: const Color(0xFFEFF6FF),
                labelStyle: const TextStyle(color: Color(0xFF0B4D88)),
                side: const BorderSide(color: Color(0xFF99C9FF)),
                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 0),
              ),
            ]
          ],
        ),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) {
              switch (value) {
                case 'profile':
                  // Navigator.push(context, MaterialPageRoute(builder: (context) => ProfileScreen()));
                  break;
                case 'logout':
                  _showLogoutDialog();
                  break;
              }
            },
            itemBuilder: (BuildContext context) => <PopupMenuEntry<String>>[
              const PopupMenuItem<String>(
                value: 'profile',
                child: Row(
                  children: [
                    Icon(Icons.person, color: Colors.black54),
                    SizedBox(width: 8),
                    Text('Profil'),
                  ],
                ),
              ),
              const PopupMenuItem<String>(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, color: Colors.red),
                    SizedBox(width: 8),
                    Text('Déconnexion', style: TextStyle(color: Colors.red)),
                  ],
                ),
              ),
            ],
            icon: const Icon(Icons.more_vert),
          ),
        ],
      ),
      body: _screens[_selectedIndex],
      bottomNavigationBar: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        currentIndex: _selectedIndex,
        onTap: _onItemTapped,
        selectedItemColor: const Color(0xFFFFD700),
        unselectedItemColor: const Color(0xFF757575),
        backgroundColor: Colors.white,
        elevation: 8,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Tableau de bord',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people),
            label: 'Clients',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.shopping_cart),
            label: 'Ventes',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.inventory),
            label: 'Stock',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.payment),
            label: 'Paiements',
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Êtes-vous sûr de vouloir vous déconnecter ?'),
          actions: <Widget>[
            TextButton(
              child: const Text('Annuler'),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
            TextButton(
              child: const Text('Déconnexion',
                  style: TextStyle(color: Colors.red)),
              onPressed: () {
                Navigator.of(context).pop();
                Provider.of<AuthProvider>(context, listen: false).logout();
              },
            ),
          ],
        );
      },
    );
  }
}

class DashboardTab extends StatelessWidget {
  const DashboardTab({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Carte de bienvenue
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Row(
                    children: [
                      const CircleAvatar(
                        radius: 30,
                        backgroundColor: Color(0xFFFFD700),
                        child:
                            Icon(Icons.person, size: 30, color: Colors.black87),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Bonjour, ${authProvider.user?.fullName ?? 'Utilisateur'} !',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              authProvider.user?.role ?? 'Utilisateur',
                              style: const TextStyle(
                                fontSize: 14,
                                color: Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 20),

          // Statistiques rapides
          const Text(
            'Aperçu rapide',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            childAspectRatio: 1.5,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildStatCard(
                title: 'Ventes du jour',
                value: '0 FCFA',
                icon: Icons.trending_up,
                color: Colors.green,
              ),
              _buildStatCard(
                title: 'Clients',
                value: '0',
                icon: Icons.people,
                color: Colors.blue,
              ),
              _buildStatCard(
                title: 'Stock critique',
                value: '0',
                icon: Icons.warning,
                color: Colors.orange,
              ),
              _buildStatCard(
                title: 'Paiements en attente',
                value: '0',
                icon: Icons.pending,
                color: Colors.red,
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Actions rapides
          const Text(
            'Actions rapides',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            childAspectRatio: 1.2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildActionCard(
                title: 'Nouvelle vente',
                icon: Icons.add_shopping_cart,
                onTap: () {
                  // Navigator.push(context, MaterialPageRoute(builder: (context) => NewSaleScreen()));
                },
              ),
              _buildActionCard(
                title: 'Ajouter client',
                icon: Icons.person_add,
                onTap: () {
                  // Navigator.push(context, MaterialPageRoute(builder: (context) => AddClientScreen()));
                },
              ),
              _buildActionCard(
                title: 'Scanner QR',
                icon: Icons.qr_code_scanner,
                onTap: () {
                  // Navigator.push(context, MaterialPageRoute(builder: (context) => QRScannerScreen()));
                },
              ),
              _buildActionCard(
                title: 'Rapport',
                icon: Icons.assessment,
                onTap: () {
                  // Navigator.push(context, MaterialPageRoute(builder: (context) => ReportScreen()));
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 32, color: color),
            const SizedBox(height: 8),
            Text(
              value,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              title,
              style: const TextStyle(
                fontSize: 12,
                color: Colors.grey,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionCard({
    required String title,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 32, color: const Color(0xFFFFD700)),
              const SizedBox(height: 8),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
