# Wardrobe Management API — document de conception

**Date** : 2026-07-25
**Statut** : validé pour amorçage
**Périmètre de ce document** : les sept blocs fonctionnels du produit

> Convention : la prose est en français, tous les identifiants, noms de tables, de
> colonnes, de classes et d'enums sont en anglais.

---

## 1. Objectif

Une API back-end Laravel 13 pour une application de gestion de garde-robe. La référence
concurrente est *Alta Daily: Digital AI Closet* (Flagship AI, Inc.), à laquelle ce produit
ajoute deux fonctions absentes : le **suivi de disponibilité** des vêtements (propre, sale,
en lavage) et la **mise en vente assistée sur Vinted**.

## 2. Périmètre

### Dans le périmètre

Sept blocs fonctionnels, construits par tranches :

| # | Bloc | Dépend de |
|---|------|-----------|
| 1 | Socle — comptes, authentification, permissions | — |
| 2 | Garde-robe — vêtements, catégories, photos | 1 |
| 3 | Disponibilité — propre / sale / en lavage / indisponible | 2 |
| 4 | Identification — OCR d'étiquette, vision, code-barres | 2 |
| 5 | Avatar — image canonique fournie ou générée | 1 |
| 6 | Tenues — composition, planche, essayage virtuel | 2, 5 |
| 7 | Revente — publication assistée sur Vinted | 2 |

### Hors périmètre

- **Abonnements et facturation.** Le produit cible un usage personnel sans revenu.
- **Cycles de lavage modélisés.** Le statut porté par le vêtement couvre le besoin exprimé ;
  une entité `laundry_cycle` serait de la complexité non demandée.
- **Recommandation de tenue par IA.** Alta la propose ; elle n'est pas demandée ici et
  dépendrait de la météo et de l'agenda, deux intégrations supplémentaires.
- **Réseau social / page d'exploration.** Non demandé.
- **Prédiction de taille par mesures corporelles.** Non demandé.

## 3. Décisions verrouillées

| Sujet | Décision | Conséquence |
|-------|----------|-------------|
| Clients | Flutter mobile, web utilisateur Vue.js, back-office web | Sanctum en double mode : tokens Bearer pour Flutter, cookies SPA pour les fronts web |
| Langues | Français et anglais | Aucun texte utilisateur en dur, fichiers de langue pour messages, catégories et enums |
| Échelle | Usage personnel, zéro coût récurrent | Pas de quotas ni de facturation implémentés ; le modèle les accueille sans reprise |
| Avatar | Image canonique **fournie par l'utilisateur** | Aucune génération d'avatar à financer |
| Essayage | Modèles à licence non commerciale, génération en différé | Légal en usage personnel uniquement ; à remplacer si le produit devient commercial |
| Détourage | BiRefNet, licence MIT, exécution locale | Utilisable commercialement, coût marginal nul |
| Identification | OCR étiquette → vision → code-barres | Catalogue contributif, aucun abonnement à une base produit |
| Vinted | Publication assistée + candidature Pro en parallèle | Aucune donnée d'identification Vinted stockée |
| Base de données | PostgreSQL | Support JSON et index partiels |
| Identifiants | ULID sur toutes les clés primaires | Règle projet |

## 4. Contraintes externes établies

Ces trois constats proviennent d'une recherche documentée et **conditionnent la conception**.
Ils doivent être relus avant toute remise en cause d'une décision ci-dessus.

### 4.1 Vinted — aucune délégation d'identité possible

Une API officielle existe : *Vinted Pro Integrations*
(<https://pro-docs.svc.vinted.com/>). Elle expose `CreateItems`, `UpdateItems`,
`DeleteItems`, `ValidateItems`, `GetItemStatus`, une API Orders avec étiquettes
d'expédition, et des webhooks.

Deux blocages :

1. **Authentification par clé d'API et signature HMAC-SHA256** (en-têtes
   `X-Vpi-Access-Key`, `X-Vpi-Hmac-Sha256`). Ce n'est pas OAuth : aucun flux
   « connectez votre compte Vinted » n'existe. La clé appartient à une entreprise, pas à
   un utilisateur final. **L'exigence initiale « 1 utilisateur = 1 compte Vinted
   connecté » est donc techniquement irréalisable**, y compris avec l'accès accordé.
2. **Réservée aux comptes Vinted Pro**, sur liste blanche après candidature. Plafond
   initial de 500 articles actifs par utilisateur d'API.

