# Plan d’implémentation - MOD-02 Gestion des produits et des catégories

> Fichier cible : `docs/modules/gestion-produits-categories.md`, versionné avec le projet.
> Source : document d’architecture « Conception et développement d’un logiciel de gestion de stock », v1.0, novembre 2026 (§1.3, §3, §5.2.2, §6, §8).

## 1. Identification

| Champ | Valeur |
|---|---|
| Module | MOD-02 - Gestion des produits et des catégories |
| Version du plan | 0.1 (brouillon) |
| Auteur | À compléter |
| Date | 4 novembre 2026 |
| Statut | À valider (étape 0 non commencée) |
| Validateur | À compléter (enseignant) |

Légende des statuts d’étape : à faire / en cours / bloquée / à valider / validée. « Codé », « testé » et « validé » sont des états différents.

## 2. Contexte, problème et objectifs

**Contexte.** Le catalogue est le socle des modules fournisseurs, entrées, ventes, inventaires et alertes. Si un produit est mal défini (doublon, prix perdu, quantité modifiée à la main), tous les autres modules en souffrent.

**Problème.** Le magasin a besoin d’un catalogue fiable où chaque produit a un code unique, une catégorie, un prix, une unité, une photo et un code-barres lisible en caisse.

**Objectifs.**
- Le magasinier et l’administrateur créent, modifient et archivent les produits et les catégories.
- Chaque produit reçoit automatiquement un code et un code-barres imprimable.
- Le vendeur retrouve un produit par son nom ou son code-barres.
- Les ventes passées gardent les valeurs d’origine même si le produit change ensuite.

**Parcours principal.** Création du produit → validation des informations → génération du code → association à une catégorie → enregistrement.

## 3. Périmètre

**Inclus.**
- Catégories et sous-catégories à plusieurs niveaux.
- Produits : création, modification, archivage, photo, prix d’achat et de vente, unité, quantité en stock (en lecture), fournisseur principal facultatif.
- Code produit automatique, code-barres unique par produit, impression des codes-barres.
- Recherche par nom, code ou code-barres ; liste paginée et filtrable.
- Liste d’unités prédéfinies.

**Exclus.**
- Mouvements de stock (entrées MOD-04, ventes MOD-05, sorties MOD-06, ajustements MOD-08) : MOD-02 expose la quantité, il ne la modifie pas à la main (voir Q-03).
- Seuil général d’alerte (paramètre de MOD-15, alertes MOD-09).
- Gestion des fournisseurs elle-même (MOD-03), taxes, dates de péremption, produits multi-prix, plusieurs codes-barres par produit.

## 4. Existant, stack et contraintes

| Élément | État | Remarque |
|---|---|---|
| Laravel, Blade, Tailwind CSS, MySQL | Annoncé dans le document | **Versions exactes à relever dans `composer.json` / `package.json` avant tout code** |
| Rôles, authentification et policies (MOD-01) | Prérequis, **à confirmer** | Le module ne recrée pas d’authentification |
| Table des fournisseurs (MOD-03) | **À confirmer** | Si absente, `supplier_id` nullable sans clé étrangère jusqu’à MOD-03 (voir Q-06) |
| Stockage des fichiers (photos) | À créer | Disque dédié ; fichiers servis de façon contrôlée |
| Bibliothèque de codes-barres | À choisir | Voir Q-02 |
| Tests PHPUnit ou Pest | À confirmer | Un seul outil |

**Contraintes.** Monolithe Laravel modulaire ; validation côté serveur ; protection CSRF et XSS ; téléversements strictement validés ; requêtes via Eloquent ou Query Builder ; interface responsive.

## 5. Règles métier et autorisations

