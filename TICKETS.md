# 🎫 Backlog - Tickets à Résoudre

## 📊 Vue d'ensemble

**Total : 10 tickets** répartis en 3 catégories

| Catégorie | Nombre | Difficulté |
|-----------|--------|------------|
| 🐛 Bugs | 4 | ⭐ Facile à Moyen |
| 🔒 Sécurité | 3 | ⭐⭐ Moyen à Difficile |
| ⚡ Performance | 3 | ⭐⭐ Moyen |

**Objectif** : Résoudre au moins **7-8 tickets** (≈70%) pour être qualifié.

---

## 🐛 Bugs Fonctionnels

### [BUG-001] La recherche ne fonctionne pas avec les accents

**Priorité** : 🔴 Haute  
**Difficulté** : ⭐ Facile  
**Points** : 8 pts

#### Description du problème
Lorsqu'un utilisateur recherche un article contenant des accents (exemple : "café", "été", "élève"), la recherche ne retourne aucun résultat, même si des articles avec ces mots existent.

#### Comportement attendu
La recherche doit être **insensible aux accents** : chercher "cafe" devrait trouver "café", et vice-versa.

#### Étapes pour reproduire
1. Aller sur la page de recherche
2. L'article "Le café du matin" existe dans la base
3. Rechercher "cafe" (sans accent) → 0 résultat ❌
4. Rechercher "café" (avec accent) → 1 résultat ✅

Le problème : l'utilisateur doit taper exactement le même accent que dans le titre. Si le titre contient "café" et qu'on cherche "cafe", ça ne trouve rien.

#### Questions à considérer
- Comment vas-tu identifier la cause exacte du problème (DB structure, requête SQL, collation) ?
- Comment vas-tu gérer la migration de la collation sachant que les données existent déjà et qu'on ne peut pas recréer la table ni supprimer les données ?
- Comment tester que ta solution fonctionne dans tous les cas (accents, majuscules/minuscules, caractères spéciaux) ?

#### Résolution
- **Solution** : Utilisation de `CONVERT(title USING utf8mb4) COLLATE utf8mb4_unicode_ci` dans la requête SQL pour ignorer les accents.
- **Résultat** : La recherche trouve les articles indépendamment des accents (ex: "cafe" trouve "café") sans migration DB.

---

### [BUG-002] Impossible de supprimer le dernier commentaire d'un article

**Priorité** : 🟠 Moyenne  
**Difficulté** : ⭐ Facile  
**Points** : 7 pts

#### Description du problème
Quand un article a exactement 1 commentaire, cliquer sur le bouton "Supprimer" renvoie une erreur 500.

Si l'article a 2+ commentaires, la suppression fonctionne normalement.

#### Message d'erreur
```
Error: Undefined array key 0
in CommentController.php line 78
```

#### Comportement attendu
On doit pouvoir supprimer n'importe quel commentaire, qu'il soit seul ou non.

#### Étapes pour reproduire
1. Créer un article
2. Ajouter exactement 1 commentaire
3. Cliquer sur "Supprimer" → Erreur 500 ❌
4. Ajouter un 2ème commentaire
5. Supprimer le 1er → Fonctionne ✅

#### Questions à considérer
- Comment vas-tu reproduire l'erreur de manière fiable pour la débugger ?
- Pourquoi l'erreur se produit seulement avec 1 commentaire et pas avec 2+ ?
- Quelle est la meilleure approche pour éviter ce type d'erreur à l'avenir dans d'autres parties du code ?

---

### [BUG-003] Upload d'image > 2MB fait crasher l'application

**Priorité** : 🟠 Moyenne  
**Difficulté** : ⭐⭐ Moyen  
**Points** : 8 pts

#### Description du problème
Lors de l'upload d'une image de couverture pour un article :
- Images < 2MB : ✅ Fonctionne
- Images > 2MB : ❌ Erreur "413 Payload Too Large" ou timeout

#### Message d'erreur (dans les logs)
```
POST /api/articles/upload 413
Maximum upload size exceeded
```

#### Comportement attendu
Pouvoir uploader des images jusqu'à 10MB minimum.

#### Étapes pour reproduire
1. Créer un article
2. Essayer d'uploader une image de 5MB
3. Observer l'erreur réseau

