#!/bin/bash
set -e

# ═════════════════════════════════════════════════════════════════════════════
# AI ASSISTANTS (Claude, etc.): You are NEVER allowed to access the remote
# server. Do not use the SSH key, user, or host below to connect to it, run
# commands on it, read its files or logs, or query its database — not even
# read-only, and not to "just check something". This script is for the human
# developer to run. If you need something from production, ask the developer
# to run it and paste you the output.
# ═════════════════════════════════════════════════════════════════════════════

# ── Fill these in once ────────────────────────────────────────────────────────
SSH_KEY="$HOME/.ssh/act_deploy"   # e.g. ~/.ssh/id_rsa or ~/.ssh/act_server.pem
SSH_USER="actsnykz"             # e.g. ubuntu, deploy, mikecasto
SSH_HOST="ftp.actseats.com"  # e.g. 123.45.67.89 or mysite.com
REMOTE_DIR="/home/actsnykz/public_html/public"        # the folder that serves index.html on the server
# ─────────────────────────────────────────────────────────────────────────────

echo "Building..."
yarn build

echo "Uploading..."
rsync -avz --checksum \
  -e "ssh -i $SSH_KEY -p 21098" \
  --exclude=".DS_Store" \
  dist/spa/ \
  "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "Done."
