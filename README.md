# wardrobe-management-api

API back-end d'une application de gestion de garde-robe personnelle. Chaque utilisateur
numérise ses vêtements, compose des tenues, suit ce qui est propre ou au sale, et prépare
la mise en vente de ce qu'il ne porte plus.

La référence concurrente est *Alta Daily: Digital AI Closet*, à laquelle ce produit ajoute
deux fonctions absentes : le **suivi de disponibilité** des vêtements et la **mise en vente
assistée sur Vinted**.

> **État du projet** : les sept blocs fonctionnels sont livrés. **161 tests, 161 passés,
> 528 assertions — vérifiés sur MySQL 8.4 via Sail**, Larastan niveau 7 à zéro erreur,
> 37 routes sur `/api/v1`. La contrainte `CHECK` sur `outfit_items` est éprouvée en base.
>
> Deux choses restent à brancher, toutes deux dépendantes d'un fournisseur externe :
> l'**essayage sur avatar** (modèles sous licence non commerciale) et l'**extraction
> d'attributs par photo**. Le détourage et l'OCR d'étiquette attendent seulement qu'un pilote
> soit installé. Voir le
> [document de conception](docs/superpowers/specs/2026-07-25-wardrobe-management-api-design.md).

## Fonctionnalités

| Bloc | Contenu |
|------|---------|
| Garde-robe | Saisie manuelle des vêtements — marque, modèle, taille, couleur, composition, prix d'achat |
| Identification | OCR de l'étiquette d'entretien, vision pour les attributs visuels, code-barres en bonus |
| Disponibilité | Propre, sale, en lavage, en séchage, à réparer, rangé, prêté — statut d'affichage dérivé |
| Journal de port | Chaque port horodaté, base du calcul de **coût par port** |
| Avatar | Image canonique versionnée, fournie ou générée |
| Tenues | Composition, planche de vêtements, essayage virtuel sur avatar, calendrier |
| Articles envisagés | Visualiser un vêtement pas encore acheté avec sa garde-robe existante |
| Revente | Brouillon d'annonce Vinted complet, relu et publié par l'utilisateur |

## Architecture

Le projet suit **OSDD** (Open Source Driven Development), l'approche architecturale de
référence Xefi, via `xefi/laravel-osdd`. L'application est découpée en layers autonomes,
chacune versionnée, testable isolément et déclarant ses dépendances.

```
technical/
  osdd/           la layer technique du package OSDD lui-même
  media/          stockage, variantes d'images, détourage
  ai-gateway/     abstraction des fournisseurs d'IA, journal des opérations, coûts
functional/
  users/          utilisateurs, authentification, permissions, locale
  catalog/        produits, variantes, marques, catégories — mutualisé, sans user_id
  wardrobe/       vêtements possédés, disponibilité, journal de port, souhaits
  identification/ résolution d'un scan ou d'une photo
  styling/        avatars, tenues, prévisualisations
  resale/         brouillons d'annonce Vinted
```

La layer d'authentification s'appelle `users` et non `identity` : c'est le nom que produit
`osdd:start`, et suivre la convention de l'outil vaut mieux qu'imposer la nôtre.

La frontière décisive est **`catalog` / `wardrobe`** : le catalogue est une donnée de
référence mutualisée entre tous les utilisateurs, alimentée par les identifications et
modérée depuis le back-office ; la garde-robe est strictement per-user. Deux cycles de vie,
deux régimes de permissions, deux stratégies de cache.

`ai-gateway` isole tout appel à un fournisseur externe. C'est cette layer qui permet de
passer d'une exécution locale à une API managée sans toucher au reste du code.

## Pile technique

| | |
|---|---|
| Runtime | PHP 8.5 (repli 8.4), Laravel 13 |
| Environnement local | Docker Desktop + Laravel Sail |
| Base de données | MySQL 8.4, en conteneur |
| File d'attente et cache | Redis, en conteneur |
| CRUD et API | `lomkit/laravel-rest-api` |
| Autorisation | `lomkit/laravel-access-control` |
| Rôles et permissions | `spatie/laravel-permission` |
| Médias | `spatie/laravel-medialibrary` |
| Outillage Claude | `laravel/boost` |
| Tests | PHPUnit, factories via `xefi/faker-php-laravel` |
| Analyse statique | Larastan niveau 7, `xefi/phpstan-xefi-rules` |

