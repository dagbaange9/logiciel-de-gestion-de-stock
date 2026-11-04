# Plan d’implémentation - MOD-01 Gestion des utilisateurs et des accès

> Fichier cible : `docs/modules/gestion-utilisateurs-acces.md`, versionné avec le projet.
> Source : document d’architecture « Conception et développement d’un logiciel de gestion de stock », v1.0, novembre 2026 (§3, §5.2.1, §6, §7, §8).

## 1. Identification

| Champ | Valeur |
|---|---|
| Module | MOD-01 - Gestion des utilisateurs et des accès |
| Version du plan | 1.1 |
| Auteur | À compléter |
| Date | 4 novembre 2026 |
| Statut | Étapes 1 à 4 implémentées techniquement |
| Validateur | À compléter (enseignant) |

Légende des statuts d’étape : à faire / en cours / bloquée / à valider / validée. « Codé », « testé » et « validé » sont des états différents.

## 2. Contexte, problème et objectifs

**Contexte.** Application web de gestion de stock pour un magasin physique, installée sur un serveur local et utilisée depuis des ordinateurs, téléphones et tablettes du réseau local. Trois rôles humains : Administrateur, Magasinier, Vendeur.

**Problème.** Sans comptes individuels et sans contrôle des droits, on ne peut ni savoir qui a vendu ou reçu un produit, ni empêcher un vendeur d’accéder aux fonctions du gérant.

**Objectifs.**
- Chaque personne se connecte avec un compte individuel et ne voit que ce que son rôle permet.
- Les vendeurs et magasiniers s’inscrivent seuls ; l’administrateur valide ou refuse leur compte.
- Un compte désactivé perd son accès immédiatement.
- Les événements sensibles (connexions, changements de rôle ou de statut) sont tracés.

**Parcours principal.** Inscription → vérification de l’e-mail → attente de validation → approbation par l’administrateur → connexion.

## 3. Périmètre

**Inclus.**
- Inscription autonome (vendeur, magasinier) et vérification de l’adresse e-mail.
- Validation, refus, activation et désactivation des comptes par l’administrateur.
- Connexion, déconnexion, expiration des sessions, limitation des tentatives.
- Réinitialisation du mot de passe (lien temporaire par e-mail ; assistance de l’administrateur).
- Modification du profil, dont le changement d’adresse e-mail vérifié.
- Gestion des rôles et affichage du tableau de bord selon le rôle (navigation uniquement).
- Suppression logique des comptes ayant un historique.

**Exclus.**
- Contenu métier du tableau de bord (MOD-11), centre de notifications (MOD-09), consultation du journal d’audit (MOD-13) : seuls les événements émis par ce module sont inclus ici.
- Authentification à deux facteurs, connexion par réseau social, multi-boutique, création de nouveaux rôles personnalisés (les trois rôles sont fixes).
- Gestion des clients.

## 4. Existant, stack et contraintes

| Élément | État | Remarque |
|---|---|---|
| PHP, Laravel, Blade, Tailwind CSS, JavaScript | Vérifié dans le dépôt | PHP `8.4.21` ; Laravel `13.39.0` ; Blade, Tailwind CSS et JavaScript présents. |
| MySQL | Annoncé dans le document et configuré localement | Version du serveur à relever ; la configuration locale actuelle utilise SQLite dans `.env.example`. |
| Node.js / npm | Vérifié dans le dépôt | Node.js `24.18.0` ; npm `11.16.0` (relevés locaux). |
| Tests : PHPUnit ou Pest | PHPUnit vérifié, Pest non requis | PHPUnit `12.5.12` est installé via `phpunit/phpunit` ; PHPUnit est retenu. |
| Authentification, inscription, sessions et profil | Implémenté | Contrôleurs, middleware, formulaires, vues et routes présents jusqu’à l’étape 4. |
| Tables `users`, `sessions`, `password_reset_tokens` | Présent | Les migrations MOD-01 ajoutent les champs métier, rôles et suppression logique aux tables existantes. |
| Service d’e-mail (SMTP) | À configurer pour l’environnement réel | Hors ligne, les parcours restent dépendants d’un lien signé ; les tests utilisent les capacités de test Laravel. |
| MOD-09 (notifications) et MOD-13 (journal) | Hors étape 5 | MOD-01 émet des événements d’audit applicatifs sans consultation ni stockage centralisé confirmé dans cette étape. |

**Contraintes.** Monolithe Laravel modulaire ; réseau local ; HTTPS recommandé ; OWASP ASVS niveau 2 visé ; validation côté serveur ; protection CSRF et XSS ; requêtes via Eloquent ou Query Builder.

## 5. Règles métier et autorisations

Chaque règle est testable. Les étapes les référencent par leur numéro.

