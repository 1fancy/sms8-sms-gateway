package io.sms8.capacitor

import com.getcapacitor.JSObject
import com.getcapacitor.Plugin
import com.getcapacitor.PluginCall
import com.getcapacitor.PluginMethod
import com.getcapacitor.annotation.CapacitorPlugin
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL

@CapacitorPlugin(name = "SmsOtp")
class SmsOtpPlugin : Plugin() {

    private var apiKey: String? = null
    private var baseUrl: String = "https://mcp.sms8.io"
    private val scope = CoroutineScope(Dispatchers.IO)

    @PluginMethod
    fun configure(call: PluginCall) {
        val key = call.getString("apiKey")
        if (key.isNullOrBlank()) {
            call.reject("configure: apiKey is required. Get one free at https://sms8.io")
            return
        }
        apiKey = key
        call.getString("baseUrl")?.let { baseUrl = it.trimEnd('/') }
        call.resolve()
    }

    @PluginMethod
    fun sendSms(call: PluginCall) {
        val phone   = call.getString("phone")   ?: return call.reject("phone required")
        val message = call.getString("message") ?: return call.reject("message required")
        val args = JSONObject().apply {
            put("phone", phone)
            put("message", message)
            call.getInt("deviceId")?.let   { put("device_id", it) }
            call.getString("simSlot")?.let { put("sim_slot",  it) }
        }
        scope.launch { mcpCall("send_sms", args, call) { json ->
            JSObject().apply {
                put("success",   json.optBoolean("success", false))
                put("messageId", json.opt("message_id"))
                put("phone",     json.opt("phone"))
                put("error",     json.opt("error"))
            }
        } }
    }

    @PluginMethod
    fun sendOtp(call: PluginCall) {
        val phone = call.getString("phone") ?: return call.reject("phone required")
        val args = JSONObject().apply {
            put("phone", phone)
            call.getInt("length")?.let      { put("length", it) }
            call.getString("template")?.let { put("template", it) }
            call.getInt("expiresIn")?.let   { put("expires_in", it) }
            call.getInt("maxAttempts")?.let { put("max_attempts", it) }
            call.getInt("deviceId")?.let    { put("device_id", it) }
            call.getString("simSlot")?.let  { put("sim_slot", it) }
        }
        scope.launch { mcpCall("send_otp", args, call) { json ->
            JSObject().apply {
                put("success",   json.optBoolean("success", false))
                put("otpId",     json.opt("otp_id"))
                put("phone",     json.opt("phone"))
                put("expiresAt", json.opt("expires_at"))
                put("expiresIn", json.opt("expires_in"))
                put("error",     json.opt("error"))
            }
        } }
    }

    @PluginMethod
    fun verifyOtp(call: PluginCall) {
        val phone = call.getString("phone") ?: return call.reject("phone required")
        val code  = call.getString("code")  ?: return call.reject("code required")
        val args = JSONObject().apply {
            put("phone", phone)
            put("code",  code)
        }
        scope.launch { mcpCall("verify_otp", args, call) { json ->
            JSObject().apply {
                put("success",      json.optBoolean("success", false))
                put("verified",     json.opt("verified"))
                put("error",        json.opt("error"))
                put("attemptsLeft", json.opt("attempts_left"))
            }
        } }
    }

    @PluginMethod
    fun listDevices(call: PluginCall) {
        scope.launch { mcpCall("list_devices", JSONObject(), call) { json ->
            JSObject().apply {
                put("success", json.optBoolean("success", false))
                put("count",   json.optInt("count", 0))
                put("devices", json.opt("devices") ?: org.json.JSONArray())
                put("error",   json.opt("error"))
            }
        } }
    }

    @PluginMethod
    fun getMessages(call: PluginCall) {
        val args = JSONObject().apply {
            put("direction", call.getString("direction") ?: "all")
            put("limit",     call.getInt("limit") ?: 25)
            call.getString("phone")?.let { put("phone", it) }
        }
        scope.launch { mcpCall("get_messages", args, call) { json ->
            JSObject().apply {
                put("success",  json.optBoolean("success", false))
                put("count",    json.optInt("count", 0))
                put("messages", json.opt("messages") ?: org.json.JSONArray())
                put("error",    json.opt("error"))
            }
        } }
    }

    @PluginMethod
    fun getBalance(call: PluginCall) {
        scope.launch { mcpCall("get_balance", JSONObject(), call) { json ->
            JSObject().apply {
                put("success",   json.optBoolean("success", false))
                put("credits",   json.opt("credits"))
                put("unlimited", json.optBoolean("unlimited", false))
                put("expiresAt", json.opt("expires_at"))
                put("daysLeft",  json.opt("days_left"))
                put("summary",   json.opt("summary"))
                put("error",     json.opt("error"))
            }
        } }
    }

    private fun mcpCall(tool: String, args: JSONObject, call: PluginCall, map: (JSONObject) -> JSObject) {
        val key = apiKey
        if (key == null) {
            call.reject("SmsOtp not configured. Call SmsOtp.configure({ apiKey }) first.")
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
                setRequestProperty("User-Agent", "capacitor-sms-otp-send-verify/1.0.0")
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
                    call.reject(it.optString("message", "MCP error"))
                    return
                }
                val content = env.optJSONObject("result")?.optJSONArray("content")
                val first   = if (content != null && content.length() > 0) content.getJSONObject(0) else null
                val text    = first?.optString("text") ?: ""
                val inner   = try { JSONObject(text) } catch (_: Exception) { JSONObject() }
                call.resolve(map(inner))
            } finally {
                conn.disconnect()
            }
        } catch (e: Exception) {
            call.reject(e.message ?: "Unknown error")
        }
    }
}
