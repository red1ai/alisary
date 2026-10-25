<?php

return [
    /*
    | Notified when a career opening moves to "published" (website: alaisary.com).
    */
    'publish_to' => env('CAREER_PUBLISH_NOTIFY_TO', 'a.alisary@alisary.com'),

    /*
    | Notified of every new application, on every application route.
    */
    'applications_to' => env('CAREER_APPLICATIONS_NOTIFY_TO', 'career@alisary.com'),
];
