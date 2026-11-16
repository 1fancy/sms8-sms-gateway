#!/bin/bash
# Build the direct-download (GitHub) version of SMS8 Gateway.
#
# Output: SMS8-Gateway-macOS.zip and SMS8-Gateway-Installer.dmg
#
# The app is AD-HOC signed (codesign -s -) as a whole bundle. It is NOT
# notarized. On first open macOS shows "Apple could not verify SMS8 Gateway";
# the user then allows it once in System Settings > Privacy & Security >
# Open Anyway (see README). Do NOT embed a provisioning profile or sign with an
# "Apple Development" certificate here: a development-signed app only launches
# on Macs listed in its profile, and on every other Mac it is reported as
# damaged with no Open Anyway option.
#
# Usage: ./build-direct.sh <version> <build>     e.g. ./build-direct.sh 10.0.54 100061
set -e
cd "$(dirname "$0")"
VERSION="${1:?version, e.g. 10.0.54}"
BUILD="${2:?build number, e.g. 100061}"
OUT="dist"
APP="$OUT/SMS8 Gateway.app"

rm -rf "$OUT"; mkdir -p "$APP/Contents/MacOS" "$APP/Contents/Resources"

# Universal binary (Apple silicon + Intel)
for a in arm64 x86_64; do
  swiftc -O -parse-as-library -target $a-apple-macos14 main.swift \
    -framework SwiftUI -framework AppKit -framework Foundation -lsqlite3 -o "$OUT/SMS8iMessage-$a"
done
lipo -create "$OUT/SMS8iMessage-arm64" "$OUT/SMS8iMessage-x86_64" -output "$APP/Contents/MacOS/SMS8iMessage"
rm -f "$OUT"/SMS8iMessage-*
cp app-resources/AppIcon.icns app-resources/logo.png "$APP/Contents/Resources/"

cat > "$APP/Contents/Info.plist" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>CFBundleDisplayName</key><string>SMS8 Gateway</string>
  <key>CFBundleExecutable</key><string>SMS8iMessage</string>
  <key>CFBundleIconFile</key><string>AppIcon</string>
  <key>CFBundleIdentifier</key><string>io.sms8.gateway</string>
  <key>CFBundleName</key><string>SMS8 Gateway</string>
  <key>CFBundlePackageType</key><string>APPL</string>
  <key>CFBundleShortVersionString</key><string>$VERSION</string>
  <key>CFBundleVersion</key><string>$BUILD</string>
  <key>LSApplicationCategoryType</key><string>public.app-category.business</string>
  <key>LSMinimumSystemVersion</key><string>14.0</string>
  <key>NSHighResolutionCapable</key><true/>
  <key>NSAppleEventsUsageDescription</key><string>SMS8 Gateway controls the Messages app to send iMessage and SMS on your behalf when your SMS8 account sends a message.</string>
  <key>NSSystemAdministrationUsageDescription</key><string>SMS8 reads incoming messages so your replies reach your SMS8 inbox.</string>
</dict>
</plist>
PLIST

chmod -R a+rX "$APP"; xattr -cr "$APP"
# Ad-hoc sign the whole bundle. Hardened runtime + the Apple Events entitlement
# keep it ready for a future Developer ID + notarization pass.
codesign --force --deep --options runtime --entitlements sms8-devid.entitlements -s - "$APP"
codesign --verify --deep --strict --verbose=2 "$APP"

# Zip with ditto so the bundle (symlinks, signature, xattrs) survives intact.
ditto -c -k --sequesterRsrc --keepParent "$APP" "$OUT/SMS8-Gateway-macOS.zip"

# DMG with an Applications shortcut for drag-to-install.
STAGE="$OUT/dmg"; mkdir -p "$STAGE"
ditto "$APP" "$STAGE/SMS8 Gateway.app"
ln -s /Applications "$STAGE/Applications"
hdiutil create -volname "SMS8 Gateway" -srcfolder "$STAGE" -ov -format UDZO "$OUT/SMS8-Gateway-Installer.dmg" >/dev/null
rm -rf "$STAGE"

echo "Built $OUT/SMS8-Gateway-macOS.zip and $OUT/SMS8-Gateway-Installer.dmg ($VERSION, build $BUILD). Ad-hoc signed, not notarized."
