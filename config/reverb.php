<?php

return [
    'default' => 'reverb',
    
    'servers' => [
        'reverb' => [
            'host' => '0.0.0.0',
            'port' => 8084,
            'hostname' => 'localhost',
            'options' => [],
            'max_request_size' => 10000,
            'scaling' => [
                'enabled' => false,
            ],
            'pulse_ingest_interval' => 15,
            'telescope_ingest_interval' => 15,
        ],
    ],
    
    'apps' => [
        [
            'id' => 176496,
            'name' => 'per-ankh',
            'key' => 'cwzlhdz6usudnzyhdkz4',
            'secret' => '6ub6tkbc51spdxbcgjva',
            'capacity' => null,
            'enable_client_messages' => true,
            'enable_statistics' => true,
        ],
    ],
];