**Voie retenue** : publication assistée. L'API prépare la fiche complète ; l'utilisateur
relit et publie lui-même. C'est le modèle que List Perfectly revendique comme conforme.

**Voie écartée** : l'API interne `/api/v2` avec cookies de session. Elle exigerait de
stocker les identifiants Vinted des utilisateurs et d'automatiser des accès contraires aux
conditions d'utilisation, pour un résultat instable.

### 4.2 Code-barres — non viable en voie principale pour le textile

- Les étiquettes de vêtements portent fréquemment du **Code 128 de suivi interne**, non
  résolvable publiquement. Le SKU est propre à la marque ; le GTIN est attribué par GS1 et
  son existence n'est pas garantie.
- Quand un EAN-13 existe, il est **unique par variante taille/couleur** — un scan résout
  donc une variante, jamais un produit.
- **Aucun équivalent d'Open Food Facts pour le textile.** Open Product Data et Outpan sont
  morts. La couverture réelle d'Open Products Facts en vêtements **n'a pas pu être
  établie** — à vérifier avant d'en dépendre.
- Bases commerciales : Barcode Lookup (99 à 499 $/mois, 34 champs, 100 requêtes/minute),
  EAN-Search (1,2 milliard d'EAN annoncés). **Point non résolu** : aucune ne documente
  clairement le droit de stocker durablement les données renvoyées — à lire dans les CGU
  avant de concevoir un cache de catalogue alimenté par ces sources.
- Les flux d'affiliation (AWIN, Zalando, Decathlon) portent le GTIN/EAN et constituent une
  piste d'alimentation en masse, en batch et non en temps réel.

**Voie retenue** : identification en couches, vision d'abord, catalogue contributif.

### 4.3 Essayage virtuel — verrou de licence, non de technique

- **Tous les grands modèles libres d'essayage virtuel sont en licence non commerciale** —
  IDM-VTON, OOTDiffusion, CatVTON, StableVITON, VITON-HD, HR-VITON, tous en CC BY-NC-SA
  4.0. Le périmètre « usage personnel » les rend utilisables ; un passage en commercial
  les interdirait.
- **Besoins matériels** : 24 Go de VRAM par worker. La machine de développement dispose
  d'une Quadro T1000 à **4 Go** — insuffisant d'un facteur six. Paliers gratuits
  utilisables : Google Colab et Kaggle (T4/P100, 16 Go), à confirmer par un test réel.
- **En production hébergée** : 1 500 à 3 000 $/mois de GPU avant tout trafic.
- **API managée** (FASHN.ai, 0,0488 à 0,075 $/image, usage commercial autorisé) : son
  intérêt principal est de **porter la licence**, pas de fournir le GPU.
- Les modèles d'essayage exigent en entrée une **image 2D photoréaliste d'un corps**, pas
  une géométrie 3D. Aucun pipeline paramétrique n'alimente directement l'essayage.
- Ready Player Me est hors jeu : racheté par Netflix, API coupées le 31 janvier 2026.

**Voie retenue** : l'avatar est une image canonique **versionnée**, fournie par
l'utilisateur. Tout rendu est asynchrone et mis en cache.

## 5. Architecture — layers OSDD

Package : `xefi/laravel-osdd`, amorcé par `php artisan osdd:start`. Les layers sont des
dépôts Composer en chemin ; Laravel auto-découvre leurs service providers.

```
technical/
  media/          stockage, variantes d'images, détourage BiRefNet
  ai-gateway/     abstraction des fournisseurs, journal des opérations, coûts
functional/
  identity/       utilisateurs, authentification, permissions
  catalog/        produits, variantes, marques, catégories — mutualisé, sans user_id
  wardrobe/       vêtements possédés, disponibilité, journal de port, souhaits
  identification/ résolution scan/photo vers variante ou attributs
  styling/        avatars, tenues, prévisualisations
  resale/         brouillons d'annonce Vinted
```

### La frontière décisive : `catalog` / `wardrobe`

- `catalog` est une **donnée de référence mutualisée**, sans `user_id`, alimentée par les
  identifications et modérée depuis le back-office.
- `wardrobe` est **strictement per-user**.

Deux cycles de vie, deux régimes de permissions, deux stratégies de cache. Les confondre
produirait la god-layer qu'on ne peut plus découper.

### Pourquoi la disponibilité n'est pas une layer

Elle n'est qu'un état porté par le vêtement plus une méthode de dérivation. Une layer
`availability` séparée n'aurait aucun modèle propre et devrait atteindre les modèles de
`wardrobe` — exactement l'anti-pattern OSDD d'une layer qui fouille les internes d'une
autre.

### Pourquoi `ai-gateway` est une layer technique

Elle porte la file d'attente, le journal des opérations, la comptabilité de coûts et le
choix de fournisseur. `identification` et `styling` la consomment sans dupliquer cette
logique, et ne voient jamais qu'un rendu à état. C'est cette abstraction qui permet de
passer d'un notebook exécuté à la main à une API managée sans toucher au reste — donc de
respecter la décision « usage personnel sans fermer la porte au commercial ».

## 6. Modèle de données

Règles transversales, sans exception :

- **Clés primaires en ULID.**
- **Aucun enum en base.** Enums PHP portant leur comportement.
- **Aucune cascade en base.** Clés étrangères en `->constrained()` simple ; la cascade
  passe par des listeners `Cascade*Deletion` sur les événements `deleting`.
- **`SoftDeletes` implique `Prunable`.**

### 6.1 `identity`

| Table | Colonnes notables |
|-------|-------------------|
| `users` | `email`, `password`, `name`, `locale`, `SoftDeletes` + `Prunable` |
| tables `spatie/laravel-permission` | rôles et permissions ; **les vérifications portent sur les permissions, jamais sur les noms de rôles** |

Le back-office consomme la même API que les clients utilisateurs, avec des permissions
distinctes. Pas de seconde API.

### 6.2 `catalog` — aucun `user_id`

| Table | Colonnes notables |
|-------|-------------------|
| `brands` | `name`, `slug` |
| `categories` | `parent_id` (auto-référence, nullable), `slug`, `sort` ; libellés traduits |
| `products` | `brand_id` nullable, `category_id`, `name`, `style_reference`, `material_composition`, `retail_price_cents`, `currency`, `source`, `verified_at` |
| `product_variants` | `product_id`, `size_label`, `colour_name`, `colour_hex`, `ean` **unique**, `sku` |

`ProductSource` (enum PHP) : `UserContributed`, `BarcodeLookup`, `AffiliateFeed`, `Manual`.

**L'EAN vit sur la variante.** Dans le textile, quand un GTIN existe il est unique par
couple taille/couleur (§4.2). Un scan résout une variante.

`verified_at` matérialise la modération back-office du catalogue contributif.

### 6.3 `wardrobe` — per-user

| Table | Colonnes notables |
|-------|-------------------|
| `garments` | `user_id`, `product_variant_id` **nullable**, `category_id`, `brand_id` nullable, `name`, `size_label`, `colour_name`, `colour_hex`, `material_composition`, `condition`, `availability_status`, `purchase_price_cents`, `purchase_currency`, `purchased_at`, `notes`, `SoftDeletes` + `Prunable` |
| `wear_events` | `garment_id`, `worn_on`, `outfit_id` nullable |
| `wishlist_items` | `user_id`, `product_variant_id` nullable, `name`, `brand_label`, `size_label`, `colour_name`, `external_url`, `price_cents` |

**Le vêtement porte toujours ses propres attributs.** `product_variant_id` est un
enrichissement optionnel, pas la source de vérité. Sinon chaque lecture de garde-robe
devient une jointure conditionnelle sur des colonnes nullables, et un vêtement saisi à la
main n'a pas d'existence propre.

**Le vert/rouge n'est pas stocké.** `GarmentAvailability` (enum PHP) :
`Available`, `Dirty`, `InLaundry`, `Drying`, `NeedsRepair`, `Stored`, `Lent`. Il porte
`isAvailable(): bool`. Le statut d'affichage est **dérivé** ; ajouter un état ne casse rien.

`GarmentCondition` (enum PHP) : `New`, `LikeNew`, `Good`, `Fair`, `Worn`. Il porte le
libellé Vinted correspondant, consommé par `resale`.

`wear_events` est le socle du **coût par port** : `purchase_price_cents` divisé par le
nombre d'événements. Cette table doit exister dès la première migration — la reconstituer
après coup est impossible.

`wishlist_items` matérialise l'**article envisagé** : il entre dans une tenue, n'a ni
disponibilité, ni journal de port, ni revente.

### 6.4 `identification`

| Table | Colonnes notables |
|-------|-------------------|
| `identification_requests` | `user_id`, `kind`, `status`, `resolved_product_variant_id` nullable, `extracted_attributes` (JSON), `confidence`, `provider`, `cost_cents`, `error_message` |

`IdentificationKind` : `Barcode`, `Photo`, `CareLabel`.
`IdentificationStatus` : `Pending`, `Processing`, `Succeeded`, `Failed`.

### 6.5 `styling`

| Table | Colonnes notables |
|-------|-------------------|
| `avatars` | `user_id`, `name` |
| `avatar_versions` | `avatar_id`, `version`, `parameters` (JSON), image canonique |
| `outfits` | `user_id`, `name`, `occasion`, `season`, `notes`, `SoftDeletes` + `Prunable` |
| `outfit_items` | `outfit_id`, `garment_id` nullable, `wishlist_item_id` nullable, `slot`, `sort` |
| `outfit_previews` | `outfit_id`, `avatar_version_id` nullable, `mode`, `status`, image, `cache_key` **unique**, `provider`, `cost_cents`, `error_message` |
| `outfit_plans` | `user_id`, `outfit_id`, `scheduled_for` |

`PreviewMode` : `FlatLay`, `AvatarTryOn`.
`RenderStatus` : `Pending`, `Processing`, `Succeeded`, `Failed`.

**Le cache de rendus est la table elle-même.** `cache_key` est l'empreinte de
`(avatar_version_id, identifiants de vêtements ordonnés, mode)`, en index unique. Une tenue
re-consultée retrouve son rendu par une simple lecture. Pas de couche de cache séparée à
invalider, et l'économie de GPU est structurelle.

**L'avatar est versionné** parce que toute modification de paramètres ou d'image invalide
les rendus existants. La version entre dans la clé de cache ; l'invalidation est donc
automatique et il n'y a rien à purger.

**Deux modes dès le départ.** `FlatLay` — planche de vêtements détourés et mis en page,
instantanée et gratuite. `AvatarTryOn` — rendu sur l'avatar, différé. Le second est un
raffinement du premier, jamais un prérequis.

Sur `outfit_items`, deux clés étrangères nullables plus une contrainte `CHECK` garantissant
qu'exactement une est renseignée — plutôt qu'une relation polymorphe, qui ferait perdre
l'intégrité référentielle que la règle « pas de cascade en base » rend indispensable.

### 6.6 `resale`

| Table | Colonnes notables |
|-------|-------------------|
| `vinted_listing_drafts` | `user_id`, `garment_id`, `title`, `description`, `brand_label`, `size_label`, `colour_label`, `condition_label`, `price_cents`, `currency`, `status`, `handed_off_at` |

`VintedDraftStatus` : `Draft`, `Ready`, `HandedOff`, `PublishedByUser`, `Abandoned`.

**Aucune donnée d'identification Vinted n'est stockée.** La table
`vinted_pro_connections`, qui porterait des clés chiffrées, est **conçue mais pas créée** ;
elle se branchera derrière la même interface si la candidature à la liste blanche aboutit.

### 6.7 `technical/media`

`spatie/laravel-medialibrary` pour le stockage et les variantes. Le détourage BiRefNet est
une conversion, synchrone (1 à 3 s).

### 6.8 `technical/ai-gateway`

| Table | Colonnes notables |
|-------|-------------------|
| `ai_operations` | `user_id` nullable, `operation`, `provider`, `status`, `request_hash`, `cost_cents`, `latency_ms`, `error_message` |

`AiOperation` : `GarmentCutout`, `CareLabelOcr`, `AttributeExtraction`, `TryOnRender`.

Une seule table pour tous les fournisseurs : c'est l'audit et la comptabilité de coûts. Les
quotas se **dérivent** par comptage sur période, sans compteur séparé susceptible de dériver.

### 6.9 Cascades par listener

Aucune cascade en base. Listeners sur `deleting`, itérant en `cursor()` :

| Parent | Enfants |
|--------|---------|
| `User` | `garments`, `outfits`, `avatars`, `wishlist_items`, `identification_requests`, `vinted_listing_drafts`, `ai_operations` |
| `Garment` | `wear_events`, `outfit_items`, `vinted_listing_drafts` |
| `Outfit` | `outfit_items`, `outfit_previews`, `outfit_plans` |
| `Avatar` | `avatar_versions` |

Sans cela, la suppression d'un utilisateur effacerait des vêtements sans qu'aucun événement
ne se déclenche : le catalogue contributif et l'audit deviendraient faux en silence.

### 6.10 Trois cycles de vie à états

`identification_requests`, `outfit_previews` et `vinted_listing_drafts` ont des transitions
illégales — un brouillon `HandedOff` ne peut pas revenir à `Draft`. Le pattern State
s'applique, plutôt que des `match` sur le statut dispersés dans les services.

## 7. Surface d'API

- **CRUD** via `lomkit/laravel-rest-api` : une `Resource` par modèle, enregistrée dans
  `routes/api.php`. Aucun contrôleur CRUD écrit à la main.
- **Autorisation** via `lomkit/laravel-access-control` : un `Control` par modèle avec ses
  périmètres, trait `HasControl` sur le modèle. Le périmètre par défaut de tout modèle de
  `wardrobe`, `styling` et `resale` est « appartient à l'utilisateur courant ».
- **Actions non-CRUD** en contrôleurs classiques : soumettre une identification, demander un
  rendu, préparer un brouillon Vinted, marquer un vêtement comme porté ou comme sale.
- **Versionnement** : préfixe `/api/v1`.

## 8. Flux principaux

### 8.1 Identification d'un vêtement

```
POST /api/v1/identification-requests   (photo ou code-barres)
  -> 202, ressource à l'état Pending
  -> job en file
       couche 1 : OCR de l'étiquette d'entretien -> composition, taille, code de style
       couche 2 : vision -> catégorie, couleur, motif, coupe
       couche 3 : code-barres -> résolution de variante si EAN présent et connu
  -> Succeeded, extracted_attributes rempli
  -> l'utilisateur confirme, corrige, puis crée le Garment
  -> ce qui est confirmé enrichit le catalog
```

L'utilisateur confirme toujours. Aucune identification ne crée un vêtement directement :
la confiance des couches vision n'est pas suffisante pour écrire sans relecture.

### 8.2 Prévisualisation d'une tenue

```
POST /api/v1/outfits/{outfit}/previews   (mode)
  -> calcul du cache_key
  -> si un rendu Succeeded existe : 200, retour immédiat
  -> sinon 202, ressource Pending
       FlatLay      : composition des détourages, quasi immédiat
       AvatarTryOn  : job différé vers le fournisseur d'essayage
  -> Succeeded, image disponible
```

### 8.3 Mise en vente assistée

```
POST /api/v1/garments/{garment}/vinted-draft
  -> composition du brouillon depuis les attributs du vêtement
     titre, description, marque, taille, couleur, libellé d'état Vinted,
     photos détourées, prix suggéré
  -> statut Ready
  -> l'utilisateur relit, ajuste, puis publie lui-même dans Vinted
  -> statut HandedOff, puis PublishedByUser sur confirmation
```

## 9. Gestion des erreurs

- **Aucun try-catch.** Les exceptions remontent ; le handler Laravel les traduit en réponses.
- **Aucune exception générique.** Une exception nommée par cas métier.
- Les échecs de fournisseur externe se matérialisent en **état d'entité**
  (`Failed` + `error_message`), pas en exception avalée. Un rendu échoué est une donnée
  consultable et rejouable, pas une trace perdue.
- Les erreurs de validation passent par des Form Requests ou les règles de la `Resource`
  lomkit.

## 10. Stratégie de test

- **PHPUnit exclusivement.** Pas de Pest.
- Majoritairement des tests **Feature** ; Unit réservé aux enums porteurs de comportement,
  aux objets valeur et aux calculs purs (coût par port, composition de clé de cache).
- **Chaque layer porte sa propre suite**, enregistrée par `php artisan osdd:phpunit`. Un
  layer doit se tester sans démarrer les autres : `catalog` sans utilisateur, `wardrobe`
  sans fournisseur d'IA, `identification` avec un `ai-gateway` bouchonné.
- **Factories via `xefi/faker-php-laravel`** et l'assistant `faker()`. Jamais
  `fakerphp/faker` ni `$this->faker`.
- Couverture obligatoire : les listeners de cascade (créer un enfant, supprimer le parent,
  vérifier les effets de bord), les transitions d'état illégales, la réutilisation du cache
  de rendus, et le périmètre d'accès (un utilisateur ne voit jamais la garde-robe d'un autre).
