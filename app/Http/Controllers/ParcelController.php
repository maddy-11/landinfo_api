<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    private function formatKhasraNumber($value)
    {
        if ($value === null) {
            return null;
        }
        // Convert to float and remove trailing zeros and decimal point if not needed
        $num = (float) $value;
        return rtrim(rtrim(sprintf('%.2f', $num), '0'), '.');
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
            ->where('District', $district)
            ->where('Tehsil', $tehsil)
            ->distinct();

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
        $mauza = $request->input('mauza');
        
        if (!$district || !$tehsil || !$mauza) {
            return response()->json([
                'success' => false,
                'message' => 'District, tehsil, and mauza are required'
            ], 400);
        }

        $query = Parcel::select('Khassra_No')
            ->whereNotNull('Khassra_No')
            ->where('District', $district)
            ->where('Tehsil', $tehsil)
            ->where('Mauza_Name', $mauza)
            ->distinct();

        $khasras = $query->orderBy('Khassra_No')
            ->pluck('Khassra_No')
            ->map(function ($khasra) {
                // Convert to string and remove trailing zeros and decimal point if not needed
                $khasra = (float) $khasra;
                return rtrim(rtrim(sprintf('%.2f', $khasra), '0'), '.');
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $khasras
        ]);
    }

    /**
     * Get filtered parcels with optional filters
     * POST /api/parcels/filtered
     * Payload: { "district": "Kurram", "tehsil": "Upper Kurram", "mauza": "Alamsher", "khasra": "870" }
     */
    public function getFilteredParcels(Request $request)
    {
        $query = Parcel::query();

        if ($request->filled('district')) {
            $query->where('District', $request->district);
        }

        if ($request->filled('tehsil')) {
            $query->where('Tehsil', $request->tehsil);
        }

        if ($request->filled('mauza')) {
            $query->where('Mauza_Name', $request->mauza);
        }

        if ($request->filled('khasra')) {
            // Convert khasra string to float for comparison with database decimal
            // This handles formatted values like "870" matching "870.00" in database
            $khasraValue = (float) $request->khasra;
            $query->where('Khassra_No', $khasraValue);
        }

        $parcels = $query->get();
        $geoJson = $this->parcelsToGeoJson($parcels);

        return response()->json([
            'success' => true,
            'data' => $geoJson,
            'count' => count($geoJson['features'])
        ]);
    }
}