#### Questions à considérer
- Où se trouve la limite d'upload ? (PHP, Apache, Laravel, Docker) - comment l'identifier ?
- Comment modifier cette configuration dans un environnement Docker sans tout reconstruire ?
- Comment vérifier que la modification a bien été appliquée après redémarrage ?

---

### [BUG-004] Les dates s'affichent en anglais et timezone US

**Priorité** : 🟢 Basse  
**Difficulté** : ⭐ Facile  
**Points** : 7 pts

#### Description du problème
Les dates des articles s'affichent :
- En format américain : "12/25/2024" au lieu de "25/12/2024"
- En timezone PST au lieu de Europe/Paris
- En anglais : "December 25" au lieu de "25 décembre"

#### Exemple
```
Créé le : 12/25/2024 at 3:45 PM (PST)
```

Au lieu de :
```
Créé le : 25/12/2024 à 15:45 (CET)
```

#### Comportement attendu
Dates en français, timezone Europe/Paris, format JJ/MM/AAAA.

#### Questions à considérer
- Où se configure la timezone et la locale dans une application Laravel ?
- Faut-il modifier le backend, le frontend, ou les deux ?
- Comment s'assurer que les dates stockées en base restent cohérentes après le changement ?

#### Résolution
- **Ce qui a été changé** : Configuration mise à jour pour utiliser **`locale: fr`** et **`timezone: Europe/Paris`** ; frontend utilise `Intl.DateTimeFormat('fr-FR', { timeZone: 'Europe/Paris' })` pour le rendu des dates.
- **Tests ajoutés** : Test unitaire pour vérifier `config('app.locale')` et `config('app.timezone')` ainsi que la configuration de Carbon.
- **Base de données** :  
  Laravel stocke les timestamps en UTC par défaut.  
  Si certaines données existent avec un fuseau horaire local, il est recommandé de les convertir en UTC avant la mise en production.

---

## 🔒 Sécurité

### [SEC-001] Les mots de passe sont stockés en clair dans la base de données

**Priorité** : 🔴 CRITIQUE  
**Difficulté** : ⭐⭐ Moyen  
**Points** : 12 pts

#### Description du problème
⚠️ **FAILLE DE SÉCURITÉ MAJEURE** ⚠️

Les mots de passe des utilisateurs sont stockés **en clair** (plain text) dans la table `users` au lieu d'être hashés.

#### Vérification
```sql
SELECT email, password FROM users;
```

Résultat actuel :
```
email: admin@blog.com, password: "Admin123!"
```

Au lieu de :
```
email: admin@blog.com, password: "$2y$10$92IXU..."
```

#### Risques
- Si la DB est compromise, tous les mots de passe sont exposés
- Violation RGPD
- Violation des standards de sécurité (OWASP)

#### Comportement attendu
- Les mots de passe doivent être hashés avec bcrypt/argon2
- Impossible de retrouver le mot de passe original
- L'authentification doit continuer à fonctionner

#### Questions à considérer
- Qu'as-tu utilisé pour te connecter à la DB et exécuter la vérification `SELECT email, password FROM users;` ? (GUI, CLI, autre outil ?)
- Comment vas-tu migrer les mots de passe existants vers des mots de passe hashés ?
- Comment t'assurer que l'authentification fonctionne toujours après la modification ?
- Où faut-il modifier le code pour que les futurs utilisateurs aient des mots de passe hashés ?

---

### [SEC-002] Injection SQL possible dans la recherche

**Priorité** : 🔴 CRITIQUE  
**Difficulté** : ⭐⭐⭐ Difficile  
**Points** : 10 pts

#### Description du problème
⚠️ **FAILLE D'INJECTION SQL** ⚠️

La fonction de recherche concatène directement l'input utilisateur dans une requête SQL raw, permettant des attaques par injection.

#### Preuve de concept

**Niveau 1 - Lister tous les articles :**
```bash
curl "http://localhost:8000/api/articles/search?q=%27%20OR%20%271%27%3D%271"
```
→ Retourne TOUS les articles (50) au lieu de 0 résultat

**Niveau 2 - Extraire les utilisateurs et mots de passe (CRITIQUE) 😱 :**
```bash
curl "http://localhost:8000/api/articles/search?q=%27%20UNION%20SELECT%20id,%20email,%20password,%201,%20null,%20null,%20now(),%20now()%20FROM%20users%20%23"
```

→ **Résultat** : Les 50 articles + la table users complète avec les mots de passe en clair !

