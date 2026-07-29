# Plan d'implémentation

**Date** : 2026-07-25
**Conception de référence** : [design](../specs/2026-07-25-wardrobe-management-api-design.md)

Chaque tranche est livrable et testable seule. Une tranche n'est terminée que quand ses
tests passent, que Larastan est propre au niveau 7 et que Pint est passé.

---

## Tranche 0 — Amorçage

**Bloquée par l'environnement.** Aucun prérequis n'est installé et `sudo` demande un mot de
passe ; cette tranche doit être lancée par un humain.

1. `scripts/bootstrap.sh system` — PHP 8.5 (repli 8.4), Composer, Node 22, PostgreSQL, Redis.
2. `scripts/bootstrap.sh laravel` — squelette Laravel 13, fusionné sans écraser les fichiers suivis.
3. `scripts/bootstrap.sh packages` — jeu de packages obligatoire et outillage de développement.
4. `scripts/bootstrap.sh osdd` — `osdd:start`, puis relevé de la signature réelle de la
   commande de création de layer.
5. Créer les huit layers : `technical/media`, `technical/ai-gateway`, puis
   `functional/identity`, `catalog`, `wardrobe`, `identification`, `styling`, `resale`.
6. Configurer `.env` pour PostgreSQL et Redis, créer la base, `php artisan migrate`.

**Critère de sortie** : `php artisan osdd:phpunit` enregistre les huit suites et la suite par
défaut passe.

---

## Tranche 1 — Socle et garde-robe

Blocs 1, 2 et 3 du périmètre. Aucune dépendance externe : entièrement testable hors ligne.

### 1.1 `identity`

- `users` en ULID, avec `locale`, `SoftDeletes` et `Prunable`.
- Sanctum en double mode : tokens Bearer et cookies SPA.
- `spatie/laravel-permission` publié et migré, trait `HasRoles` sur `User`.
- Seeder de permissions. Les vérifications portent sur les permissions, jamais sur les noms
  de rôles.
- `UserControl` avec son périmètre.

### 1.2 `catalog`

- Migrations `brands`, `categories` (auto-référence), `products`, `product_variants`.
- `ean` en index unique sur `product_variants`.
- Enum `ProductSource`.
- Fichiers de langue français et anglais pour les libellés de catégories.
- Resources lomkit, en lecture seule pour les utilisateurs, en écriture pour le back-office
  via permissions.
- Seeder d'une arborescence de catégories de départ.

### 1.3 `wardrobe`

- Migrations `garments`, `wear_events`, `wishlist_items`.
- Enums `GarmentAvailability` (avec `isAvailable(): bool`) et `GarmentCondition` (portant son
  libellé Vinted).
- `GarmentControl` : périmètre « appartient à l'utilisateur courant ».
- Resources lomkit pour le CRUD.
- Contrôleurs dédiés pour les actions : marquer porté, changer de statut de disponibilité.
- Calcul du coût par port, en objet valeur testé unitairement.
- Factories via `faker()`.

### 1.4 Cascades

- `CascadeUserDeletion`, `CascadeGarmentDeletion`, enregistrés dans un
  `ModelEventServiceProvider`.
- Itération en `cursor()`, jamais sur la relation en propriété dynamique.

**Critère de sortie** : un utilisateur crée un vêtement à la main, le marque porté, le passe
au sale, consulte son coût par port ; un second utilisateur ne voit rien de tout ça. La
suppression de l'utilisateur déclenche les événements sur chaque enfant.

---

## Tranche 2 — Médias et détourage

Layer `technical/media`.

- `spatie/laravel-medialibrary` configuré, disque local en développement.
- Collections de médias sur `Garment`, `WishlistItem`, `AvatarVersion`.
- Détourage BiRefNet en conversion, synchrone.
- `AiOperation::GarmentCutout` journalisé dans `ai_operations`.

**Critère de sortie** : une photo de vêtement produit une version détournée, et l'opération
est tracée avec sa latence.

**Point à valider par un test réel** : BiRefNet sur une Quadro T1000 de 4 Go, en résolution
réduite. Si l'exécution locale ne tient pas, basculer sur un service de détourage — le coût
reste marginal et l'interface ne change pas.

---

## Tranche 3 — Tenues et planche

Bloc 6 partiel, sans dépendance GPU. Livrable avant l'essayage.

- `technical/ai-gateway` : migration `ai_operations`, enum `AiOperation`, contrat de
  fournisseur, journalisation des coûts et latences.
- `styling` : migrations `outfits`, `outfit_items`, `outfit_previews`, `outfit_plans`.
- Contrainte `CHECK` sur `outfit_items` garantissant qu'exactement une des deux clés
  étrangères est renseignée.
