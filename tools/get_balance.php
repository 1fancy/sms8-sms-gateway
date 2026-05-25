<?php
/**
 * get_balance — dedicated credit-check tool.
 *
 * setup_sms8 already returns credits in its snapshot, but agents often want
 * to check balance mid-flow before kicking off a bulk send. A dedicated tool
 * is friendlier than re-running the heavier setup_sms8 call. Mirrors the
 * AgentSIM get_balance affordance so a Claude Code user familiar with that
 * MCP finds the same surface here.
 */
ToolRegistry::register([
    'name' => 'get_balance',
    'description' => 'Return the user\'s remaining SMS credits, plan name, expiry date and a one-line human summary. Lightweight, safe to call any time before a bulk send to check whether the account has enough credits left for the job.',
    'inputSchema' => [
        'type'  => 'object',
        'properties' => new stdClass(), // no inputs
    ],
    'handler' => function(array $args, User $user): array {
        $credits = $user->getCredits();
        $expiry  = $user->getExpiryDate();

        $creditsHuman = is_null($credits) ? 'unlimited' : (string)$credits;
        $expiryHuman  = $expiry ? date('Y-m-d', strtotime($expiry)) : 'no expiry';
        $daysLeft     = $expiry ? max(0, (int)ceil((strtotime($expiry) - time()) / 86400)) : null;

        $summary = is_null($credits)
            ? "Unlimited SMS credits. Subscription " . ($daysLeft !== null ? "renews in {$daysLeft} days." : "has no expiry.")
            : "{$creditsHuman} SMS credits remaining. Subscription " . ($daysLeft !== null ? "renews in {$daysLeft} days." : "has no expiry.");

        return [
            'success'    => true,
            'credits'    => $credits,                 // int or null when unlimited
            'unlimited'  => is_null($credits),
            'expires_at' => $expiry ? date('c', strtotime($expiry)) : null,
            'days_left'  => $daysLeft,
            'summary'    => $summary,
        ];
    },
]);
