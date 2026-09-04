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
- [ ] La commande test a bien déclenché **deux** emails : la confirmation au
      client et l'alerte à `store_email`. Vérifier aussi qu'ils n'arrivent pas
      en spam (SPF/DKIM du domaine).

> Les emails partent pendant la requête (`QUEUE_CONNECTION=sync`). Un serveur
> SMTP lent ou mal configuré n'empêche **jamais** la commande d'être
> enregistrée : l'échec est écrit dans `storage/logs`. Si un client dit ne pas
> avoir reçu sa confirmation, chercher `Confirmation email failed` dans les
> logs, puis utiliser l'action « Renvoyer la confirmation » sur la commande.

### DNS mail : Cloudflare Email Routing + SMTP de l'hébergeur

Email Routing prend l'**entrant** (MX Cloudflare) et réécrit le SPF du domaine
avec son seul include. Le **sortant** part toujours du serveur cPanel Namecheap
(`server706.web-hosting.com`, 198.177.120.0/24) : plus autorisé par le SPF, il
n'est ni aligné SPF ni DKIM, et le DMARC en `p=reject` fait rejeter le message
(`550-5.7.26 ... domain's DMARC policy` chez Gmail).

Les trois enregistrements TXT à publier dans Cloudflare (DNS → Records) :

| Nom | Valeur |
| --- | --- |
| `@` | `v=spf1 include:_spf.mx.cloudflare.net include:spf.web-hosting.com ~all` |
| `default._domainkey` | la clé DKIM exacte affichée par cPanel → **Email Deliverability** |
| `_dmarc` | `v=DMARC1; p=reject; sp=reject; adkim=r; aspf=r; rua=mailto:…` |

- Un **seul** enregistrement SPF : les deux `include` dans la même ligne, jamais
  deux TXT `v=spf1` (SPF devient invalide et tout casse). La chaîne coûte 8 des
  10 résolutions DNS autorisées — ne pas ajouter d'`include` à la légère.
- **La clé DKIM doit être sur une seule ligne.** Collée depuis cPanel avec un
  retour à la ligne, Cloudflare le stocke tel quel (`` au milieu du base64) :
  la clé ne décode plus, DKIM échoue en silence et, avec `p=reject`, Gmail
  renvoie `550-5.7.26`. C'est exactement ce qui s'est produit ici.
- `adkim=s; aspf=s` (strict) casse dès que le Return-Path est un sous-domaine de
  l'hébergeur : rester en `r` (relaxed), l'alignement reste vérifié.
- Le DMARC est **temporairement en `p=none`** le temps de confirmer les rapports
  `rua`. Repasser à `p=reject; sp=reject` une fois que Gmail montre vert.
- Contrôle : cPanel → Email Deliverability doit afficher « Valid » pour SPF et
  DKIM, puis dans Gmail « Afficher l'original » doit montrer
  `spf=pass`, `dkim=pass`, `dmarc=pass`.

## Comptes du back-office

Filament n'a pas d'écran de gestion des utilisateurs. Tout passe par une
commande :

```bash
php artisan admin:make sofia@skinchemists.ma --name="Sofia Alaoui"   # crée + autorise
php artisan admin:make un.compte@existant.ma                          # autorise un compte existant
php artisan admin:make un.compte@existant.ma --revoke                 # retire l'accès
```

La commande ne modifie **jamais** le mot de passe d'un compte existant.

> Si la connexion renvoie « Ces identifiants ne correspondent pas à nos
> enregistrements » alors que le mot de passe est bon, c'est que `is_admin` est
> à `false` : Filament signale un refus de `canAccessPanel()` comme un échec
> d'identifiants. Lancer `php artisan admin:make <email>`.

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
  dossiers contiennent des espaces et des accents ; voir la section « Images ».
  `CatalogSeeder` échoue si une image référencée n'existe pas.
- **Paiement** : paiement à la livraison uniquement. CMI (carte bancaire) n'est
  pas intégré — cela demande un contrat marchand et leur spécification.

## Images

Les images vivent dans `public/uploads/products/`. Les téléversements de
l'administration y sont écrits via le disque `public_files` — pas de
`storage:link` nécessaire, ce qui évite un point de rupture sur mutualisé.

Le chemin stocké en base est **décodé** (espaces et accents tels quels) ;
`image_url()` encode chaque segment au moment de l'affichage. Ne pas stocker
de chemin déjà encodé, il serait encodé deux fois.

> **`db:seed` ne réécrit plus le catalogue.** `CatalogSeeder` importe un produit
> uniquement s'il n'existe pas déjà. Après la mise en ligne, la base fait foi :
> relancer le seeder n'efface ni les prix, ni le stock, ni les images
> téléversées par l'équipe.

Supprimer une image dans l'administration retire la ligne en base mais laisse
le fichier sur le disque — volontaire, pour qu'une fausse manipulation ne
détruise pas un original. Le ménage se fait à la main si besoin.