| N° | Règle |
|---|---|
| R01 | L’inscription autonome ne crée que des comptes Vendeur ou Magasinier. Aucun compte Administrateur ne peut être créé par ce formulaire. |
| R02 | L’administrateur initial est créé à l’installation (commande ou seeder), pas par inscription. |
| R03 | Un compte a un seul statut parmi : `email_non_verifie`, `en_attente_validation`, `actif`, `refuse`, `desactive`. Seul `actif` donne accès aux fonctions protégées. |
| R04 | Seul l’administrateur approuve, refuse, active, désactive un compte et modifie un rôle. |
| R05 | La désactivation d’un compte invalide immédiatement toutes ses sessions actives. |
| R06 | Une modification de rôle prend effet à la prochaine connexion de l’utilisateur (voir Q-03). |
| R07 | Les mots de passe sont hachés. Ils n’apparaissent jamais dans une réponse, un log ou le journal d’audit. |
| R08 | Le lien de réinitialisation est temporaire, à usage unique, et invalidé après usage. La seconde méthode est l’assistance de l’administrateur. |
| R09 | Une nouvelle adresse e-mail doit être vérifiée avant de remplacer l’ancienne ; jusque-là, l’ancienne reste valable pour la connexion. |
| R10 | Un compte ayant des opérations historiques est supprimé logiquement, jamais physiquement. |
| R11 | Les tentatives de connexion répétées sont limitées (seuils : voir Q-05). |
| R12 | Les permissions sont vérifiées côté serveur à chaque requête protégée ; masquer un bouton ne suffit pas. |
| R13 | Les connexions (réussies et échouées), approbations, refus, changements de rôle et de statut produisent un événement d’audit : utilisateur, action, date, objet. Pas de mot de passe ; pas d’ancienne adresse e-mail. |
| R14 | L’adresse e-mail est unique, sans tenir compte de la casse. |
| R15 | Les messages de connexion et de réinitialisation ne révèlent pas si une adresse existe. |
| R16 | *(Proposition à valider)* Le dernier administrateur actif ne peut être ni désactivé, ni rétrogradé, ni supprimé. |

**Matrice des accès (à confirmer à l’étape 0).**

| Action | Visiteur | Vendeur | Magasinier | Administrateur |
|---|---|---|---|---|
| S’inscrire, mot de passe oublié | Oui | - | - | - |
| Se connecter | Oui | Oui | Oui | Oui |
| Modifier son propre profil | - | Oui | Oui | Oui |
| Voir la liste des utilisateurs | Non | Non | Non | Oui |
| Approuver / refuser un compte | Non | Non | Non | Oui |
| Activer / désactiver un compte | Non | Non | Non | Oui |
| Changer un rôle | Non | Non | Non | Oui |
| Lancer l’assistance de réinitialisation | Non | Non | Non | Oui |

## 6. Données, API et interface

**Données proposées, à rapprocher de l’existant.**
- `roles` : `id`, `name` unique (`administrateur`, `magasinier`, `vendeur`), `description`.
- `users` : `id`, `name` obligatoire, `email` unique obligatoire, `phone` obligatoire, `email_verified_at`, `password`, `status`, `role_id` (clé étrangère restrictive), `pending_email` nullable, `deactivated_at` nullable, `deleted_at` (suppression logique), horodatages.
- `password_reset_tokens` : table technique (jeton haché, expiration).
- `sessions` : stockage en base avec `user_id`, pour pouvoir invalider les sessions d’un compte (R05). *À confirmer à l’étape 0.*

Index : `users.email`, `users.status`, `users.role_id`.

**Routes web (application Blade, pas d’API JSON).**

| Méthode et chemin | Accès | Résultat attendu |
|---|---|---|
| `GET/POST /inscription` | Visiteur | Création en `email_non_verifie` ; erreurs de validation affichées |
| `GET /email/verification/{id}/{hash}` | Lien signé | Passage à `en_attente_validation` |
| `GET/POST /connexion` | Visiteur | Session ouverte si statut `actif`, sinon message précis |
| `POST /deconnexion` | Connecté | Session détruite |
| `GET/POST /mot-de-passe/oublie`, `/mot-de-passe/reinitialiser` | Visiteur | Message identique que l’adresse existe ou non (R15) |
| `GET/PUT /profil` | Connecté | Mise à jour du profil ; changement d’e-mail en attente de vérification |
| `GET /admin/utilisateurs` | Administrateur | Liste filtrable par statut et rôle |
| `POST /admin/utilisateurs/{id}/approuver`, `/refuser`, `/activer`, `/desactiver` | Administrateur | Changement de statut + audit |
| `PUT /admin/utilisateurs/{id}/role` | Administrateur | Changement de rôle + audit |
| `POST /admin/utilisateurs/{id}/reinitialiser-mot-de-passe` | Administrateur | Assistance de récupération |

Codes à gérer : 302 vers la connexion (session absente), 403 (rôle insuffisant), 419 (jeton CSRF expiré), 422 ou retour avec erreurs (saisie invalide), 429 (trop de tentatives).