| N° | Règle |
|---|---|
| R01 | Le magasinier et l’administrateur gèrent produits et catégories. Le vendeur consulte et recherche uniquement. |
| R02 | Le code produit est généré automatiquement et n’est pas modifiable. Il est unique. |
| R03 | Un produit appartient à une seule catégorie ou sous-catégorie. |
| R04 | Les catégories peuvent avoir plusieurs niveaux ; une catégorie ne peut pas devenir parente d’elle-même ni de ses ancêtres (pas de cycle). |
| R05 | Une photo est obligatoire à la création d’un produit. Formats et taille acceptés : voir Q-01. |
| R06 | Chaque produit a un seul code-barres, unique dans le catalogue. |
| R07 | Chaque produit a un seul prix de vente. Les prix d’achat et de vente sont modifiables. |
| R08 | Les prix sont positifs ou nuls, avec le format monétaire défini en Q-04. |
| R09 | Les quantités sont entières ou décimales, avec au plus trois décimales. La quantité n’est jamais négative. |
| R10 | L’unité est choisie dans une liste prédéfinie. |
| R11 | Deux produits ne peuvent pas avoir le même nom normalisé (définition en Q-05). Les formats différents se distinguent par le nom ou la description. |
| R12 | Un produit ayant des opérations historiques n’est jamais supprimé physiquement ; il est archivé. Un produit archivé n’est plus vendable ni sélectionnable pour une entrée, mais reste visible dans l’historique. |
| R13 | Modifier un produit après sa première vente ne change pas les ventes passées (elles conservent désignation et prix). Ce module expose donc les valeurs courantes ; la copie historique est faite par MOD-05. |
| R14 | La quantité en stock n’est pas saisissable dans le formulaire produit après création ; elle change uniquement par les opérations de stock (voir Q-03). |
| R15 | Les prix d’achat sont masqués au vendeur (voir Q-07). |
| R16 | La modification des prix et l’archivage d’un produit produisent un événement d’audit : utilisateur, date, ancienne et nouvelle valeur (MOD-13 ; voir D-03). |

**Matrice des accès (à confirmer à l’étape 0).**

| Action | Vendeur | Magasinier | Administrateur |
|---|---|---|---|
| Voir et rechercher les produits | Oui (sans prix d’achat) | Oui | Oui |
| Créer / modifier un produit | Non | Oui | Oui |
| Archiver / réactiver un produit | Non | Oui | Oui |
| Gérer les catégories | Non | Oui | Oui |
| Imprimer les codes-barres | Non | Oui | Oui |

## 6. Données, API et interface

**Données proposées, à rapprocher de l’existant.**
- `units` : `id`, `name` unique, `symbol`, `allows_decimals` (booléen).
- `categories` : `id`, `name`, `description`, `parent_id` nullable (auto-référence), `is_active`, horodatages. Unicité du nom parmi les catégories de même parent.
- `products` : `id`, `code` unique, `name`, `name_normalized` unique, `description`, `photo_path`, `purchase_price`, `sale_price`, `quantity` decimal(12,3) défaut 0, `unit_id`, `category_id`, `supplier_id` nullable, `is_archived`, `created_by`, horodatages.
- `barcodes` : `id`, `product_id` unique, `value` unique (ou colonne `barcode` dans `products` si un seul par produit ; choix à trancher en Q-02).
- Clés étrangères restrictives. Index : `code`, `barcode`, `name_normalized`, `category_id`, `is_archived`.

**Routes web (Blade).**

| Méthode et chemin | Accès | Résultat attendu |
|---|---|---|
| `GET /produits` | Tous les rôles connectés | Liste paginée, recherche, filtres (catégorie, archivé) |
| `GET /produits/creer`, `POST /produits` | Magasinier, administrateur | Création ; erreurs de champ conservées |
| `GET /produits/{id}` | Tous | Détail ; prix d’achat masqué au vendeur |
| `GET/PUT /produits/{id}` (modification) | Magasinier, administrateur | Mise à jour + audit des prix |
| `POST /produits/{id}/archiver`, `/reactiver` | Magasinier, administrateur | Changement d’état |
| `GET /produits/recherche?q=` | Tous | Résultats par nom, code ou code-barres |
| `GET/POST/PUT /categories` | Magasinier, administrateur | CRUD hiérarchique |
| `GET /produits/codes-barres` | Magasinier, administrateur | Planche imprimable des codes-barres choisis |

Codes à gérer : redirection vers la connexion (session absente), 403 (rôle insuffisant), 404 (produit inexistant), 413/422 (fichier trop grand ou invalide), 419 (CSRF expiré).

