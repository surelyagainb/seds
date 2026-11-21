<?php

// Settings for the citizen concern form.
return [
    // The barangays shown in the dropdown (also used to check the submitted value)
    'barangays' => [
        'Alcate', 'Babangonan', 'Bagong Buhay', 'Bambanin', 'Bethel', 'Canaan', 'Concepcion', 'Duongan',
        'Leido', 'Loyal', 'Mabini', 'Macatoc', 'Malabo', 'Merit', 'Ordovilla', 'Pakyas', 'Poblacion I',
        'Poblacion II', 'Poblacion III', 'Poblacion IV', 'San Antonio', 'San Cristobal', 'San Gabriel',
        'San Gelacio', 'San Isidro', 'San Juan', 'San Narciso', 'Urdaneta', 'Villa Cerveza',
    ],

    // key => label, css class, keywords used to suggest the category while typing
    'categories' => [
        'waste'  => ['label' => 'Waste Management',        'cls' => 'waste',  'kw' => ['garbage', 'trash', 'waste', 'basura', 'dump', 'litter', 'collection']],
        'flood'  => ['label' => 'Flooding / Public Safety', 'cls' => 'flood',  'kw' => ['flood', 'baha', 'drain', 'canal', 'clog', 'overflow', 'rain']],
        'health' => ['label' => 'Health / Sanitation',      'cls' => 'health', 'kw' => ['dengue', 'mosquito', 'sanitation', 'sewage', 'smell', 'disease', 'sick']],
        'road'   => ['label' => 'Roads / Infrastructure',   'cls' => 'road',   'kw' => ['road', 'pothole', 'streetlight', 'bridge', 'sidewalk', 'light']],
        'other'  => ['label' => 'Other',                    'cls' => 'other',  'kw' => []],
    ],
];
