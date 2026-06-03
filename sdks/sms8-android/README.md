# SMS8 Android SDK — Kotlin SMS & OTP, a free Twilio Verify alternative

[![](https://jitpack.io/v/1fancy/sms8-android.svg)](https://jitpack.io/#1fancy/sms8-android)
[![Kotlin 1.9+](https://img.shields.io/badge/Kotlin-1.9%2B-7F52FF)](https://kotlinlang.org)
[![Min SDK 21](https://img.shields.io/badge/minSdk-21-3DDC84)](https://developer.android.com/about/versions/lollipop)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Send SMS, send and verify OTP codes from your Android app** with **SMS Retriever auto-fill** out of the box. Routes through your own paired Android phone via the SMS8 platform. **No Twilio, no Vonage Verify, no MessageBird. No per-OTP fees, no markups, no A2P 10DLC.**

```kotlin
val sms8 = SMS8(apiKey = BuildConfig.SMS8_API_KEY)

lifecycleScope.launch {
    val sent   = sms8.sendOTP(phone = "+14155550100")
    val result = sms8.verifyOTP(phone = "+14155550100", code = "482937")
    if (result.verified == true) { /* allow login */ }
}
```

Free 5-day trial at **[sms8.io](https://sms8.io)** — no credit card.

---

## Why this instead of Twilio Verify?

| | Twilio Verify / Vonage Verify | SMS8 Android SDK |
|---|---|---|
| Per-OTP cost | $0.05 – $0.10 each | $0 (flat $29/mo unlimited) |
| A2P 10DLC paperwork | Required in the US | Not required — P2P SMS from real SIM |
| SMS auto-fill | Requires extra setup | Built in via Google SMS Retriever |
| Sender ID | Short code / random LCN | Your real mobile number |
| Setup time | Days | Minutes |
| Free trial | None — pay from message 1 | 5 days unlimited, no card required |

## Install with JitPack

Add the JitPack repo in your **root** `settings.gradle.kts`:

```kotlin
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        maven { url = uri("https://jitpack.io") }   // <-- add this
    }
}
```

Then in your **app** `build.gradle.kts`:

```kotlin
dependencies {
    implementation("com.github.1fancy:sms8-android:1.0.0")
}
```

Min SDK 21 (Android 5.0 Lollipop).

## Quick start

### 1. Get a free API key

1. Sign up at [sms8.io](https://sms8.io) — 5-day trial, no card
2. Install the [SMS8 Android app](https://sms8.io/sms-gateway-apk-android) on any Android phone, pair it
3. Copy your API key from [app.sms8.io/api.php](https://app.sms8.io/api.php)

### 2. Send + verify an OTP

```kotlin
import io.sms8.SMS8
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {
    private val sms8 = SMS8(apiKey = BuildConfig.SMS8_API_KEY)

    fun onSendClick(phone: String) {
        lifecycleScope.launch {
            val r = sms8.sendOTP(phone = phone)
            // r.otpId, r.expiresAt available
        }
    }

    fun onVerifyClick(phone: String, code: String) {
        lifecycleScope.launch {
            val r = sms8.verifyOTP(phone = phone, code = code)
            if (r.verified == true) {
                startActivity(Intent(this@LoginActivity, HomeActivity::class.java))
            } else {
                Toast.makeText(this@LoginActivity, "Bad code", Toast.LENGTH_SHORT).show()
            }
        }
    }
}
```

### 3. Plain SMS

```kotlin
sms8.sendSMS(phone = "+14155550100", message = "Welcome aboard!")
```

## SMS Retriever auto-fill — no SMS_READ permission

The SDK wraps Google's SMS Retriever API so codes auto-fill **without asking for SMS permission**.

### 1. Format the OTP SMS

The SMS body must start with `<#>` and end with your **app signing-cert hash** (11 chars). Use SMS8's `template` argument:

```kotlin
sms8.sendOTP(
    phone    = "+14155550100",
    template = "<#> Your YourApp code: {code}\n\nAbCd1234efg"   // <-- replace with your hash
)
```

Generate the hash with Google's [`AppSignatureHelper`](https://developers.google.com/identity/sms-retriever/verify#computing_your_apps_hash_string) (~30 lines of Kotlin).

### 2. Listen for the SMS

```kotlin
import io.sms8.autofill.SMSRetriever

SMSRetriever.start(this,
    onCode  = { code ->
        codeEditText.setText(code)
        // auto-verify immediately
        lifecycleScope.launch {
            val r = sms8.verifyOTP(phone, code)
            if (r.verified == true) goToHome()
        }
    },
    onError = { reason -> Log.w("SMS8", "auto-fill: $reason") }
)
```

Times out after 5 minutes. The user never sees a permission prompt.

## API reference

### `SMS8(apiKey: String, baseUrl: String = "https://mcp.sms8.io")`

Create the client. Stores the key in memory only.

### `suspend fun sendSMS(phone, message, deviceId?, simSlot?)`

Send a plain SMS. `deviceId` pins a specific paired Android; `simSlot` picks a SIM on multi-SIM phones.

### `suspend fun sendOTP(phone, length=6, template?, expiresIn=300, maxAttempts=5, deviceId?, simSlot?)`

Send a verification code. Returns `SendOTPResult(success, otpId, expiresAt, expiresIn, error)`.

### `suspend fun verifyOTP(phone, code)`

Verify the code. Returns `VerifyOTPResult(success, verified, error, attemptsLeft)`.

### `object SMSRetriever`

- `start(activity, onCode, onError)` — start the Retriever and call `onCode(String)` when a matching SMS arrives.

## Recommended pattern: keep the API key OFF the device

Don't ship your master API key in production apps. Proxy through your backend:

```kotlin
// App side
val res = client.post("https://your.app/api/send-otp") { setBody("""{"phone":"$phone"}""") }
```

```kotlin
// Backend side (Ktor, Spring Boot, etc.) — use the same SDK
val sms8 = SMS8(apiKey = System.getenv("SMS8_API_KEY"))
val r = sms8.sendOTP(phone = phone)
```

For development / prototyping, embedding the key in `local.properties` is fine.

## Use cases

- **Phone-verified signup / login** (Tinder-style)
- **2FA on sensitive actions** (Stripe payments, password reset)
- **Tournament / event check-in** without an account
- **WhatsApp-style account recovery** without email magic links
- **High-value in-app purchases** confirmed with a fresh code

## Companion packages

- [react-sms-otp](https://www.npmjs.com/package/react-sms-otp) — React hooks + components
- [sms8-cli](https://www.npmjs.com/package/sms8-cli) — terminal CLI
- [sms8-swift](https://github.com/1fancy/sms8-swift) — iOS Swift SDK
- [sms8-mcp](https://www.npmjs.com/package/sms8-mcp) — MCP launcher for Claude / Cursor / Windsurf

## Links

- **Free signup**: [sms8.io](https://sms8.io)
- **Dashboard**: [app.sms8.io](https://app.sms8.io)
- **OTP API docs**: [sms8.io/sms-otp-verification-api-android](https://sms8.io/sms-otp-verification-api-android)
- **GitHub**: [github.com/1fancy/sms8-android](https://github.com/1fancy/sms8-android)
- **JitPack**: [jitpack.io/#1fancy/sms8-android](https://jitpack.io/#1fancy/sms8-android)

## License

MIT