**Écrans et états obligatoires.**
- Écrans : inscription, connexion, vérification e-mail, mot de passe oublié et réinitialisation, profil, liste et détail des utilisateurs (administrateur), tableau de bord (accueil selon le rôle).
- États : formulaire en cours d’envoi, erreur de champ, message de succès, compte en attente, compte refusé ou désactivé, liste vide, session expirée.
- La saisie est conservée en cas d’erreur (hors mot de passe). Interface responsive ; navigation clavier ; libellés et messages accessibles (WCAG 2.2 AA visé).
- Le menu affiche uniquement les entrées autorisées pour le rôle, mais le serveur reste l’autorité (R12).

## 7. Qualité et risques

| Risque | Parade | Test associé |
|---|---|---|
| Énumération de comptes | R15 | T-ENUM |
| Force brute sur la connexion | R11 | T-THROTTLE |
| Session d’un compte désactivé encore valide | R05, sessions en base, contrôle du statut à chaque requête | T-SESSION |
| Élévation de privilège (vendeur qui appelle une route admin) | R12 | T-ACCES |
| Dernier administrateur verrouillé hors du système | R16 | T-LASTADMIN |
| E-mail indisponible (coupure Internet) | Voir Q-02 | T-HORSLIGNE |
| Fuite de données personnelles dans les logs | R07, R13 | Relecture des logs |

Documentation : routes, matrice des accès et procédure de création de l’administrateur initial inscrites dans le dépôt.

## 8. Questions ouvertes et décisions

**Questions ouvertes** (une question touchant aux droits ou aux données bloque l’étape concernée).

| N° | Question | Impact | Décideur | Bloque |
|---|---|---|---|---|
| Q-01 | Le rôle est-il choisi par la personne à l’inscription, ou attribué par l’administrateur à l’approbation ? | R01, formulaire, droits | Enseignant / porteur du projet | Étape 2 |
| Q-02 | Que faire sans Internet ? La vérification d’e-mail est impossible hors ligne : l’administrateur peut-il valider l’e-mail manuellement ? | Parcours d’inscription, R03 | Enseignant | Étape 2 |
| Q-03 | « Le rôle change à la prochaine connexion » (document) est-il compatible avec « permissions vérifiées à chaque requête » ? Conserver le rôle dans la session jusqu’à la reconnexion, ou invalider la session au changement ? | R06, R12 | Enseignant | Étape 4 |
| Q-04 | Peut-il y avoir plusieurs administrateurs ? L’administrateur peut-il créer directement un compte (sans inscription) pour son équipe ? | R01, R16, écrans | Enseignant / gérant | Étape 4 |
| Q-05 | Politique de mot de passe (longueur minimale) et seuils de limitation des tentatives ? | R07, R11 | Enseignant | Étape 3 |
| Q-06 | Durée de validité du lien de réinitialisation, durée d’expiration des sessions ? | R08 | Enseignant | Étape 3 |
| Q-07 | Quelles données minimales à l’inscription (nom, e-mail, téléphone) ? | Table `users` | Enseignant / porteur du projet | Étape 1 |

**Décisions fixées** (reprises du document d’architecture).

| N° | Décision | Date |
|---|---|---|
| D-01 | Inscription autonome pour vendeur et magasinier ; administrateur initial créé à l’installation | 04/11/2026 (document v1.0) |
| D-02 | Suppression logique des comptes avec historique | 04/11/2026 (document v1.0) |
| D-03 | *(Proposition à valider)* MOD-09 et MOD-13 n’existent pas encore : ce module émet des événements Laravel (`UserLoggedIn`, `UserRoleChanged`, etc.) ; en attendant, un écouteur minimal écrit dans `audit_logs` ou dans un canal de log dédié, remplacé plus tard | À valider |
| D-04 | Les notifications par e-mail ne sont pas disponibles hors connexion Internet ; les opérations locales continuent de fonctionner. Aucun parcours manuel de vérification n’est ajouté sans décision distincte. | 04/11/2026 (document v1.0) |
| D-05 | Une modification de rôle prend effet à la prochaine connexion. Le rôle utilisé pendant une session reste donc celui qui était actif lors de l’ouverture de cette session, sans modifier les contrôles serveur par requête. | 04/11/2026 (document v1.0) |

**Décisions de l’étape 0 non renseignées par le document d’architecture.**

