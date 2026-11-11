#!/bin/bash
# Build + sign SMS8 Gateway for the Mac App Store, produce an upload-ready .pkg.
# Usage: ./build-mas.sh "<SMS8 Gateway.app template dir>" <build number>
set -e
APP="${1:-SMS8 Gateway.app}"
BUILD="${2:?build number, e.g. 100061}"
DIST="Apple Distribution: NEXT LABS LLC (7MMY55WK78)"
INST="3rd Party Mac Developer Installer: NEXT LABS LLC (7MMY55WK78)"
# Universal binary, App Store flavour (-D MAS: this Mac only, no remote engine)
for a in arm64 x86_64; do
  swiftc -O -parse-as-library -D MAS -target $a-apple-macos14 main.swift \
    -framework SwiftUI -framework AppKit -framework Foundation -lsqlite3 -o SMS8iMessage-$a
done
lipo -create SMS8iMessage-arm64 SMS8iMessage-x86_64 -output SMS8iMessage
cp SMS8iMessage "$APP/Contents/MacOS/SMS8iMessage"
/usr/libexec/PlistBuddy -c "Set :CFBundleVersion $BUILD" "$APP/Contents/Info.plist"
# Apple rejects packages with files only the owner can read (ITMS 409) and quarantine xattrs (ITMS-91109)
chmod -R a+rX "$APP"; xattr -cr "$APP"
# embedded.provisionprofile must already be in $APP/Contents (profile "iMessage CrtP", io.sms8.gateway)
codesign --force --deep --options runtime --entitlements sms8-mas.entitlements --sign "$DIST" "$APP"
rm -f SMS8-Gateway-MAS.pkg
productbuild --component "$APP" /Applications --sign "$INST" SMS8-Gateway-MAS.pkg
echo "Built SMS8-Gateway-MAS.pkg (build $BUILD). Upload:"
echo "xcrun altool --upload-app -f SMS8-Gateway-MAS.pkg -t macos --apple-id 6828430425 --apiKey <KEY> --apiIssuer <ISSUER>"
