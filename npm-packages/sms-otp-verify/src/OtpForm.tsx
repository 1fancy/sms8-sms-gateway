import { CSSProperties, useState } from 'react';
import { useSms8Otp } from './react';
import { OtpInput, OtpInputProps } from './OtpInput';
import { SendOptions } from './index';

export interface OtpFormProps {
  /** SMS8 API key from app.sms8.io → Profile → API. Free at https://sms8.io. */
  apiKey: string;

  /** Pre-fill the phone field. If omitted, the form shows a phone input. */
  phone?: string;

  /** Code length 4-8. Default 6. */
  length?: number;

  /** Template, expiry, max attempts — forwarded to send_otp. */
  sendOptions?: Omit<SendOptions, 'phone' | 'length'>;

  /** Called when the code verifies successfully. */
  onVerified?: (phone: string) => void;

  /** Called when the user submits a phone but verify fails after N attempts. */
  onFailed?: (error: string) => void;

  /**
   * Pass-through props for the inner `<OtpInput />` — separators, RTL, mask,
   * custom render, styles, classNames. Anything OtpInput accepts except
   * `value`, `onChange`, `onComplete`, `length` (managed by the form).
   */
  otpInputProps?: Omit<OtpInputProps, 'value' | 'onChange' | 'onComplete' | 'length'>;

  /** Custom labels for the UI strings. */
  labels?: Partial<{
    phone: string;
    sendButton: string;
    sending: string;
    code: string;
    verifyButton: string;
    verifying: string;
    sent: string;
    verified: string;
    resend: string;
  }>;

  /** Inline style for the outer container. */
  style?: CSSProperties;

  /** Override base URL (rarely needed). */
  baseUrl?: string;
}

const defaultLabels = {
  phone: 'Phone number',
  sendButton: 'Send code',
  sending: 'Sending…',
  code: 'Enter the 6-digit code',
  verifyButton: 'Verify',
  verifying: 'Verifying…',
  sent: 'Code sent. Check your phone.',
  verified: 'Verified.',
  resend: 'Resend code',
};

/**
 * Drop-in OTP verification form. Renders phone input → send → 6-box code
 * input → verify. Calls `onVerified` once the code matches.
 *
 * @example
 *   <OtpForm
 *     apiKey={process.env.NEXT_PUBLIC_SMS8_API_KEY!}
 *     onVerified={(phone) => router.push('/dashboard')}
 *   />
 *
 * For a fully custom UI, use the `useSms8Otp()` hook directly.
 */
export function OtpForm({
  apiKey,
  phone: phoneProp,
  length = 6,
  sendOptions,
  onVerified,
  onFailed,
  otpInputProps,
  labels: l,
  style,
  baseUrl,
}: OtpFormProps) {
  const L = { ...defaultLabels, ...l };
  const { status, send, verify, error, reset } = useSms8Otp({ apiKey, baseUrl });

  const [phone, setPhone] = useState(phoneProp ?? '');
  const [code, setCode] = useState('');

  const isSending  = status === 'sending';
  const isVerifying = status === 'verifying';
  const isVerified = status === 'verified';
  const isSent     = status === 'sent' || status === 'verifying' || status === 'error';

  const wrap: CSSProperties = {
    display: 'flex', flexDirection: 'column', gap: 12, maxWidth: 360,
    fontFamily: 'Inter, system-ui, sans-serif', ...style,
  };
  const input: CSSProperties = {
    padding: '10px 12px', fontSize: 16, border: '1px solid #d4d4d8',
    borderRadius: 8, outline: 'none', fontFamily: 'inherit',
  };
  const btn: CSSProperties = {
    padding: '11px 18px', fontSize: 15, fontWeight: 600, color: '#fff',
    background: '#6366f1', border: 'none', borderRadius: 8, cursor: 'pointer',
    transition: 'opacity 0.15s',
  };
  const btnDisabled: CSSProperties = { ...btn, opacity: 0.6, cursor: 'not-allowed' };
  const linkBtn: CSSProperties = {
    background: 'transparent', border: 'none', color: '#6366f1', cursor: 'pointer',
    fontSize: 13, padding: 0, textAlign: 'left', fontFamily: 'inherit',
  };
  const errStyle: CSSProperties = { color: '#b91c1c', fontSize: 13 };
  const okStyle: CSSProperties  = { color: '#15803d', fontSize: 13 };

  const handleSend = async () => {
    if (!phone) return;
    await send({ phone, length, ...(sendOptions || {}) });
  };

  const handleVerify = async (typedCode?: string) => {
    const c = (typedCode ?? code).trim();
    if (!c || !phone) return;
    const result = await verify({ phone, code: c });
    if (result.verified) {
      onVerified?.(phone);
    } else if (!result.success) {
      onFailed?.(result.error || 'Verify failed');
    }
  };

  if (isVerified) {
    return (
      <div style={wrap}>
        <p style={okStyle}>✓ {L.verified}</p>
      </div>
    );
  }

  return (
    <div style={wrap}>
      {!isSent && (
        <>
          {!phoneProp && (
            <label>
              <div style={{ fontSize: 13, fontWeight: 500, marginBottom: 4 }}>{L.phone}</div>
              <input
                type="tel"
                inputMode="tel"
                autoComplete="tel"
                placeholder="+14155550100"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                style={input}
              />
            </label>
          )}
          <button onClick={handleSend} disabled={!phone || isSending} style={(!phone || isSending) ? btnDisabled : btn}>
            {isSending ? L.sending : L.sendButton}
          </button>
        </>
      )}

      {isSent && (
        <>
          <div style={{ fontSize: 13, fontWeight: 500 }}>{L.code}</div>
          <OtpInput
            {...otpInputProps}
            length={length}
            value={code}
            onChange={setCode}
            onComplete={(c) => handleVerify(c)}
            disabled={isVerifying || otpInputProps?.disabled}
            error={status === 'error' || otpInputProps?.error}
          />
          <button onClick={() => handleVerify()} disabled={code.length !== length || isVerifying} style={(code.length !== length || isVerifying) ? btnDisabled : btn}>
            {isVerifying ? L.verifying : L.verifyButton}
          </button>
          <button onClick={() => { reset(); setCode(''); }} style={linkBtn} type="button">
            {L.resend}
          </button>
          <p style={{ fontSize: 12, color: '#71717a' }}>{L.sent}</p>
        </>
      )}

      {error && <p style={errStyle}>{error}</p>}
    </div>
  );
}
