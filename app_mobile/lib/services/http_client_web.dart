import 'package:http/http.dart' as http;
import 'package:http/browser_client.dart';

http.Client createHttpClient() {
  final client = BrowserClient()
    ..withCredentials = true; // inclure cookies pour sessions PHP
  return client;
}
