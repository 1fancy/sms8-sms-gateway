/**
 * SMS8 — minimal JS / Node fetch client.
 *
 * Works in Node 18+, Bun, Deno, Cloudflare Workers, browsers (CORS allowed).
 * Zero dependencies.
 *
 *   const sms8 = createSms8(process.env.SMS8_API_KEY);
 *   await sms8.send('+1234567890', 'Hello!');
 */

export function createSms8(apiKey, baseUrl = 'https://app.sms8.io') {
    if (!apiKey) throw new Error('SMS8 API key required');

    async function post(path, params) {
        const body = new URLSearchParams({ api_key: apiKey, ...params });
        const res  = await fetch(`${baseUrl}${path}`, { method: 'POST', body });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(`SMS8: ${json.error || res.statusText}`);
        }
        return json;
    }

    return {
        /** Send a single SMS. Returns the new message ID. */
        async send(phone, message, deviceId = null) {
            const params = { phone, message };
            if (deviceId) params.device_id = deviceId;
            const r = await post('/api.php?action=send', params);
            return r.message_id;
        },

        /** Send an OTP. Defaults: 6 digits, 5-minute expiry. */
        async sendOtp(phone, opts = {}) {
            return post('/ajax/otp-send.php', {
                phone,
                length:     opts.length     || 6,
                expires_in: opts.expiresIn  || 300,
                ...(opts.template ? { template: opts.template } : {}),
            });
        },

        /** Verify a user-typed code. Returns true / false. */
        async verifyOtp(phone, code) {
            const r = await post('/ajax/otp-verify.php', { phone, code });
            return Boolean(r.verified);
        },

        /** List recent messages on the account. */
        async messages({ direction = 'all', limit = 25 } = {}) {
            const u = new URL(`${baseUrl}/api.php`);
            u.searchParams.set('action',    'inbox');
            u.searchParams.set('api_key',   apiKey);
            u.searchParams.set('direction', direction);
            u.searchParams.set('limit',     limit);
            const res = await fetch(u);
            return res.json();
        },
    };
}

// CommonJS compatibility for Node without ESM
if (typeof module !== 'undefined') module.exports = { createSms8 };
