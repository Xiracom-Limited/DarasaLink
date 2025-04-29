<?php
require_once 'mpesaplugin.php';

$mpesa = new MpesaSdk();

$mpesa->init([
    'consumer_key' => 'ToyeZbkIWfE8bfs8AD33BKMilK2BtRFm1DkJgS8Pc40cXoks',
    'consumer_secret' => '4EM1qXrgrN6YJ4jnf1rWkP5uxhAbmGC3SKPrUKJv17YIDIr0AKOmnhJzOIlkeW95',
    'shortcode' => '174379',
    'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
    'callback_url' => 'https://yourdomain.com/mpesa/callback'
]);

$mpesa->on('onSuccess', function ($data) {
    echo "Payment successful:\n";
    print_r($data);
});

$mpesa->on('onPending', function ($data) {
    echo "Payment pending:\n";
    print_r($data);
});

$mpesa->on('onFail', function ($data) {
    echo "Payment failed:\n";
    print_r($data);
});

$mpesa->pay(1, '254724086424', 'ORDER123'); 