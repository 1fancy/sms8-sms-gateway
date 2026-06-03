import XCTest
@testable import SMS8

final class SMS8Tests: XCTestCase {
    func testInit() {
        let s = SMS8(apiKey: "sk_test")
        XCTAssertNotNil(s)
    }
}
