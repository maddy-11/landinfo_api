<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    private function whereInsensitive($query, string $column, string $value, string $operator = '=')
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        return $query->whereRaw("LOWER({$wrapped}) {$operator} ?", [mb_strtolower(trim($value))]);
    }

    private function formatKhasraNumber($value)
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        // Non-numeric khasras like "104/3" must pass through untouched
        if (!is_numeric($value)) {
            return $value;
        }
        // Remove trailing zeros and decimal point if not needed ("104.00" -> "104")
        return rtrim(rtrim(sprintf('%.2f', (float) $value), '0'), '.');
    }

    private function parcelsToGeoJson($parcels)
    {
        $features = $parcels->map(function ($parcel) {
            // Get all attributes except id and timestamps for properties
            $attributes = $parcel->getAttributes();
            unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

            // Build properties object similar to GeoJSON structure
            $properties = [];
            foreach ($attributes as $key => $value) {
                if ($key !== 'geometry') {
                    // Format Khassra_No to remove trailing zeros
                    if ($key === 'Khassra_No') {
                        $properties[$key] = $this->formatKhasraNumber($value);
                    } else {
                        $properties[$key] = $value;
                    }
                }
            }

            return [
                'type' => 'Feature',
                'properties' => $properties,
                'geometry' => $parcel->geometry
            ];
        });

        return [
            'type' => 'FeatureCollection',
            'features' => $features->toArray()
        ];
    }

    public function index()
    {
        $parcels = Parcel::all();
        $geoJson = $this->parcelsToGeoJson($parcels);

        // Get filter options for initial load
        $districts = Parcel::select('District')
            ->whereNotNull('District')
            ->distinct()
            ->orderBy('District')
            ->pluck('District')
            ->filter()
            ->values();

        return view('home', [
            'parcelsGeoJson' => $geoJson,
            'districts' => $districts
        ]);
    }

    /**
     * Get all districts
     * GET /api/districts
     */
    public function getDistricts()
    {
        $districts = Parcel::select('District')
            ->whereNotNull('District')
            ->distinct()
            ->orderBy('District')
            ->pluck('District')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $districts
        ]);
    }

    /**
     * Get tehsils filtered by district
     * POST /api/tehsils
     * Payload: { "district": "Kurram" }
     * GET /api/tehsils?district=Kurram (for backward compatibility)
     */
    public function getTehsils(Request $request)
    {
        // Support both POST (JSON) and GET (query params)
        $district = $request->input('district');
        
        if (!$district) {
            return response()->json([
                'success' => false,
                'message' => 'District is required'
            ], 400);
        }

        $query = Parcel::select('Tehsil')
            ->whereNotNull('Tehsil')
            ->where('District', $district)
            ->distinct();

        $tehsils = $query->orderBy('Tehsil')
            ->pluck('Tehsil')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $tehsils
        ]);
    }

    /**
     * Get mauzas filtered by district and tehsil
     * POST /api/mauzas
     * Payload: { "district": "Kurram", "tehsil": "Upper Kurram" }
     * GET /api/mauzas?district=Kurram&tehsil=Upper Kurram (for backward compatibility)
     */
    public function getMauzas(Request $request)
    {
        // Support both POST (JSON) and GET (query params)
        $district = $request->input('district');
        $tehsil = $request->input('tehsil');
        
        if (!$district || !$tehsil) {
            return response()->json([
                'success' => false,
                'message' => 'District and tehsil are required'
            ], 400);
        }

        $query = Parcel::select('Mauza_Name')
            ->whereNotNull('Mauza_Name')
            ->distinct();
        $this->whereInsensitive($query, 'District', $district);
        $this->whereInsensitive($query, 'Tehsil', $tehsil);

        $mauzas = $query->orderBy('Mauza_Name')
            ->pluck('Mauza_Name')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $mauzas
        ]);
    }

    /**
     * Get ALL unique mauzas for fuzzy matching
     * GET /api/all-mauzas
     */
    public function getMauzasList()
    {
        $mauzas = Parcel::select('Mauza_Name')
            ->whereNotNull('Mauza_Name')
            ->distinct()
            ->pluck('Mauza_Name')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $mauzas
        ]);
    }

    /**
     * Get khasras filtered by district, tehsil, and mauza
     * POST /api/khasras
     * Payload: { "district": "Kurram", "tehsil": "Upper Kurram", "mauza": "Alamsher" }
     * GET /api/khasras?district=Kurram&tehsil=Upper Kurram&mauza=Alamsher (for backward compatibility)
     */
    public function getKhasras(Request $request)
    {
        // Support both POST (JSON) and GET (query params)
        $district = $request->input('district');
        $tehsil = $request->input('tehsil');
        $mauza = $request->input('mauza') ?? $request->input('mauza_name');
        
        if (!$district || !$tehsil || !$mauza) {
            return response()->json([
                'success' => false,
                'message' => 'District, tehsil, and mauza are required'
            ], 400);
        }

        $query = Parcel::select('Khassra_No')
            ->whereNotNull('Khassra_No')
            ->distinct();
        $this->whereInsensitive($query, 'District', $district);
        $this->whereInsensitive($query, 'Tehsil', $tehsil);
        $this->whereInsensitive($query, 'Mauza_Name', $mauza);

        $khasras = $query->orderBy('Khassra_No')
            ->pluck('Khassra_No')
            ->map(fn ($khasra) => $this->formatKhasraNumber($khasra))
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $khasras
        ]);
    }

    public function getFilteredParcels(Request $request)
    {
        $query = Parcel::query();

        $district = $request->input('district');
        if (is_string($district) && trim($district) !== '') {
            $this->whereInsensitive($query, 'District', $district);
        }

        $tehsil = $request->input('tehsil');
        if (is_string($tehsil) && trim($tehsil) !== '') {
            $this->whereInsensitive($query, 'Tehsil', $tehsil);
        }

        $mauza = $request->input('mauza')
            ?? $request->input('Mauza')
            ?? $request->input('mauza_name')
            ?? $request->input('mauzaName')
            ?? $request->input('Mauza_Name')
            ?? $request->input('MauzaName')
            ?? $request->input('moza')
            ?? $request->input('moza_name')
            ?? $request->input('MozaName')
            ?? $request->input('Moza_Name');
        if (is_string($mauza) && trim($mauza) !== '') {
            $this->whereInsensitive($query, 'Mauza_Name', $mauza);
        }

        $khasra = $request->input('khasra');
        if (is_string($khasra) && trim($khasra) !== '') {
            // Handle khasra input which might have / or .
            $khasraInput = trim($khasra);
            
            // If it's a numeric-only or decimal string
            if (is_numeric(str_replace('/', '.', $khasraInput))) {
                $khasraValue = str_replace('/', '.', $khasraInput);
                // Khassra_No is stored as a string, possibly with trailing zeros
                // ("876", "876.0", "876.00") — match the common variants.
                $candidates = array_unique([
                    $khasraInput,
                    $khasraValue,
                    $this->formatKhasraNumber($khasraValue),
                    sprintf('%.1f', (float) $khasraValue),
                    sprintf('%.2f', (float) $khasraValue),
                ]);
                $query->whereIn('Khassra_No', $candidates);
            } else {
                // Fallback for non-numeric khasra IDs if any
                $this->whereInsensitive($query, 'Khassra_No', $khasraInput);
            }
        }

        $parcels = $query->get();
        
        // If no results and it was a mauza search, try to find "similar" mauzas to suggest or just return counts
        if ($parcels->isEmpty() && is_string($mauza) && trim($mauza) !== '') {
             $similarQuery = Parcel::query()->distinct();
             $this->whereInsensitive($similarQuery, 'Mauza_Name', substr(trim($mauza), 0, 3) . '%', 'LIKE');
             $similarMauzas = $similarQuery
                ->pluck('Mauza_Name')
                ->take(5);
             
             return response()->json([
                'success' => false,
                'message' => 'No parcels found. Did you mean: ' . $similarMauzas->implode(', ') . '?',
                'suggestions' => $similarMauzas
            ], 404);
        }

        $geoJson = $this->parcelsToGeoJson($parcels);

        return response()->json([
            'success' => true,
            'data' => $geoJson,
            'count' => count($geoJson['features'])
        ]);
    }
}

