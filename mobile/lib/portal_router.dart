import 'package:flutter/widgets.dart';

import 'api_client.dart';
import 'screens/home_screen.dart';
import 'screens/parent_home_screen.dart';
import 'screens/student_home_screen.dart';
import 'screens/admin_home_screen.dart';
import 'screens/platform_home_screen.dart';
import 'screens/transport_officer_screen.dart';
import 'screens/health_officer_screen.dart';
import 'screens/admission_officer_screen.dart';

Widget homeForCurrentSession() {
  final user = ApiClient.instance.user ?? const <String, dynamic>{};
  final portal = user['portal']?.toString();
  final roleKey = user['role_key']?.toString();

  if (portal == 'platform' || roleKey == 'super_admin') {
    return const PlatformHomeScreen();
  }

  if (portal == 'student' || roleKey == 'student') {
    return const StudentHomeScreen();
  }
  if (portal == 'parent' || roleKey == 'parent') {
    return const ParentHomeScreen();
  }
  if (roleKey == 'transport_officer') {
    return const TransportOfficerScreen();
  }
  if (roleKey == 'health_officer') {
    return const HealthOfficerScreen();
  }
  if (roleKey == 'admission_officer') {
    return const AdmissionOfficerScreen();
  }
  const managementRoles = {
    'admin',
    'principal',
    'head',
    'head_teacher',
    'vice_principal',
    'academic_administrator',
  };
  if (portal == 'admin' || managementRoles.contains(roleKey)) {
    return const AdminHomeScreen();
  }
  return const HomeScreen();
}
