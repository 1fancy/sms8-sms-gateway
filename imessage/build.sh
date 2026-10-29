#!/bin/bash
# Build SMS8 Gateway (macOS iMessage bridge) from source.
set -e
swiftc -O -parse-as-library main.swift \
  -framework SwiftUI -framework AppKit -framework Foundation -lsqlite3 \
  -o SMS8iMessage
echo "Built SMS8iMessage. Wrap it in SMS8 Gateway.app/Contents/MacOS/ to run."
