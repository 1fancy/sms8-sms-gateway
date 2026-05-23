<?php
/**
 * list_devices — paired Android devices on the account.
 *
 * Lets the AI know what device IDs are valid before calling send_sms, and
 * surfaces a clear "you need to pair a device" message when the account
 * has none.
 */
ToolRegistry::register([
    'name' => 'list_devices',
    'description' => 'List the user\'s paired SMS8 devices (phones running the SMS8 Android app). Returns each device\'s ID, model, primary flag, and enabled status. If the list is empty, the user needs to install + pair the Android app at app.sms8.io/devices.php.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => new stdClass(),
    ],
    'handler' => function(array $args, User $user): array {
        $list = [];
        foreach ($user->getDevices() as $d) {
            $list[] = [
                'id'           => $d->getID(),
                'model'        => $d->getModel(),
                'androidVersion' => $d->getAndroidVersion(),
                'appVersion'   => $d->getAppVersion(),
                'enabled'      => (bool)$d->getEnabled(),
                'primary'      => $user->getPrimaryDeviceID() == $d->getID(),
            ];
        }
        return [
            'success'      => true,
            'count'        => count($list),
            'devices'      => $list,
            'pair_new_url' => 'https://app.sms8.io/devices.php',
            'help' => count($list) === 0
                ? 'No devices paired yet. Install the SMS8 Android app and scan the QR shown on the devices page.'
                : null,
        ];
    },
]);
