# sms-otp-verify — React SMS OTP verification + input UI, free Twilio Verify alternative

[![npm version](https://img.shields.io/npm/v/sms-otp-verify)](https://www.npmjs.com/package/sms-otp-verify)
[![npm downloads](https://img.shields.io/npm/dm/sms-otp-verify)](https://www.npmjs.com/package/sms-otp-verify)
[![bundle size](https://img.shields.io/bundlephobia/minzip/sms-otp-verify?label=minzip)](https://bundlephobia.com/package/sms-otp-verify)
[![TypeScript](https://img.shields.io/badge/TypeScript-strict-3178c6)](https://www.typescriptlang.org/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send, verify, and auto-fill SMS OTP codes in React** — with a **ready-to-use `<OtpInput />` UI** (separators, RTL, mask, render props, error states), a **`<OtpForm />` drop-in** for the full phone-verification flow, and the **`useSms8Otp()` hook** for custom UIs.

```tsx
import { OtpForm } from 'sms-otp-verify';

<OtpForm
  apiKey={process.env.NEXT_PUBLIC_SMS8_API_KEY!}
  onVerified={(phone) => router.push('/dashboard')}
/>
```

**No Twilio, no Vonage Verify, no MessageBird, no per-OTP fees.** Routes through your own paired Android phone via SMS8. Free 5-day trial at **[sms8.io](https://sms8.io)** — no credit card.

---

## Why this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | sms-otp-verify + SMS8 |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 each | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required for any US OTP volume | Not required — P2P SMS from a real SIM |
| Sender ID | Short code or random LCN | Your real mobile number |
| Built-in `<OtpInput />` UI | No — bring your own | Yes — ready to use |
| Free trial | Pay-as-you-go from message 1 | 5 days unlimited, no card required |

## Install

```bash
npm install sms-otp-verify
# or
pnpm add sms-otp-verify
# or
yarn add sms-otp-verify
```

Works with **React 17+** including Next.js 13/14/15 (App Router + Pages Router), Remix, Vite, CRA.

## The OTP input UI everyone copy-pastes

`<OtpInput />` is the **ready-to-use OTP input UI** at feature parity with [react-otp-input](https://www.npmjs.com/package/react-otp-input) — and you can use it on its own even if you already have Twilio, Auth0, Supabase, or Clerk doing the actual verify:

- **Auto-fill** — iOS "From Messages" suggestion + Android SMS Retriever
- **Paste** — pasting a 6-digit code anywhere fills all boxes
- **Keyboard nav** — Backspace, Arrows, Home/End, Delete
- **Custom separators** — `123-456` style
- **RTL layout** — Arabic, Hebrew
- **Mask mode** — `••••••` instead of digits
- **Error states** — red border + `aria-invalid`
- **Render-prop** — full Tailwind / shadcn / styled-components control
- **TypeScript-strict** with `OtpInputProps`, `OtpInputState`, `OtpRenderInputProps`

```tsx
import { OtpInput } from 'sms-otp-verify';

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

With separator and Tailwind styling:

```tsx
<OtpInput
  length={6}
  value={code}
  onChange={setCode}
  renderSeparator={(i) => i === 2 ? <span className="mx-1">—</span> : null}
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

RTL + mask:

```tsx
<OtpInput length={6} value={code} onChange={setCode} rtl mask />
```

## Quick start: full send+verify flow

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — 5-day trial, no card
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android), pair your phone
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Drop in the full form

```tsx
import { OtpForm } from 'sms-otp-verify';

export default function LoginPage() {
  return (
    <OtpForm
      apiKey={process.env.NEXT_PUBLIC_SMS8_API_KEY!}
      onVerified={(phone) => { /* create session, redirect, etc. */ }}
    />
  );
}
```

That's the entire phone-verification flow: phone input → send → 6-box code input → verify → success state.

> **Production tip:** never ship your API key to the browser. Proxy SMS8 through your backend (Next.js route handler, Remix loader, Express endpoint) using the `Sms8Otp` class on the server.

## Custom flow with `useSms8Otp()`

```tsx
import { useSms8Otp, OtpInput } from 'sms-otp-verify';

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

## Server-side / non-React: `Sms8Otp` class

```tsx
// app/api/send-otp/route.ts (Next.js App Router)
import { Sms8Otp } from 'sms-otp-verify';

export async function POST(req: Request) {
  const { phone } = await req.json();
  const otp = new Sms8Otp({ apiKey: process.env.SMS8_API_KEY! });
  const result = await otp.send({ phone, length: 6 });
  return Response.json(result);
}
```

## Common use cases

- **Phone-verified signup / login** — replace email-only auth
- **2FA on sensitive actions** — re-verify on password change or payment
- **Account recovery** — send a code instead of an email magic link
- **Marketplace listing trust** — verify sellers before allowing posts
- **CI integration tests** — automate phone-verification end-to-end
- **Internal break-glass tools** — confirm admin actions with a code

## Related packages

- [`react-sms-otp`](https://www.npmjs.com/package/react-sms-otp) — same code, alternate name
- [`sms8-cli`](https://www.npmjs.com/package/sms8-cli) — terminal CLI
- [`sms-otp-using-myphone`](https://www.npmjs.com/package/sms-otp-using-myphone) — OTP-focused CLI
- [`sms8-mcp`](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude Code / Cursor / Windsurf

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **OTP API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **GitHub**: [github.com/1fancy/sms8-sms-gateway](https://github.com/1fancy/sms8-sms-gateway)

## License

MIT
