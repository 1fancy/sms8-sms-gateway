#!/bin/bash
# Build + sign SMS8 Gateway for the Mac App Store, produce an upload-ready .pkg.
set -e
APP="SMS8 Gateway.app"
DIST_CERT="${1:?Pass the Apple Distribution cert common name, e.g. '3rd Party Mac Developer Application: NEXT LABS LLC (7MMY55WK78)'}"
INSTALLER_CERT="${2:?Pass the Mac Installer cert, e.g. '3rd Party Mac Developer Installer: NEXT LABS LLC (7MMY55WK78)'}"

# Recompile fresh
swiftc -O -parse-as-library main.swift -framework SwiftUI -framework AppKit -framework Foundation -lsqlite3 -o SMS8iMessage
cp SMS8iMessage "$APP/Contents/MacOS/SMS8iMessage"

# Sign with sandbox entitlements
codesign --force --deep --options runtime --entitlements sms8-mas.entitlements \
  --sign "$DIST_CERT" "$APP"
codesign -dvv "$APP" 2>&1 | grep -E "Identifier|TeamId|Authority" | head -4

# Build the .pkg installer (Mac App Store wants a signed pkg)
productbuild --component "$APP" /Applications \
  --sign "$INSTALLER_CERT" "SMS8-Gateway-MAS.pkg"
echo "Built SMS8-Gateway-MAS.pkg — ready to upload."