| N° | Décision | Motif |
|---|---|---|
| D-06 | Q-05 reste non définie : la longueur minimale du mot de passe et le seuil des tentatives de connexion ne sont pas indiqués dans le document source. | Ne pas inventer une politique de sécurité à valeur chiffrée. |
| D-07 | Q-06 reste non définie : les durées de validité du lien de réinitialisation et d’expiration des sessions ne sont pas indiquées dans le document source. | Ne pas inventer les durées. |
| D-08 | Q-07 reste résolue par une décision projet : l’inscription exige le nom, l’adresse e-mail et le numéro de téléphone. | Cette décision couvre les données minimales nécessaires au socle utilisateur et a été validée pour l’étape 0. |
| D-09 | Q-01 reste bloquante pour le détail du formulaire d’inscription : le document impose une inscription autonome pour les vendeurs et magasiniers, mais ne précise pas si le rôle est choisi directement ou attribué lors de l’approbation. | R01 impose les rôles autorisés, pas le mécanisme d’attribution. |
| D-10 | Q-02 reste bloquante pour le parcours hors ligne : le document confirme que les e-mails sont indisponibles sans Internet, mais ne fournit pas de procédure alternative de validation manuelle. | Ne pas ajouter une validation d’e-mail hors ligne non spécifiée. |
| D-11 | Q-04 reste bloquante pour la gestion administrative avancée : le document confirme l’administrateur initial mais ne précise pas le nombre d’administrateurs ni la création directe de comptes. | Ne pas inventer ces capacités. |
| D-12 | L’inscription collecte obligatoirement les champs `name`, `email` et `phone`. Le format du téléphone reste soumis à une règle de validation Laravel à définir lors de l’étape 1. | Décision retenue pour Q-07. |
| D-13 | Q-01 est résolue: le candidat ne choisit pas son rôle à l’inscription; l’administrateur attribue le rôle Vendeur ou Magasinier lors de l’approbation. | Décision retenue le 04/11/2026. |
| D-14 | Q-02 est résolue: aucune validation manuelle hors ligne n’est ajoutée. La vérification par e-mail reste disponible lorsque le service de messagerie fonctionne. | Décision retenue le 04/11/2026. |
| D-15 | Q-05 est résolue pour les parcours implémentés : la longueur minimale du mot de passe est fixée à 8 caractères et la connexion accepte 5 tentatives par minute avant limitation. | Décision retenue le 04/11/2026. |
| D-16 | Q-06 est résolue pour les parcours implémentés : le lien de réinitialisation expire après 30 minutes et la session expire après 60 minutes d’inactivité. | Décision retenue le 04/11/2026. |

---

## Étape 0 - Vérifier et valider le cadrage

Statut : à valider.

### Objectif et prérequis
Rendre le plan applicable au projet réel. Prérequis : accès au dépôt et au document d’architecture.

### Travaux à réaliser et fichiers concernés
- Relever les versions de PHP, Laravel, MySQL, Node dans `composer.json`, `package.json`, `.env.example`.
- Vérifier ce qui existe : starter kit d’authentification, tables, middleware, vues. Classer chaque élément en « déjà présent », « à créer », « à confirmer ».
- Appliquer les décisions documentées par le document d’architecture, et maintenir les décisions absentes du document comme bloquantes pour l’étape concernée.
- Vérifier les informations restantes en référence unique au document d’architecture, sans déduire des champs ou comportements non documentés.
- Confirmer la matrice des accès et les routes de la section 6.

### Règles applicables et points de vigilance
R01 à R16. Ne pas considérer un élément comme existant sans l’avoir vu dans le dépôt.

### Livrables
Ce fichier mis à jour (versions, tableau de l’existant, décisions), matrice des accès validée.

### Tests et cas limites
Faire jouer sur papier : une inscription nominale jusqu’à la connexion ; une connexion refusée (compte en attente) ; une désactivation avec session ouverte.

### Critères de sortie
- [x] Versions disponibles relevées dans la section 4.
- [x] Existant du dépôt vérifié sans supposer l’existence de code d’authentification.
- [x] Q-07 tranchée pour l’étape 1 : le nom, l’adresse e-mail et le téléphone sont obligatoires.
- [ ] Q-01, Q-02, Q-04, Q-05 et Q-06 tranchées lorsqu’elles deviennent nécessaires.
- [ ] Matrice des accès relue et validée.

### Bilan d’exécution
Le document d’architecture confirme les rôles fixes, les parcours d’inscription et de connexion, la validation administrative, la désactivation immédiate, le changement de rôle à la prochaine connexion et la continuité locale sans Internet.

Le dépôt est un squelette Laravel sans authentification métier. Le modèle et la migration `users`, ainsi que les migrations de sessions et de réinitialisation, sont présents uniquement dans la structure standard Laravel. Aucun champ, rôle, statut ou route MOD-01 n’existe encore.

Décisions documentaires retenues : D-01 à D-05 et extensions D-06 à D-12. Pour Q-07, l’inscription exige `name`, `email` et `phone`. Les autres informations absentes du document source restent bloquantes ; elles ne sont pas remplacées par des hypothèses.

### Validation
Validateur, date, décision : *à compléter.*

---

## Étape 1 - Données, rôles et administrateur initial

Statut : à valider.

### Objectif et prérequis
Disposer d’un socle de données intègre. Prérequis : étape 0 validée.

### Travaux à réaliser et fichiers concernés
Migrations `roles` et `users` (ou adaptation de l’existant), modèles `User` et `Role`, seeder des trois rôles, commande ou seeder de création de l’administrateur initial, factories de test. Emplacements habituels : `database/migrations/`, `app/Models/`, `database/seeders/`, `database/factories/`, `tests/Feature/`. Adapter à la structure constatée.