- Enums `PreviewMode`, `RenderStatus`, `Season`.
- Composition de la `cache_key` en objet valeur, testée unitairement.
- Mode `FlatLay` : composition des détourages en une planche mise en page.
- Pattern State sur `OutfitPreview`.
- `CascadeOutfitDeletion`.

**Critère de sortie** : une tenue mêlant vêtements possédés et articles envisagés produit une
planche ; une seconde demande identique retourne le rendu en cache sans régénérer.

---

## Tranche 4 — Avatar et essayage

Blocs 5 et 6 complets.

- Migrations `avatars` et `avatar_versions`.
- Téléversement de l'image canonique fournie par l'utilisateur ; versionnement à chaque
  modification.
- Mode `AvatarTryOn` : job différé via `ai-gateway`.
- La version d'avatar entre dans la `cache_key`, ce qui rend l'invalidation automatique.
- `CascadeAvatarDeletion`.

**Prérequis externe** : un fournisseur d'essayage exécutable. Sur le périmètre personnel,
modèles à licence non commerciale sur GPU emprunté (Colab ou Kaggle, 16 Go), à confirmer par
un test avant de bâtir dessus.

**Critère de sortie** : un rendu sur avatar aboutit en différé, son état est consultable, et
modifier l'avatar invalide les rendus antérieurs sans purge manuelle.

---

## Tranche 5 — Identification

Bloc 4.

- Migration `identification_requests`, enums `IdentificationKind` et `IdentificationStatus`.
- Pattern State sur `IdentificationRequest`.
- Couche 1 : OCR de l'étiquette d'entretien via Tesseract — composition, taille, code de style.
- Couche 2 : extraction d'attributs visuels via API multimodale.
- Couche 3 : résolution d'EAN sur `product_variants`, en bonus.
- Confirmation par l'utilisateur obligatoire ; aucune identification ne crée un vêtement
  directement.
- Ce que l'utilisateur confirme enrichit `catalog` avec `source = UserContributed` et
  `verified_at` nul.

**Critère de sortie** : une photo d'étiquette remplit composition et taille ; l'utilisateur
corrige puis crée le vêtement ; le catalogue s'enrichit d'une variante non vérifiée.

**À vérifier avant d'aller plus loin** : les CGU des bases code-barres commerciales sur le
stockage durable des données renvoyées, si cette couche doit être ajoutée.

---

## Tranche 6 — Revente Vinted

Bloc 7.

- Migration `vinted_listing_drafts`, enum `VintedDraftStatus`.
- Pattern State avec transitions illégales — `HandedOff` ne revient pas à `Draft`.
- Composition du brouillon depuis les attributs du vêtement : titre, description, libellés
  marque, taille, couleur, état Vinted, photos détourées, prix suggéré.
- Contrôleur dédié pour la préparation et le passage de relais.
- Interface `ListingPublisher` avec une seule implémentation, `AssistedHandoff`. Un
  adaptateur `VintedProApi` viendra derrière la même interface si la candidature aboutit.

**Interdit, rappelé ici volontairement** : ne jamais stocker d'identifiants Vinted, ne jamais
appeler les endpoints internes `/api/v2`.

**Critère de sortie** : un vêtement produit un brouillon complet et cohérent, dont
l'utilisateur peut suivre le cycle jusqu'à `PublishedByUser`.

---

## Tranche 7 — Back-office

- Permissions de modération distinctes.
- Resources lomkit en écriture sur `catalog` pour les détenteurs de la permission.
- Fusion de doublons de produits et de variantes.
- Passage de `verified_at` sur les contributions relues.
- Supervision des `identification_requests` et des `ai_operations` en échec.

**Critère de sortie** : un modérateur relit une contribution, la valide ou la fusionne, sans
jamais accéder à la garde-robe d'un utilisateur.

---

## Ordonnancement

```
0 amorçage
└─ 1 socle et garde-robe
   ├─ 2 médias et détourage
   │  ├─ 3 tenues et planche
   │  │  └─ 4 avatar et essayage
   │  └─ 5 identification
   ├─ 6 revente
   └─ 7 back-office
```

Les tranches 5, 6 et 7 sont indépendantes entre elles et parallélisables une fois la 1
livrée. La tranche 4 est la seule dont le prérequis externe reste non validé.

## Règles applicables à chaque tranche

- `vendor/bin/pint --dirty --format agent` avant toute finalisation.
- `vendor/bin/phpstan analyse` sur les chemins modifiés, niveau 7.
- `php artisan test --compact` avec un filtre ciblé, puis la suite de la layer.
- Un commit séparé par unité de travail, message d'une phrase en anglais, gitmoji, poussé
  après chaque commit.
- Aucun commentaire dans le code, seulement des docstrings d'une phrase en anglais.
- Aucun try-catch : les exceptions remontent.