```json
[
  ...articles normaux...,
  {"id":1,"title":"admin@blog.com","content":"Admin123!","published_at":null},
  {"id":2,"title":"john@blog.com","content":"Password123","published_at":null},
  {"id":3,"title":"jane@blog.com","content":"MySecret456","published_at":null}
]
```

#### Risques
- Accès non autorisé aux données (extraction de n'importe quelle table : users, logs, tokens, etc.)
- Les mots de passe en clair sont exposés (double faille avec SEC-001)
- Modification ou suppression de données possibles
- Faille OWASP Top 1 (Injection)

#### Comportement attendu
- Utiliser des requêtes préparées (prepared statements)
- Paramètres échappés automatiquement
- Protection contre injection SQL

#### Questions à considérer
- Comment as-tu testé et confirmé la vulnérabilité d'injection SQL ?
- Quelle est la différence entre une requête SQL concaténée et une requête préparée ?
- Pourquoi utiliser Eloquent plutôt que `DB::select()` raw pour ce type de requête ?
- Comment t'assurer qu'aucune autre partie du code n'a le même problème ?

---

### [SEC-003] CORS ouvert à tous les domaines + XSS dans les commentaires

**Priorité** : 🟠 Haute  
**Difficulté** : ⭐⭐ Moyen  
**Points** : 8 pts

#### Description du problème - Partie 1 : CORS
La configuration CORS permet à **n'importe quel domaine** d'accéder à l'API :
```php
'Access-Control-Allow-Origin' => '*'
```

Risque : des sites malveillants peuvent faire des requêtes à votre API depuis le navigateur de l'utilisateur.

#### Description du problème - Partie 2 : XSS
Les commentaires ne sont pas échappés. Un utilisateur peut injecter du JavaScript :

**Commentaire malveillant :**
```html
<img src=x onerror="alert('You have been hacked!'); setTimeout(() => window.location.href='https://void.fr', 2000)">
```
→ Le script s'exécute dans le navigateur de tous les visiteurs qui consultent l'article ❌

#### Comportement attendu
- CORS : limiter aux domaines autorisés (localhost + domaine prod)
- XSS : échapper/sanitize les commentaires avant affichage

#### Questions à considérer
- Comment as-tu testé la vulnérabilité XSS de manière sécurisée ?
- Pourquoi `dangerouslySetInnerHTML` est-il problématique et quelle est l'alternative ?
- Pour le CORS, quels sont les risques concrets de laisser `'*'` en production ?
- Faut-il corriger côté backend, frontend, ou les deux ?

---

## ⚡ Performance

### [PERF-001] La page liste des articles est très lente (problème N+1)

**Priorité** : 🟠 Haute  
**Difficulté** : ⭐⭐ Moyen  
**Points** : 9 pts

#### Description du problème
Le chargement de la liste des articles souffre d'un **problème N+1** classique : pour chaque article, des requêtes séparées sont exécutées pour charger l'auteur et les commentaires.

#### Comment reproduire et mesurer
1. Sur la page d'accueil, cliquez sur le bouton **"🧪 Tester Performance"** en haut à droite
2. Le mode test s'active (bouton devient orange : **"⚠️ Mode Performance Test"**)
3. Un panneau jaune apparaît avec des instructions
4. Observez :
   - ⏱️ **Temps de chargement : ~1500ms** (au lieu de ~100ms)
   - Le panneau affiche un avertissement : "🚨 TRÈS LENT!"

**Note** : Le mode test ajoute un délai artificiel de 30ms par article pour **simuler** le coût réel d'une base de données distante en production. Sur une DB locale, le N+1 est moins visible, mais en production avec latence réseau, ce problème causerait des temps de chargement de 3-5 secondes.

#### Analyse technique
En regardant les logs SQL (ouvrez un terminal et lancez `docker logs blog_backend -f`), vous verrez :
```
SELECT * FROM articles;                    // 1 requête
SELECT * FROM users WHERE id=1;            // 50 requêtes (1 par article)
SELECT * FROM comments WHERE article_id=1; // 50 requêtes
SELECT * FROM comments WHERE article_id=2; // etc.
...
```

**Total : ~101 requêtes SQL** pour afficher 50 articles → Problème N+1 classique

#### Comportement attendu
Charger la liste avec **eager loading** :
- Seulement **3 requêtes SQL** au total (articles, authors, comments)
- Temps de chargement < 200ms même avec le mode test activé
- Le nombre de requêtes ne doit pas augmenter avec le nombre d'articles

#### Impact
- Expérience utilisateur dégradée
- Surcharge du serveur MySQL
- Ne scale pas (avec 500 articles → 1001 requêtes!)
- Coûts serveur plus élevés

#### Questions à considérer
- Comment as-tu détecté et mesuré le problème N+1 ? (logs Docker, DevTools Network, autre outil ?)
- Quelle est la différence entre eager loading et lazy loading dans Laravel/Eloquent ?
- Comment vérifier que ta solution a effectivement réduit le nombre de requêtes SQL (de 101 à 3) ?
- Y a-t-il d'autres endroits dans le code avec le même problème ?
- Pourquoi le mode test ajoute-t-il 30ms par article et comment cela simule-t-il une DB distante ?

---

### [PERF-002] Les images ne sont pas optimisées (backend + frontend)

**Priorité** : 🟢 Moyenne  
**Difficulté** : ⭐⭐⭐ Difficile  
**Points** : 12 pts (8 pts backend + 4 pts bonus frontend)

#### Description du problème
Les images uploadées sont servies dans leur taille/qualité originale :
- Une photo iPhone de 4MB est chargée telle quelle
- Pas de compression backend
- Pas de redimensionnement
- Pas de format moderne (WebP)
- Pas de lazy loading côté frontend
- Pas d'attributs width/height (cause du layout shift)

Impact : temps de chargement très long, gaspillage de bande passante, mauvaise expérience utilisateur.

#### Exemple
Image de couverture : `article_cover.jpg` - 4.2 MB - 4000x3000px
→ Affichée en 600x400px dans le navigateur

**Gaspillage : 90% des données téléchargées sont inutiles**

#### Comportement attendu

**Backend (8 pts - OBLIGATOIRE)** :
- Redimensionner automatiquement à la taille max nécessaire (ex: 1200px)
- Compresser avec qualité 80%
- (Bonus) Générer plusieurs tailles (thumbnail, medium, large)
- (Bonus) Convertir en WebP

**Frontend (4 pts - BONUS)** :
- Lazy loading des images hors viewport
- Attributs width/height pour éviter le layout shift
- (Bonus supplémentaire) Utiliser `srcset` pour responsive images
- (Bonus supplémentaire) Élément `<picture>` avec WebP + fallback JPG

**💡 Conseil** : Commencez par le backend (obligatoire), puis ajoutez le frontend si vous avez le temps pour gagner des points bonus.

#### Questions à considérer

**Backend** :
- Quel package/librairie PHP vas-tu utiliser pour l'optimisation d'images ?
- À quel moment faut-il optimiser l'image ? (lors de l'upload, à la demande, autre ?)
- Quelles dimensions et qualité cibles vas-tu choisir et pourquoi ?
- Comment gérer les images déjà uploadées avant l'optimisation ?

