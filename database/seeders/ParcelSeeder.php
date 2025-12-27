<?php

namespace Database\Seeders;

use App\Models\Parcel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ParcelSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/public/kurram_parcels.geojson');
        
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

            Parcel::create([
                'OBJECTID' => $properties['OBJECTID'] ?? null,
                'Khassra_No' => $properties['Khassra_No'] ?? null,
                'Old_Khassr' => $properties['Old_Khassr'] ?? null,
                'Massavi_No' => $properties['Massavi_No'] ?? null,
                'KhasraType' => $properties['KhasraType'] ?? null,
                'Mauza_Name' => $properties['Mauza_Name'] ?? null,
                'PC_Name' => $properties['PC_Name'] ?? null,
                'KhassraId' => $properties['KhassraId'] ?? null,
                'MozaId' => $properties['MozaId'] ?? null,
                'JamaBandiY' => $properties['JamaBandiY'] ?? null,
                'HadBastNo' => $properties['HadBastNo'] ?? null,
                'MozaName_U' => $properties['MozaName_U'] ?? null,
                'KhataNo' => $properties['KhataNo'] ?? null,
                'Status' => $properties['Status'] ?? null,
                'UC' => $properties['UC'] ?? null,
                'Tehsil' => $properties['Tehsil'] ?? null,
                'District' => $properties['District'] ?? null,
                'Division' => $properties['Division'] ?? null,
                'Province' => $properties['Province'] ?? null,
                'Shape_Leng' => $properties['Shape_Leng'] ?? null,
                'Shape_Le_1' => $properties['Shape_Le_1'] ?? null,
                'Shape_Area' => $properties['Shape_Area'] ?? null,
                'geometry' => $geometry,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Imported {$total} parcels successfully");
    }
}

