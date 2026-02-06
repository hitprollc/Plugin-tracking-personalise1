# Guide d'utilisation - Plugin Tracking Personalise

## Installation rapide

1. **Télécharger le plugin**
   ```bash
   # Créer un ZIP du plugin
   zip -r plugin-tracking-personalise.zip plugin-tracking-personalise/
   ```

2. **Installer dans WordPress**
   - Aller dans "Extensions > Ajouter"
   - Cliquer sur "Téléverser une extension"
   - Choisir le fichier ZIP
   - Activer l'extension

3. **Configuration initiale**
   - Aller dans "Tracking > Réglages"
   - Vérifier que les pages de suivi ont été créées automatiquement
   - Activer la protection par email si souhaité

## Créer votre premier envoi

### Méthode 1 : Depuis l'admin WordPress

1. Aller dans **"Tracking > Ajouter un envoi"**
2. Remplir les informations :
   - **Titre** : "Envoi pour Jean Dupont" (par exemple)
   - **Numéro de suivi** : `1Z999AA10123456784` (obligatoire)
   - **Transporteur** : Sélectionner UPS, FedEx, etc.
   - **Statut actuel** : En attente
   - **Nom du client** : Jean Dupont
   - **Email du client** : jean@example.com
3. Cliquer sur **"Publier"**

### Méthode 2 : Depuis une commande WooCommerce

1. Aller dans **"WooCommerce > Commandes"**
2. Ouvrir une commande existante
3. Dans la metabox **"Suivi d'expédition"** (colonne droite)
4. Entrer un **numéro de suivi**
5. **Enregistrer la commande**
6. Un envoi sera créé automatiquement !

## Ajouter des événements de tracking

1. Ouvrir un envoi existant
2. Faire défiler jusqu'à **"Événements de suivi"**
3. Dans le formulaire en bas :
   - **Date/Heure** : Sélectionner la date de l'événement
   - **Statut** : Choisir le statut (En transit, etc.)
   - **Localisation** : Ex: "Paris, France"
   - **Description** : Ex: "Colis en cours d'acheminement"
4. Cliquer sur **"Ajouter l'événement"**
5. L'événement apparaît instantanément dans la liste !

## Afficher le suivi sur votre site

### Étape 1 : Pages créées automatiquement

Lors de l'activation, 2 pages sont créées :
- **"Suivi de colis"** - avec `[ptp_tracking_lookup]`
- **"Résultat du suivi"** - avec `[ptp_tracking_result]`

### Étape 2 : Personnaliser les pages

Vous pouvez éditer ces pages pour ajouter :
- Du texte d'introduction
- Des images
- Des instructions

**Important** : Gardez les shortcodes !

### Étape 3 : Ajouter au menu

1. Aller dans **"Apparence > Menus"**
2. Ajouter la page **"Suivi de colis"** au menu
3. Enregistrer

## Exemples d'utilisation

### Exemple 1 : Client qui recherche son colis

1. Le client va sur la page "Suivi de colis"
2. Entre son numéro de suivi : `1Z999AA10123456784`
3. Entre son email (si requis) : `jean@example.com`
4. Clique sur "Suivre mon colis"
5. Voir la timeline complète avec :
   - Barre de progression
   - Statut actuel
   - Historique des événements

### Exemple 2 : Boutique WooCommerce

**Workflow complet :**

```
1. Client passe commande
   ↓
2. Vous préparez la commande
   ↓
3. Vous créez l'étiquette d'expédition (UPS, FedEx, etc.)
   ↓
4. Vous ouvrez la commande dans WooCommerce
   ↓
5. Vous entrez le numéro de suivi
   ↓
6. Le plugin crée automatiquement l'envoi
   ↓
7. Le client reçoit un email avec le tracking
   ↓
8. Le client peut suivre dans "Mon compte"
```

### Exemple 3 : Ajouter des mises à jour

**Scénario** : Le colis arrive dans un nouveau centre de tri

1. Ouvrir l'envoi concerné
2. Ajouter un événement :
   - Date : Aujourd'hui 14:30
   - Statut : En transit
   - Localisation : "Centre de tri Lyon, France"
   - Description : "Colis arrivé au centre de tri"
3. Le client voit immédiatement la mise à jour !

## Intégration WooCommerce avancée

### Affichage dans "Mon compte"

