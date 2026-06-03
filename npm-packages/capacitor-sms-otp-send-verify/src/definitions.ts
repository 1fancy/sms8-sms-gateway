// Capacitor plugin: send + verify SMS OTP codes through your own Android phone.
// Twilio Verify alternative. Free 5-day trial at https://sms8.io.

export interface SendSmsOptions {
  /** E.164 phone number. */
  phone: string;
  /** SMS body. */
  message: string;
  /** Optional paired-device ID. */
  deviceId?: number;
  /** Optional SIM slot (multi-SIM Android). */
  simSlot?: string;
}

export interface SendOtpOptions {
  phone: string;
  /** Code length 4-8. Default 6. */
  length?: number;
  /** SMS body with `{code}` placeholder. */
  template?: string;
  /** Seconds until expiry (60-900). Default 300. */
  expiresIn?: number;
  /** Verification attempts allowed (1-10). Default 5. */
  maxAttempts?: number;
  deviceId?: number;
  simSlot?: string;
}

export interface VerifyOtpOptions {
  phone: string;
  /** Code the user typed. */
  code: string;
}

export interface ConfigureOptions {
  /**
   * SMS8 API key. Required before any send/verify call. Get one free at
   * https://sms8.io. **Production: proxy via your backend, do not embed.**
   */
  apiKey: string;
  /** Override base URL. Default https://mcp.sms8.io */
  baseUrl?: string;
}

export interface SendResult {
  success: boolean;
  messageId?: number;
  phone?: string;
  error?: string;
}

export interface SendOtpResult {
  success: boolean;
  otpId?: number;
  phone?: string;
  expiresAt?: string;
  expiresIn?: number;
  error?: string;
}

export interface VerifyOtpResult {
  success: boolean;
  verified?: boolean;
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
  /** Filter to one phone (E.164). */
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

export interface SmsOtpPlugin {
  /** Set the API key + base URL once before any other call. */
  configure(options: ConfigureOptions): Promise<void>;

  /** Send a plain SMS through your paired Android phone. */
  sendSms(options: SendSmsOptions): Promise<SendResult>;

  /** Send a verification code. */
  sendOtp(options: SendOtpOptions): Promise<SendOtpResult>;

  /** Verify the code the user typed. */
  verifyOtp(options: VerifyOtpOptions): Promise<VerifyOtpResult>;

  /** List paired Android devices and their IDs. */
  listDevices(): Promise<ListDevicesResult>;

  /** Fetch recent SMS messages (inbox + sent). */
  getMessages(options?: GetMessagesOptions): Promise<GetMessagesResult>;

  /** Account credit + expiry summary. */
  getBalance(): Promise<GetBalanceResult>;
}