**Écrans et états obligatoires.** Liste des produits, formulaire de création et de modification, détail, gestion des catégories (arbre ou liste indentée), impression des codes-barres. États : chargement, liste vide, aucun résultat de recherche, erreur de champ, téléversement en cours, succès, produit archivé. Saisie conservée en cas d’erreur (hors fichier, à resélectionner). Navigation clavier ; photos avec texte alternatif ; affichage correct sur téléphone, tablette et ordinateur.

## 7. Qualité et risques

| Risque | Parade | Test associé |
|---|---|---|
| Fichier malveillant déguisé en photo | Validation du type réel, taille maximale, nom de fichier généré, stockage hors de l’exécution de scripts | T-UPLOAD |
| Doublons de produits par variations d’écriture | R11 avec normalisation | T-DOUBLON |
| Cycle dans l’arbre des catégories | R04 | T-CYCLE |
| Collision de codes générés en parallèle | Code produit généré dans une transaction avec contrainte d’unicité | T-CODE |
| Vendeur qui lit le prix d’achat dans le HTML ou une réponse | R15 : le champ n’est pas envoyé au vendeur | T-PRIXACHAT |
| Perte de l’historique des prix | R13, R16 | T-HISTORIQUE |
| Recherche lente avec beaucoup de produits | Index, pagination | Mesure sur jeu de test |

## 8. Questions ouvertes et décisions

| N° | Question | Impact | Décideur | Bloque |
|---|---|---|---|---|
| Q-01 | Formats de photo et taille maximale ? Redimensionnement automatique ? | R05, T-UPLOAD | Enseignant | Étape 3 |
| Q-02 | Standard de code-barres (EAN-13, Code 128 ?) : les produits ont-ils déjà un code-barres fabricant à saisir, ou le système en génère-t-il toujours un ? Bibliothèque de génération ? | R06, table `barcodes`, impression | Enseignant / gérant | Étape 1 |
| Q-03 | Quantité initiale à la création : fixée à 0 (le stock entre par MOD-04), ou saisissable une seule fois (stock de départ) ? | R09, R14 | Enseignant | Étape 3 |
| Q-04 | Monnaie et précision des prix (FCFA sans décimale ? stockage en entier ?) | R08, colonnes de prix | Enseignant / gérant | Étape 1 |
| Q-05 | Définition du « nom normalisé » : minuscules, sans accents, espaces réduits ? | R11 | Enseignant | Étape 1 |
| Q-06 | MOD-03 sera-t-il réalisé avant MOD-02 ? Sinon, `supplier_id` sans clé étrangère au début. | Données | Enseignant | Étape 1 |
| Q-07 | Le vendeur voit-il le prix d’achat ? Le document ne le dit pas ; proposition : non. | R15, matrice | Gérant | Étape 3 |
| Q-08 | Liste initiale des unités (pièce, kg, litre, carton…) et lesquelles autorisent les décimales ? | R09, R10 | Gérant | Étape 1 |
| Q-09 | Profondeur maximale des catégories ? Que devient une catégorie qui contient des produits quand on la désactive ? | R03, R04 | Enseignant | Étape 2 |
| Q-10 | Un prix de vente inférieur au prix d’achat est-il refusé, ou simplement signalé ? | R07, R08 | Gérant | Étape 3 |

**Décisions fixées** (reprises du document d’architecture).

| N° | Décision | Date |
|---|---|---|
| D-01 | Un seul code-barres et un seul prix de vente par produit ; une seule catégorie par produit | 04/11/2026 (document v1.0) |
| D-02 | Produits avec historique archivés, jamais supprimés ; produits modifiables après première vente sans toucher aux ventes passées | 04/11/2026 (document v1.0) |
| D-03 | *(Proposition à valider)* Même approche que MOD-01 : événements Laravel pour l’audit, écouteur minimal en attendant MOD-13 | À valider |

---

## Étape 0 - Vérifier et valider le cadrage

Statut : à faire.

### Objectif et prérequis
Rendre le plan applicable au projet réel. Prérequis : MOD-01 au moins planifié ; accès au dépôt.

