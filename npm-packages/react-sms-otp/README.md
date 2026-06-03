# react-sms-otp — SMS OTP for React, a free Twilio Verify alternative

[![npm version](https://img.shields.io/npm/v/react-sms-otp)](https://www.npmjs.com/package/react-sms-otp)
[![npm downloads](https://img.shields.io/npm/dm/react-sms-otp)](https://www.npmjs.com/package/react-sms-otp)
[![bundle size](https://img.shields.io/bundlephobia/minzip/react-sms-otp?label=minzip)](https://bundlephobia.com/package/react-sms-otp)
[![TypeScript](https://img.shields.io/badge/TypeScript-strict-3178c6)](https://www.typescriptlang.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send and verify SMS OTP codes from React** — `useSms8Otp()` hook, `<OtpForm />` drop-in, and `<OtpInput />` six-box code field. Routes through your own Android phone. **No Twilio, no Vonage Verify, no MessageBird Verify. No per-OTP fees. No markups.**

```tsx
import { OtpForm } from 'react-sms-otp';

<OtpForm
  apiKey={process.env.NEXT_PUBLIC_SMS8_API_KEY!}
  onVerified={(phone) => router.push('/dashboard')}
/>
```

Get a free API key at **[sms8.io](https://sms8.io)** — 5-day trial, no credit card.

---

## Why use this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | react-sms-otp + SMS8 |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 each | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required for any US OTP volume | Not required — P2P SMS from a real SIM |
| Sender ID | Short code or random LCN | Your real mobile number |
| Reply support | Extra fees, harder to wire up | Built-in — replies sync into your dashboard |
| Setup time | Days (brand registration, A2P review) | Minutes (pair Android, paste API key) |
| Free trial | Pay-as-you-go from message 1 | 5 days unlimited, no card required |

## Install

```bash
npm install react-sms-otp
# or
pnpm add react-sms-otp
# or
yarn add react-sms-otp
```

Works with **React 17+**, including Next.js 13/14/15 (App Router or Pages Router), Remix, Vite, CRA. Server-side calls (route handlers, server actions, edge functions) can use the `Sms8Otp` class without React.

## Quick start

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — no card required, 5-day trial
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Drop in the form

```tsx
import { OtpForm } from 'react-sms-otp';

export default function LoginPage() {
  return (
    <OtpForm
      apiKey={process.env.NEXT_PUBLIC_SMS8_API_KEY!}
      onVerified={(phone) => {
        console.log('Verified:', phone);
        // redirect, create session, etc.
      }}
    />
  );
}
```

That's the entire phone-verification flow. The form handles phone input → send → 6-box code input → verify → success state. iOS SMS auto-fill and Android SMS Retriever work because the inputs use `autoComplete="one-time-code"` and `inputMode="numeric"`.

> **Tip:** in production, never expose your API key to the browser. Proxy through your backend (Next.js route handler, Remix loader, Express endpoint) and call the [Sms8Otp class](#server-side-class) from there.

## API

### `useSms8Otp(opts)` hook — full control

```tsx
import { useSms8Otp, OtpInput } from 'react-sms-otp';

function CustomLogin() {
  const { send, verify, status, error, reset } = useSms8Otp({
    apiKey: process.env.NEXT_PUBLIC_SMS8_API_KEY!,
  });

  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');

  return (
    <>
      <input
        type="tel"
        autoComplete="tel"
        value={phone}
        onChange={(e) => setPhone(e.target.value)}
      />
      <button onClick={() => send({ phone })} disabled={status === 'sending'}>
        {status === 'sending' ? 'Sending…' : 'Send code'}
      </button>

      {status === 'sent' && (
        <OtpInput
          length={6}
          value={code}
          onChange={setCode}
          onComplete={(c) => verify({ phone, code: c })}
        />
      )}

      {status === 'verified' && <p>Welcome!</p>}
      {error && <p style={{ color: 'red' }}>{error}</p>}
    </>
  );
}
```

Status values: `idle | sending | sent | verifying | verified | error`.

### `<OtpForm />` drop-in component

```tsx
<OtpForm
  apiKey="sk_xxx"
  phone="+14155550100"           // optional, hides the phone input
  length={6}                     // 4 to 8
  sendOptions={{
    template: 'Your YourApp code: {code}',
    expiresIn: 300,
    maxAttempts: 5,
    deviceId: 10700,              // pin a specific paired phone
    simSlot: '1',                 // multi-SIM Android
  }}
  onVerified={(phone) => { /* success */ }}
  onFailed={(error) => { /* retried too many times */ }}
  labels={{
    phone: 'Mobile number',
    sendButton: 'Text me a code',
    code: 'Enter the 6 digits',
    verifyButton: 'Confirm',
  }}
/>
```

### `<OtpInput />` ready-to-use OTP input UI

A polished N-box OTP input you can drop into **any** auth flow — even if you
already have your own send/verify logic with Twilio, Auth0, Supabase, or Clerk.
Use it standalone as a pure UI component.

**Features at parity with [react-otp-input](https://www.npmjs.com/package/react-otp-input) and more:**

- **Auto-fill** — `inputMode="numeric"` + `autoComplete="one-time-code"` triggers iOS "From Messages" suggestion
- **Paste** — pasting a 6-digit code anywhere fills all boxes
- **Keyboard nav** — Backspace, Arrow keys, Home/End, Delete all work
- **Custom separators** — `<OtpInput renderSeparator={(i) => i === 2 ? <span>-</span> : null} />`
- **RTL layout** — `rtl` prop swaps direction for Arabic / Hebrew
- **Mask mode** — `mask` shows `•` instead of digits (useful in shared screens)
- **Error states** — `error` prop adds red border, sets `aria-invalid`
- **Render-prop** — full control with `renderInput={(props, state) => ...}`
- **Styled variants** — `inputClassName`, `focusedClassName`, `filledClassName`, `errorClassName`
- **Accessible** — `aria-label` per box, `role="group"` on container
- **TypeScript-strict** with `OtpInputState`, `OtpInputProps`, `OtpRenderInputProps` types

Basic usage:

```tsx
import { OtpInput } from 'react-sms-otp';

<OtpInput
  length={6}
  value={code}
  onChange={setCode}
  onComplete={(c) => submitVerify(c)}
/>
```

With separator (e.g. `123-456` style):

```tsx
<OtpInput
  length={6}
  value={code}
  onChange={setCode}
  renderSeparator={(i) => i === 2 ? <span style={{margin:'0 4px'}}>—</span> : null}
/>
```

Masked / hidden code:

```tsx
<OtpInput length={6} value={code} onChange={setCode} mask />
```

RTL (Arabic, Hebrew):

```tsx
<OtpInput length={6} value={code} onChange={setCode} rtl />
```

Error state:

```tsx
<OtpInput length={6} value={code} onChange={setCode} error={!!verifyError} />
```

Full custom render with Tailwind / shadcn:

```tsx
<OtpInput
  length={6}
  value={code}
  onChange={setCode}
  renderInput={(props, { focused, error }) => (
    <input
      {...props}
      className={`
        w-12 h-14 text-center text-xl font-bold border-2 rounded-lg
        ${focused ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-gray-200'}
        ${error   ? 'border-red-500 bg-red-50' : ''}
      `}
    />
  )}
/>
```

Works with **your own send/verify** — drop it into a Clerk, Auth0, Supabase, or
custom-backed OTP flow:

```tsx
const [code, setCode] = useState('');

<OtpInput
  length={6}
  value={code}
  onChange={setCode}
  onComplete={async (c) => {
    const ok = await yourBackend.verifyCode(phone, c);
    if (ok) router.push('/dashboard');
  }}
/>
```

### `Sms8Otp` class — server-side / non-React

```tsx
// app/api/send-otp/route.ts (Next.js App Router)
import { Sms8Otp } from 'react-sms-otp';

export async function POST(req: Request) {
  const { phone } = await req.json();
  const otp = new Sms8Otp({ apiKey: process.env.SMS8_API_KEY! });
  const result = await otp.send({ phone, length: 6 });
  return Response.json(result);
}
```

## Recommended pattern: keep the API key on the server

```tsx
// Server route handler (Next.js, Remix, etc.)
import { Sms8Otp } from 'react-sms-otp';

const otp = new Sms8Otp({ apiKey: process.env.SMS8_API_KEY! });

export async function sendOtp(phone: string) {
  'use server';
  return otp.send({ phone, length: 6 });
}
```

```tsx
// Client component — call your server action, not SMS8 directly
'use client';
import { useState } from 'react';
import { OtpInput } from 'react-sms-otp';
import { sendOtp, verifyOtp } from './actions';

export function PhoneVerify() {
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [stage, setStage] = useState<'phone' | 'code'>('phone');

  return stage === 'phone' ? (
    <>
      <input value={phone} onChange={(e) => setPhone(e.target.value)} />
      <button onClick={async () => { await sendOtp(phone); setStage('code'); }}>
        Send code
      </button>
    </>
  ) : (
    <OtpInput
      length={6}
      value={code}
      onChange={setCode}
      onComplete={async (c) => {
        const result = await verifyOtp(phone, c);
        if (result.verified) router.push('/welcome');
      }}
    />
  );
}
```

## SMS auto-fill on iOS & Android

The `<OtpInput />` and `<OtpForm />` components both set:
- `autoComplete="one-time-code"` — triggers iOS SMS auto-fill suggestion above the keyboard
- `inputMode="numeric"` — shows the number pad on mobile
- `pattern="[0-9]*"` — extra hint for older keyboards

For Android SMS Retriever API (auto-read without user pasting), you need a server-side hash appended to the SMS body. SMS8 supports this via the `template` option:

```tsx
<OtpForm
  apiKey="sk_xxx"
  sendOptions={{
    template: '<#> Your YourApp code: {code}\n\nABcd1234efg', // <-- 11-char app hash at end
  }}
/>
```

See Google's [SMS Retriever docs](https://developers.google.com/identity/sms-retriever/overview) for hash generation.

## Use cases

- **Login / signup phone verification** — replace email-only auth with SMS step
- **2FA for sensitive actions** — re-verify on password change, payment, etc.
- **Account recovery** — text a code instead of the magic-link-from-email dance
- **Marketplace listing trust** — verify seller phone before allowing posts
- **Tournament / event registration** — confirm reachable phone before issuing tickets
- **Internal tools** — break-glass admin actions confirmed with an OTP

## Related packages

- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — terminal CLI (same backend)
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude Code / Cursor / Windsurf
- [`sms-otp-using-myphone`](https://www.npmjs.com/package/sms-otp-using-myphone) — OTP-focused CLI
- [`phone-sms-gateway`](https://www.npmjs.com/package/phone-sms-gateway) — phone-as-gateway CLI
- [`send-sms-from-android`](https://www.npmjs.com/package/send-sms-from-android) — Android-first CLI

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **Android app**: [sms8.io/sms-gateway-apk-android](https://sms8.io/sms-gateway-apk-android)
- **GitHub**: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
