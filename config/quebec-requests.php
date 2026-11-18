<?php

return [

    'table' => 'quebec_requests',

    /*
     * Answer in writing within 30 days of receipt. A request left unanswered within that time is
     * deemed refused (s. 32), and the person may apply to the Commission d'accès à l'information.
     */
    'deadline' => '30 days',

    /*
     * The private sector act has no extension: extend() refuses unless you set one here.
     */
    'extension' => null,

    /*
     * Where exports (JSON, s. 27) are written. Keep it a private disk: the file holds the
     * person's personal information.
     */
    'disk' => 'local',
    'directory' => 'quebec/requests',

];