- **Larastan niveau 7 minimum**, plus `xefi/phpstan-xefi-rules`.

## 11. Risques et points ouverts

| Risque | Nature | Traitement |
|--------|--------|------------|
| Modèles d'essayage en licence non commerciale | Bloquant si le produit devient commercial | Ils sont derrière `ai-gateway` : changement de fournisseur, pas réécriture |
| GPU de développement à 4 Go | Insuffisant pour l'essayage | Tester Colab / Kaggle ; le mode `FlatLay` ne dépend d'aucun GPU |
| Couverture d'Open Products Facts en textile | **Non établie** | À mesurer avant toute dépendance |
| CGU des bases code-barres sur le stockage durable | **Non résolu** | À lire avant d'alimenter le catalogue depuis ces sources |
| Prix de Google Vertex `virtual-try-on-001` | **Non publié** | À obtenir si un fournisseur commercial devient nécessaire |
| Candidature à la liste blanche Vinted Pro | Incertaine, et impliquerait un modèle de dépôt-vente | La publication assistée ne dépend pas de son issue |

## 12. Prérequis d'environnement

Le script `scripts/bootstrap.sh` exécute l'installation puis le scaffolding complet, par
étapes réexécutables. Les étapes `system` et `database` demandent `sudo` et doivent être
lancées par un humain.

