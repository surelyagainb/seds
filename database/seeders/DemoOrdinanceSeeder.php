<?php

namespace Database\Seeders;

use App\Models\Ordinance;
use Illuminate\Database\Seeder;

/** Adds DEMO ordinances (sample text, not official) so the matcher has something to suggest. Replace them with the real ordinances later. */
class DemoOrdinanceSeeder extends Seeder
{
    /** Run with: php artisan db:seed --class=DemoOrdinanceSeeder */
    public function run(): void
    {
        // Remove the very first placeholder samples (DEMO-001 ... DEMO-005) if they exist
        Ordinance::where('number', 'like', 'DEMO-%')->delete();

        $demo = [
            [
                'number' => 'Demo Ordinance No. 2024-012', 'title' => 'Solid Waste Management Ordinance', 'category' => 'waste',
                'status' => 'Active', 'version' => 'v2', 'enacted_on' => '2024-03-12',
                'keywords' => ['garbage', 'trash', 'waste', 'basura', 'dump', 'litter', 'collection', 'segregat', 'flood', 'clog'],
                'sections' => [
                    ['n' => 1, 'title' => 'Purpose and Scope', 'text' => 'This ordinance sets rules for the proper handling of solid waste in all barangays of the municipality. It applies to households, markets, businesses and public places.'],
                    ['n' => 2, 'title' => 'Proper Disposal and Clean Waterways', 'text' => 'No person shall throw garbage, trash or other waste into canals, drainage lines, creeks or rivers. Households and establishments must keep the drainage in front of their property free of waste so that rainwater can flow and flooding is prevented.'],
                    ['n' => 3, 'title' => 'Collection and Segregation', 'text' => 'Waste must be segregated into biodegradable, recyclable and residual materials. Garbage collection follows a schedule set by the municipality for each barangay, and waste must be placed out only on collection days.'],
                    ['n' => 4, 'title' => 'Penalties and Enforcement', 'text' => 'Violations are subject to a warning for the first offense, a fine for the second offense and community service for repeat offenses. Barangay officials and municipal enforcers are authorized to issue notices of violation.'],
                ],
            ],
            [
                'number' => 'Demo Ordinance No. 2023-007', 'title' => 'Drainage and Veterinary Protection Ordinance', 'category' => 'flood',
                'status' => 'Active', 'version' => 'v1', 'enacted_on' => '2023-08-21',
                'keywords' => ['drain', 'canal', 'clog', 'overflow', 'flood', 'rain', 'animal', 'dog', 'stray'],
                'sections' => [
                    ['n' => 1, 'title' => 'Drainage Maintenance', 'text' => 'Canals and drainage lines are public infrastructure and must be inspected and desilted before the rainy season. Obstructions, including illegal structures and dumped materials, shall be removed.'],
                    ['n' => 2, 'title' => 'Flood-Prone Areas', 'text' => 'The municipality shall identify flood-prone areas, put up warning signs and prioritize drainage repair in those areas after heavy rain.'],
                    ['n' => 3, 'title' => 'Animal Control and Veterinary Protection', 'text' => 'Owners shall keep dogs and other animals leashed or confined. Stray animals may be impounded and owners must have their pets vaccinated against rabies.'],
                ],
            ],
            [
                'number' => 'Demo Ordinance No. 2022-015', 'title' => 'Anti-Littering Ordinance', 'category' => 'waste',
                'status' => 'Active', 'version' => 'v1', 'enacted_on' => '2022-05-09',
                'keywords' => ['litter', 'trash', 'dump', 'throw', 'plastic', 'bottle', 'burn'],
                'sections' => [
                    ['n' => 1, 'title' => 'Prohibited Acts', 'text' => 'Throwing, dumping or leaving trash, plastic and bottles in streets, parks, creeks and other public places is prohibited. Open burning of garbage is also prohibited.'],
                    ['n' => 2, 'title' => 'Fines', 'text' => 'A fine applies to each violation. Persons caught more than once may be required to do community cleanup work.'],
                ],
            ],
            [
                'number' => 'Demo Ordinance No. 2024-020', 'title' => 'Dengue Prevention and Sanitation Ordinance', 'category' => 'health',
                'status' => 'Under review', 'version' => 'v1', 'enacted_on' => '2024-09-30',
                'keywords' => ['dengue', 'mosquito', 'stagnant', 'sanitation', 'sewage', 'disease', 'sick', 'smell'],
                'sections' => [
                    ['n' => 1, 'title' => 'Clean-Up Drives', 'text' => 'Barangays shall hold regular clean-up drives to remove containers that collect stagnant water where mosquitoes breed.'],
                    ['n' => 2, 'title' => 'Sanitation Standards', 'text' => 'Households and establishments must keep sewage and septic systems in working order and must report foul odors, sewage leaks and disease outbreaks to the barangay health worker.'],
                ],
            ],
            [
                'number' => 'Demo Ordinance No. 2021-004', 'title' => 'Road Safety and Streetlight Ordinance', 'category' => 'road',
                'status' => 'Draft', 'version' => 'v1', 'enacted_on' => null,
                'keywords' => ['road', 'pothole', 'streetlight', 'bridge', 'sidewalk'],
                'sections' => [
                    ['n' => 1, 'title' => 'Road Maintenance', 'text' => 'Potholes and damaged road sections shall be reported to the municipal engineering office and repaired in order of safety risk.'],
                    ['n' => 2, 'title' => 'Public Lighting', 'text' => 'Streetlights along main roads and public plazas shall be inspected monthly and broken lights repaired within a reasonable time.'],
                ],
            ],
        ];

        foreach ($demo as $row) {
            Ordinance::updateOrCreate(['number' => $row['number']], $row);
        }
    }
}
