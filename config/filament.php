<?php

return [
    /*
     * Admin-panel uploads (media library images etc.) must live on the
     * public disk so the website can serve them. Filament's default is
     * env('FILESYSTEM_DISK') which is "local" (private) here — that made
     * every admin-uploaded image 404 on the public site.
     */
    'default_filesystem_disk' => 'public',
];
