<?php

// Settings for the SB member (staff) interface.
return [
    // Offices a concern can be reassigned to (the barangay captain of the concern's barangay is added automatically)
    'offices' => [
        'Municipal Planning and Development Office',
        'Municipal Environment and Natural Resources Office',
        'Municipal Engineering Office',
        'Municipal Health Office',
        'Sangguniang Bayan Secretariat',
    ],

    // Where the pins go on the dashboard map. Only these barangays have a pin for now.
    // "primary" = drawn bigger. Add more barangays here when you have their coordinates.
    'pins' => [
        'Alcate'        => ['lat' => 13.190, 'lng' => 121.236],
        'Malabo'        => ['lat' => 13.205, 'lng' => 121.320],
        'San Juan'      => ['lat' => 13.163, 'lng' => 121.245],
        'Poblacion I'   => ['lat' => 13.177, 'lng' => 121.278, 'primary' => true],
        'San Isidro'    => ['lat' => 13.159, 'lng' => 121.302],
        'Macatoc'       => ['lat' => 13.142, 'lng' => 121.248],
        'Bambanin'      => ['lat' => 13.137, 'lng' => 121.296],
        'Villa Cerveza' => ['lat' => 13.125, 'lng' => 121.321],
    ],

    // Map centre
    'map_center' => ['lat' => 13.177, 'lng' => 121.278, 'zoom' => 12],

    // A problem counts as "recurring" when it was reported in at least this many different months
    'recurring_min_months' => 2,
];
