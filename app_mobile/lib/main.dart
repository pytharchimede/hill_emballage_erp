import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'providers/auth_provider.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
      ],
      child: MaterialApp(
        title: 'HILL EMBALLAGE',
        theme: ThemeData(
          primarySwatch: Colors.yellow,
          primaryColor: const Color(0xFFFFD700), // Jaune doré
          colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(0xFFFFD700),
            brightness: Brightness.light,
          ).copyWith(
            secondary: const Color(0xFF757575), // Gris
            surface: const Color(0xFFF5F5F5), // Gris très clair
          ),
          scaffoldBackgroundColor: const Color(0xFFFAFAFA),
          appBarTheme: const AppBarTheme(
            backgroundColor: Color(0xFFFFD700),
            foregroundColor: Colors.black87,
            elevation: 2,
            centerTitle: true,
            titleTextStyle: TextStyle(
              color: Colors.black87,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFFD700),
              foregroundColor: Colors.black87,
              elevation: 2,
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
          ),
          cardTheme: const CardThemeData(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.all(Radius.circular(12)),
            ),
            color: Colors.white,
          ),
          inputDecorationTheme: const InputDecorationTheme(
            border: OutlineInputBorder(
              borderRadius: BorderRadius.all(Radius.circular(8)),
              borderSide: BorderSide(color: Color(0xFF757575)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.all(Radius.circular(8)),
              borderSide: BorderSide(color: Color(0xFFFFD700), width: 2),
            ),
            filled: true,
            fillColor: Colors.white,
          ),
          useMaterial3: true,
        ),
        home: Consumer<AuthProvider>(
          builder: (context, auth, _) {
            // Splash pendant l'initialisation unique
            if (!auth.isInitialized || auth.isLoading) {
              return const Scaffold(
                body: Center(
                  child: CircularProgressIndicator(color: Color(0xFFFFD700)),
                ),
              );
            }

            // Transition fluide entre Login et Dashboard
            final Widget child = auth.isAuthenticated
                ? const DashboardScreen(key: ValueKey('dashboard'))
                : const LoginScreen(key: ValueKey('login'));

            return AnimatedSwitcher(
              duration: const Duration(milliseconds: 250),
              switchInCurve: Curves.easeOut,
              switchOutCurve: Curves.easeIn,
              layoutBuilder: (currentChild, previousChildren) {
                return Stack(
                  alignment: Alignment.center,
                  children: <Widget>[
                    ...previousChildren,
                    if (currentChild != null) currentChild,
                  ],
                );
              },
              child: child,
            );
          },
        ),
        debugShowCheckedModeBanner: false,
      ),
    );
  }
}
