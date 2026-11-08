<?php

/**
 * Desktop director app (Go/Wails, separate repo). Read by
 * DirectorController::version() so the app can show an update banner.
 */
return [
    'latest_version' => env('DIRECTOR_APP_LATEST_VERSION'),
    'download_url'   => env('DIRECTOR_APP_DOWNLOAD_URL'),
];
