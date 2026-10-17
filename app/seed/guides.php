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
['chemini-demarrer', 'Chemini · Démarrer', '🧭', 'Chemini selon votre rôle', 'Le point de départ : ce que vous voyez dans Chemini selon votre rôle, et le bon guide pour chaque besoin.', '3 min', 'Débutant', <<<'MD'
## Ce que vous voyez selon votre rôle
- **Toute l'équipe :** les sections **Perso** (votre espace privé) et **Équipe** (la vie du cabinet).
- **Direction :** en plus, la section **Direction** (immeubles, honoraires, comptabilité, pilotage, signatures).
- **Famille :** la section **Famille** pour les personnes concernées.
- Les pictos en bas du menu (sécurité, nouveautés, installation, thème) sont pour tout le monde.

## Par où commencer
1. **Installez Chemini sur votre iPhone** pour l'ouvrir comme une application. [[Installer sur iPhone|/installer]]
2. **Faites le tour du menu et de la bulle.** [[Guide : se repérer|/guides/chemini-se-reperer]]
3. **Sécurisez votre connexion** (Face ID, appareils de confiance). [[Guide : connexion et sécurité|/guides/chemini-connexion-securite]]
4. **Découvrez votre espace Perso.** [[Guide : mon espace Perso|/guides/chemini-perso]]
5. **Découvrez la section Équipe.** [[Guide : la section Équipe|/guides/chemini-equipe]]

## Guides par rôle
- **Toute l'équipe :** [[Créer une tâche|/guides/chemini-taches]] [[Tâches IA|/guides/chemini-taches-ia]] [[Utiliser les guides|/guides/chemini-utiliser-guides]]
- **Direction :** [[Vue d'ensemble Direction|/guides/chemini-direction]] [[Honoraires|/guides/chemini-honoraires]] [[Pilotage et AG|/guides/chemini-pilotage]] [[Signatures e-mail|/guides/chemini-signatures]] [[Site Web|/guides/chemini-site-web]]
- **Président :** [[Mot du Président|/guides/chemini-mot-president]]
- **Famille :** [[Espace Famille|/guides/chemini-famille]]

> Astuce : la barre de recherche de la page Guides retrouve un guide par n'importe quel mot (« AG », « facture », « signature »…).
MD, 'tous'],
['chemini-se-reperer', 'Chemini · Démarrer', '🗺️', 'Se repérer : menu, onglets et bulle', 'Le menu latéral, les onglets en haut des pages, la bulle de création rapide et les réglages d\'affichage.', '3 min', 'Débutant', <<<'MD'
## Le menu
1. **Les sections se replient.** Touchez le titre d'une section (Perso, Direction, Équipe, Famille) pour l'ouvrir ou la fermer ; Chemini s'en souvient.
2. **Par défaut, seule Perso est ouverte**, ainsi que la section de la page affichée.
3. **Le chiffre à droite d'une section** indique combien de personnes y ont accès : touchez-le pour voir la liste.
4. **Sur iPhone**, le menu s'ouvre avec le bouton ☰ en haut à droite.

## Les onglets en haut des pages
- Certaines entrées du menu regroupent plusieurs pages : **Tâches** (Mes tâches, Tâches IA), **Notes et livres**, **Informations** (Informations, Accueil) et, pour la Direction, **Immeubles** (+ Planning des AG), **Honoraires** (+ Stratégies), **Pilotage** (+ Enquête).
- Passez d'une page à l'autre avec les onglets juste sous la barre du haut.

## La bulle en bas à droite
1. **Touchez la bulle** sur n'importe quelle page.
2. **« Tâche équipe »** : une tâche pour vous ou un collègue. [[Guide : créer une tâche|/guides/chemini-taches]]
3. **« Tâche IA »** : une consigne confiée à l'assistant. [[Guide : tâches IA|/guides/chemini-taches-ia]]

## Affichage
- **A− / A+** (en haut des pages, ou dans le menu sur iPhone) agrandit ou réduit le texte.
- **Les pictos en bas du menu :** Sécurité, Nouveautés (versioning), Installer sur iPhone, puis le thème clair, sombre ou automatique.

[[Installer sur iPhone|/installer]] [[Voir les nouveautés|/versioning]]
MD, 'tous'],
['chemini-connexion-securite', 'Chemini · Démarrer', '🔐', 'Connexion et sécurité', 'Se connecter, reconnaître ses appareils, utiliser Face ID et se déconnecter proprement.', '4 min', 'Débutant', <<<'MD'
## Se connecter
1. **Saisissez votre e-mail GID** sur la page de connexion.
2. **Choisissez votre méthode :** mot de passe puis code à 6 chiffres, Face ID (clé d'accès) ou lien de connexion reçu par e-mail.
3. **Lien par e-mail :** ouvrez-le puis touchez « Se connecter ». Il ne sert qu'une fois et expire rapidement.
4. **« Faire confiance à cet appareil »** évite de tout ressaisir pendant 90 jours, uniquement sur votre téléphone ou votre poste personnel.

! Ne cochez jamais « faire confiance » sur un ordinateur partagé, et ne transmettez jamais un lien de connexion.

## Gérer ses appareils
1. **Ouvrez la page Sécurité** (premier picto en bas du menu). [[Ouvrir Sécurité|/securite]]
2. **Activez Face ID** sur votre iPhone pour vous connecter sans mot de passe.
3. **Retirez un appareil** que vous n'utilisez plus ou que vous avez perdu.

## Se déconnecter
- Bouton à droite de votre nom, en bas du menu.

> Astuce : après plusieurs essais erronés, l'adresse est bloquée 30 minutes ; patientez ou demandez à la Direction.
MD, 'tous'],
['chemini-perso', 'Chemini · Perso', '🏠', 'Mon espace Perso', 'Votre tableau de bord, vos mails à traiter, le Mot du Président, vos tâches, vos notes et vos livres.', '4 min', 'Débutant', <<<'MD'
## Tableau de bord
1. **C'est la page d'accueil de Chemini.** Elle s'adapte à votre rôle et affiche vos compteurs du jour (tâches, mails, AG…).
2. **Chaque bloc mène au détail** : touchez-le pour ouvrir la page correspondante. [[Ouvrir mon tableau de bord|/]]

## Mails à traiter
1. **Les e-mails qui demandent une action** sont listés ici ; le chiffre rouge dans le menu indique combien il en reste.
2. **Cochez un mail une fois traité.** Vos points communs du tableau de bord apparaissent aussi sur cette page. [[Ouvrir Mails à traiter|/espace]]

## Mot du Président
- Chaque 1er du mois, l'actualité du mois écoulé et l'analyse de nos trois métiers. [[Lire le Mot du Président|/mot-du-president]]

## Tâches
1. **Mes tâches** : vos tâches perso, celles qui vous sont affectées et celles partagées avec vos sections. [[Ouvrir Mes tâches|/mes-taches]]
2. **Tâches IA** : le suivi des consignes confiées à l'assistant. [[Ouvrir Tâches IA|/assistant]]

## Notes et livres
- **Bloc-notes** : vos notes privées, visibles par vous seul. [[Ouvrir le bloc-notes|/bloc-notes]]
- **Mes livres** : votre bibliothèque EPUB ou PDF, lisible sur ordinateur et iPhone. [[Ouvrir Mes livres|/mes-livres]]

> Astuce : tout ce qui est dans Perso est privé ; rien n'est visible par vos collègues sauf une tâche que vous choisissez de partager.
MD, 'tous'],
['chemini-taches-ia', 'Chemini · Perso', '🤖', 'Confier une tâche à l\'IA', 'Une recherche, un courrier, un tableau : décrivez le besoin, l\'assistant s\'en charge et vous suivez l\'avancement.', '2 min', 'Débutant', <<<'MD'
## Confier la tâche
1. **Touchez la bulle** en bas à droite puis **« Tâche IA »**.
2. **Décrivez le résultat attendu** : le sujet, le format (courrier, tableau, synthèse) et l'échéance éventuelle.
3. **Envoyez.** Les consignes sont vérifiées et traitées toutes les heures.

## Suivre le résultat
1. **Ouvrez Tâches IA** pour voir où en est chaque consigne. [[Ouvrir Tâches IA|/assistant]]
2. **Le résultat** est déposé avec la consigne ; les tâches terminées apparaissent aussi dans les nouveautés. [[Voir les nouveautés|/versioning]]

! N'indiquez jamais de mot de passe ni de coordonnées bancaires dans une consigne.
MD, 'tous'],
['chemini-equipe', 'Chemini · Équipe', '👥', 'La section Équipe', 'Le tableau de bord de l\'équipe, les informations générales, les collaborateurs, l\'accueil, les guides et le questionnaire.', '4 min', 'Débutant', <<<'MD'
## Tableau de bord Équipe
- **Les prochaines AG, la veille juridique et les dernières actualités** du secteur. [[Ouvrir le tableau de bord Équipe|/tableau-de-bord]]

## Informations et Accueil
1. **Informations** : les tâches partagées, le bloc-notes commun et les outils de l'équipe. [[Ouvrir Informations|/gid]]
2. **Accueil** : la société, l'organisation, les équipes et les bonnes pratiques, idéal pour un nouvel arrivant. [[Ouvrir Accueil|/accueil]]

## Collaborateurs
1. **L'organigramme** présente chaque personne avec son nombre de tâches en cours. [[Ouvrir Collaborateurs|/organigramme]]
2. **La fiche d'une personne** donne sa ligne directe, son raccourci (4 derniers chiffres) et un lien e-mail qui ouvre Outlook.

## Guides et questionnaire
- **Guides** : les procédures ImmOpen et Chemini, à cocher étape par étape. [[Tous les guides|/guides]]
- **Questionnaire** : 10 minutes pour dire vos tâches récurrentes et ce que vous aimeriez automatiser. [[Répondre au questionnaire|/enquete]]
MD, 'tous'],
['chemini-utiliser-guides', 'Chemini · Équipe', '📘', 'Utiliser et enrichir les guides', 'Suivre un guide en cochant les étapes ; pour la Direction, créer et modifier les guides.', '3 min', 'Débutant', <<<'MD'
## Suivre un guide
1. **Ouvrez un guide** depuis la page Guides ou la recherche. [[Tous les guides|/guides]]
2. **Cochez chaque étape** au fur et à mesure : la barre de progression avance et votre avancement est gardé sur cet appareil.
3. **« Recommencer »** remet les cases à zéro.
4. **Une étape a changé ?** Signalez-le à la Direction avec la bulle « Tâche équipe ».

## Créer ou modifier (Direction)
1. **« Nouveau guide »** en haut de la page Guides, ou **« Modifier le guide »** sur un guide.
2. **Choisissez qui le voit** : toute l'équipe, Direction, Président ou Famille.
3. **Mise en forme :** « ## » pour une partie, « 1. » pour une étape à cocher, « - » pour une liste, « > Astuce : » et « ! » pour un avertissement.
4. **Bouton vers une page :** écrivez `[[Libellé|/adresse]]`, par exemple `[[Ouvrir Mes tâches|/mes-taches]]`.
MD, 'tous'],
['chemini-direction', 'Chemini · Direction', '🏛️', 'Vue d\'ensemble Direction', 'Les outils réservés à la Direction et le bon réflexe pour chacun.', '4 min', 'Débutant', <<<'MD'
## Tableau de bord Direction
- **Les indicateurs du cabinet** et les alertes du jour. [[Ouvrir le tableau de bord Direction|/direction]]

## Immeubles et Planning des AG
1. **Immeubles** : la fiche de chaque copropriété (lots, exercice, plage d'AG, contacts). [[Ouvrir Immeubles|/immeubles]]
2. **Planning des AG** : AG à planifier, convocations en retard, mandats à renouveler, par gestionnaire. [[Ouvrir le planning|/planning-ag]]

## Honoraires et Stratégies
1. **Honoraires** : le suivi des honoraires par immeuble, voté et à voter. [[Guide Honoraires|/guides/chemini-honoraires]]
2. **Stratégies** : les immeubles non rentables ou fragiles et les leviers de marge. [[Ouvrir Stratégies|/strategies]]

## Comptabilité, Pilotage et Enquête
1. **Comptabilité** : le contrôle des factures et le suivi par comptable. [[Ouvrir Comptabilité|/comptabilite]]
2. **Pilotage** : finances, AG, registres et conformité. [[Guide Pilotage|/guides/chemini-pilotage]]
3. **Enquête** : les réponses de l'équipe au questionnaire. [[Voir les résultats|/enquete/resultats]]

## Signatures
- **Le visuel du bas des signatures e-mail**, programmé selon les périodes. [[Guide Signatures|/guides/chemini-signatures]]

! Ces données sont confidentielles : ne les copiez pas dans l'espace Équipe.
MD, 'direction'],
['chemini-honoraires', 'Chemini · Direction', '💶', 'Honoraires : suivre et mettre à jour', 'Lire le tableau des honoraires par immeuble, filtrer, ouvrir une fiche et exporter, sur ordinateur et sur iPhone.', '5 min', 'Intermédiaire', <<<'MD'
## Ouvrir le tableau
1. **Menu Direction › Honoraires**, puis l'onglet **Immeubles** du document. [[Ouvrir Honoraires|/honoraires]]
2. **Choisissez une vue** : Essentiel, AG et prestations, Budgets, Travaux votés ou Tout. Votre choix est mémorisé.
3. **Recherchez** un immeuble par nom ou numéro ; filtrez par portefeuille et par statut (actifs, perdus, AG tenues).
4. **Les filtres rapides** (pastilles colorées) isolent les alertes : voté sous le proposé, PV en retard…

## Ouvrir la fiche d'un immeuble
1. **Touchez « › »** au bout de la ligne (ou la carte sur iPhone) : la fiche s'ouvre avec les montants et l'évolution.
2. **Les champs de la fiche** se modifient directement ; chaque modification est enregistrée pour toute l'équipe.
3. **L'historique des changements** est dans Modifications. [[Voir les modifications|/honoraires/modifications]]

## Sur iPhone
- **La recherche et la liste** s'affichent d'abord.
- **Premier picto** (à gauche de A−/A+) : titre, menu Fichier et onglets. **Second picto** : filtres et boutons ; une pastille rouge indique les filtres actifs.

## Exporter
- **Menu Fichier** : export de l'onglet en CSV pour Excel, sauvegarde ou impression.

> Astuce : la modification des montants est réservée à Mehdi Chemini ; les autres membres de la Direction consultent.
MD, 'direction'],
['chemini-pilotage', 'Chemini · Direction', '🧭', 'Pilotage et préparation des AG', 'Le tableau de pilotage, la préparation des AG et les registres du cabinet.', '4 min', 'Intermédiaire', <<<'MD'
## Le tableau de pilotage
1. **Menu Direction › Pilotage.** Chaque bloc (finances et équipes, assemblées générales, registres) mène au détail. [[Ouvrir Pilotage|/pilotage]]
2. **Renouvellements de mandat, charge des gestionnaires, prévision d'encaissement** : un bloc chacun.

## Préparer les AG
1. **Pilotage › Préparation des AG** liste les AG à venir avec leur état. [[Ouvrir la préparation des AG|/pilotage/ag]]
2. **La fiche d'une AG** regroupe les points à préparer ; **« Imprimer la fiche »** la sort sur papier.
3. **Le calendrier des AG** peut s'abonner sur votre iPhone (adresse donnée sur la page).
4. **Planning des AG** (onglet d'Immeubles) : la vue par gestionnaire tirée d'ImmOpen. [[Ouvrir le planning|/planning-ag]]

## Enquête
- **Les réponses au questionnaire** de l'équipe, avec impression. [[Voir l'enquête|/enquete/resultats]]
MD, 'direction'],
['chemini-signatures', 'Chemini · Direction', '✉️', 'Signatures e-mail : programmer les visuels', 'Ajouter un visuel, choisir quand il s\'affiche et vérifier qu\'Exclaimer est bien relié.', '5 min', 'Intermédiaire', <<<'MD'
## Ajouter un visuel
1. **Menu Direction › Signatures**, puis « Ajouter un visuel ». [[Ouvrir Signatures|/signatures]]
2. **Image au format 600 × 200** (PNG ou JPEG) ; elle est recadrée au centre si besoin.
3. **Choisissez le mode :** par défaut, période (dates de début et de fin), rotation (visuel du mois) ou brouillon.
4. **Le lien au clic** mène toujours à www.gidsas.fr.

## Comprendre les priorités
- **Un brouillon activé** passe avant tout ; puis **les périodes** (fêtes, vœux, été) ; puis **la rotation** du mois ; sinon **le bandeau par défaut**.
- **Il y a toujours un bandeau par défaut** : il ne peut pas être supprimé.
- **Le calendrier des 12 prochains mois** montre quel visuel partira chaque mois.

## Créer le visuel avec Gemini
1. **Remplissez les champs du message** (surlignés tant qu'ils sont vides).
2. **Copiez le prompt** et collez-le dans Gemini, puis importez l'image obtenue.

## Vérifier Exclaimer
- **L'indicateur « relié »** passe au vert dès qu'Exclaimer ou une messagerie lit le bandeau.

! Changer les réglages d'Exclaimer se fait dans Exclaimer lui-même ; Chemini ne fournit que l'image du moment.
MD, 'direction'],
['chemini-site-web', 'Chemini · Direction', '🌐', 'Site Web : statistiques et annonces', 'Suivre le site gidsas.fr, publier et gérer les annonces immobilières depuis Chemini ou le téléphone, sans ouvrir WordPress.', '5 min', 'Débutant', <<<'MD'
## Relier le site (une seule fois, administrateur)
1. **Dans WordPress, Comptes › Profil**, section « Mots de passe d'application » : nommez-le « Chemini » et cliquez sur Ajouter. [[Ouvrir la page Connexion|/site-web/connexion]]
2. **Copiez la clé** (24 caractères) et collez-la dans Site Web › Connexion avec votre identifiant WordPress, puis « Tester et relier ».

! Cette clé se révoque à tout moment depuis le profil WordPress ; elle est chiffrée dans Chemini et n'est jamais réaffichée.

## Statistiques
- **Annonces en ligne, à vendre, sous promesse, vendues**, par secteur et par mois de publication. [[Voir les statistiques|/site-web]]
- **Référencement** : pages sans description Google, pages exclues des moteurs.
- **Santé technique** : HTTPS, mises à jour, cache, extensions.
- **Actualiser** relit les données du site ; sinon elles se rafraîchissent toutes les 10 minutes.

## Gérer les annonces
1. **Site Web › Annonces** : recherchez par rue ou arrondissement, filtrez par statut. [[Ouvrir les annonces|/site-web/annonces]]
2. **Changez le statut** dans la liste déroulante (En vente, Sous promesse, Vendu…) : le site est mis à jour aussitôt.
3. **« Retirer du site »** repasse l'annonce en brouillon ; **« Mettre en ligne »** la republie.
4. **Texte, photos et prix** : « Modifier dans WordPress » ouvre l'annonce dans WordPress.

## Publier une annonce depuis le téléphone
1. **Site Web › Nouvelle annonce** (ou le bouton « + Nouvelle annonce »). [[Nouvelle annonce|/site-web/annonces/nouvelle]]
2. **Choisissez Vente ou Location**, le type de bien, le secteur, l'adresse et le titre.
3. **Prenez les photos** directement avec le téléphone : la première est la photo principale.
4. **Remplissez les champs marqués d'un astérisque** : ils correspondent aux mentions obligatoires (prix et honoraires ou loyer, charges, dépôt de garantie, encadrement des loyers, copropriété, DPE et dépenses d'énergie).
5. **Vérifiez le récapitulatif** « Mentions ajoutées à l'annonce », puis « Envoyer l'annonce ». Décochez « Publier tout de suite » pour une relecture en brouillon.

> Astuce : la mention Géorisques et le calcul du prix hors honoraires sont ajoutés automatiquement.

## Pages et SEO
- **Titre et description affichés dans Google** pour chaque page, avec la longueur idéale (120 à 160 caractères). [[Voir les pages|/site-web/pages]]
MD, 'annonceurs'],
['chemini-mot-president', 'Chemini · Président', '🖋️', 'Mot du Président : relire, publier, partager', 'Le cycle mensuel du Mot du Président, de la rédaction automatique au partage par lien.', '3 min', 'Débutant', <<<'MD'
## Chaque mois
1. **Le 25, un brouillon est rédigé** d'après les actualités du mois ; une alerte apparaît sur votre tableau de bord. [[Ouvrir le Mot du Président|/mot-du-president]]
2. **Relisez et modifiez** avec « Modifier » : l'article garde sa mise en page, la barre d'outils propose intertitre, paragraphe, gras et liste.
3. **« Valider la publication »** : il paraît le 1er du mois pour toute l'équipe. Sans validation, il reste en brouillon.
4. **« Repasser en brouillon »** annule la validation si besoin.

## Partager par lien
1. **Encadré « Partager par lien »** sur la page du mot : « Créer le lien » le copie aussitôt.
2. **Le lien est non répertorié**, en lecture seule, sans compte, et non indexé par les moteurs de recherche.
3. **« Désactiver le lien »** le rend immédiatement inutilisable.

! Un lien créé sur un brouillon montre la version actuelle, avant publication.
MD, 'president'],
['chemini-famille', 'Chemini · Famille', '🧳', 'Espace Famille et voyages', 'Le tableau de bord Famille et les guides de voyage préparés par Claude.', '2 min', 'Débutant', <<<'MD'
## L'espace Famille
1. **Menu Famille › Tableau de bord** : les voyages, les tâches partagées et le bloc-notes commun. [[Ouvrir l'espace Famille|/famille]]

## Les voyages
1. **Menu Famille › Voyages** : chaque guide donne la meilleure période, les vols Air France ou Transavia, la météo, les préparatifs et le programme jour par jour. [[Ouvrir Voyages|/voyages]]
2. **Partager un voyage** : depuis sa fiche, créez un lien en lecture seule ; il se désactive à tout moment.
MD, 'famille'],
['chemini-taches', 'Chemini · Perso', '🤝', 'Créer une tâche pour vous ou un collègue', 'La bulle en bas à droite : une tâche en trois touches, depuis l\'ordinateur ou l\'iPhone.', '1 min', 'Débutant', <<<'MD'
## Créer la tâche
1. **Touchez la bulle** en bas à droite de n'importe quelle page.
2. **Choisissez « Tâche équipe ».**
3. **Touchez « Pour qui ? »** et choisissez la personne dans la liste (Moi ou un collègue, avec recherche), écrivez la tâche, choisissez l'échéance.
4. **Touchez « Affecter ».** La tâche apparaît aussitôt dans ses tâches.

## Suivre et terminer
- **Mes tâches** (Perso) liste vos tâches ; cochez-les une fois faites, la date est enregistrée.
- **Collaborateurs** montre le nombre de tâches en cours de chacun.

> Astuce : « Tâche IA » dans la même bulle confie une consigne à l'assistant (recherche, courrier, tableau).
MD],
];
