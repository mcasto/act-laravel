#!/bin/bash
set -e

# ── Fill these in once ────────────────────────────────────────────────────────
SSH_KEY="$HOME/.ssh/act_deploy"   # e.g. ~/.ssh/id_rsa or ~/.ssh/act_server.pem
SSH_USER="u466389499"             # e.g. ubuntu, deploy, mikecasto
SSH_HOST="ftp.castoware.com"  # e.g. 123.45.67.89 or mysite.com
SSH_PASS="***REMOVED***"
REMOTE_DIR="/home/u466389499/castoware.com/diet-journal/public"        # the folder that serves index.html on the server
# ─────────────────────────────────────────────────────────────────────────────

echo "Building..."
yarn build

echo "Uploading..."
rsync -avz --checksum \
  -e "ssh -i $SSH_KEY -p 65002" \
  --exclude=".DS_Store" \
  dist/spa/ \
  "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "Done."
