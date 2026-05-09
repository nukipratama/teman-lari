#!/usr/bin/env bash
# Run once after cloning. Wires git hooks dir to the repo's .githooks/.
set -euo pipefail
git config core.hooksPath .githooks
echo "core.hooksPath set to .githooks"
