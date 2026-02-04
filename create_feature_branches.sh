#!/bin/bash
# Paperless Engine - Create develop and feature branches with README
# Author: Nassia Chemlal - 3e année BUT MMI
# Commits in English, README in French

set -e
cd "$(dirname "$0")"

REMOTE=origin
MAIN=main
DEVELOP=develop

# Ordered list of feature branches
BRANCHES=(
  feature/auth
  feature/pdf-generation
  feature/pdf-queue
  feature/pdf-history
  feature/dashboard
  feature/subscriptions
  feature/contacts
  feature/ui
  feature/security
  feature/tests-ci
  feature/i18n
  feature/docker
)

# French description for each branch (same order as BRANCHES)
DESCRIPTIONS=(
  "Authentification : inscription, connexion, déconnexion, mot de passe oublié et réinitialisation."
  "Génération de PDF : conversion URL, fichier et WYSIWYG (HTML), quota par abonnement."
  "File d'attente PDF : tâches différées, traitement par lot, commande app:handle-queue."
  "Historique PDF : liste paginée, recherche, filtre par statut, téléchargement et suppression."
  "Tableau de bord : statistiques (mois, total), quota du jour, file d'attente."
  "Abonnements : offres Free/Premium/Unlimited, changement d'abonnement, affichage quota."
  "Contacts : CRUD des contacts utilisateur, sélection pour envoi du PDF par email."
  "Interface utilisateur : design system, thème clair/sombre, avatar, sidebar, responsive."
  "Sécurité : CSRF, rôles, contrôle d'accès (ROLE_USER), formulaire login sécurisé."
  "Tests et CI : PHPUnit, Cypress E2E, GitHub Actions (PHPStan, PHPMD, PHPCS)."
  "Internationalisation : locale (fr/en), traductions (placeholder)."
  "Docker : docker-compose (Symfony, MySQL, Gotenberg), configuration et scripts."
)

echo "=== Fetching from $REMOTE ==="
git fetch "$REMOTE" 2>/dev/null || true

echo "=== Ensuring main is current and develop is created from main ==="
git checkout "$MAIN" 2>/dev/null || { echo "Branch main not found."; exit 1; }
git push "$REMOTE" "$MAIN" 2>/dev/null || true
git checkout -B "$DEVELOP" "$MAIN"

echo "=== Cleaning develop: remove extra .md (keep README.md only) and useless files ==="
# Remove all .md files except README.md at project root
rm -f INSTALLATION.md QUICK_START.md DEPARRAGE.md 2>/dev/null || true
rm -f docs/CRITERES_PROJET.md docs/GIT_BRANCHES.md docs/RESUME_PROJET.md 2>/dev/null || true
rmdir docs 2>/dev/null || true
# Optional: remove one-off / useless files (uncomment if needed)
# rm -f create_feature_branches.sh create_pdf_task.sql 2>/dev/null || true
if git status --short | grep -q .; then
  git add -A
  git commit -m "chore: clean develop branch (keep README only, remove extra docs)"
fi

for i in "${!BRANCHES[@]}"; do
  branch="${BRANCHES[$i]}"
  desc="${DESCRIPTIONS[$i]}"
  echo "=== Branch: $branch ==="
  git checkout "$DEVELOP"
  git checkout -B "$branch"
  cat > README.txt << EOF
Branche : $branch
Fonctionnalité : $desc

Projet : Paperless Engine (WR602D)
Auteur : Nassia Chemlal — 3e année BUT MMI
EOF
  git add README.txt
  if ! git diff --staged --quiet; then
    git commit -m "docs: add branch README for $branch"
  fi
done

git checkout "$DEVELOP"

echo "=== Pushing develop and all feature branches to $REMOTE ==="
# Push develop first and set upstream (creates develop on remote if missing)
git push -u "$REMOTE" "$DEVELOP" || git push "$REMOTE" "$DEVELOP" --force-with-lease
for branch in "${BRANCHES[@]}"; do
  # Force-with-lease: overwrite remote feature branch with local (develop + README)
  # so that re-running the script after a reset does not fail with "non-fast-forward"
  git push "$REMOTE" "$branch" --force-with-lease
done

echo "=== Done. Current branch: $(git branch --show-current) ==="
