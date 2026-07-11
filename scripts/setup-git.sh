#!/usr/bin/env bash
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

ENV_FILE="$ROOT/git.local.env"
EXAMPLE="$ROOT/git.local.env.example"

if [[ ! -f "$ENV_FILE" ]]; then
  cp "$EXAMPLE" "$ENV_FILE"
  echo "Created git.local.env from git.local.env.example."
  echo "Edit git.local.env with your name and email, then run this script again."
  exit 1
fi

# shellcheck disable=SC1090
source "$ENV_FILE"

if [[ -z "${GIT_USER_NAME:-}" || "$GIT_USER_NAME" == "Your Name" ]]; then
  echo "Set GIT_USER_NAME in git.local.env (not the placeholder)."
  exit 1
fi

if [[ -z "${GIT_USER_EMAIL:-}" || "$GIT_USER_EMAIL" == "you@example.com" ]]; then
  echo "Set GIT_USER_EMAIL in git.local.env (use your GitHub email)."
  exit 1
fi

git config user.name "$GIT_USER_NAME"
git config user.email "$GIT_USER_EMAIL"

echo "Git identity set for this repo only:"
echo "  name:  $(git config user.name)"
echo "  email: $(git config user.email)"

if [[ -n "${GITHUB_REPO_URL:-}" ]] && [[ "$GITHUB_REPO_URL" != "https://github.com/your-user/kingdomcity-mis.git" ]]; then
  if git remote get-url origin >/dev/null 2>&1; then
    git remote set-url origin "$GITHUB_REPO_URL"
    echo "Updated origin → $GITHUB_REPO_URL"
  else
    git remote add origin "$GITHUB_REPO_URL"
    echo "Added origin → $GITHUB_REPO_URL"
  fi
fi

echo "Done. You can commit and push from Cursor now."
