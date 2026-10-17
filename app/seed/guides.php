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
['immopen-planning-ag', 'ImmOpen', '📅', 'Consulter le planning des AG', 'Planifier les AG ordinaires et calculer automatiquement les dates de convocation, de conseil syndical et de devis.', '5 min', 'Débutant', <<<'MD'
## Ouvrir le planning
1. **Menu A.G. › Planning AG.** Ce programme planifie les futures AG ordinaires, indépendamment des préparations en cours dans la gestion d'assemblée.
2. **Choisissez la sélection** : un gestionnaire, un comptable, ou tous les gestionnaires pour la vue du cabinet.
3. **Cochez « Syndic »** pour n'afficher que les immeubles en syndic.

## Lire les colonnes (aide ImmOpen)
- **Dernière AG** : date de la dernière assemblée générale, reprise de la fiche Immeuble.
- **Nomination et Durée** : date de nomination du syndic et durée de son mandat.
- **Début et fin d'exercice** : exercice en cours, tel qu'il apparaît dans la fiche Immeuble.
- **Délai maximum** : date limite pour tenir l'AG (6 mois par défaut, réglable avec le bouton Paramètres).
- **AG souhaitée** : date de la prochaine AG ordinaire, à saisir.
- **Limite convoc.** : date limite d'envoi de la convocation, calculée d'après l'AG souhaitée (23 jours par défaut).
- **Prép. charges, Conseil, Rech. devis** : dates calculées d'après l'AG souhaitée et les délais paramétrés.

## Planifier une AG
1. **Sélectionnez l'immeuble** dans la liste.
2. **Cliquez sur « Modifie »** et saisissez la date d'AG souhaitée : les autres dates se calculent seules.
3. **Pour imprimer le planning**, utilisez le bouton « Edite » (plusieurs critères de sélection et de tri).

! Sur un poste partagé, notez les filtres d'origine avant de les changer et remettez-les à la fin.

> Astuce : Chemini reprend ce planning chaque mois (Direction › Planning des AG) avec des filtres rapides et l'agenda sur iPhone.
MD],
['immopen-fiche-immeuble', 'ImmOpen', '🏢', 'Gérer la fiche d\'un immeuble', 'Rechercher un immeuble, consulter ses informations, lots, clés de répartition, charges, digicodes et bips.', '6 min', 'Débutant', <<<'MD'
## Trouver l'immeuble
1. **Menu Patrimoine › Immeubles.** Tout lot devant être rattaché à un immeuble, c'est le premier fichier de la copropriété.
2. **Recherchez avec les zones du haut** : Code (recherche directe), Commence par (ordre alphabétique), Contient (nom), Adresse, Ville. Majuscules et minuscules indifférentes.
3. **Sélectionnez la ligne** de l'immeuble voulu.

## Consulter sans modifier
- **Info** : toutes les informations de l'immeuble (adresse, copropriétaires, contrats, carnet d'entretien, évènements, affaires, archives).
- **Lots** et **Bât et Esc** : lots, bâtiments et escaliers.
- **Clés répartition** : clés de 001 à 999 ; la clé générale est la 001, les clés par index (compteurs) à partir de 949 (949 eau froide, 950 eau chaude, 951 autre compteur d'eau, 952 calories).
- **Charges** : groupes de charges (rubriques de dépenses) ; le 48 pour les factures d'eau, 49 à 52 pour les consommations aux index.
- **Digicodes**, **Bips** et **Recherche Bip** (retrouver le détenteur d'un bip par son numéro, tous immeubles confondus).
- **Calendrier** : calendrier métier de l'immeuble. **Fiche** : fiche Word de l'immeuble.

## Les trois niveaux de clés de répartition
- **Niveau général (1)** : un seul tantième pour tout l'immeuble.
- **Niveau bâtiment (2)** : un tantième par bâtiment ; chaque dépense doit être affectée à un bâtiment.
- **Niveau escalier (3)** : un tantième par escalier ; chaque dépense doit être affectée à un escalier.

! Les boutons Ajoute, Modifie et Supprime changent la fiche pour tout le cabinet : ne les utilisez que si c'est votre rôle.

> Astuce : un seul groupe de charges « Entretien escalier » relié à une clé de niveau 3 suffit ; ImmOpen crée lui-même un compte par escalier (015 A, 015 B).
MD],
['immopen-balance-fournisseurs', 'ImmOpen', '🧾', 'Balance fournisseurs et honoraires annexes', 'Retrouver les montants facturés par fournisseur et par immeuble sur un exercice (du 1er avril au 31 mars).', '6 min', 'Intermédiaire', <<<'MD'
## Lancer la balance
1. **Menu Syndic › Suivi comptable › Balance fournisseurs.**
2. **Dates de début et de fin : l'exercice GID**, du 1er avril au 31 mars (par exemple 01/04/2026 → 31/03/2027).
3. **Classe : 401.** Laissez le compte vide pour avoir tous les fournisseurs, ou saisissez-en un seul. Le champ « Code im » limite à un immeuble.
4. **Filtres collaborateurs** (Gestionnaire, Comptable, Assistant, Groupe) : laissez-les vides pour tout le cabinet.
5. **Écritures : choisissez « Toutes »**, ou « Non lettrées » pour ne garder que ce qui reste à payer, ou « Lettrées » pour ce qui est soldé. Cochez « Factures parvenues » pour inclure les factures reçues pas encore comptabilisées.
6. **Tri : « Fournisseur »** pour regrouper par prestataire, ou **« Immeuble »** pour une lecture par copropriété.
7. **Cliquez sur « Lancer Calcul »**, puis « Éditer » pour imprimer, ou exportez la grille vers Excel (voir le guide « Exporter une liste vers Excel »).

## Lire le résultat
- Une ligne par immeuble et par fournisseur ; la colonne **Crédit** donne le montant facturé.
- Les comptes d'honoraires GID : Travaux 400067, Mutations 400002, Relances 400009, Procédures 400044, Sinistres 400016, Vacations AG/CS 400076, Vacations 400012, Comptabilité 400176, Photocopies 400011, Location de salles 400113.

> Astuce : Chemini publie ces totaux par immeuble dans Honoraires › annexes, mis à jour le 1er du mois.
MD],
['immopen-synthese-factures', 'ImmOpen', '🔎', 'Suivre les factures en cours', 'Voir où en est chaque facture fournisseur, de la numérisation à la comptabilisation.', '5 min', 'Intermédiaire', <<<'MD'
## Afficher les factures
1. **Menu Syndic › Suivi comptable › Synthèse des Factures.**
2. **Choisissez ce que vous voulez voir** : « Brouillard provisoire », « Factures dans la numérisation » et/ou « Factures comptabilisées depuis la numérisation ».
3. **Filtrez si besoin** : Collaborateur, Compte fournisseur, Code gestionnaire ou comptable, et une **Date à filtrer** avec début et fin.
4. **Lisez le circuit de chaque facture** dans les colonnes : scannée → à compléter → à viser → à comptabiliser → comptabilisée. La colonne remplie la plus à droite indique l'étape atteinte.
5. **Triez** en cliquant sur un en-tête ; **Ctrl + clic** sur un deuxième en-tête trie sur plusieurs colonnes (par exemple immeuble puis fournisseur).
6. **« Voir écriture complète »** ouvre le détail comptable ; **« Exporter vers Excel »** sort la liste.

## Points à contrôler
- **Factures bloquées « à viser »** depuis longtemps : relancez le gestionnaire concerné.
- **Doublons** : même fournisseur, même montant, même référence ou dates proches.
- **Factures « ne pas payer »** encore ouvertes, **avoirs saisis comme des factures**, **factures sans immeuble**.

> Astuce : codes comptables ImmOpen : 02 KILAMOU, 03 SUBRA, 04 PIATTE, 05 DALOT, 06 MOUMEN. Le résultat du contrôle hebdomadaire est dans Direction › Comptabilité.
MD],
['immopen-preparer-reglements', 'ImmOpen', '💶', 'Préparer les règlements fournisseurs', 'Sélectionner les factures à payer par immeuble et par banque avant l\'émission des règlements.', '8 min', 'Avancé', <<<'MD'
! Écran réservé aux personnes habilitées à régler les factures. Sans habilitation, ImmOpen affiche « Vous n'êtes pas habilité(e) à régler les factures ».

## Ce que fait cet écran
- Il **prépare** les règlements : il ne paie rien. Le paiement se fait ensuite dans **« Émission et validation des règlements »**.
- Il est **mono-utilisateur** : une seule personne à la fois peut préparer des règlements.

## Sélectionner les factures
1. **Menu Syndic › Saisie comptable › Règlements factures.**
2. **Date d'écriture** : la date du jour est proposée.
3. **Fournisseur et/ou Code immeuble** : laissez vides pour tout voir, ou limitez à un prestataire ou une copropriété.
4. **« Règlement des dépenses payables au »** : seules les factures échues à cette date sont proposées.
5. **Banque de règlement** et **Mode de paiement** (virement, chèque…). Filtres Gestion / Compta / Assist et plage de codes immeuble si besoin.
6. **Validez la recherche** : la liste des factures à régler s'affiche.

## Choisir quoi régler
- **Détail facture** : ouvre la facture pour vérifier montant et pièce.
- **Régler / Modifier** : marque une facture en règlement total (T) ou partiel (P).
- **Forcer règlement** : règle une facture même si les conditions habituelles ne sont pas réunies — à utiliser avec prudence.
- **Tout régler** : règle automatiquement par ordre de priorité fournisseur, **dans la limite du solde bancaire** de l'immeuble.

## Ensuite
1. **Contrôlez la sélection** : montants, immeubles, solde de chaque banque.
2. **Passez à « Émission et validation des règlements »** pour générer les virements ou les chèques.

> Astuce : en cas de doute sur le mode de paiement d'un fournisseur, vérifiez sa fiche (bouton « Info fournisseur » depuis les contrats).
MD],
['immopen-recherche-montant', 'ImmOpen', '🔢', 'Retrouver une écriture par son montant', 'Le réflexe quand un relevé, un virement ou un fournisseur cite un montant sans référence.', '3 min', 'Débutant', <<<'MD'
## Lancer la recherche
1. **Menu Syndic › Suivi comptable › Recherche par montant.**
2. **« A partir du »** : date de début de la recherche (obligatoire). Remontez assez loin, par exemple au début de l'exercice.
3. **Montant** : saisissez-le **en positif**, sans signe (obligatoire).
4. **Sens : Débit et/ou Crédit.** Si vous ne savez pas, **cochez les deux**.
5. **Lancez la recherche** : toutes les écritures de ce montant s'affichent, avec immeuble, compte, date et libellé.

## Exploiter le résultat
- Double-cliquez sur une ligne pour ouvrir l'écriture complète.
- Le bouton **« Liste »** imprime le résultat.

> Astuce : pour un montant arrondi par la banque, essayez aussi le montant au centime près des deux côtés.
MD],
['immopen-recherche-piece', 'ImmOpen', '🧷', 'Retrouver une écriture par son numéro de pièce', 'Rechercher une facture ou une opération à partir de sa référence de pièce.', '2 min', 'Débutant', <<<'MD'
## Lancer la recherche
1. **Menu Syndic › Suivi comptable › Recherche par n° pièce.**
2. **Saisissez le numéro de pièce**, en entier ou en partie.
3. **Choisissez le mode** : **« commence par »** si vous avez le début de la référence, **« contient »** si vous n'en avez qu'un morceau.
4. **Lancez la recherche** : les écritures correspondantes s'affichent.
5. **« Liste »** imprime le résultat.

> Astuce : « contient » est plus large mais plus lent ; commencez par « commence par » si la référence est complète.
MD],
['immopen-consulter-compte', 'ImmOpen', '📒', 'Consulter un compte comptable', 'Afficher le journal d\'un compte (fournisseur, banque, charges) d\'une copropriété.', '3 min', 'Débutant', <<<'MD'
## Ouvrir le compte
1. **Menu Syndic › Suivi comptable › Consultation de comptes.**
2. **Saisissez l'immeuble**, puis la **classe** et le **compte** : les deux sont obligatoires (par exemple classe 401 et le compte du fournisseur).
3. **Cliquez sur « Consulte »** : le journal du compte s'affiche, écriture par écriture, avec le solde.

## À savoir
- Le **bouton de sélection** à côté du compte ouvre la liste des comptes (maintenance du plan comptable) : utilisez-le pour **trouver** un compte, pas pour le modifier.
- Pour un fournisseur sur tous les immeubles, préférez la **Balance fournisseurs** (guide dédié).

! Consultation uniquement : ne modifiez pas le plan comptable depuis cet écran.
MD],
['immopen-contrats', 'ImmOpen', '📑', 'Retrouver un contrat d\'immeuble', 'Ascenseur, entretien, assurance : retrouver un contrat, son fournisseur et ses documents.', '3 min', 'Débutant', <<<'MD'
## Rechercher
1. **Menu Syndic › Patrimoine › Contrats.**
2. **Filtrez** : **Immeuble**, **N° de contrat commence par**, **Libellé contient** (par exemple « ascenseur »), **Fournisseur** ou **Profession du fournisseur**.
3. Filtres Gestion / Compta / Assist / Groupe pour votre portefeuille ; cochez **« Supprimé »** seulement pour retrouver un ancien contrat.

## Consulter
- **Visualise** : ouvre le contrat en lecture.
- **Fournisseur / Info fournisseur** : coordonnées et informations du prestataire.
- **Imputation** : compte de charges et clé de répartition du contrat.
- **Document** : pièces jointes (contrat signé, avenants).

! Ajoute, Modifie, Supprime, Renumérote et Copie modifient les données : à réserver au gestionnaire en charge de l'immeuble.
MD],
['immopen-recherche-impayes', 'ImmOpen', '⏰', 'Rechercher les impayés', 'Lister les écritures restées impayées sur une période, par gestionnaire ou comptable.', '3 min', 'Débutant', <<<'MD'
## Lancer la recherche
1. **Menu Syndic › Suivi comptable › Recherche impayés.**
2. **Filtrez votre portefeuille** : Gestion, Compta ou Assist (laissez vide pour tout le cabinet).
3. **Période : « Entre le … et le … ».**
4. **Cliquez sur « Lance la recherche ».**

## Exploiter le résultat
- **Écriture complète** : ouvre le détail de l'écriture sélectionnée.
- **Liste** : imprime le résultat, utile pour préparer les relances.

> Astuce : les relances facturées sont suivies dans Honoraires (compte 400009).
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
