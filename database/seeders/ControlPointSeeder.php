<?php

namespace Database\Seeders;

use App\Models\ControlPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ControlPointSeeder extends Seeder
{
    /**
     * Geodetic control monuments, one source file per level.
     * Drop a Level-1 export beside this one and add a row here — nothing else
     * needs to change.
     */
    private array $sources = [
        2 => 'Level-2_-_Benchmark.geojson',
        // 1 => 'Level-1_-_Benchmark.geojson',
    ];

    public function run(): void
    {
        foreach ($this->sources as $level => $fileName) {
            $this->importLevel((int) $level, $fileName);
        }
    }

    private function importLevel(int $level, string $fileName): void
    {
        $filePath = storage_path('app/public/' . $fileName);

        if (!File::exists($filePath)) {
            $this->command->warn("Level {$level}: file not found, skipping — {$filePath}");
            return;
        }

        $data = json_decode(File::get($filePath), true);

        if (!isset($data['features']) || !is_array($data['features'])) {
            $this->command->error("Level {$level}: invalid GeoJSON in {$fileName}");
            return;
        }

        // Re-running the seeder replaces that level rather than duplicating it.
        ControlPoint::where('level', $level)->delete();

        $features = $data['features'];
        $total = count($features);
        $this->command->info("Level {$level}: importing {$total} control points from {$fileName}");

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        $rows = [];
        $skipped = 0;

        foreach ($features as $feature) {
            $p = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            // A monument with no geometry cannot be mapped, so it is of no use here.
            if (!$geometry || empty($geometry['coordinates'])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $statusRaw = $this->str($p, ['status_1', 'Status', 'status']);

            $rows[] = [
                'level'        => $level,
                'bm_code'      => $this->str($p, ['BM Code', 'BM_Code', 'bm_code', 'Code']),
                'lims_id'      => $this->str($p, ['LIMS_ID', 'LIMS Id', 'lims_id']),
                'District'     => $this->str($p, ['District', 'district']),
                'Tehsil'       => $this->str($p, ['Tehsil', 'tehsil']),
                'status_raw'   => $statusRaw,
                'status'       => ControlPoint::normaliseStatus($statusRaw),
                'execution'    => $this->str($p, ['Execution', 'execution']),
                'within_10k'   => $this->str($p, ['Within 10k', 'Within_10k', 'within_10k']),
                'distance'     => $this->str($p, ['Distance', 'distance']),
                'alt_location' => $this->str($p, ['Alt Locati', 'Alt_Location', 'alt_location']),
                'original_x'   => $this->num($p, ['Original X', 'Original_X', 'original_x']),
                'original_y'   => $this->num($p, ['Original Y', 'Original_Y', 'original_y']),
                'altered_x'    => $this->num($p, ['Altered X', 'Altered_X', 'altered_x']),
                'altered_y'    => $this->num($p, ['Altered Y', 'Altered_Y', 'altered_y']),
                'remarks'      => $this->str($p, ['Remarks', 'remarks']),
                'geometry'     => json_encode($geometry),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            $bar->advance();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ControlPoint::insert($chunk);
        }

        $bar->finish();
        $this->command->newLine();

        $imported = count($rows);
        $this->command->info("Level {$level}: imported {$imported} control points"
            . ($skipped ? " ({$skipped} skipped — no geometry)" : ''));
    }

    /** First non-empty value among the candidate keys, trimmed. */
    private function str(array $props, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($props[$k]) && trim((string) $props[$k]) !== '') {
                return trim((string) $props[$k]);
            }
        }
        return null;
    }

    /** First numeric value among the candidate keys. */
    private function num(array $props, array $keys): ?float
    {
        foreach ($keys as $k) {
            if (isset($props[$k]) && is_numeric($props[$k])) {
                return (float) $props[$k];
            }
        }
        return null;
    }
}