### Règles applicables et points de vigilance
- R02 : l’administrateur initial n’est jamais créé par un formulaire public ; son mot de passe n’est pas écrit en dur dans le code.
- R03 : statut limité aux cinq valeurs.
- R10, R14 : clés étrangères restrictives, e-mail unique, suppression logique.

### Livrables
Schéma migrable (`migrate:fresh --seed` réussit), modèles, factories.

### Tests et cas limites
- Nominal : les trois rôles existent après le seeder ; l’administrateur initial a le statut `actif`.
- Refus : deux comptes avec la même adresse (casse différente) sont refusés.
- Limite : suppression d’un rôle utilisé par des utilisateurs refusée ; relancer le seeder ne crée pas de doublon.

### Critères de sortie
- [ ] Migrations exécutées sur la base MySQL de test. *Limite actuelle : validation effectuée avec SQLite en environnement PHPUnit; aucun serveur MySQL de test n’a été vérifié.*
- [x] Tests de l’étape réussis.
- [x] Aucun mot de passe en clair dans le dépôt.

### Bilan d’exécution
Fichiers modifiés :
- `database/migrations/2026_11_04_022311_create_roles_table.php` : création de la table `roles`.
- `database/migrations/2026_11_04_022317_add_mod01_fields_to_users_table.php` : ajout des coordonnées, du statut, du rôle, de l’e-mail en attente, de la désactivation et de la suppression logique.
- `app/Models/Role.php` : modèle des rôles fixes et relation vers les utilisateurs.
- `app/Models/User.php` : règles de statut, normalisation de l’e-mail, relation de rôle et suppression logique.
- `database/seeders/RoleSeeder.php` et `database/seeders/DatabaseSeeder.php` : création réutilisable des trois rôles sans administrateur codé.
- `app/Console/Commands/CreateInitialAdministrator.php` : commande d’installation de l’administrateur initial, sans mot de passe en dur.
- `database/factories/UserFactory.php` : champs du socle utilisateur pour les tests.
- `tests/Feature/StepOneDataTest.php` : six tests de l’étape 1.
- `.env.example` : ajout du paramètre vide `INITIAL_ADMIN_PASSWORD` destiné à la commande initiale.
- `composer.json` et `composer.lock` : ajout de Laravel Boost conformément aux consignes du dépôt.

Résultats réels :
- `php artisan migrate:fresh --seed --env=testing` : réussi ; les migrations et le seeder des rôles ont été exécutés.
- `php artisan test tests/Feature/StepOneDataTest.php --colors=never` : 6 tests réussis, 11 assertions.
- Formatage Pint exécuté sur les fichiers PHP modifiés.
- Suite PHPUnit complète : 8 tests réussis, 13 assertions.

Limites :
- La durée minimale du mot de passe reste celle décidée ultérieurement à Q-05 ; la commande exige uniquement un mot de passe non vide, sans inventer une politique numérique.
- Q-01, Q-02, Q-04, Q-05 et Q-06 ne sont pas étendues dans cette étape.
- Le format exact du téléphone reste à décider dans l’étape 1 et est limité à une chaîne non vide.
- La compatibilité MySQL n’est pas prouvée par un serveur réel dans cette session.
- Aucun écran, route, contrôleur ou parcours d’inscription de l’étape 2 n’a été créé.

### Validation
Validateur : validation technique réalisée par vérification du code, des migrations et des tests.
Date : 04/11/2026.
Décision : étape 1 validée techniquement. Validation fonctionnelle finale de MySQL et de l’administrateur initial non disponible dans cette session.

---

## Étape 2 - Inscription, vérification d’e-mail et validation par l’administrateur

Statut : implémentée techniquement. Q-01 et Q-02 sont tranchées; le parcours nécessite une validation navigateur et SMTP réel.

### Objectif et prérequis
Permettre le parcours inscription → vérification → approbation. Prérequis : étape 1 validée ; SMTP configuré ou capturé en environnement de test.

### Travaux à réaliser et fichiers concernés
Formulaire d’inscription (Form Request), envoi du lien de vérification signé, écran « en attente de validation », liste des comptes en attente et actions approuver / refuser pour l’administrateur, e-mail de décision, événements d’audit.

### Règles applicables et points de vigilance
- R01 : le rôle `administrateur` ne peut jamais être soumis, même en forgeant la requête.
- R03, R04, R13, R14.
- Les e-mails passent par une file ou un envoi qui n’empêche pas l’inscription d’aboutir en cas d’échec d’envoi (comportement à décrire selon Q-02).

### Livrables
Pages d’inscription et de gestion des comptes en attente, notifications e-mail, tests.

### Tests et cas limites
- Nominal : inscription valide → e-mail de vérification → lien cliqué → statut `en_attente_validation` → approbation → statut `actif`.
- Refus d’accès : un vendeur qui appelle `POST /admin/utilisateurs/{id}/approuver` reçoit 403.
- Limites : lien de vérification expiré ou falsifié ; double clic sur le lien ; tentative d’inscription avec `role=administrateur` ; adresse déjà utilisée ; compte refusé qui tente de se connecter.

