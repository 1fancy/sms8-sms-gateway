package io.sms8.autofill

import android.app.Activity
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import androidx.core.content.ContextCompat
import com.google.android.gms.auth.api.phone.SmsRetriever
import com.google.android.gms.common.api.CommonStatusCodes
import com.google.android.gms.common.api.Status

/**
 * One-call wrapper around Google's SMS Retriever API for auto-filling SMS
 * verification codes without the SMS_READ permission.
 *
 * Requirements:
 *   - Your OTP SMS body must end with the 11-character hash of your app's
 *     signing certificate. SMS8 supports this via the `template` argument:
 *
 *     sms8.sendOTP(
 *         phone = phone,
 *         template = "<#> Your code: {code}\n\nAbCd1234efg"
 *     )
 *
 *   - Generate the hash with `AppSignatureHelper` (Google sample code).
 *   - The phone running your Android app + the SIM SMS8 sends from can be
 *     different devices.
 *
 * Usage in an Activity / Fragment:
 *
 *   SMSRetriever.start(this) { code ->
 *       editText.setText(code)
 *       lifecycleScope.launch {
 *           val r = sms8.verifyOTP(phone, code)
 *           if (r.verified == true) { ... }
 *       }
 *   }
 */
object SMSRetriever {

    private const val EXTRACT = """\b\d{4,8}\b"""

    /**
     * Start the SMS Retriever. The callback fires once with the extracted code
     * (4–8 digits) when an SMS arrives that ends with your app hash. Times out
     * after 5 minutes; you can call `start` again to retry.
     *
     * @param activity the host activity (usually `this`)
     * @param onCode called on the main thread with the extracted code
     * @param onError called if the retriever times out or fails
     */
    fun start(
        activity: Activity,
        onCode:  (code: String) -> Unit,
        onError: (reason: String) -> Unit = {},
    ) {
        val client = SmsRetriever.getClient(activity)
        client.startSmsRetriever()
            .addOnSuccessListener {
                registerReceiver(activity, onCode, onError)
            }
            .addOnFailureListener { e ->
                onError(e.message ?: "SMS Retriever start failed")
            }
    }

    private fun registerReceiver(
        activity: Activity,
        onCode:  (code: String) -> Unit,
        onError: (reason: String) -> Unit,
    ) {
        val receiver = object : BroadcastReceiver() {
            override fun onReceive(context: Context, intent: Intent) {
                if (intent.action != SmsRetriever.SMS_RETRIEVED_ACTION) return
                val extras = intent.extras ?: return
                val status = extras.get(SmsRetriever.EXTRA_STATUS) as? Status ?: return
                when (status.statusCode) {
                    CommonStatusCodes.SUCCESS -> {
                        val message = extras.getString(SmsRetriever.EXTRA_SMS_MESSAGE) ?: ""
                        val match = Regex(EXTRACT).find(message)
                        if (match != null) {
                            onCode(match.value)
                        } else {
                            onError("No code found in SMS body")
                        }
                    }
                    CommonStatusCodes.TIMEOUT -> onError("SMS Retriever timed out (5 min)")
                    else                      -> onError("SMS Retriever failed: ${status.statusMessage ?: status.statusCode}")
                }
                try { activity.unregisterReceiver(this) } catch (_: Exception) {}
            }
        }
        val filter = IntentFilter(SmsRetriever.SMS_RETRIEVED_ACTION)
        ContextCompat.registerReceiver(
            activity, receiver, filter,
            ContextCompat.RECEIVER_EXPORTED
        )
    }
}