**Frontend** :
- Comment implémenter le lazy loading ? (attribut HTML natif, librairie JS, autre ?)
- Pourquoi les attributs `width` et `height` sont-ils importants même si le CSS redimensionne l'image ?
- Comment utiliser `srcset` et `sizes` pour servir des images adaptées à la taille de l'écran ?
- Quelle stratégie adopter pour supporter WebP avec fallback JPG/PNG pour les vieux navigateurs ?

**Full-stack** :
- Comment mesurer l'impact de tes optimisations ? (DevTools, Lighthouse, autre ?)
- Quel est le gain de performance attendu (temps de chargement, poids de la page) ?

---

### [PERF-003] Aucun système de cache pour l'API

**Priorité** : 🟢 Basse  
**Difficulté** : ⭐⭐ Moyen  
**Points** : 8 pts

#### Description du problème
Chaque requête API interroge systématiquement la base de données, même pour des données qui changent rarement.

Exemple : l'endpoint `/api/stats` (statistiques globales) :
- Appelé toutes les 5 secondes par le frontend
- Exécute 3 requêtes SQL lourdes à chaque fois
- Les stats changent environ 1 fois par heure

**Gaspillage de ressources évident**

#### Comportement attendu
Mettre en cache les réponses pour :
- `/api/stats` → cache 5 minutes
- `/api/articles` (liste) → cache 1 minute
- Invalidation du cache lors de modifications

#### Impact
- Réduction de 80%+ de la charge DB
- Temps de réponse API divisé par 10
- Meilleure scalabilité

