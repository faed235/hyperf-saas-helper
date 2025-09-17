<?php

use function Hyperf\Support\env;

return [
    'apifox'=>[
        'apifox_project_id'=> env('APIFOX_PROJECT_ID'),
        'apifox_version'=> env('APIFOX_VERSION'),
        'apifox_token'=> env('APIFOX_TOKEN'),
    ],
];