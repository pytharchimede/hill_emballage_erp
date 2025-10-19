// ignore: avoid_web_libraries_in_flutter
import 'dart:html' as html;
import 'package:http/http.dart' as http;

class WebClient extends http.BaseClient {
  final http.Client _inner;
  WebClient(this._inner);

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    // Permet les cookies/sessions côté web
    request.headers['Accept'] = request.headers['Accept'] ?? 'application/json';
    // Le package http pour web utilise fetch() qui respecte 'credentials: include' via cookie store
    // On force le mode same-origin pour autoriser les cookies si backend est même origine.
    // Si CORS est cross-domain, configurer le serveur: Access-Control-Allow-Credentials: true.
    return _inner.send(request);
  }
}

http.Client createHttpClient() => WebClient(http.Client());
