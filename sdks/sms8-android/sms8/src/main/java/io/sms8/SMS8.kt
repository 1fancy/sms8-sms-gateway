package io.sms8

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL

/**
 * SMS8 Android SDK — send and verify SMS OTP codes through your own Android phone.
 *
 * No Twilio, no Vonage Verify, no MessageBird. No per-OTP fees, no markups,
 * no A2P 10DLC. Free 5-day trial at https://sms8.io.
 *
 * ```kotlin
 * val sms8 = SMS8(apiKey = BuildConfig.SMS8_API_KEY)
 *
 * lifecycleScope.launch {
 *     val sent = sms8.sendOTP(phone = "+14155550100")
 *     // show OTP UI...
 *     val result = sms8.verifyOTP(phone = "+14155550100", code = "482937")
 *     if (result.verified == true) { /* allow login */ }
 * }
 * ```
 */
class SMS8(
    private val apiKey: String,
    private val baseUrl: String = "https://mcp.sms8.io",
) {
    init {
        require(apiKey.isNotBlank()) { "SMS8: apiKey must not be blank. Get one at https://sms8.io" }
    }

    data class SendOTPResult(
        val success: Boolean,
        val otpId: Int?,
        val phone: String?,
        val expiresAt: String?,
        val expiresIn: Int?,
        val error: String?,
    )

    data class VerifyOTPResult(
        val success: Boolean,
        val verified: Boolean?,
        val error: String?,
        val attemptsLeft: Int?,
    )

    // ── SMS ────────────────────────────────────────────────────────────
    suspend fun sendSMS(
        phone: String,
        message: String,
        deviceId: Int? = null,
        simSlot: String? = null,
    ): JSONObject = mcpCall("send_sms", JSONObject().apply {
        put("phone", phone)
        put("message", message)
        deviceId?.let { put("device_id", it) }
        simSlot?.let  { put("sim_slot",  it) }
    })

    // ── OTP send ───────────────────────────────────────────────────────
    suspend fun sendOTP(
        phone: String,
        length: Int = 6,
        template: String? = null,
        expiresIn: Int = 300,
        maxAttempts: Int = 5,
        deviceId: Int? = null,
        simSlot: String? = null,
    ): SendOTPResult {
        val args = JSONObject().apply {
            put("phone", phone)
            put("length", length)
            put("expires_in", expiresIn)
            put("max_attempts", maxAttempts)
            template?.let { put("template", it) }
            deviceId?.let  { put("device_id", it) }
            simSlot?.let   { put("sim_slot",  it) }
        }
        val out = mcpCall("send_otp", args)
        return SendOTPResult(
            success    = out.optBoolean("success", false),
            otpId      = out.optInt("otp_id").takeIf { out.has("otp_id") },
            phone      = out.optString("phone").takeIf { it.isNotEmpty() },
            expiresAt  = out.optString("expires_at").takeIf { it.isNotEmpty() },
            expiresIn  = out.optInt("expires_in").takeIf { out.has("expires_in") },
            error      = out.optString("error").takeIf { it.isNotEmpty() },
        )
    }

    // ── OTP verify ─────────────────────────────────────────────────────
    suspend fun verifyOTP(phone: String, code: String): VerifyOTPResult {
        val out = mcpCall("verify_otp", JSONObject().apply {
            put("phone", phone)
            put("code",  code)
        })
        return VerifyOTPResult(
            success      = out.optBoolean("success", false),
            verified     = out.optBoolean("verified").takeIf { out.has("verified") },
            error        = out.optString("error").takeIf { it.isNotEmpty() },
            attemptsLeft = out.optInt("attempts_left").takeIf { out.has("attempts_left") },
        )
    }

    // ── HTTP plumbing ──────────────────────────────────────────────────
    private suspend fun mcpCall(tool: String, args: JSONObject): JSONObject = withContext(Dispatchers.IO) {
        args.put("api_key", apiKey)
        val body = JSONObject().apply {
            put("jsonrpc", "2.0")
            put("method",  "tools/call")
            put("params",  JSONObject().apply {
                put("name", tool)
                put("arguments", args)
            })
            put("id", System.currentTimeMillis())
        }
        val url  = URL(baseUrl)
        val conn = (url.openConnection() as HttpURLConnection).apply {
            requestMethod = "POST"
            doOutput = true
            setRequestProperty("Content-Type", "application/json")
            setRequestProperty("Authorization", "Bearer $apiKey")
            setRequestProperty("User-Agent", "sms8-android/1.0.0")
            connectTimeout = 15_000
            readTimeout    = 30_000
        }
        try {
            conn.outputStream.use { it.write(body.toString().toByteArray()) }
            val code = conn.responseCode
            if (code !in 200..299) {
                val err = conn.errorStream?.bufferedReader()?.use { it.readText() } ?: ""
                throw IOException("HTTP $code: $err")
            }
            val response = conn.inputStream.bufferedReader().use { it.readText() }
            val env = JSONObject(response)
            env.optJSONObject("error")?.let {
                throw IOException(it.optString("message", "MCP error"))
            }
            val content = env.optJSONObject("result")?.optJSONArray("content") ?: JSONArray()
            if (content.length() == 0) return@withContext env.optJSONObject("result") ?: JSONObject()
            val text = content.getJSONObject(0).optString("text", "")
            return@withContext try { JSONObject(text) } catch (_: Exception) { JSONObject().apply { put("text", text) } }
        } finally {
            conn.disconnect()
        }
    }
}