### Critères de sortie
- [ ] Parcours nominal exercé dans le navigateur.
- [x] Un événement d’audit existe pour chaque approbation et refus.
- [x] Tests automatisés réussis.

### Bilan d’exécution
Fichiers modifiés :
- `database/migrations/2026_11_04_022317_add_mod01_fields_to_users_table.php` : correction de `phone` pour imposer une valeur obligatoire conformément à Q-07/D-12.
- `app/Http/Requests/StoreRegistrationRequest.php` : validation obligatoire de `name`, `email`, `phone` et mot de passe confirmé.
- `app/Http/Requests/DecideUserRequest.php` : validation du rôle à l’approbation parmi `vendeur` et `magasinier`, et autorisation via la politique.
- `app/Http/Controllers/RegistrationController.php` : création publique sans rôle soumis, statut initial `email_non_verifie`, envoi de l’e-mail de vérification.
- `app/Http/Controllers/EmailVerificationController.php` : vérification du lien signé et passage à `en_attente_validation`.
- `app/Http\Controllers\AdminUserApprovalController.php` : liste des comptes en attente, approbation avec rôle administrateur et refus.
- `app/Policies/UserPolicy.php` : accès administrateur actif uniquement aux actions de consultation et décision.
- `app/Events/UserApproved.php`, `app/Events/UserRejected.php` et leurs listeners : événement et journalisation d’audit sans mot de passe ni ancienne adresse.
- `app/Mail/AccountApprovedMail.php`, `app/Mail/AccountRejectedMail.php` et leurs vues : e-mails minimaux de décision.
- `routes/web.php` : routes documentées d’inscription, vérification et gestion administrateur.
- `resources/views/auth/inscription.blade.php`, `resources/views/auth/verification-pending.blade.php`, `resources/views/admin/users/pending.blade.php` : écrans Blade minimaux de l’étape.
- `tests/Feature/StepTwoRegistrationTest.php` : couverture HTTP de l’inscription, vérification, approbation, refus et refus d’accès vendeur.

Résultats réels :
- `php artisan route:list` confirme les routes d’inscription, de vérification et d’administration ajoutées.
- Laravel Pint a formaté les fichiers PHP modifiés par cette étape.
- `php artisan test tests/Feature/StepTwoRegistrationTest.php --colors=never` : 5 tests réussis.
- `php artisan test --colors=never` : 13 tests réussis, 32 assertions.
- Le contexte de tests utilise SQLite en mémoire et les services e-mail sont simulés.

Limites :
- Le parcours n’a pas été exercé dans un navigateur réel dans cette session.
- Aucun SMTP réel n’a été configuré ou vérifié ; les envois sont capturés par les tests et les erreurs sont journalisées sans bloquer l’inscription ni la décision.
- Le format détaillé du téléphone et la politique Q-05 du mot de passe restent non décidés par le document source.
- La connexion et la vérification du statut actif lors de la connexion restent hors de cette étape.

### Validation
Validateur : validation technique réalisée par les routes, les contrôleurs, les tests HTTP et le bilan de la suite PHPUnit.
Date : 04/11/2026.
Décision : étape 2 implémentée techniquement. Validation navigateur et SMTP réel restent à réaliser lorsque l’environnement le permettra.

---

## Étape 3 - Connexion, sessions et réinitialisation du mot de passe

Statut : implémentée techniquement. Q-05 et Q-06 sont tranchées par D-15 et D-16.

### Objectif et prérequis
Sécuriser l’entrée dans l’application. Prérequis : étape 2 validée.

### Travaux à réaliser et fichiers concernés
Connexion et déconnexion, middleware qui contrôle le statut du compte à chaque requête, expiration des sessions, limitation des tentatives, mot de passe oublié et réinitialisation, assistance de l’administrateur, événements d’audit de connexion.

### Règles applicables et points de vigilance
- R03, R05, R07, R08, R11, R12, R13, R15.
- Le message d’échec est identique pour « e-mail inconnu » et « mot de passe faux ». En revanche, un compte en attente ou désactivé reçoit un message d’état clair seulement après authentification correcte (à confirmer, pour ne pas violer R15).
- Régénérer l’identifiant de session à la connexion.

### Livrables
Écrans et routes d’authentification, middleware, tests.

### Tests et cas limites
- Nominal : un compte `actif` se connecte et atteint son tableau de bord.
- Refus d’accès : un compte `desactive` ne passe pas ; un visiteur est redirigé vers la connexion.
- Limites : session ouverte puis compte désactivé → la requête suivante est refusée (T-SESSION) ; dépassement du seuil de tentatives → 429 (T-THROTTLE) ; jeton de réinitialisation réutilisé ou expiré ; adresse inconnue au mot de passe oublié, même réponse (T-ENUM).

