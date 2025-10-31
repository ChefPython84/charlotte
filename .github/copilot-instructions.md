## Contexte rapide

Ce dépôt est une application Symfony 7 (PHP >= 8.2) avec front-end géré par Webpack Encore. Le projet est destiné à tourner facilement en conteneurs (docker-compose.yml présent) ; la README indique d'utiliser `docker-compose up` pour démarrer l'environnement.

Principaux répertoires à connaître:
- `src/` : code PHP (contrôleurs dans `src/Controller`, Form dans `src/Form`, EventSubscriber, Message, Repository, etc.)
- `templates/` : vues Twig (partial `_partials/`, dossiers par fonctionnalité comme `admin/`, `salle/`, `reservation/`)
- `assets/` : sources JS/CSS gérées par Webpack Encore (`assets/app.js`, `assets/bootstrap.js`, `assets/controllers/` pour Stimulus)
- `public/build/` : artefacts produits par `npm run build` / `yarn build` (ne pas modifier manuellement)
- `migrations/` : fichiers de migration Doctrine (ex. `VersionYYYYMMDDHHMMSS.php`)

## Workflows et commandes utiles

- Démarrer l'environnement (README) :
  - `docker-compose up`
- Installer les dépendances PHP :
  - `composer install` (ou via l'image Docker si vous travaillez dans les conteneurs)
- Commandes Symfony courantes :
  - `bin/console doctrine:migrations:migrate` — appliquer migrations
  - `bin/console doctrine:fixtures:load` — charger fixtures
  - `bin/console cache:clear` — vider le cache
  - `bin/console assets:install public` — installer assets (géré via auto-scripts composer)
- Front-end (Webpack Encore, défini dans `package.json`) :
  - Développement : `npm run dev` ou `npm run watch`
  - Serveur dev : `npm run dev-server`
  - Build production : `npm run build`

## Patterns et conventions spécifiques au projet

- Nomination des migrations : suivez le pattern `VersionYYYYMMDDHHMMSS.php` (voir `migrations/`).
- Templates Twig : regrouper par fonctionnalité dans `templates/<feature>/`. Les fragments réutilisables vont dans `_partials/`.
- Contrôleurs : utiliser `src/Controller/*Controller.php`. Respectez les actions RESTful lorsque possible (index/show/create/edit/delete).
- EasyAdmin est utilisé (`easycorp/easyadmin-bundle`) pour l'administration ; les surcharges de templates admin se trouvent dans `templates/bundles/easyadmin/` ou `templates/admin/`.
- Front-end Stimulus : les contrôleurs sont dans `assets/controllers/` et référencés via `assets/controllers.json` et `assets/bootstrap.js`.

## Intégrations et dépendances externes

- Doctrine ORM + Migrations, fixtures pour jeu de données (voir `doctrine` et `migrations` dans `config/packages/`).
- Webpack Encore pour bundling JS/CSS (scripts dans `package.json`).
- Docker/Docker Compose pour l'environnement local (lancement via `docker-compose up`).

## Règles pour un agent IA

- Ne modifie pas directement `public/build/` — génère via `npm run build`.
- Avant d'ajouter ou modifier une migration, exécute `bin/console doctrine:migrations:status` pour comprendre l'état.
- Pour toute modification du front-end, référence `assets/app.js` et `assets/bootstrap.js` ; rebuild via `npm run dev` ou `npm run build`.
- Cherche pattern et tests existants avant d'ajouter un nouveau fichier : tests unitaires sont dans `tests/`, le projet utilise PHPUnit (`bin/phpunit` disponible).

## Exemples concrets pour l'agent

- Si tu dois ajouter une route et une vue pour gérer une entité `Salle` :
  1. Ajouter une méthode dans `src/Controller/SalleController.php`.
  2. Créer le template dans `templates/salle/<action>.html.twig` et réutiliser `_partials/` si nécessaire.
  3. Mettre à jour les services ou repository si besoin dans `src/Repository/`.
  4. Si la DB change, créer une migration dans `migrations/` et exécuter `bin/console doctrine:migrations:migrate`.

## Points d'attention

- Le `composer.json` active `symfony/flex` et `auto-scripts` — certaines opérations sont lancées automatiquement après `composer install`.
- PHP >= 8.2 requis. Respecte les types et nullables utilisés dans le code existant.
- Ne pas supprimer ou éditer les fixtures/migrations sans vérifier l'impact sur les environnements CI/production.

## Améliorations pratiques (à suivre)

- Fichiers à ne jamais modifier manuellement :
  - `public/build/*` (artefacts compilés), `var/*` (cache/log), `vendor/*`.

- Auth & sécurité : si tu dois modifier l'authentification, regarde `src/Security/AppAuthenticator.php` (authenticator personnalisé) et `src/Entity/User.php` (utilisateur). Ne change pas la hiérarchie des rôles sans valider l'impact sur `config/packages/security.yaml`.

- CI / tests rapides :
  - Local (si PHP installé) :
    - `bin/phpunit`
  - Dans le conteneur Docker (si vous utilisez `docker-compose` fourni) :
    - `docker-compose exec php bin/phpunit`
  - Pour exécuter les tests qui utilisent la base de données, vérifie la variable `TEST_TOKEN` et les fixtures.

- Build assets en production :
  1. `npm run build`
  2. (optionnel) `bin/console assets:install public` ou laisser `composer` auto-scripts si configuré

- Migrations :
  - Toujours exécuter `bin/console doctrine:migrations:status` avant de créer ou appliquer une migration.
  - Nommer les migrations selon le pattern `VersionYYYYMMDDHHMMSS.php`.

## Exemple de mini-checklist avant une PR

1. Exécuter la suite de tests : `bin/phpunit` (ou via Docker).
2. Vérifier l'absence de changements manuels dans `public/build/`.
3. Si modification DB : ajouter une migration et vérifier `doctrine:migrations:status`.
4. Rebuild assets si nécessaire : `npm run build`.

---

Si tu veux, j'ajoute maintenant :
- une version anglaise séparée (`.github/copilot-instructions.en.md`),
- un snippet d'exemple (route + contrôleur + template),
- ou des conventions de commit/PR. Dis-moi ce que tu préfères et je l'ajoute.

Si quelque chose est ambigu ou si vous voulez que j’insère des exemples précis (ex. un modèle de migration ou un template Twig standardisé), dites-moi où vous préférez que je l’ajoute. Merci de me dire si vous voulez la version anglaise aussi.
