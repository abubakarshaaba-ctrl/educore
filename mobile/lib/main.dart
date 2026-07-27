import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

import 'firebase_options.dart';
import 'push_service.dart';
import 'screens/splash_screen.dart';

// EduCore brand
const kNavy = Color(0xFF071E45);
const kGold = Color(0xFFD79A21);
const kInk = Color(0xFF101828);
const kMuted = Color(0xFF667085);
const kPage = Color(0xFFF4F7FB);
const kGood = Color(0xFF16794B);
const kRisk = Color(0xFFB42318);

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );

  // Register background message handler
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

  // Initialize push notification service
  await PushService.instance.init();

  runApp(const EduCoreApp());
}

class EduCoreApp extends StatelessWidget {
  const EduCoreApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'EduCore',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: kPage,
        colorScheme: ColorScheme.fromSeed(
          seedColor: kNavy,
          primary: kNavy,
          secondary: kGold,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: kNavy,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: false,
          titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 19,
            fontWeight: FontWeight.w800,
          ),
        ),
        cardTheme: CardThemeData(
          color: Colors.white,
          elevation: 0,
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.all(Radius.circular(14)),
            side: BorderSide(color: Color(0xFFD8E0E8)),
          ),
        ),
        navigationBarTheme: const NavigationBarThemeData(
          backgroundColor: Colors.white,
          indicatorColor: Color(0x24D79A21),
          labelTextStyle: WidgetStatePropertyAll(
            TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600),
          ),
          iconTheme: WidgetStatePropertyAll(IconThemeData(color: kNavy)),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            backgroundColor: kGold,
            foregroundColor: kNavy,
            minimumSize: const Size.fromHeight(52),
            textStyle:
                const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Color(0xFFC9D3DF)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Color(0xFFC9D3DF)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: kGold, width: 2),
          ),
        ),
      ),
      // Splash screen handles auth check and navigation
      home: const SplashScreen(),
    );
  }
}
