<!DOCTYPE html>
{{--
    Shown by the service worker when a page load fails. Deliberately standalone:
    it is cached on install, so it must not depend on any other asset.
--}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hors ligne — skinChemists Admin</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #18181b; color: #e4e4e7; font-family: ui-sans-serif, system-ui, sans-serif; }
        .box { max-width: 22rem; padding: 2rem; text-align: center; }
        h1 { font-size: 1.125rem; font-weight: 600; margin: 0 0 .5rem; }
        p { font-size: .875rem; line-height: 1.6; color: #a1a1aa; margin: 0 0 1.5rem; }
        button { background: #e4e4e7; color: #18181b; border: 0; border-radius: .5rem;
                 padding: .625rem 1.25rem; font-size: .875rem; font-weight: 500; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Pas de connexion</h1>
        <p>
            L'administration a besoin du réseau pour afficher les commandes.
            Reconnectez-vous à Internet puis réessayez.
        </p>
        <button onclick="location.reload()">Réessayer</button>
    </div>
</body>
</html>