Le tracking s'affiche automatiquement :
- Dans la page de détails de commande
- Sous le tableau des produits
- Avec un bouton "Suivre mon colis"

### Emails de commande

Le tracking est ajouté automatiquement à TOUS les emails de commande :
- Email de confirmation
- Email d'expédition
- Email de commande terminée

Format :
```
INFORMATIONS DE SUIVI
Numéro de suivi: 1Z999AA10123456784
Transporteur: UPS
Statut: En transit
[Suivre mon colis]
```

## Shortcodes personnalisés

### [ptp_tracking_lookup]

**Paramètres :**
- `redirect` : ID de la page de résultat (optionnel)

**Exemple :**
```
[ptp_tracking_lookup redirect="123"]
```

### [ptp_tracking_result]

Pas de paramètres. Affiche les résultats selon les paramètres GET.

## Réglages disponibles

### Page "Tracking > Réglages"

1. **Page de recherche de suivi**
   - Page contenant [ptp_tracking_lookup]
   - Où les clients entrent leur numéro

2. **Page de résultat de suivi**
   - Page contenant [ptp_tracking_result]
   - Où les résultats sont affichés

3. **Vérification par email**
   - ☑ Activé : Les clients doivent entrer leur email
   - ☐ Désactivé : Numéro de suivi seul suffit

## Transporteurs et statuts

### Transporteurs disponibles

Par défaut :
- UPS
- FedEx
- USPS
- DHL
- Autre

### Statuts disponibles

- **En attente** (pending) - 10% de progression
- **En transit** (in_transit) - 50% de progression
- **En livraison** (out_for_delivery) - 80% de progression
- **Livré** (delivered) - 100% de progression
- **Exception** (exception) - 50% de progression
- **Retourné** (returned) - 100% de progression

## Conseils et bonnes pratiques

### ✅ À faire

1. **Ajouter des événements réguliers** pour informer les clients
2. **Utiliser des localisations précises** (ville, pays)
3. **Écrire des descriptions claires** pour chaque événement
4. **Tester avec un vrai numéro** avant de partager
5. **Personnaliser les pages de suivi** avec votre charte graphique

### ❌ À éviter

1. Ne pas dupliquer les numéros de suivi
2. Ne pas oublier de mettre à jour les statuts
3. Ne pas laisser des envois sans événements
4. Ne pas supprimer les pages de suivi par erreur

## Résolution de problèmes

### Le formulaire ne s'affiche pas

**Cause** : Shortcode manquant ou mal écrit
**Solution** : Vérifier que `[ptp_tracking_lookup]` est bien présent

### "Aucun envoi trouvé"

**Causes possibles** :
- Numéro de suivi incorrect
- Envoi non publié
- Email incorrect (si protection activée)

**Solutions** :
- Vérifier l'orthographe du numéro
- Vérifier que l'envoi est "Publié" (pas "Brouillon")
- Désactiver temporairement la protection par email

### Les événements ne s'affichent pas

**Cause** : Aucun événement ajouté
**Solution** : Ajouter au moins un événement depuis l'admin

### Les styles ne s'appliquent pas

**Cause** : Conflit CSS avec le thème
**Solution** : Ajouter `!important` ou personnaliser le CSS

## Personnalisation CSS

Ajouter dans **Apparence > Personnaliser > CSS additionnel** :

```css
/* Changer la couleur de la barre de progression */
.ptp-progress-fill {
    background: linear-gradient(90deg, #your-color, #your-color-dark) !important;
}

/* Changer la couleur du statut "Livré" */
.ptp-current-status.ptp-status-delivered h3 {
    color: #your-color !important;
}

/* Personnaliser la timeline */
.ptp-timeline-dot {
    background: #your-color !important;
}
```

## Support et aide

Pour toute question :
1. Consulter README.md
2. Consulter FEATURES.md
3. Ouvrir une issue sur GitHub

## Mise à jour du plugin

1. Sauvegarder votre base de données
2. Désactiver le plugin
3. Supprimer l'ancienne version
4. Installer la nouvelle version
5. Réactiver le plugin

**Note** : Les données ne sont PAS supprimées lors de la désactivation, uniquement lors de la désinstallation complète.

---

**Vous êtes prêt !** 🚀

Commencez par créer votre premier envoi et testez le système de tracking.
