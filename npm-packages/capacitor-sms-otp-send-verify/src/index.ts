import { registerPlugin } from '@capacitor/core';
import type { SmsOtpPlugin } from './definitions';
export * from './definitions';

/**
 * SMS OTP plugin for Capacitor — send + verify codes via your own Android phone.
 *
 * Native bridges:
 *   iOS     → ios/Sources/CapacitorSmsOtpSendVerifyPlugin/SmsOtpPlugin.swift
 *   Android → android/src/main/java/io/sms8/capacitor/SmsOtpPlugin.kt
 *
 * Web fallback: same code, calls https://mcp.sms8.io directly.
 *
 * Free SMS8 trial at https://sms8.io
 */
const SmsOtp = registerPlugin<SmsOtpPlugin>('SmsOtp', {
  web: () => import('./web').then((m) => new m.SmsOtpWeb()),
});

export { SmsOtp };
export default SmsOtp;
