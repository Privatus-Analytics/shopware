#!/usr/bin/env sh
# Builds build/PrivatusAnalytics.zip for the Shopware Store: one root folder
# named after the plugin, with no tests, dev files or composer.lock.
set -eu
cd "$(dirname "$0")/.."
rm -rf build
mkdir -p build/PrivatusAnalytics
cp -R src composer.json LICENSE README.md CHANGELOG.md CHANGELOG_de-DE.md build/PrivatusAnalytics/
find build/PrivatusAnalytics -name '.DS_Store' -delete
(cd build && zip -qr PrivatusAnalytics.zip PrivatusAnalytics)
echo "build/PrivatusAnalytics.zip"
