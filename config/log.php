<?php

return [
    'default'      => 'file',
    'channels'     => [
        'file' => [
            'type'           => 'File',
            'path'           => '',
            'level'          => [],
            'single'         => false,
            'apart_level'    => [],
            'max_files'      => 0,
            'json'           => false,
            'close'          => false,
            'format'         => '[%s][%s] %s',
            'realtime_write' => false,
        ],
    ],
];
