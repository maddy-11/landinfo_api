<?php

namespace Database\Seeders;

use App\Models\Karam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class KaramSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/public/kurram_karam.geojson');
        
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $json = File::get($filePath);
        $data = json_decode($json, true);

        if (!isset($data['features'])) {
            $this->command->error("Invalid GeoJSON format");
            return;
        }

        $features = $data['features'];
        $total = count($features);
        $this->command->info("Found {$total} features to import");

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        foreach ($features as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            Karam::create([
                'OBJECTID_1' => $properties['OBJECTID_1'] ?? null,
                'Karam' => $properties['Karam'] ?? null,
                'Karam_Urdu' => $properties['Karam_Urdu'] ?? null,
                'Khasra_No_' => $properties['Khasra_No_'] ?? null,
                'Massavi_No' => $properties['Massavi_No'] ?? null,
                'Hadbast_No' => $properties['Hadbast_No'] ?? null,
                'Mauza_Name' => $properties['Mauza_Name'] ?? null,
                'PC_Name' => $properties['PC_Name'] ?? null,
                'QH_Name' => $properties['QH_Name'] ?? null,
                'UC_Name' => $properties['UC_Name'] ?? null,
                'Tehsil_Nam' => $properties['Tehsil_Nam'] ?? null,
                'Distt_Name' => $properties['Distt_Name'] ?? null,
                'Division_N' => $properties['Division_N'] ?? null,
                'Province_N' => $properties['Province_N'] ?? null,
                'Country_Na' => $properties['Country_Na'] ?? null,
                'Remarks' => $properties['Remarks'] ?? null,
                'SHAPE_Leng' => $properties['SHAPE_Leng'] ?? null,
                'Shape_Le_1' => $properties['Shape_Le_1'] ?? null,
                'Cal' => $properties['Cal'] ?? null,
                'Status' => $properties['Status'] ?? null,
                'testing' => $properties['testing'] ?? null,
                'geometry' => $geometry,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Imported {$total} karams successfully");
    }
}

