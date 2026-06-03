import { useCallback, useMemo, useState } from 'react';
import { Sms8Otp, OtpStatus, SendOptions, VerifyOptions, SendResult, VerifyResult, Sms8OtpOptions } from './index';

export interface UseSms8OtpReturn {
  /** Current status of the OTP flow. */
  status: OtpStatus;
  /** Last result returned by the send call. */
  sendResult: SendResult | null;
  /** Last result returned by the verify call. */
  verifyResult: VerifyResult | null;
  /** First error message encountered. Cleared on each new send/verify. */
  error: string | null;
  /** Trigger a send_otp call. Resolves to the result, also stored in `sendResult`. */
  send: (opts: SendOptions) => Promise<SendResult>;
  /** Trigger a verify_otp call. Resolves to the result, also stored in `verifyResult`. */
  verify: (opts: VerifyOptions) => Promise<VerifyResult>;
  /** Reset state back to idle. Call this when the user re-enters the form. */
  reset: () => void;
}

/**
 * React hook for the SMS8 OTP flow.
 *
 * @example
 *   const { send, verify, status, error } = useSms8Otp({ apiKey });
 *
 *   <button disabled={status === 'sending'}
 *           onClick={() => send({ phone })}>
 *     Send code
 *   </button>
 *   {status === 'sent' && (
 *     <input onBlur={(e) => verify({ phone, code: e.target.value })} />
 *   )}
 *   {error && <p style={{color:'red'}}>{error}</p>}
 *
 * Free SMS8 account at https://sms8.io (5-day trial, no card).
 */
export function useSms8Otp(opts: Sms8OtpOptions): UseSms8OtpReturn {
  const client = useMemo(() => new Sms8Otp(opts), [opts.apiKey, opts.baseUrl]);

  const [status, setStatus] = useState<OtpStatus>('idle');
  const [sendResult, setSendResult] = useState<SendResult | null>(null);
  const [verifyResult, setVerifyResult] = useState<VerifyResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  const send = useCallback(async (sendOpts: SendOptions): Promise<SendResult> => {
    setStatus('sending');
    setError(null);
    try {
      const result = await client.send(sendOpts);
      setSendResult(result);
      if (result.success) {
        setStatus('sent');
      } else {
        setStatus('error');
        setError(result.error || 'Send failed');
      }
      return result;
    } catch (e: any) {
      const msg = e?.message || String(e);
      setStatus('error');
      setError(msg);
      const failed = { success: false, error: msg };
      setSendResult(failed);
      return failed;
    }
  }, [client]);

  const verify = useCallback(async (verifyOpts: VerifyOptions): Promise<VerifyResult> => {
    setStatus('verifying');
    setError(null);
    try {
      const result = await client.verify(verifyOpts);
      setVerifyResult(result);
      if (result.verified) {
        setStatus('verified');
      } else {
        setStatus('error');
        setError(result.error || 'Code did not match');
      }
      return result;
    } catch (e: any) {
      const msg = e?.message || String(e);
      setStatus('error');
      setError(msg);
      const failed = { success: false, verified: false, error: msg };
      setVerifyResult(failed);
      return failed;
    }
  }, [client]);

  const reset = useCallback(() => {
    setStatus('idle');
    setSendResult(null);
    setVerifyResult(null);
    setError(null);
  }, []);

  return { status, sendResult, verifyResult, error, send, verify, reset };
}
