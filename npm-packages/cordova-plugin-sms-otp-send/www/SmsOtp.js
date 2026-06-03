/**
 * cordova-plugin-sms-otp-send — JS bridge to native iOS Swift / Android Kotlin.
 *
 * window.SmsOtp.configure({ apiKey: 'sk_xxx' })
 *   .then(() => SmsOtp.sendOtp({ phone: '+1...' }))
 *   .then(r => SmsOtp.verifyOtp({ phone: '+1...', code: '482937' }))
 *   .then(r => r.verified && goToDashboard())
 *
 * Free SMS8 trial at https://sms8.io
 */
var exec = require('cordova/exec');

var SERVICE = 'SmsOtp';

function call(action, args) {
  return new Promise(function (resolve, reject) {
    exec(resolve, reject, SERVICE, action, [args || {}]);
  });
}

module.exports = {
  /** Set the API key once before any send/verify call. */
  configure: function (options) { return call('configure', options); },
  /** Send a plain SMS through your paired Android phone. */
  sendSms:   function (options) { return call('sendSms',   options); },
  /** Send a verification code. */
  sendOtp:   function (options) { return call('sendOtp',   options); },
  /** Verify the code the user typed. */
  verifyOtp: function (options) { return call('verifyOtp', options); },
};
