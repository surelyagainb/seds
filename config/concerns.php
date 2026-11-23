<?php

// Settings for the citizen concern form.
return [
    // Edit these placeholder numbers in one place.
    'emergency' => [
        '911' => '911',
        'mdrrmo' => '0999-000-0000',
        'police' => '0999-000-0001',
        'rhu' => '0999-000-0002',
    ],
    'barangay_coordinates' => [
        'Alcate' => [13.190, 121.236], 'Babangonan' => [13.184, 121.254],
        'Bagong Buhay' => [13.198, 121.260], 'Bambanin' => [13.137, 121.296],
        'Bethel' => [13.169, 121.267], 'Canaan' => [13.149, 121.288],
        'Concepcion' => [13.157, 121.274], 'Duongan' => [13.191, 121.311],
        'Leido' => [13.145, 121.306], 'Loyal' => [13.168, 121.322],
        'Mabini' => [13.188, 121.295], 'Macatoc' => [13.142, 121.248],
        'Malabo' => [13.205, 121.320], 'Merit' => [13.181, 121.270],
        'Ordovilla' => [13.153, 121.312], 'Pakyas' => [13.203, 121.285],
        'Poblacion I' => [13.177, 121.278], 'Poblacion II' => [13.175, 121.282],
        'Poblacion III' => [13.179, 121.282], 'Poblacion IV' => [13.181, 121.276],
        'San Antonio' => [13.165, 121.257], 'San Cristobal' => [13.161, 121.315],
        'San Gabriel' => [13.160, 121.251], 'San Gelacio' => [13.153, 121.252],
        'San Isidro' => [13.159, 121.302], 'San Juan' => [13.163, 121.245],
        'San Narciso' => [13.173, 121.309], 'Urdaneta' => [13.202, 121.260],
        'Villa Cerveza' => [13.125, 121.321],
    ],
    // The barangays shown in the dropdown (also used to check the submitted value)
    'barangays' => [
        'Alcate', 'Babangonan', 'Bagong Buhay', 'Bambanin', 'Bethel', 'Canaan', 'Concepcion', 'Duongan',
        'Leido', 'Loyal', 'Mabini', 'Macatoc', 'Malabo', 'Merit', 'Ordovilla', 'Pakyas', 'Poblacion I',
        'Poblacion II', 'Poblacion III', 'Poblacion IV', 'San Antonio', 'San Cristobal', 'San Gabriel',
        'San Gelacio', 'San Isidro', 'San Juan', 'San Narciso', 'Urdaneta', 'Villa Cerveza',
    ],

    // key => label, css class, keywords used to suggest the category while typing
    'categories' => [
        'litter'     => ['label' => 'Improper Garbage Disposal & Littering',        'cls' => 'waste',  'kw' => ['garbage', 'trash', 'waste', 'basura', 'litter', 'throw']],
        'dumping'    => ['label' => 'Illegal Dumping',                            'cls' => 'waste',  'kw' => ['dump', 'basura', 'garbage', 'trash', 'waste']],
        'collection' => ['label' => 'Uncollected Garbage & Waste Collection',     'cls' => 'waste',  'kw' => ['collection', 'garbage', 'trash', 'waste', 'basura']],
        'flood'      => ['label' => 'Drainage & Waterway Obstruction / Flooding', 'cls' => 'flood',  'kw' => ['flood', 'baha', 'drain', 'canal', 'clog', 'overflow', 'rain', 'sewage']],
        'health'     => ['label' => 'Sanitation & Public Hygiene',                'cls' => 'health', 'kw' => ['sanitation', 'dengue', 'mosquito', 'smell', 'disease', 'sick']],
        'road'       => ['label' => 'Road or Street Damage',                     'cls' => 'road',   'kw' => ['lubak', 'pothole', 'sirang kalsada', 'road', 'street', 'kalsada']],
        'fire'       => ['label' => 'Open or Dangerous Burning',                 'cls' => 'other',  'kw' => ['sunog', 'burning', 'apoy', 'fire']],
        'other'      => ['label' => 'Others',                                    'cls' => 'other',  'kw' => []],
    ],
];