### Travaux à réaliser et fichiers concernés
Relever les versions ; vérifier ce qui existe (rôles, policies, table fournisseurs, disque de stockage) ; trancher les questions bloquantes de l’étape 1 (Q-02, Q-04, Q-05, Q-06, Q-08) ; valider la matrice des accès et les routes.

### Règles applicables et points de vigilance
R01 à R16. Ne pas supposer l’existence d’un rôle, d’une table ou d’une policy sans l’avoir vue dans le dépôt.

### Livrables
Ce fichier mis à jour, matrice des accès validée.

### Tests et cas limites
Faire jouer sur papier : création d’un produit nominal ; tentative de création par un vendeur (refus) ; modification d’un prix après une vente.

### Critères de sortie
- [ ] Versions inscrites en section 4.
- [ ] Questions bloquantes de l’étape 1 tranchées.
- [ ] Matrice des accès validée.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Étape 1 - Données de référence : unités, catégories, produits, codes-barres

Statut : à faire. Dépend de Q-02, Q-04, Q-05, Q-06, Q-08.

### Objectif et prérequis
Disposer d’un schéma intègre. Prérequis : étape 0 validée ; MOD-01 disponible pour les rôles.

### Travaux à réaliser et fichiers concernés
Migrations, modèles `Unit`, `Category`, `Product` (et `Barcode` si séparé), relations, seeder des unités, factories. Emplacements habituels : `database/migrations/`, `app/Models/`, `database/seeders/`, `database/factories/`, `tests/Feature/`. Adapter à la structure constatée.

### Règles applicables et points de vigilance
R02, R03, R06, R09, R10, R11, R12 : contraintes d’unicité, clés étrangères restrictives, précision décimale à trois chiffres.

### Livrables
Schéma migrable, unités initiales, modèles et factories.

### Tests et cas limites
- Nominal : un produit valide est créé avec unité et catégorie.
- Refus : deux produits au nom normalisé identique ; deux produits avec le même code-barres.
- Limites : quantité à 3 décimales acceptée, à 4 décimales refusée ou arrondie selon décision ; suppression d’une catégorie ou d’une unité utilisée refusée.

### Critères de sortie
- [ ] `migrate:fresh --seed` réussit sur la base MySQL de test.
- [ ] Tests de contraintes réussis.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Étape 2 - Catégories et sous-catégories

Statut : à faire. Dépend de Q-09.

### Objectif et prérequis
Organiser le catalogue. Prérequis : étape 1 validée ; policies de MOD-01.

### Travaux à réaliser et fichiers concernés
Form Request, contrôleur, policy, vues de gestion en arbre, règle de validation anti-cycle.

### Règles applicables et points de vigilance
R01, R03, R04, R16 (audit des changements importants). Un vendeur ne voit aucun bouton de modification, et le serveur refuse ses requêtes.

### Livrables
Écrans de gestion des catégories, tests.

### Tests et cas limites
- Nominal : création d’une sous-catégorie de niveau 2.
- Refus : un vendeur reçoit 403 sur la création (T-ACCES).
- Limites : catégorie définie comme parent de sa propre descendante (T-CYCLE) ; nom identique sous des parents différents accepté, sous le même parent refusé.

### Critères de sortie
- [ ] T-CYCLE et T-ACCES réussis.
- [ ] Parcours exercé dans le navigateur.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Étape 3 - Produits : création, modification, photo, archivage

Statut : à faire. Dépend de Q-01, Q-03, Q-07, Q-10.

### Objectif et prérequis
Gérer le cycle de vie complet d’un produit. Prérequis : étape 2 validée.

### Travaux à réaliser et fichiers concernés
Form Request, contrôleur, policy, service de génération du code (transaction), téléversement et stockage de la photo, formulaire, détail, archivage et réactivation, audit des prix, masquage du prix d’achat au vendeur.

### Règles applicables et points de vigilance
R02, R05, R07, R08, R09, R11, R12, R13, R14, R15, R16. Le champ quantité n’apparaît pas dans le formulaire de modification. Le fichier n’est jamais servi avec son nom d’origine.

### Livrables
Écrans produit, tests Feature, journal des prix.

