<?php

namespace Database\Seeders;

use App\Models\Parcel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ParcelSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/public/Zaka Khel - Parcel.json');

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

        $data = [];

        foreach ($features as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;
            $data[] = [
                'OBJECTID'     => $properties['OBJECTID'] ?? null,
                'Khassra_No'   => $properties['Khasra_no'] ?? $properties['Khasra_No'] ?? $properties['Khassra_No'] ?? $properties['khasra_num'] ?? null,
                'Old_Khassr'   => $properties['Old_Khassr'] ?? null,
                'Massavi_No'   => $properties['Massavi_No'] ?? null,
                'KhasraType'   => $properties['KhasraType'] ?? $properties['LandUse'] ?? $properties['Landuse']??  null,
                'Mauza_Name'   => $properties['Mouza_Name'] ?? $properties['Mauza_Name'] ?? $properties['mauza'] ?? $properties['mouza'] ?? $properties['Village'] ?? null,
                'PC_Name'      => $properties['PC_Name'] ?? null,
                'KhassraId'    => $properties['KhassraId'] ?? null,
                'MozaId'       => $properties['MozaId'] ?? $properties['mauza_id'] ?? null,
                'JamaBandiY'   => $properties['JamaBandiY'] ?? null,
                'HadBastNo'    => $properties['HadBastNo'] ?? null,
                'MozaName_U'   => $properties['MozaName_U'] ?? null,
                'KhataNo'      => $properties['KhataNo'] ?? null,
                'Status'       => $properties['Status'] ?? null,
                'UC'           => $properties['UC'] ?? null,
                'Tehsil'       => $properties['Tehsil'] ?? $properties['tehsil'] ?? null,
                'District'     => $properties['District'] ?? $properties['district'] ?? null,
                'Division'     => $properties['Division'] ?? null,
                'Province'     => $properties['Province'] ?? null,
                'Shape_Leng'   => $properties['Shape_Leng'] ?? $properties['Shape_Length'] ?? $properties['SHAPE_Length']?? null,
                'Shape_Le_1'   => $properties['Shape_Le_1'] ?? null,
                'Shape_Area'   => $properties['SHAPE_Area'] ?? $properties['Shape_Area'] ?? null,
                'geometry'     => json_encode($geometry),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $bar->advance();
        }
        foreach (array_chunk($data, 1000) as $chunk) {
            Parcel::insert($chunk);
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Imported {$total} parcels successfully");
    }
}

