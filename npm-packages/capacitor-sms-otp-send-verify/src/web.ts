import { WebPlugin } from '@capacitor/core';
import type {
  SmsOtpPlugin,
  ConfigureOptions,
  SendSmsOptions,
  SendOtpOptions,
  VerifyOtpOptions,
  SendResult,
  SendOtpResult,
  VerifyOtpResult,
} from './definitions';

/**
 * Web fallback for the plugin. Calls the SMS8 MCP endpoint directly via fetch.
 * Used when the app runs in a browser (Capacitor `web` platform) instead of
 * on iOS or Android.
 */
export class SmsOtpWeb extends WebPlugin implements SmsOtpPlugin {
  private apiKey = '';
  private baseUrl = 'https://mcp.sms8.io';

  async configure(options: ConfigureOptions): Promise<void> {
    if (!options?.apiKey) throw new Error('configure: apiKey is required. Get one free at https://sms8.io');
    this.apiKey = options.apiKey;
    if (options.baseUrl) this.baseUrl = options.baseUrl.replace(/\/$/, '');
  }

  async sendSms(opts: SendSmsOptions): Promise<SendResult> {
    const args: Record<string, unknown> = { phone: opts.phone, message: opts.message };
    if (opts.deviceId != null) args.device_id = opts.deviceId;
    if (opts.simSlot   != null) args.sim_slot  = opts.simSlot;
    const r = await this.mcp('send_sms', args);
    return {
      success:   !!r?.success,
      messageId: r?.message_id,
      phone:     r?.phone,
      error:     r?.error,
    };
  }

  async sendOtp(opts: SendOtpOptions): Promise<SendOtpResult> {
    const args: Record<string, unknown> = { phone: opts.phone };
    if (opts.length      != null) args.length       = opts.length;
    if (opts.template    != null) args.template     = opts.template;
    if (opts.expiresIn   != null) args.expires_in   = opts.expiresIn;
    if (opts.maxAttempts != null) args.max_attempts = opts.maxAttempts;
    if (opts.deviceId    != null) args.device_id    = opts.deviceId;
    if (opts.simSlot     != null) args.sim_slot     = opts.simSlot;
    const r = await this.mcp('send_otp', args);
    return {
      success:   !!r?.success,
      otpId:     r?.otp_id,
      phone:     r?.phone,
      expiresAt: r?.expires_at,
      expiresIn: r?.expires_in,
      error:     r?.error,
    };
  }

  async verifyOtp(opts: VerifyOtpOptions): Promise<VerifyOtpResult> {
    const r = await this.mcp('verify_otp', { phone: opts.phone, code: opts.code });
    return {
      success:      !!r?.success,
      verified:     r?.verified,
      error:        r?.error,
      attemptsLeft: r?.attempts_left,
    };
  }

  private async mcp(tool: string, args: Record<string, unknown>): Promise<any> {
    if (!this.apiKey) throw new Error('SmsOtp not configured. Call SmsOtp.configure({ apiKey }) first.');
    const res = await fetch(this.baseUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${this.apiKey}`,
      },
      body: JSON.stringify({
        jsonrpc: '2.0',
        method:  'tools/call',
        params:  { name: tool, arguments: { api_key: this.apiKey, ...args } },
        id:      Date.now(),
      }),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const env = await res.json();
    if (env.error) throw new Error(env.error.message);
    const text = env.result?.content?.[0]?.text;
    if (typeof text !== 'string') return env.result;
    try {
      return JSON.parse(text);
    } catch {
      return text;
    }
  }
}