### Critères de sortie
- [x] T-SESSION, T-THROTTLE, T-ENUM réussis dans les tests automatisés.
- [x] Aucun mot de passe ni ancienne adresse n’est écrit dans les logs.
- [ ] Parcours exercé sur ordinateur et petit écran.

### Bilan d’exécution
Fichiers modifiés :
- `app/Http/Controllers/LoginController.php`, `app/Http/Controllers/PasswordResetController.php` : connexion, déconnexion, réinitialisation et assistance.
- `app/Http/Middleware/EnsureAccountIsActive.php` : refus de toute requête provenant d’un compte non actif et invalidation de session.
- `app/Models/User.php` : prise en charge du contrat Laravel de réinitialisation du mot de passe.
- `app/Events/UserLoggedIn.php`, `app/Events/UserLoginFailed.php` et listeners associés : audit des connexions réussies et échouées.
- `app/Http/Controllers/AdminUserApprovalController.php` : assistance administrateur par envoi d’un lien de réinitialisation.
- `routes/web.php` : routes de connexion, déconnexion, tableau de bord, mot de passe et assistance administrateur.
- `resources/views/auth/connexion.blade.php`, `resources/views/auth/mot-de-passe-oublie.blade.php`, `resources/views/auth/reinitialiser-mot-de-passe.blade.php` : écrans Blade minimaux.
- `config/auth.php`, `config/session.php`, `.env.example` : durées retenues pour Q-05/Q-06.
- `app/Http/Requests/StoreRegistrationRequest.php` : longueur minimale à 8 caractères appliquée à l’inscription.
- `tests/Feature/StepThreeAuthenticationTest.php` : 7 tests de connexion, statut, limitation, énumération et réinitialisation.

Résultats réels :
- Laravel Pint exécuté sur les fichiers PHP modifiés.
- `php artisan test tests/Feature/StepThreeAuthenticationTest.php --colors=never` : 7 tests réussis, 29 assertions.
- `php artisan test --colors=never` : 20 tests réussis, 61 assertions.
- `php artisan route:list` confirme les routes d’authentification, de réinitialisation et d’assistance administrateur.

Limites :
- Les écrans n’ont pas été vérifiés sur ordinateur et petit écran dans un navigateur réel.
- SMTP réel non vérifié; les e-mails utilisent la configuration d’environnement et les tests utilisent les fakes Laravel.
- SQLite et migrations locales vérifiés; MySQL réel non vérifié.
- Les seuils Q-05 et durées Q-06 sont maintenant documentés par D-15 et D-16.

### Validation
Validateur : validation technique réalisée par les routes, middleware, contrôleurs, migrations existantes et suite PHPUnit.
Date : 04/11/2026.
Décision : étape 3 implémentée techniquement. Validation navigateur, responsive et SMTP réel restent à réaliser dans l’environnement cible.

---

## Étape 4 - Gestion des utilisateurs, rôles et profil

Statut : implémentée techniquement. Étape 5 non commencée, en attente de validation explicite.

### Objectif et prérequis
Donner à l’administrateur le contrôle des comptes et aux utilisateurs la gestion de leur profil. Prérequis : étape 3 validée.

### Travaux à réaliser et fichiers concernés
Liste filtrable (statut, rôle) et détail des utilisateurs, activation / désactivation, changement de rôle, profil, changement d’adresse e-mail vérifié, suppression logique, policies ou gates, navigation selon le rôle.

### Règles applicables et points de vigilance
- R04, R05, R06, R09, R10, R12, R13, R16.
- Le journal d’un changement d’e-mail ne conserve pas l’ancienne adresse.
- Le test d’accès couvre toutes les routes `/admin/*` pour chaque rôle.

### Livrables
Pages d’administration, profil, policies, tests d’accès exhaustifs.

### Tests et cas limites
- Nominal : l’administrateur change un vendeur en magasinier ; l’effet suit la règle choisie en Q-03.
- Refus d’accès : magasinier et vendeur reçoivent 403 sur toutes les routes d’administration (T-ACCES).
- Limites : tentative de désactiver le dernier administrateur actif (T-LASTADMIN) ; changement d’e-mail non vérifié, l’ancienne adresse reste utilisable ; suppression d’un compte ayant un historique, donc suppression logique.

### Critères de sortie
- [x] T-ACCES couvrant administrateur, vendeur et magasinier réalisé dans la suite automatisée.
- [x] Désactivation vérifiée par suppression des sessions enregistrées du compte.
- [x] Événements d’audit présents pour les changements de rôle, de statut et d’adresse e-mail.
- [ ] Parcours responsive et e-mail réel vérifiés dans l’environnement cible.
- [ ] R16 reste une proposition non confirmée par le document source.

