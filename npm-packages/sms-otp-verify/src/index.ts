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

/** Shared routing options for send-style calls (per-device, per-SIM, broadcast). */
export interface RoutingOptions {
  /** Pin a specific paired Android device by ID (see `listDevices()` for IDs). */
  deviceId?: number;
  /** Pin a specific SIM slot on that device (multi-SIM Androids). */
  simSlot?: string;
  /**
   * Explicit list of senders. Each entry is `"<deviceID>"` or `"<deviceID>|<simSlot>"`.
   * Overrides `deviceId`/`simSlot`.
   */
  devices?: string[];
  /**
   * Routing mode.
   *  - `0` (default) → use `deviceId`/`devices`
   *  - `1` → broadcast across all paired devices
   *  - `2` → broadcast across all SIMs across all paired devices
   */
  option?: 0 | 1 | 2;
  /** If true, pick one random sender from the resolved list (load-balancing). */
  randomDevice?: boolean;
}

export interface SendSMSOptions extends RoutingOptions {
  /** E.164 phone, e.g. "+14155550100". */
  phone: string;
  /** SMS body. */
  message: string;
}

export interface SendOptions extends RoutingOptions {
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

export interface SendSMSResult {
  success: boolean;
  messageId?: number;
  phone?: string;
  senders?: string[];
  statusUrl?: string;
  error?: string;
}

export interface VerifyResult {
  success: boolean;
  verified?: boolean;
  reason?: string;
  error?: string;
  attemptsLeft?: number;
}

export interface DeviceInfo {
  id: number;
  name: string | null;
  model: string | null;
  androidVersion: string | null;
  appVersion: string | null;
  enabled: boolean;
  primary: boolean;
}

export interface ListDevicesResult {
  success: boolean;
  count: number;
  devices: DeviceInfo[];
  error?: string;
}

export interface GetMessagesOptions {
  /** "all" | "received" | "sent". Default "all". */
  direction?: 'all' | 'received' | 'sent';
  /** Max rows (1-100). Default 25. */
  limit?: number;
  /** Filter to messages to/from this phone only (E.164). */
  phone?: string;
}

export interface MessageRecord {
  ID: number;
  number: string;
  message: string;
  status: string;
  sentDate: string;
  deliveredDate: string;
  type: string;
}

export interface GetMessagesResult {
  success: boolean;
  count: number;
  messages: MessageRecord[];
  error?: string;
}

export interface GetBalanceResult {
  success: boolean;
  credits: number | null;
  unlimited: boolean;
  expiresAt?: string | null;
  daysLeft?: number | null;
  summary?: string;
  error?: string;
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

/** Convert RoutingOptions to the snake_case fields the MCP backend expects. */
function routingArgs(opts: RoutingOptions): Record<string, unknown> {
  const a: Record<string, unknown> = {};
  if (opts.deviceId      !== undefined) a.device_id      = opts.deviceId;
  if (opts.simSlot       !== undefined) a.sim_slot       = opts.simSlot;
  if (opts.devices       !== undefined) a.devices        = opts.devices;
  if (opts.option        !== undefined) a.option         = opts.option;
  if (opts.randomDevice) a.random_device = true;
  return a;
}

/**
 * SMS8 client — programmatic SMS + OTP for Node.js, Deno, Bun, edge runtimes,
 * Next.js server actions, Remix loaders, Express handlers. **Works without React.**
 *
 * Methods:
 *   • `send(opts)`         — send an OTP code (alias of `sendOTP`)
 *   • `sendOTP(opts)`      — send an OTP code (preferred)
 *   • `verify(opts)`       — verify a code (alias of `verifyOTP`)
 *   • `verifyOTP(opts)`    — verify a code (preferred)
 *   • `sendSMS(opts)`      — send a plain SMS (not an OTP)
 *   • `listDevices()`      — list paired Android phones + IDs
 *   • `getMessages(opts)`  — recent inbox / sent messages
 *   • `getBalance()`       — credits + expiry summary
 *
 * @example
 *   const sms8 = new Sms8Otp({ apiKey: process.env.SMS8_API_KEY! });
 *
 *   // SMS
 *   await sms8.sendSMS({ phone: '+14155550100', message: 'Order shipped!' });
 *
 *   // OTP
 *   const sent   = await sms8.sendOTP({ phone: '+14155550100', length: 6 });
 *   const check  = await sms8.verifyOTP({ phone: '+14155550100', code: '482937' });
 *   if (check.verified) { ... }
 *
 *   // Route through a specific phone or SIM
 *   await sms8.sendSMS({
 *     phone: '+14155550100', message: 'Hi',
 *     deviceId: 10700, simSlot: '2'
 *   });
 *
 *   // Broadcast across all paired devices
 *   await sms8.sendSMS({ phone: '+14155550100', message: 'Hi', option: 1 });
 */
export class Sms8Otp {
  private apiKey: string;
  private baseUrl: string;

