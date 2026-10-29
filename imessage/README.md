# SMS8 Gateway — iMessage and SMS for your Mac

**Send and receive iMessage and SMS from GoHighLevel, Zapier, the SMS8 API and webhooks.** SMS8 Gateway is a small native macOS app that links a Mac to your [SMS8](https://sms8.io) account and turns it into an iMessage device. It is a self-hosted alternative to Sendblue, LoopMessage, Sendara, Blooio and Sent.dm, with no per-message iMessage fees.

> Part of [SMS8.io](https://sms8.io) — the SMS gateway that runs on your own phone. This folder is the Apple side: iMessage through a Mac. The [Android app](https://sms8.io/android-sms-gateway) is the SIM side.

## What it does

- **Send iMessage via API** — deliver blue-bubble iMessage from GoHighLevel, Zapier, the SMS8 REST API, or a webhook.
- **Two-way** — replies flow back into your SMS8 inbox and your webhooks, threaded by contact.
- **One account for iMessage and Android SMS** — one inbox, one API, one flat plan.
- **Bring your own Mac**, or run a hosted Mac for SMS to Android, group chats, voice notes and RCS.

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
Your SMS8 account  ──queued message──►  SMS8 Gateway (Mac)  ──►  Messages.app  ──►  iMessage
        ▲                                      │
        └────────── reply ◄── chat.db ◄────────┘
```

## Build from source

The app is a single SwiftUI file, compiled without Xcode:

```bash
cd imessage
./build.sh        # runs: swiftc -O -parse-as-library main.swift ...
```

See [`main.swift`](./main.swift) for the full source. It uses:
- AppleScript to drive Messages.app for iMessage send
- `~/Library/Messages/chat.db` (with Full Disk Access) for receive
- The SMS8 `/services/v-ios/` device contract (isolated from the Android app)

## iMessage vs SMS

- **iMessage** reaches Apple users (iPhone, iPad, Mac) as blue bubbles. Works on any paired Mac.
- **SMS to Android** needs a hosted Mac with the BlueBubbles bridge. On a local Mac, use the [SMS8 Android app](https://sms8.io/android-sms-gateway) for SMS.
- **Group chats, voice notes and RCS** are available on the hosted Mac setup.

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
