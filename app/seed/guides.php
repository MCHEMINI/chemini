<?php
/* Guides de départ (version 1) : procédures ImmOpen relevées au cabinet. Modifiables ensuite depuis Équipe › Guides. */
return [
['immopen-bien-demarrer', 'ImmOpen', '🚀', 'Bien démarrer dans ImmOpen', 'Les réflexes à avoir avant toute manipulation : session Bureau à distance, fenêtres, sécurité.', '5 min', 'Débutant', <<<'MD'
## Avant de commencer
1. **Vérifiez si ImmOpen est déjà ouvert.** Regardez la barre des tâches : si la fenêtre « Connexion Bureau à distance » existe déjà, utilisez-la.
2. **N'ouvrez pas une deuxième session Bureau à distance.** Une seule session à la fois sur le serveur ImmOpen : une nouvelle connexion ferme celle d'un collègue.
3. **Repérez le compte ouvert.** Le nom de l'utilisateur et ses filtres (gestionnaire, comptable, case Syndic) s'appliquent à tous les écrans.

! N'appuyez jamais sur la touche Échap dans ImmOpen : elle ferme les fenêtres une à une, puis propose de quitter l'application.

## Pendant votre travail
1. **Consultez sans modifier.** Pour une recherche, restez sur les écrans de consultation et ne validez rien.
2. **Fermez proprement.** Utilisez la croix de la fenêtre ou le bouton « Fermer » de l'écran, jamais Échap.
3. **Remettez les filtres en l'état.** Si vous avez changé un filtre sur un écran partagé (gestionnaire, comptable, Syndic), remettez-le comme vous l'avez trouvé.

> Astuce : les fenêtres RaiDrive qui passent parfois devant ImmOpen disparaissent seules au bout de quelques secondes.
MD],
['immopen-export-excel', 'ImmOpen', '📤', 'Exporter une liste vers Excel', 'Récupérer n\'importe quelle grille ImmOpen dans Excel pour la trier, la filtrer ou la partager.', '3 min', 'Débutant', <<<'MD'
## Exporter la grille
1. **Affichez la liste voulue** (planning des AG, balance, factures…) avec les bons filtres.
2. **Faites un clic droit sur la grille** puis choisissez « Exporter la table vers Excel… ».
3. **Nommez le fichier.** Effacez le nom proposé (Ctrl + A), tapez un nom clair, par exemple `planning-ag-octobre.xlsx`, puis validez.

## Exploiter le fichier
1. **Ouvrez le fichier dans Excel** depuis la session.
2. **Sélectionnez la plage utile** : tapez-la dans la zone Nom, en haut à gauche (par exemple `A1:R400`), puis Entrée.
3. **Copiez avec Ctrl + C** et collez où vous en avez besoin.
4. **Fermez sans enregistrer** si vous n'avez fait que consulter : Ctrl + W puis « Ne pas enregistrer ».

> Astuce : un export est une photo à l'instant T. Pour des chiffres à jour, refaites l'export plutôt que de rouvrir un ancien fichier.
MD],
['immopen-planning-ag', 'ImmOpen', '📅', 'Consulter le planning des AG', 'Voir les AG à venir, les dates limites de convocation et les fins de mandat de chaque immeuble.', '4 min', 'Débutant', <<<'MD'
## Ouvrir le planning
1. **Menu A.G. › Planning AG.**
2. **Choisissez la sélection** : un gestionnaire, un comptable, ou tous les gestionnaires pour la vue du cabinet.
3. **Cochez « Syndic »** pour n'afficher que les immeubles en syndic.

## Lire les colonnes
- **Dernière AG et AG planifiée** : date et heure de la prochaine assemblée.
- **Date limite de convocation** : au-delà, la convocation est en retard.
- **Fin de mandat** : à surveiller pour inscrire le renouvellement à l'ordre du jour.
- **Convocation, notification, PV** : cases cochées quand l'étape est faite.

! Sur un poste partagé, notez les filtres d'origine avant de les changer et remettez-les à la fin.

> Astuce : Chemini reprend ce planning chaque mois (Direction › Planning des AG) avec des filtres rapides et l'agenda sur iPhone.
MD],
['immopen-balance-fournisseurs', 'ImmOpen', '🧾', 'Balance fournisseurs et honoraires annexes', 'Retrouver les montants facturés par fournisseur et par immeuble sur un exercice (du 1er avril au 31 mars).', '6 min', 'Intermédiaire', <<<'MD'
## Lancer la balance
1. **Menu Syndic › Suivi comptable › Balance fournisseurs.**
2. **Classe : 401.** Laissez le compte vide pour avoir tous les fournisseurs.
3. **Écritures : « Toutes ».** Type de tri : « Fournisseur ». Aucun filtre gestionnaire ou comptable.
4. **Période : l'exercice GID**, du 1er avril au 31 mars (par exemple 01/04/2026 → 31/03/2027).
5. **Lancez le calcul**, puis exportez la grille vers Excel (voir le guide « Exporter une liste vers Excel »).

## Lire le résultat
- Une ligne par immeuble et par fournisseur ; la colonne **Crédit** donne le montant facturé.
- Les comptes d'honoraires GID : Travaux 400067, Mutations 400002, Relances 400009, Procédures 400044, Sinistres 400016, Vacations AG/CS 400076, Vacations 400012, Comptabilité 400176, Photocopies 400011, Location de salles 400113.

> Astuce : Chemini publie ces totaux par immeuble dans Honoraires › annexes, mis à jour le 1er du mois.
MD],
['immopen-synthese-factures', 'ImmOpen', '🔎', 'Suivre les factures en cours', 'Lister les factures fournisseurs non réglées ou en attente, par comptable et par immeuble.', '4 min', 'Intermédiaire', <<<'MD'
## Afficher les factures
1. **Menu Syndic › Suivi comptable › Synthèse des Factures.**
2. **Sélection : factures en cours, tous collaborateurs** (ou votre code comptable pour votre seul portefeuille).
3. **Triez par immeuble ou par fournisseur** en cliquant sur l'en-tête de colonne.

## Points à contrôler
- **Doublons** : même fournisseur, même montant, même référence ou dates proches.
- **Factures « ne pas payer »** encore ouvertes.
- **Avoirs saisis comme des factures.**
- **Factures sans immeuble** ou de plus d'un an non réglées.

> Astuce : codes comptables ImmOpen : 02 KILAMOU, 03 SUBRA, 04 PIATTE, 05 DALOT, 06 MOUMEN. Le résultat du contrôle hebdomadaire est dans Direction › Comptabilité.
MD],
['chemini-taches', 'Chemini', '🤝', 'Créer une tâche pour vous ou un collègue', 'La bulle en bas à droite : une tâche en trois touches, depuis l\'ordinateur ou l\'iPhone.', '1 min', 'Débutant', <<<'MD'
## Créer la tâche
1. **Touchez la bulle** en bas à droite de n'importe quelle page.
2. **Choisissez « Tâche équipe ».**
3. **Touchez la personne** (Moi ou un collègue), écrivez la tâche, choisissez l'échéance.
4. **Touchez « Affecter ».** La tâche apparaît aussitôt dans ses tâches.

## Suivre et terminer
- **Mes tâches** (Perso) liste vos tâches ; cochez-les une fois faites, la date est enregistrée.
- **Collaborateurs** montre le nombre de tâches en cours de chacun.

> Astuce : « Tâche IA » dans la même bulle confie une consigne à l'assistant (recherche, courrier, tableau).
MD],
];