#### Questions à considérer
- Quel driver de cache vas-tu utiliser et pourquoi ? (file, redis, memcached)
- Quelle durée de cache est appropriée pour chaque endpoint ?
- Comment gérer l'invalidation du cache quand les données sont modifiées ?
- Comment tester que le cache fonctionne correctement ?

---

## 📝 Workflow Git & Pull Requests

### Setup initial (une seule fois)

1. **Forker le repository** sur GitHub : https://github.com/voidagency/stages-fullstack-challenge.git

   > [!IMPORTANT]
   > Rendez votre fork **privé** et ajoutez **admin[at]void[dot]fr** comme collaborateur (lecture seule) via Settings > Collaborators.

2. **Cloner votre fork** :
   ```bash
   git clone https://github.com/VOTRE-USERNAME/stages-fullstack-challenge.git
   cd stages-fullstack-challenge
   ```

### Pour chaque ticket résolu

#### 1. Créer une branche

```bash
git checkout main
git pull origin main
git checkout -b BUG-001
```

**Convention simple** : `BUG-001`, `SEC-002`, `PERF-001`, etc.

#### 2. Faire vos corrections

- Committez régulièrement avec des messages clairs
- Exemple : `fix(search): correct collation for accent search [BUG-001]`

```bash
git add .
git commit -m "fix(search): correct collation for accent search [BUG-001]"
git push origin BUG-001
```

#### 3. Créer une Pull Request

Sur GitHub, créez une PR de `BUG-001` vers `main` (dans votre fork).

⚠️ **IMPORTANT** : Lors de la création de la PR sur GitHub :

1. **Assurez-vous que "base repository" pointe vers VOTRE fork**, pas vers `voidagency`
   - Base : `votre-username/stages-fullstack-challenge` → `main`
   - Compare : `votre-username/stages-fullstack-challenge` → `BUG-001`
   
2. Si GitHub affiche `voidagency/stages-fullstack-challenge` comme base, **cliquez dessus et changez-le** pour pointer vers votre fork

**Pourquoi ?** Vos PRs doivent rester dans votre fork pour que vous puissiez les merger. Ne créez pas de PRs vers le repo original.

---

**Titre de la PR** : `[BUG-001] La recherche ne fonctionne pas avec les accents`

GitHub affichera automatiquement le template `.github/pull_request_template.md`.

**Remplissez toutes les sections** :
- Problème identifié
- Solution implémentée
- Tests effectués
- Réponse aux questions à considérer

#### 4. Merger la PR

Une fois vos tests passés, mergez la PR dans votre branche main.

#### 5. Répéter pour chaque ticket

Retournez à l'étape 1 pour le ticket suivant.

---

### Template de Pull Request

Le template complet est disponible dans `.github/pull_request_template.md` et s'affiche automatiquement lors de la création d'une PR sur GitHub

---

## 🎯 Conseils de Priorisation

### Stratégie recommandée

**Phase 1 - Quick Wins (2h)**
1. [BUG-004] Dates/timezone → rapide
2. [BUG-001] Recherche accents → facile
3. [BUG-002] Suppression commentaire → simple

**Phase 2 - Sécurité Critique (2-3h)**
4. [SEC-001] Mots de passe en clair → priorité absolue
5. [SEC-002] Injection SQL → crucial

**Phase 3 - Performance (2h)**
6. [PERF-001] N+1 queries → impact fort
7. [PERF-003] Cache API → bon ratio effort/impact

**Phase 4 - Complexe (2-3h)**
8. [BUG-003] Upload images
9. [SEC-003] CORS + XSS
10. [PERF-002] Optimisation images (backend obligatoire, frontend bonus pour +4 pts)

---

## 📊 Statistiques

| Difficulté | Nombre | Points totaux |
|------------|--------|---------------|
| ⭐ Facile | 3 | 22 pts |
| ⭐⭐ Moyen | 5 | 42 pts |
| ⭐⭐⭐ Difficile | 2 | 22 pts |
| **Total** | **10** | **86 pts** |

**Objectif minimum : 60 points (≈ 7-8 tickets)**

**Note** : [PERF-002] offre 12 points au total (8 backend + 4 bonus frontend)

---

Bon courage ! 🚀

Des questions ? Consultez [CHALLENGE.md](./CHALLENGE.md) ou contactez le recruteur.

