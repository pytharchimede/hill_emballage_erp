# Hill Emballage — Application client

Application Flutter grand public destinée au Play Store, séparée de l'application métier/livreur existante dans `app_mobile/`.

## Identité

- Nom produit : Hill Emballage
- Projet Flutter : `hill_emballage_client`
- Direction visuelle : jaune doré `#FFD700`, noir `#171717`, blanc et gris clair
- UI : Material 3, cartes arrondies, navigation basse, recherche omniprésente, composants tactiles larges

## Parcours cible

1. Accueil / promotions
2. Recherche et catégories
3. Fiche produit / variantes / stock
4. Favoris
5. Panier persistant
6. Adresse et mode de livraison
7. Paiement (Paiement Pro)
8. Confirmation
9. Suivi de commande
10. Historique / nouvelle commande
11. Profil et adresses
12. Centre de notifications
13. Assistance / WhatsApp

## Notifications push

Les campagnes seront créées et programmées depuis le back-office Hill Emballage. Firebase Cloud Messaging servira uniquement de transport push.

Contrat API prévu :

- `POST /api/v1/customer/devices` — enregistrer un terminal
- `DELETE /api/v1/customer/devices/{token}` — désinscrire un terminal
- `GET /api/v1/customer/notifications` — historique côté client
- `POST /api/v1/admin/push-campaigns` — créer/programmer une campagne
- `GET /api/v1/admin/push-campaigns` — historique et statuts

Une campagne devra supporter : titre, message, image optionnelle, audience, date/heure d'envoi, lien profond, produit/catégorie/commande cible et statut.

## Structure initiale

```text
app_customer/
  lib/
    main.dart
    providers/
      cart_provider.dart
    screens/
      home_shell.dart
    services/
      push_notification_service.dart
    theme/
      app_theme.dart
```

## Scaffold Android/iOS

Avant le premier build natif, générer les plateformes sans remplacer `lib/` ni `pubspec.yaml` :

```bash
cd app_customer
flutter create . --project-name hill_emballage_client --org ci.hillemballage --platforms=android,ios
flutter pub get
```

Le package Android cible sera ensuite figé pour publication Play Store, puis l'icône adaptative, le splash screen et la signature release seront configurés.

## État actuel

Le prototype dispose déjà d'un accueil marchand moderne, recherche, catégories, grille produits, panier fonctionnel et navigation Accueil / Catégories / Commandes / Panier / Compte.
