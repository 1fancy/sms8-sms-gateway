package io.sms8.cordova

import org.apache.cordova.CallbackContext
import org.apache.cordova.CordovaPlugin
import org.json.JSONArray
import org.json.JSONObject
import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL

/**
 * Cordova Android native plugin: send + verify SMS OTP codes through your
 * paired Android phone via SMS8.
 *
 * Free SMS8 trial at https://sms8.io
 */
class SmsOtp : CordovaPlugin() {

    private var apiKey: String? = null
    private var baseUrl: String = "https://mcp.sms8.io"

    override fun execute(action: String, args: JSONArray, callback: CallbackContext): Boolean {
        val opts = if (args.length() > 0) args.optJSONObject(0) ?: JSONObject() else JSONObject()
        when (action) {
            "configure" -> configure(opts, callback)
            "sendSms"   -> cordova.threadPool.execute { sendSms(opts, callback) }
            "sendOtp"   -> cordova.threadPool.execute { sendOtp(opts, callback) }
            "verifyOtp" -> cordova.threadPool.execute { verifyOtp(opts, callback) }
            else        -> return false
        }
        return true
    }

    private fun configure(opts: JSONObject, callback: CallbackContext) {
        val key = opts.optString("apiKey")
        if (key.isNullOrBlank()) {
            callback.error("configure: apiKey is required. Get one free at https://sms8.io")
            return
        }
        apiKey = key
        opts.optString("baseUrl").takeIf { it.isNotEmpty() }?.let { baseUrl = it.trimEnd('/') }
        callback.success()
    }

    private fun sendSms(opts: JSONObject, callback: CallbackContext) {
        val phone   = opts.optString("phone")
        val message = opts.optString("message")
        if (phone.isEmpty())   return callback.error("phone required")
        if (message.isEmpty()) return callback.error("message required")
        val args = JSONObject().apply {
            put("phone", phone)
            put("message", message)
            if (opts.has("deviceId")) put("device_id", opts.optInt("deviceId"))
            if (opts.has("simSlot"))  put("sim_slot",  opts.optString("simSlot"))
        }
        mcpCall("send_sms", args, callback) { json ->
            JSONObject().apply {
                put("success",   json.optBoolean("success", false))
                put("messageId", json.opt("message_id"))
                put("phone",     json.opt("phone"))
                put("error",     json.opt("error"))
            }
        }
    }

    private fun sendOtp(opts: JSONObject, callback: CallbackContext) {
        val phone = opts.optString("phone")
        if (phone.isEmpty()) return callback.error("phone required")
        val args = JSONObject().apply {
            put("phone", phone)
            if (opts.has("length"))      put("length",       opts.optInt("length"))
            if (opts.has("template"))    put("template",     opts.optString("template"))
            if (opts.has("expiresIn"))   put("expires_in",   opts.optInt("expiresIn"))
            if (opts.has("maxAttempts")) put("max_attempts", opts.optInt("maxAttempts"))
            if (opts.has("deviceId"))    put("device_id",    opts.optInt("deviceId"))
            if (opts.has("simSlot"))     put("sim_slot",     opts.optString("simSlot"))
        }
        mcpCall("send_otp", args, callback) { json ->
            JSONObject().apply {
                put("success",   json.optBoolean("success", false))
                put("otpId",     json.opt("otp_id"))
                put("phone",     json.opt("phone"))
                put("expiresAt", json.opt("expires_at"))
                put("expiresIn", json.opt("expires_in"))
                put("error",     json.opt("error"))
            }
        }
    }

    private fun verifyOtp(opts: JSONObject, callback: CallbackContext) {
        val phone = opts.optString("phone")
        val code  = opts.optString("code")
        if (phone.isEmpty()) return callback.error("phone required")
        if (code.isEmpty())  return callback.error("code required")
        val args = JSONObject().apply {
            put("phone", phone)
            put("code",  code)
        }
        mcpCall("verify_otp", args, callback) { json ->
            JSONObject().apply {
                put("success",      json.optBoolean("success", false))
                put("verified",     json.opt("verified"))
                put("error",        json.opt("error"))
                put("attemptsLeft", json.opt("attempts_left"))
            }
        }
    }

    private fun mcpCall(tool: String, args: JSONObject, callback: CallbackContext, map: (JSONObject) -> JSONObject) {
        val key = apiKey
        if (key == null) {
            callback.error("SmsOtp not configured. Call SmsOtp.configure({ apiKey }) first.")
            return
        }
        try {
            args.put("api_key", key)
            val body = JSONObject().apply {
                put("jsonrpc", "2.0")
                put("method",  "tools/call")
                put("params",  JSONObject().apply {
                    put("name", tool)
                    put("arguments", args)
                })
                put("id", System.currentTimeMillis())
            }
            val conn = (URL(baseUrl).openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                doOutput = true
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Authorization", "Bearer $key")
                setRequestProperty("User-Agent", "cordova-plugin-sms-otp-send/1.0.0")
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
                    callback.error(it.optString("message", "MCP error"))
                    return
                }
                val content = env.optJSONObject("result")?.optJSONArray("content")
                val first   = if (content != null && content.length() > 0) content.getJSONObject(0) else null
                val text    = first?.optString("text") ?: ""
                val inner   = try { JSONObject(text) } catch (_: Exception) { JSONObject() }
                callback.success(map(inner))
            } finally {
                conn.disconnect()
            }
        } catch (e: Exception) {
            callback.error(e.message ?: "Unknown error")
        }
    }
}
