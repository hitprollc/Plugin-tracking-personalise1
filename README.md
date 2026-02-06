# Plugin Tracking Personalise

Un plugin WordPress complet de suivi d'expédition (tracking colis), compatible WordPress 6+ et PHP 8.1+.

## Description

Plugin Tracking Personalise est un système professionnel de suivi de colis, similaire à UPS/USPS, entièrement intégré à WordPress. Il permet de créer et gérer des envois avec un historique de tracking complet, et s'intègre automatiquement avec WooCommerce.

## Fonctionnalités

### Gestion des envois
- ✅ Custom Post Type pour les envois
- ✅ Numéros de suivi uniques
- ✅ Multiples transporteurs (UPS, FedEx, USPS, DHL, personnalisés)
- ✅ Statuts de suivi multiples (En attente, En transit, En livraison, Livré, etc.)
- ✅ Historique complet des événements de tracking
- ✅ Informations client (nom, email)

### Interface Admin
- ✅ Menu dédié dans WordPress Admin
- ✅ Liste complète des envois avec filtres
- ✅ Ajout/modification facile des envois
- ✅ Gestion des événements de tracking par AJAX
- ✅ Page de réglages complète

### Affichage Public
- ✅ Shortcode de recherche de suivi `[ptp_tracking_lookup]`
- ✅ Shortcode d'affichage des résultats `[ptp_tracking_result]`
- ✅ Barre de progression visuelle
- ✅ Timeline des événements avec animations
- ✅ Protection optionnelle par email
- ✅ Design responsive et moderne

### Intégration WooCommerce
- ✅ Metabox dans les commandes
- ✅ Création automatique d'envois depuis les commandes
- ✅ Affichage dans "Mon compte"
- ✅ Inclusion dans les emails de commande
- ✅ Support HPOS (High-Performance Order Storage)

## Installation

1. Télécharger le plugin
2. Uploader dans `/wp-content/plugins/`
3. Activer depuis le menu "Extensions" de WordPress
4. Configurer dans "Tracking > Réglages"

## Structure

```
plugin-tracking-personalise/
├── plugin-tracking-personalise.php  # Fichier principal
├── uninstall.php                    # Nettoyage à la désinstallation
├── includes/                        # Classes PHP
│   ├── class-ptp-helper.php        # Méthodes utilitaires
│   ├── class-ptp-database.php      # Gestion BDD
│   ├── class-ptp-activator.php     # Activation
│   ├── class-ptp-deactivator.php   # Désactivation
│   ├── class-ptp-post-types.php    # Custom Post Type
│   ├── class-ptp-admin.php         # Menu admin
│   ├── class-ptp-admin-shipment.php # Interface CRUD
│   ├── class-ptp-admin-settings.php # Page réglages
│   ├── class-ptp-shortcodes.php    # Shortcodes publics
│   └── class-ptp-woocommerce.php   # Intégration WooCommerce
├── assets/
│   ├── css/
│   │   ├── ptp-admin.css          # Styles admin
│   │   └── ptp-public.css         # Styles frontend
│   └── js/
│       ├── ptp-admin.js           # JavaScript admin
│       └── ptp-public.js          # JavaScript frontend
└── languages/
    └── plugin-tracking-personalise.pot # Fichier de traduction
```

## Utilisation

### Créer un envoi

1. Aller dans "Tracking > Ajouter un envoi"
2. Remplir le numéro de suivi (obligatoire)
3. Sélectionner le transporteur et le statut
4. Ajouter les informations client
5. Publier

### Ajouter des événements

Dans l'éditeur d'envoi :
1. Faire défiler jusqu'à "Événements de suivi"
2. Remplir le formulaire d'ajout d'événement
3. Cliquer sur "Ajouter l'événement"

### Pages de suivi

Créer deux pages avec les shortcodes suivants :

**Page de recherche :**
```
[ptp_tracking_lookup]
```

**Page de résultats :**
```
[ptp_tracking_result]
```

Puis configurer ces pages dans "Tracking > Réglages".

### Intégration WooCommerce

1. Éditer une commande WooCommerce
2. Dans la metabox "Suivi d'expédition", entrer un numéro de suivi
3. Enregistrer la commande
4. Un envoi est automatiquement créé et lié

## Configuration requise

- WordPress 6.0+
- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.2+
- WooCommerce 7.0+ (optionnel)

## Base de données

Le plugin crée une table `wp_ptp_tracking_events` pour stocker l'historique des événements :

- `id` : ID auto-incrémenté
- `shipment_id` : ID de l'envoi
- `event_date` : Date/heure de l'événement
- `status` : Statut de l'événement
- `location` : Localisation
- `description` : Description détaillée
- `created_at` : Date de création

## Sécurité

- ✅ Toutes les entrées sont sanitizées
- ✅ Toutes les sorties sont échappées
- ✅ Nonces sur toutes les actions admin
- ✅ Vérifications de permissions
- ✅ Protection CSRF
- ✅ Validation des données AJAX

## Standards WordPress

- ✅ WordPress Coding Standards
- ✅ Utilisation de l'API WordPress
- ✅ Traductions i18n/l10n
- ✅ Hooks et filtres
- ✅ Architecture orientée objet (OOP)
- ✅ Singleton pattern pour la classe principale

## Développement

### Classes principales

- `Plugin_Tracking_Personalise` : Classe principale (singleton)
- `PTP_Helper` : Méthodes utilitaires statiques
- `PTP_Database` : Gestion de la base de données
- `PTP_Post_Types` : Enregistrement du CPT
- `PTP_Admin_*` : Interfaces d'administration
- `PTP_Shortcodes` : Shortcodes publics
- `PTP_WooCommerce` : Intégration WooCommerce

### Hooks disponibles

Le plugin offre plusieurs hooks pour personnalisation (à venir dans version future).

## Support

Pour tout support ou question, veuillez ouvrir une issue sur GitHub.

## Licence

GPL-2.0+ - Voir le fichier d'en-tête du plugin pour plus de détails.

## Auteur

**HitPro LLC**
- Site web : https://example.com
- Support : https://example.com/support

## Changelog

### Version 1.0.0
- Version initiale
- Gestion complète des envois
- Interface admin complète
- Shortcodes publics avec timeline
- Intégration WooCommerce
- Compatible WordPress 6+ et PHP 8.1+