# Déploiement — SkinChemists Maroc

Laravel 13 + Filament 5. Cible : hébergement mutualisé cPanel, PHP 8.2+.

## 1. Construire en local

Le serveur mutualisé n'a en général ni Composer ni shell. On construit ici, on
téléverse le résultat.

```bash
composer install --no-dev --optimize-autoloader
```

## 2. Téléverser

Envoyer tout le projet **au-dessus** de `public_html` (par exemple
`/home/<compte>/skinchemists`), puis pointer le domaine sur le dossier
`public/` du projet.

Si l'hébergeur ne permet pas de changer la racine du domaine, copier le contenu
de `public/` dans `public_html/` et corriger les deux chemins en haut de
`public_html/index.php` pour qu'ils remontent vers le projet.

> Ne jamais laisser `app/`, `.env`, `storage/` ou `vendor/` accessibles depuis
> le web. Si tout doit vivre dans `public_html`, l'hébergeur n'est pas adapté.

## 3. Configurer

```bash
cp .env.example .env
php artisan key:generate
```

Renseigner dans `.env` : `APP_URL`, les identifiants MySQL créés dans cPanel,
le SMTP, et `ADMIN_PASSWORD`.

Vérifier que `APP_DEBUG=false` et `APP_ENV=production`.

## 4. Base de données

```bash
php artisan migrate --force
php artisan db:seed --force        # catalogue, réglages, promotions, admin
```

`DemoSeeder` (faux avis) n'est **pas** inclus — c'est volontaire.

## 5. Mettre en cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

À relancer après **chaque** modification de `.env` ou des routes.

## 6. Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

## 7. Vérifier

- [ ] `/` s'affiche avec le bandeau, le hero et les best-sellers
- [ ] `/boutique` filtre par actif et par catégorie
- [ ] Une fiche produit affiche le prix promo et le JSON-LD
- [ ] Une commande test passe et décrémente le stock
- [ ] `/admin` refuse un visiteur non connecté
- [ ] Le mot de passe admin de `.env` a été changé
- [ ] `/sitemap.xml` répond

## Mises à jour

```bash
php artisan down
# téléverser les fichiers
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

## Points connus

- **Files d'attente** : `QUEUE_CONNECTION=sync`. Les emails partent pendant la
  requête. Dès que le volume le justifie : passer en `database` et ajouter un
  cron cPanel (`* * * * * php artisan queue:work --stop-when-empty`).
- **Cache** : `CACHE_STORE=database`. Ne jamais mettre en cache d'objets
  (Collections, modèles) — le store désérialise avec `allowed_classes`
  restreint et rend un `__PHP_Incomplete_Class`. Mettre en cache des tableaux.
- **Images produits** : servies depuis `public/uploads/products/`. Les noms de
  dossiers contiennent des espaces et des accents ; ils sont encodés en base.
  `CatalogSeeder` échoue si une image référencée n'existe pas.
- **Paiement** : paiement à la livraison uniquement. CMI (carte bancaire) n'est
  pas intégré — cela demande un contrat marchand et leur spécification.
