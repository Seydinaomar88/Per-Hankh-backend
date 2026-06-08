<?php

return [
    "default" => "reverb",
    
    "servers" => [
        "reverb" => [
            "host" => env("REVERB_SERVER_HOST", "0.0.0.0"),
            "port" => env("REVERB_SERVER_PORT", 8080),
            "hostname" => env("REVERB_HOST"),
            "options" => [],
            "max_request_size" => env("REVERB_MAX_REQUEST_SIZE", 10000),
            "scaling" => [
                "enabled" => env("REVERB_SCALING_ENABLED", false),
            ],
        ],
    ],
    
    "apps" => [
        [
            "id" => env("REVERB_APP_ID", 1),
            "name" => env("APP_NAME", "Laravel"),
            "key" => env("REVERB_APP_KEY", "per-ankh-key"),
            "secret" => env("REVERB_APP_SECRET", "per-ankh-secret"),
            "capacity" => null,
            "enable_client_messages" => true,
            "enable_statistics" => true,
        ],
    ],
];