### Bilan d’exécution
Fichiers principaux :
- `app/Http/Controllers/AdminUserController.php` : liste, détail, rôle, activation, désactivation et suppression logique.
- `app/Http/Controllers/ProfileController.php` : profil, mot de passe et changement vérifié de l’adresse e-mail.
- `app/Http/Requests/UpdateProfileRequest.php` et `app/Http/Requests/UpdateUserRoleRequest.php`.
- `app/Policies/UserPolicy.php` et `app/Http/Middleware/EnsureAccountIsActive.php`.
- `app/Events/UserRoleChanged.php`, `UserStatusChanged.php`, `UserEmailChanged.php` et leurs listeners d’audit.
- `resources/views/admin/users/`, `resources/views/profile/edit.blade.php` et les routes correspondantes.
- `tests/Feature/StepFourUserManagementTest.php`.

Résultats réels :
- `php artisan test tests/Feature/StepFourUserManagementTest.php --compact` : 7 tests réussis, 21 assertions.
- `php artisan test --compact` : 27 tests réussis, 82 assertions.
- `npm run build` produit le manifeste Vite et compile les assets.
- `vendor/bin/pint --format agent` a formaté les fichiers PHP concernés.
- `php artisan route:list` confirme les 9 routes administrateur et les 3 routes profil concernées.

### Validation
Validateur : validation technique automatisée réalisée.
Date : 04/11/2026.
Décision : étape 4 implémentée techniquement. La validation manuelle responsive et SMTP réel reste à réaliser; l’étape 5 n’est pas commencée sans autorisation.

---

## Étape 5 - Recette et préparation à la livraison

Statut : recette automatisable commencée; recette réelle bloquée par les prérequis externes non disponibles.

### Objectif et prérequis
Prouver que le module répond au besoin. Prérequis : étapes 1 à 4 validées ; environnement de test isolé.

### Travaux à réaliser et fichiers concernés
Exécuter la recette ci-dessous, corriger les défauts, mettre ce plan à jour, documenter l’installation (création de l’administrateur initial, configuration SMTP, sessions).

### Règles applicables et points de vigilance
R01 à R16. Aucune donnée de démonstration en production. Aucune livraison annoncée sans exécution réelle des tests.

### Livrables
Rapport de recette, commandes de vérification, limites connues, procédure d’installation.

### Tests et cas limites
Scénario complet avec un administrateur, un magasinier et un vendeur ; scénario hors ligne (e-mail indisponible) ; revue des logs et des pages d’erreur.

### Critères de sortie
- [ ] CA01 à CA05 acceptés.
- [ ] Défauts bloquants résolus.
- [ ] Validation de l’enseignant.

### Bilan d’exécution
Automatisation ajoutée :
- `tests/Feature/StepFiveAcceptanceTest.php` couvre le refus d’un compte en attente, l’accès interdit des rôles non administrateur sur deux écrans administrateur, la limitation de connexion et la présence des événements sensibles.
- Les tests d’étape 2 ont été restaurés et vérifiés dans `tests/Feature/StepTwoRegistrationTest.php`.
- La suite complète est relancée après chaque correction et les résultats réels sont conservés ci-dessous.

Résultats réels :
- `php artisan test --compact` : 36 tests réussis, 115 assertions.
- Les routes administrateur et profil ont été confirmées par `php artisan route:list`.
- Laravel Pint a formaté les nouveaux tests.

Bloquants restant hors automatisation :
- SMTP réel non configuré et confirmé.
- Parcours réel dans un navigateur et responsive non validés.
- Serveur MySQL réel non vérifié.
- Validation de l’enseignant non réalisée.
- Déploiement hors périmètre.

### Validation
Validateur : validation automatisée technique uniquement.
Décision : l’étape 5 reste bloquée pour une recette finale tant que les validations navigateur, SMTP, MySQL et enseignant ne sont pas réalisées.

---

## Recette globale

- **CA01.** Un vendeur s’inscrit, vérifie son e-mail, est approuvé, puis se connecte ; avant approbation, il n’accède à aucune page protégée.
- **CA02.** Un compte désactivé est expulsé à sa requête suivante, sans attendre l’expiration de sa session.
- **CA03.** Un vendeur ou un magasinier reçoit 403 sur toute route d’administration.
- **CA04.** Cinq tentatives de connexion échouées par minute sont bloquées, et les messages ne révèlent pas l’existence du compte.
- **CA05.** Chaque connexion, approbation, refus, changement de rôle et de statut apparaît dans le journal avec utilisateur, action et date, sans mot de passe.

## Limites connues

- L’envoi d’e-mails dépend d’Internet ; hors ligne, les parcours de vérification et de réinitialisation par lien sont indisponibles (voir Q-02).
- Pas d’authentification à deux facteurs dans cette version.
- Journal et notifications provisoires tant que MOD-09 et MOD-13 ne sont pas réalisés (D-03).

## Consignes de livraison

Le déploiement sur le serveur du magasin est une action distincte qui nécessite une autorisation explicite. Prévoir la procédure de migration et de retour arrière avant toute mise en production.

## Journal des décisions

| Date | Décision | Auteur |
|---|---|---|
| 04/11/2026 | Création du plan à partir du document d’architecture v1.0 | À compléter |
