# SMS8 Gateway — iMessage and SMS for your Mac

**Send and receive iMessage and SMS from GoHighLevel, Zapier, the SMS8 API and webhooks.** SMS8 Gateway is a small native macOS app that links a Mac to your [SMS8](https://sms8.io) account and turns it into an iMessage device. It is a self-hosted alternative to Sendblue, LoopMessage, Sendara, Blooio and Sent.dm, with no per-message iMessage fees.

> Part of [SMS8.io](https://sms8.io) — the SMS gateway that runs on your own phone. This folder is the Apple side: iMessage through a Mac. The [Android app](https://sms8.io/android-sms-gateway) is the SIM side.

## What it does

- **Send iMessage via API** — deliver blue-bubble iMessage from GoHighLevel, Zapier, the SMS8 REST API, or a webhook.
- **Automatic SMS fallback** — when iMessage can't deliver (for example an Android number), the app sends it as a text message through a paired iPhone instead. Pick **Auto** (iMessage, fall back to SMS), **iMessage only**, or **SMS only**.
- **Two-way** — replies (iMessage and SMS) flow back into your SMS8 inbox and your webhooks, threaded by contact.
- **iMessage to email or phone** — send to an iPhone number or an iCloud/Apple email address.
- **One account for iMessage and Android SMS** — one inbox, one API, one flat plan.
- **Bring your own Mac** (with a paired iPhone for SMS), or run a hosted Mac.

Full product page: **https://sms8.io/send-imessage-api**

## Download and install

1. **[Download SMS8-Gateway-Installer.dmg](./SMS8-Gateway-Installer.dmg)** (macOS Ventura 13 or later).
2. Open the DMG and drag **SMS8 Gateway** to Applications.
3. Open the app, enter the 6-character pairing code from your [SMS8 Devices page](https://app.sms8.io/devices).
4. Grant the two macOS permissions it asks for, send a test, start the bridge.

No SMS8 account yet? [Create one free](https://app.sms8.io/register).

## How it works

SMS8's API is device-agnostic. A device pairs with a code, polls for queued messages, sends them, and reports inbound. SMS8 Gateway is "just another device" that sends over iMessage instead of a SIM. Nothing changes server-side — it uses the same pairing, the same inbox, the same webhooks as the Android app.

```
Your SMS8 account  ──queued message──►  SMS8 Gateway (Mac)  ──►  Messages.app  ──►  iMessage (blue)
        ▲                                      │                                └─ fallback ─►  SMS (green, via paired iPhone)
        └────────── reply ◄── chat.db ◄────────┘
```

**Auto mode:** the app sends over iMessage first and verifies delivery against the Messages database. If iMessage didn't deliver (Apple's `send` never reports this directly, but the failure is recorded locally), it automatically resends the message as an SMS through the paired iPhone's SIM (Text Message Forwarding). SMS fallback needs an iPhone paired to the Mac and Full Disk Access granted so the app can read the delivery result.

## Build from source

The app is a single SwiftUI file, compiled without Xcode:

```bash
cd imessage
./build.sh        # runs: swiftc -O -parse-as-library main.swift ...
```

See [`main.swift`](./main.swift) for the full source. It uses:
- AppleScript to drive Messages.app, addressing the concrete iMessage or SMS service by id
- `~/Library/Messages/chat.db` (with Full Disk Access) to verify delivery and receive replies
- The SMS8 `/services/v-ios/` device contract (isolated from the Android app)

## iMessage vs SMS

- **iMessage** reaches Apple users (iPhone, iPad, Mac) as blue bubbles. Works on any Mac signed into an Apple ID.
- **SMS to Android** is sent as a green text message through a **paired iPhone** (same Apple ID, Text Message Forwarding on). The Mac has no SIM of its own, so the iPhone relays the SMS over its carrier. **Auto** mode uses this automatically when iMessage can't deliver.
- **No iPhone attached?** Use **iMessage only** on the Mac, and the [SMS8 Android app](https://sms8.io/android-sms-gateway) for SMS — same account, same inbox.
- **Group chats, voice notes and RCS** require a hosted-Mac / BlueBubbles setup (advanced).

## SMS8 vs other iMessage APIs

Most iMessage APIs charge 60 to 1,000 USD per line every month, only do iMessage, and run the Mac for you so you never own it. SMS8 is a flat plan, adds Android SMS on the same account, and lets you bring your own Mac.

| | SMS8 Gateway | Sendblue | LoopMessage | Sendara | Blooio |
|---|---|---|---|---|---|
| Starting price | **14.50 USD / mo** | ~100 USD / line | ~60 USD / mo | Usage based | Usage based |
| Send iMessage via API | ✅ | ✅ | ✅ | ✅ | ✅ |
| Receive replies | ✅ | ✅ | ✅ | ✅ | ✅ |
| No per-message fee | ✅ | ❌ | ❌ | ❌ | ❌ |
| Automatic SMS fallback | ✅ | ❌ | ❌ | ❌ | ❌ |
| Android SMS on same account | ✅ | ❌ | ❌ | ❌ | ❌ |
| Bring your own Mac | ✅ | ❌ | ✅ | ❌ | ❌ |
| GoHighLevel native provider | ✅ | ❌ | ❌ | ❌ | ❌ |
| One inbox for iMessage + SMS | ✅ | ❌ | ❌ | ❌ | ❌ |

A self-hosted **Sendblue alternative**, **LoopMessage alternative**, **Sendara alternative** and **Blooio alternative** that also handles Android SMS. Full comparison: **https://sms8.io/send-imessage-api**

## Use cases

- GoHighLevel agencies that want blue-bubble iMessage to their Apple contacts
- Zapier and Make automations that send iMessage on a trigger
- Developers who need an iMessage API without a per-message bill
- Teams replacing Sendblue, LoopMessage, Sendara, Blooio or Sent.dm with a self-hosted option

## Links

- Product page: https://sms8.io/send-imessage-api
- GoHighLevel SMS: https://sms8.io/gohighlevel-sms-integration
- Android SMS gateway: https://sms8.io/android-sms-gateway
- Pricing: https://sms8.io/pricing
- Dashboard: https://app.sms8.io

## License

See the repository [LICENSE](../LICENSE).