## 13. Journal des écarts

Décisions prises pendant la construction qui divergent de la conception initiale, avec leur
raison. À lire avant de s'étonner d'une différence entre ce document et le code.

| Écart | Raison |
|-------|--------|
| La layer `identity` s'appelle `users` | C'est le nom que produit `osdd:start`. Suivre la convention de l'outil plutôt que la nôtre. |
| Une neuvième layer, `technical/osdd` | Créée par `osdd:start`, elle porte la configuration du package. |
| `GarmentCondition` a cinq cas calés sur Vinted — `NewWithTag`, `NewWithoutTag`, `VeryGood`, `Good`, `Satisfactory` | Correspondance 1:1 avec les conditions Vinted, donc aucune table de correspondance inventée à maintenir dans `resale`. |
| Les modèles `PersonalAccessToken`, `Role` et `Permission` sont surchargés dans `users` | Les modèles de Sanctum et de spatie utilisent des clés auto-incrémentées ; la règle ULID impose de les surcharger avec `HasUlids`. |
| Les migrations de Sanctum et de spatie vivent dans `functional/users` | Les tokens et les rôles appartiennent au cycle de vie du compte. Une layer `technical/auth` serait plus orthodoxe et reste ouverte. |
| Une locale par utilisateur, appliquée par un middleware | La colonne `users.locale` ne servait à rien sans lui : les réponses suivaient le défaut applicatif. Le middleware `locale` la fait respecter. |
| `wear_events.outfit_id` est absent | La table `outfits` n'existe qu'en tranche 3. La colonne et sa clé étrangère y seront ajoutées. |
| Les cascades vivent dans `wardrobe`, pas dans `users` | `wardrobe` écoute `User::deleting`. L'inverse ferait connaître `wardrobe` à `users`, une dépendance à contresens. |
| Une suppression douce de vêtement **conserve** son journal de port | Restaurer un vêtement doit restaurer son coût par port. Seule la suppression définitive efface l'historique. |
| Le listener de cascade reflète le type de suppression du parent | Sans cela la contrainte `restrict` bloque la purge d'un compte dont les vêtements sont déjà en corbeille. |
| Pas de classes de base lomkit au niveau projet | Le concern `PerformsQueries` du package fournit déjà les cinq hooks. En créer dans chaque layer serait la duplication que l'OSDD proscrit. |
| Les libellés de catégories viennent des fichiers de langue, indexés par `slug` | Pas de colonne `name`, pas de table de traductions : la taxonomie est un référentiel curé, pas une donnée utilisateur. |
| Les énumérations `Ability` et `UserRole` vivent dans `users` | Simplification assumée : le vocabulaire de permissions est traité comme applicatif. Le découpage orthodoxe donnerait à chaque layer ses propres abilities. |
| `faker_mixin.php` est versionné | Régénéré à chaque appel `faker()`, mais PHPStan sur un checkout neuf ne l'aurait pas. |
| La base de développement est SQLite, pas PostgreSQL | La création du rôle PostgreSQL exige `sudo`. Sans incidence sur les tranches 0 à 3 ; à corriger avant la tranche 3, où la contrainte `CHECK` et les colonnes JSON divergent selon le moteur. |
