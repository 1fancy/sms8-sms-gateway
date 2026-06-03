// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "SMS8",
    platforms: [
        .iOS(.v13),
        .macOS(.v11)
    ],
    products: [
        .library(name: "SMS8", targets: ["SMS8"])
    ],
    targets: [
        .target(name: "SMS8"),
        .testTarget(name: "SMS8Tests", dependencies: ["SMS8"])
    ]
)