  constructor(opts: Sms8OtpOptions) {
    if (!opts?.apiKey) throw new Error('Sms8Otp: apiKey is required. Get one free at https://sms8.io');
    this.apiKey = opts.apiKey;
    this.baseUrl = (opts.baseUrl || DEFAULT_BASE).replace(/\/$/, '');
  }

  /** Send a plain SMS through your paired Android phone. */
  async sendSMS(opts: SendSMSOptions): Promise<SendSMSResult> {
    if (!opts?.phone)   throw new Error('sendSMS: phone is required');
    if (!opts?.message) throw new Error('sendSMS: message is required');
    const r = await mcpCall(this.baseUrl, this.apiKey, 'send_sms', {
      phone:   opts.phone,
      message: opts.message,
      ...routingArgs(opts),
    });
    return {
      success:   !!r?.success,
      messageId: r?.message_id,
      phone:     r?.phone,
      senders:   r?.senders,
      statusUrl: r?.status_url,
      error:     r?.error,
    };
  }

  /** Send a verification code. Defaults: 6 digits, 5-min expiry, 5 attempts. */
  async sendOTP(opts: SendOptions): Promise<SendResult> {
    if (!opts?.phone) throw new Error('sendOTP: phone is required');
    const args: Record<string, unknown> = { phone: opts.phone };
    if (opts.length      !== undefined) args.length       = opts.length;
    if (opts.template)                  args.template     = opts.template;
    if (opts.expiresIn   !== undefined) args.expires_in   = opts.expiresIn;
    if (opts.maxAttempts !== undefined) args.max_attempts = opts.maxAttempts;
    Object.assign(args, routingArgs(opts));
    const r = await mcpCall(this.baseUrl, this.apiKey, 'send_otp', args);
    return {
      success:   !!r?.success,
      otpId:     r?.otp_id,
      phone:     r?.phone,
      expiresAt: r?.expires_at,
      expiresIn: r?.expires_in,
      error:     r?.error,
    };
  }

  /** Verify a code the user typed. Checks the most-recent unverified OTP for that phone. */
  async verifyOTP(opts: VerifyOptions): Promise<VerifyResult> {
    if (!opts?.phone) throw new Error('verifyOTP: phone is required');
    if (!opts?.code)  throw new Error('verifyOTP: code is required');
    const r = await mcpCall(this.baseUrl, this.apiKey, 'verify_otp', {
      phone: opts.phone,
      code:  opts.code,
    });
    return {
      success:      !!r?.success,
      verified:     r?.verified,
      reason:       r?.reason,
      error:        r?.error,
      attemptsLeft: r?.attempts_left,
    };
  }

  /** Backwards-compatible alias for `sendOTP`. */
  async send(opts: SendOptions): Promise<SendResult> {
    return this.sendOTP(opts);
  }

  /** Backwards-compatible alias for `verifyOTP`. */
  async verify(opts: VerifyOptions): Promise<VerifyResult> {
    return this.verifyOTP(opts);
  }

  /** List paired Android devices (IDs you can pass to `deviceId`). */
  async listDevices(): Promise<ListDevicesResult> {
    const r = await mcpCall(this.baseUrl, this.apiKey, 'list_devices', {});
    return {
      success: !!r?.success,
      count:   r?.count ?? 0,
      devices: r?.devices ?? [],
      error:   r?.error,
    };
  }

  /** Fetch recent messages (inbox + sent). */
  async getMessages(opts: GetMessagesOptions = {}): Promise<GetMessagesResult> {
    const args: Record<string, unknown> = {
      direction: opts.direction ?? 'all',
      limit:     opts.limit     ?? 25,
    };
    if (opts.phone) args.phone = opts.phone;
    const r = await mcpCall(this.baseUrl, this.apiKey, 'get_messages', args);
    return {
      success:  !!r?.success,
      count:    r?.count ?? 0,
      messages: r?.messages ?? [],
      error:    r?.error,
    };
  }

  /** Account credit + expiry summary. */
  async getBalance(): Promise<GetBalanceResult> {
    const r = await mcpCall(this.baseUrl, this.apiKey, 'get_balance', {});
    return {
      success:   !!r?.success,
      credits:   r?.credits ?? null,
      unlimited: !!r?.unlimited,
      expiresAt: r?.expires_at,
      daysLeft:  r?.days_left,
      summary:   r?.summary,
      error:     r?.error,
    };
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
