# GID Chemini

Plateforme privée de l'équipe GID SAS (syndic de copropriété) : chemini.gidsas.fr.

- PHP 8.3, SQLite, aucune dépendance externe.
- Connexion sans mot de passe : clé d'accès (Face ID / Windows Hello) ou code TOTP, appareils de confiance.
- Déploiement : chaque push sur `main` est déployé automatiquement par OVH (Git + webhook) dans `chemini/site`.

Les secrets ne sont pas dans ce dépôt : ils sont lus sur le serveur dans `chemini/app/config.php`,
hors du dossier publié. Les données (base, sessions) sont dans `chemini/data`, également hors web.
