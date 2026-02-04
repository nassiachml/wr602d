# WR602D / Paperless Engine

> Turn anything into PDF. Instantly.

Micro-service de génération de PDF (Symfony + Gotenberg). Interface en français.

## Fonctionnalités

### PDF
- **URL → PDF** — Convertir une page web en PDF
- **Fichier → PDF** — Upload de documents (HTML, Office) convertis via Gotenberg/LibreOffice
- **WYSIWYG** — Saisie directe de contenu HTML converti en PDF
- **File d'attente** — Option « Ajouter à la file » + traitement manuel ou via `app:handle-queue`
- **Historique** — Liste des PDF générés avec téléchargement

### Compte utilisateur
- **Inscription / Connexion** — Authentification classique
- **Mot de passe oublié** — Demande par email + lien de réinitialisation
- **Changement de mot de passe** — Depuis « Mon compte » (mot de passe actuel + nouveau)
- **Mon compte** — Profil (nom d'affichage, avatar), formulaire sécurisé (CSRF)

### Abonnements & contacts
- **Abonnements** — Offres Free, Premium, Unlimited (quota)
- **Contacts** — CRUD de contacts (email, nom) liés au compte
- **Envoi par email** — Envoi du PDF généré à l'utilisateur et/ou aux contacts sélectionnés (config `MAILER_DSN`)

### Interface
- **Dashboard** — Vue d'ensemble, quotas, accès rapides
- **Design** — Sidebar fixe, palette sombre, accent coral

## Tech Stack

- **Backend**: Symfony 6.4, PHP 8.1+
- **PDF Engine**: Gotenberg (micro-service)
- **Database**: MySQL 8.0
- **UI**: Twig, design system custom

## Quick Start

```bash
git clone https://github.com/nassiachml/wr602d.git
cd wr602d
docker compose up -d
bash init.sh
docker compose exec symfony composer install
docker compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec symfony php bin/console doctrine:fixtures:load --no-interaction
```

**Accès**: http://localhost:8001  
**Utilisateur de test**: test@example.com / password

## Architecture

```
Symfony (8001)  ──HTTP──>  Gotenberg (3001)
      │
      └──>  MySQL (3309)
```

## Branches Git

- **main** — état stable (toute la doc)
- **develop** — intégration (créée à partir de main) ; contient tout le projet **sans** les .md sauf README.md
- **feature/xxx** — une branche par fonctionnalité (auth, pdf-generation, pdf-queue, pdf-history, dashboard, subscriptions, contacts, ui, security, tests-ci, i18n, docker)

Pour créer develop et toutes les branches feature avec leur README :

```bash
chmod +x create_feature_branches.sh
./create_feature_branches.sh
```

Voir [docs/GIT_BRANCHES.md](docs/GIT_BRANCHES.md) pour le détail.

## License

MIT