### Tests et cas limites
- Nominal : création avec photo, code généré, catégorie ; modification du prix de vente avec événement d’audit.
- Refus d’accès : le vendeur ne voit pas le prix d’achat dans la réponse (T-PRIXACHAT) et reçoit 403 sur la création.
- Limites : fichier non image renommé en `.jpg` (T-UPLOAD) ; création simultanée de deux produits (T-CODE) ; produit sans photo refusé ; archivage d’un produit avec historique.

### Critères de sortie
- [ ] T-UPLOAD, T-CODE, T-PRIXACHAT, T-DOUBLON réussis.
- [ ] Parcours magasinier exercé sur ordinateur et petit écran.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Étape 4 - Recherche, codes-barres et impression

Statut : à faire. Dépend de Q-02.

### Objectif et prérequis
Permettre de retrouver un produit et d’imprimer ses codes-barres. Prérequis : étape 3 validée.

### Travaux à réaliser et fichiers concernés
Recherche par nom, code, code-barres (champ compatible lecteur douchette), génération d’image ou de SVG de code-barres, page imprimable, filtres et pagination.

### Règles applicables et points de vigilance
R01, R06, R12 : les produits archivés sont exclus des résultats de vente par défaut. La recherche par code-barres répond rapidement (index) et ne renvoie qu’un résultat exact.

### Livrables
Recherche, page d’impression, tests.

### Tests et cas limites
- Nominal : le scan d’un code-barres retrouve le bon produit.
- Refus : un visiteur non connecté est redirigé vers la connexion.
- Limites : recherche sans résultat (état vide) ; nom avec accents ou majuscules ; code-barres inexistant ; impression de 50 codes à la fois.

### Critères de sortie
- [ ] Un code imprimé est relu correctement par un lecteur ou une application de scan.
- [ ] Temps de réponse de la recherche mesuré sur un jeu de test.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Étape 5 - Recette et préparation à la livraison

Statut : à faire.

### Objectif et prérequis
Prouver que le module répond au besoin. Prérequis : étapes 1 à 4 validées ; environnement de test isolé.

### Travaux à réaliser et fichiers concernés
Exécuter la recette, corriger les défauts, mettre à jour ce plan, documenter l’installation (disque de photos, droits des dossiers, bibliothèque de codes-barres).

### Règles applicables et points de vigilance
R01 à R16. Aucune donnée de démonstration en production. Aucune livraison annoncée sans exécution réelle des tests.

### Livrables
Rapport de recette, commandes de vérification, limites connues.

### Tests et cas limites
Scénario complet avec un administrateur, un magasinier et un vendeur ; catalogue de plusieurs centaines de produits ; vérification des fichiers stockés et des réponses réseau.

### Critères de sortie
- [ ] CA01 à CA05 acceptés.
- [ ] Défauts bloquants résolus.
- [ ] Validation de l’enseignant.

### Bilan d’exécution
*À compléter.*

### Validation
*À compléter.*

---

## Recette globale

- **CA01.** Un magasinier crée un produit avec photo et catégorie ; il reçoit un code et un code-barres uniques, et le produit apparaît dans la recherche.
- **CA02.** Deux produits au même nom normalisé ne peuvent pas coexister.
- **CA03.** Le vendeur retrouve un produit par nom et par code-barres, sans voir le prix d’achat, et ne peut ni créer ni modifier.
- **CA04.** Après modification du prix, une vente déjà enregistrée garde son ancien prix (vérifié avec MOD-05 quand il existe, sinon par test sur le contrat de données).
- **CA05.** Un produit avec historique ne peut pas être supprimé, seulement archivé, et reste visible dans l’historique.

## Limites connues

- Pas de plusieurs prix, plusieurs codes-barres ni suivi des péremptions.
- Journal et alertes provisoires tant que MOD-13 et MOD-09 ne sont pas réalisés (D-03).
- La vérification de CA04 est incomplète tant que MOD-05 n’existe pas.

## Consignes de livraison

Le déploiement sur le serveur du magasin est une action distincte qui nécessite une autorisation explicite. Prévoir la procédure de migration et de retour arrière avant toute mise en production.

## Journal des décisions

| Date | Décision | Auteur |
|---|---|---|
| 04/11/2026 | Création du plan à partir du document d’architecture v1.0 | À compléter |