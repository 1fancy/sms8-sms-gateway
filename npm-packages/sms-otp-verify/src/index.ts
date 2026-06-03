/**
 * react-sms-otp — React hooks + components for SMS OTP via your own phone.
 *
 *   const { send, verify, status, error } = useSms8Otp({ apiKey });
 *   <OtpForm apiKey={apiKey} phone="+14155550100" onVerified={...} />
 *
 * Powered by SMS8 (https://sms8.io) — free 5-day trial.
 * No Twilio, no Vonage Verify, no per-OTP fees.
 */

export type OtpStatus = 'idle' | 'sending' | 'sent' | 'verifying' | 'verified' | 'error';

export interface Sms8OtpOptions {
  /** SMS8 API key from app.sms8.io → Profile → API. Free trial at sms8.io. */
  apiKey: string;
  /** Override base URL. Default https://mcp.sms8.io (the hosted MCP endpoint). */
  baseUrl?: string;
}

export interface SendOptions {
  /** E.164 phone, e.g. "+14155550100" */
  phone: string;
  /** Code length 4-8. Default 6. */
  length?: number;
  /** SMS body with {code} placeholder. Default: "Your verification code is {code}". */
  template?: string;
  /** Seconds until expiry (60-900). Default 300. */
  expiresIn?: number;
  /** Verification attempts allowed (1-10). Default 5. */
  maxAttempts?: number;
  /** Pin a specific paired Android device. */
  deviceId?: number;
  /** Pin a specific SIM slot on that device (multi-SIM). */
  simSlot?: string;
}

export interface VerifyOptions {
  /** Same phone you sent the OTP to. */
  phone: string;
  /** The code the user typed. */
  code: string;
}

export interface SendResult {
  success: boolean;
  otpId?: number;
  phone?: string;
  expiresAt?: string;
  expiresIn?: number;
  error?: string;
}

export interface VerifyResult {
  success: boolean;
  verified?: boolean;
  error?: string;
  attemptsLeft?: number;
}

const DEFAULT_BASE = 'https://mcp.sms8.io';

async function mcpCall(baseUrl: string, apiKey: string, tool: string, args: Record<string, unknown>): Promise<any> {
  const res = await fetch(baseUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${apiKey}`,
    },
    body: JSON.stringify({
      jsonrpc: '2.0',
      method: 'tools/call',
      params: { name: tool, arguments: { api_key: apiKey, ...args } },
      id: Date.now(),
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

/**
 * Plain client class — useful in non-React code (Next.js server actions, edge
 * functions, route handlers). For React components, use {@link useSms8Otp}.
 *
 * @example
 *   const otp = new Sms8Otp({ apiKey: process.env.SMS8_API_KEY! });
 *   const sent = await otp.send({ phone: '+14155550100' });
 *   const result = await otp.verify({ phone: '+14155550100', code: '482937' });
 */
export class Sms8Otp {
  private apiKey: string;
  private baseUrl: string;

  constructor(opts: Sms8OtpOptions) {
    if (!opts?.apiKey) throw new Error('Sms8Otp: apiKey is required. Get one free at https://sms8.io');
    this.apiKey = opts.apiKey;
    this.baseUrl = (opts.baseUrl || DEFAULT_BASE).replace(/\/$/, '');
  }

  async send(opts: SendOptions): Promise<SendResult> {
    const args: Record<string, unknown> = { phone: opts.phone };
    if (opts.length !== undefined) args.length = opts.length;
    if (opts.template) args.template = opts.template;
    if (opts.expiresIn !== undefined) args.expires_in = opts.expiresIn;
    if (opts.maxAttempts !== undefined) args.max_attempts = opts.maxAttempts;
    if (opts.deviceId !== undefined) args.device_id = opts.deviceId;
    if (opts.simSlot !== undefined) args.sim_slot = opts.simSlot;
    return mcpCall(this.baseUrl, this.apiKey, 'send_otp', args);
  }

  async verify(opts: VerifyOptions): Promise<VerifyResult> {
    return mcpCall(this.baseUrl, this.apiKey, 'verify_otp', {
      phone: opts.phone,
      code: opts.code,
    });
  }
}

// ── React surface ───────────────────────────────────────────────────────────
// Hook + components live in src/react.tsx so the class above is consumable
// from server / non-React code without pulling React into the bundle.
export { useSms8Otp } from './react';
export type { UseSms8OtpReturn } from './react';
export { OtpForm } from './OtpForm';
export type { OtpFormProps } from './OtpForm';
export { OtpInput } from './OtpInput';
export type { OtpInputProps, OtpInputState, OtpRenderInputProps } from './OtpInput';
