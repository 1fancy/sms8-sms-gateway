//
//  OTPTextField.swift
//  SwiftUI and UIKit helpers for the iOS "From Messages" SMS auto-fill keyboard.
//
//  iOS detects SMS codes (any 4-8 digit code in the most recent SMS) and offers
//  it as a keyboard suggestion above the OTP field when `textContentType` is
//  `.oneTimeCode`. This file wires that up.
//

#if canImport(UIKit)
import UIKit
import SwiftUI

/// SwiftUI OTP input that triggers iOS "From Messages" SMS auto-fill.
///
/// Usage:
/// ```swift
/// @State var code = ""
/// OTPTextField(code: $code, length: 6) { fullCode in
///     // Called when 6 digits are entered (auto-fill or manual).
///     Task { try await sms8.verifyOTP(phone: phone, code: fullCode) }
/// }
/// ```
@available(iOS 15.0, *)
public struct OTPTextField: UIViewRepresentable {
    @Binding public var code: String
    public let length: Int
    public let onComplete: (String) -> Void

    public init(code: Binding<String>, length: Int = 6, onComplete: @escaping (String) -> Void) {
        self._code = code
        self.length = length
        self.onComplete = onComplete
    }

    public func makeUIView(context: Context) -> UITextField {
        let tf = UITextField()
        tf.textContentType = .oneTimeCode
        tf.keyboardType = .numberPad
        tf.font = .monospacedSystemFont(ofSize: 22, weight: .semibold)
        tf.textAlignment = .center
        tf.borderStyle = .roundedRect
        tf.placeholder = String(repeating: "•", count: length)
        tf.delegate = context.coordinator
        tf.addTarget(context.coordinator, action: #selector(Coordinator.editingChanged(_:)), for: .editingChanged)
        return tf
    }

    public func updateUIView(_ uiView: UITextField, context: Context) {
        if uiView.text != code { uiView.text = code }
    }

    public func makeCoordinator() -> Coordinator {
        Coordinator(parent: self)
    }

    public final class Coordinator: NSObject, UITextFieldDelegate {
        let parent: OTPTextField
        init(parent: OTPTextField) { self.parent = parent }

        @objc func editingChanged(_ tf: UITextField) {
            let digits = (tf.text ?? "").filter(\.isNumber)
            let trimmed = String(digits.prefix(parent.length))
            parent.code = trimmed
            tf.text = trimmed
            if trimmed.count == parent.length {
                parent.onComplete(trimmed)
            }
        }
    }
}

/// UIKit version. Configure your existing `UITextField` to receive iOS SMS
/// auto-fill suggestions with one call.
///
/// ```swift
/// let tf = UITextField()
/// SMS8.enableOTPAutoFill(on: tf)
/// ```
extension SMS8 {
    public static func enableOTPAutoFill(on textField: UITextField) {
        textField.textContentType = .oneTimeCode
        textField.keyboardType = .numberPad
    }
}
#endif
