#!/usr/bin/env bash
#
# Per-boot (or manual) launcher for the homelabweekly-core WordPress Playground site.
#
# Uses @wp-playground/cli `start` — wp-now is deprecated. The repo plugin is
# mounted live so edits under src/wp-content/plugins/homelabweekly-core appear
# without a rebuild. WordPress + SQLite persist under ~/.wordpress-playground/sites/
# (keyed off $HOME/homelabweekly-wp-dev as the working directory).
#
#   bash scripts/cloud-agent-start.sh           # foreground on :8080
#   bash scripts/cloud-agent-start.sh --reset   # wipe persisted site, then start
#   bash scripts/cloud-agent-start.sh --warmup  # boot until HTTP 200, then stop
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SRC="$REPO_DIR/src/wp-content/plugins/homelabweekly-core"
SEED_SRC="$REPO_DIR/scripts/playground/homelabweekly-dev-seed"
BLUEPRINT="$REPO_DIR/scripts/playground/blueprint.json"
PREFIX="${HOMELABWEEKLY_PLAYGROUND_PREFIX:-$HOME/.homelabweekly-playground}"
STATE_DIR="${HOMELABWEEKLY_PLAYGROUND_STATE:-$HOME/homelabweekly-wp-dev}"
WP_PORT="${WP_PORT:-8080}"
WP_URL="${WP_URL:-http://127.0.0.1:$WP_PORT}"
CLI_BIN="$PREFIX/node_modules/.bin/wp-playground-cli"

WARMUP=0
RESET=0
for arg in "$@"; do
  case "$arg" in
    --warmup) WARMUP=1 ;;
    --reset) RESET=1 ;;
    -h|--help)
      sed -n '2,16p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown argument: $arg" >&2
      exit 1
      ;;
  esac
done

if [ ! -x "$CLI_BIN" ]; then
  echo "Playground CLI is not installed. Run: bash scripts/cloud-agent-setup.sh" >&2
  exit 1
fi

if [ ! -f "$PLUGIN_SRC/homelabweekly-core.php" ]; then
  echo "homelabweekly-core not found at $PLUGIN_SRC" >&2
  exit 1
fi

mkdir -p "$STATE_DIR"
cd "$STATE_DIR"

cli_args=(
  start
  --skip-browser
  --login=false
  --port="$WP_PORT"
  --php=8.3
  --wp=latest
  --site-url="$WP_URL"
  --path="$PLUGIN_SRC"
  --blueprint="$BLUEPRINT"
  --mount="$SEED_SRC:/wordpress/wp-content/plugins/homelabweekly-dev-seed"
)

if [ "$RESET" = 1 ]; then
  cli_args+=(--reset)
  echo "==> Resetting persisted Playground site"
fi

echo "==> Starting WordPress Playground on $WP_URL"
echo "==> Plugin mount: $PLUGIN_SRC"
echo "==> Persist cwd:  $STATE_DIR"

if [ "$WARMUP" = 0 ]; then
  exec "$CLI_BIN" "${cli_args[@]}"
fi

"$CLI_BIN" "${cli_args[@]}" &
pid=$!
cleanup() {
  kill "$pid" >/dev/null 2>&1 || true
  wait "$pid" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

echo "==> Waiting for $WP_URL (pid $pid)"
ok=0
for _ in $(seq 1 90); do
  if ! kill -0 "$pid" >/dev/null 2>&1; then
    echo "Playground exited before becoming ready." >&2
    exit 1
  fi
  if code="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 5 "$WP_URL/" 2>/dev/null)" && [ "$code" = "200" ]; then
    ok=1
    break
  fi
  sleep 2
done

if [ "$ok" != 1 ]; then
  echo "Playground did not respond on $WP_URL within 180s." >&2
  exit 1
fi

echo "==> Warmup ready at $WP_URL"
