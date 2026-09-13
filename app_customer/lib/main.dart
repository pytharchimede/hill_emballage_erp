import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'providers/cart_provider.dart';
import 'screens/home_shell.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const HillCustomerApp());
}

class HillCustomerApp extends StatelessWidget {
  const HillCustomerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => CartProvider()),
      ],
      child: MaterialApp(
        title: 'Hill Emballage',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        home: const HomeShell(),
      ),
    );
  }
}