Clients consommateurs : application mobile Flutter, application web utilisateur Vue.js, et
back-office web. Authentification Sanctum en double mode — tokens Bearer pour le mobile,
cookies SPA pour les fronts web. Le back-office consomme la même API avec des permissions
distinctes.

## Dépendances externes

| Besoin | Solution retenue | Note |
|--------|------------------|------|
| Détourage des photos | Pilote sélectionnable, `rembg` par défaut | Aucun pilote installé : le statut le dit, rien n'est maquillé |
| OCR d'étiquette | Tesseract en local | Gratuit |
| Attributs par vision | API multimodale | Coût négligeable à l'échelle personnelle |
| Essayage virtuel | Modèles libres en licence **non commerciale** | Légal en usage personnel uniquement |
| Vinted | **Aucune API utilisable** | Publication assistée, voir ci-dessous |

### Pourquoi la publication Vinted est assistée et non automatique

Une API Vinted officielle existe — *Vinted Pro Integrations* — mais elle est inutilisable
ici pour deux raisons : son authentification par clé d'API et signature HMAC **n'offre
aucune délégation d'identité**, donc aucun flux « connectez votre compte Vinted » ; et elle
est réservée aux comptes professionnels sur liste blanche.

L'API prépare donc la fiche complète, et l'utilisateur relit puis publie lui-même. Aucune
donnée d'identification Vinted n'est stockée.

Le détail et les sources figurent dans le [document de conception](docs/superpowers/specs/2026-07-25-wardrobe-management-api-design.md).

## Démarrage

Aucun prérequis n'est installé sur une machine neuve. Le script d'amorçage couvre
l'ensemble, par étapes réexécutables :

```bash
scripts/bootstrap.sh system     # PHP, Composer, Node sur l'hôte — demande sudo
scripts/bootstrap.sh docker     # accès au socket Docker — demande sudo
scripts/bootstrap.sh laravel    # squelette Laravel 13
scripts/bootstrap.sh packages   # jeu de packages Xefi obligatoire
scripts/bootstrap.sh osdd       # scaffolding des layers
scripts/bootstrap.sh sail       # démarre la stack, migre et sème
```

Ou d'un bloc :

```bash
scripts/bootstrap.sh all
```

MySQL et Redis tournent dans Docker via Sail : rien d'autre que PHP, Composer et Node n'est
installé sur l'hôte. Les étapes `system` et `docker` requièrent un mot de passe sudo.

L'étape `docker` ajoute votre compte au groupe `docker`. **L'appartenance à un groupe ne
prend effet que dans une nouvelle session** — fermez le shell et rouvrez-en un, ou lancez
`newgrp docker`.

### Au quotidien

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
```

Les tests tournent sur MySQL, le même moteur qu'en production. Pour une exécution rapide
sans conteneur, un override en ligne de commande bascule sur SQLite en mémoire :

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

Attention : la contrainte `CHECK` sur `outfit_items` n'existe pas sous SQLite, seul le
listener y veille. Une exécution sous Sail reste la référence.

### À savoir sur `osdd:start`

La commande dissout le squelette monolithique dans les layers : `app/Models/User.php`, sa
factory et sa migration partent dans `functional/users`, et `config/` disparaît au profit
de la publication à la demande. L'absence de `app/`, `config/` et `database/` à la racine
est donc le comportement attendu, pas une installation ratée.

Corollaire : les migrations `sessions`, `jobs` et `cache` du squelette disparaissent aussi.
Le projet utilise Redis pour ces trois rôles, ce qui rend ces tables inutiles — mais laisser
`SESSION_DRIVER=database` provoquerait une panne silencieuse.

### Contrainte matérielle à connaître

L'essayage virtuel exige **24 Go de VRAM** par worker. Une Quadro T1000 (4 Go) ne suffit
pas. Le mode de prévisualisation `FlatLay` — la planche de vêtements détourés — ne dépend
d'aucun GPU et reste disponible partout.

## Conventions

Les règles de contribution, de workflow git et les conventions de code sont dans
[CLAUDE.md](CLAUDE.md).

## Documentation

- [Document de conception](docs/superpowers/specs/2026-07-25-wardrobe-management-api-design.md) —
  périmètre, contraintes externes établies par la recherche, modèle de données, flux,
  stratégie de test, risques ouverts.
