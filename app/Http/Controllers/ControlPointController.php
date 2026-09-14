<?php

namespace App\Http\Controllers;

use App\Models\ControlPoint;
use Illuminate\Http\Request;

class ControlPointController extends Controller
{
    private function whereInsensitive($query, string $column, string $value)
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        return $query->whereRaw("LOWER({$wrapped}) = ?", [mb_strtolower(trim($value))]);
    }

    private function toGeoJson($points): array
    {
        $features = $points->map(function ($point) {
            $attributes = $point->getAttributes();
            unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['geometry']);

            $geometry = $point->geometry;

            return [
                'type'       => 'Feature',
                'properties' => $attributes,
                'geometry'   => $geometry,
            ];
        });

        return [
            'type'     => 'FeatureCollection',
            'features' => $features->values()->toArray(),
        ];
    }

    /**
     * Applies the optional narrowing filters. Every one is optional, so the
     * same endpoint answers "all of level 2" or "level 2 in Duki, Tehsil X".
     */
    private function applyFilters($query, Request $request): void
    {
        $district = $request->input('district') ?? $request->input('District');
        if (is_string($district) && trim($district) !== '') {
            $this->whereInsensitive($query, 'District', $district);
        }

        $tehsil = $request->input('tehsil') ?? $request->input('Tehsil');
        if (is_string($tehsil) && trim($tehsil) !== '') {
            $this->whereInsensitive($query, 'Tehsil', $tehsil);
        }

        $status = $request->input('status');
        if (is_string($status) && trim($status) !== '') {
            $this->whereInsensitive($query, 'status', $status);
        }

        $bmCode = $request->input('bm_code') ?? $request->input('code');
        if (is_string($bmCode) && trim($bmCode) !== '') {
            $this->whereInsensitive($query, 'bm_code', $bmCode);
        }
    }

    /**
     * GET|POST /api/levels/{level}
     *
     * Every control point for one level, optionally narrowed by district,
     * tehsil, status or BM code. An empty result is a normal answer, not a 404 —
     * the dashboards treat "nothing published here yet" as valid.
     */
    public function getByLevel(Request $request, $level)
    {
        if (!is_numeric($level)) {
            return response()->json([
                'success' => false,
                'message' => 'Level must be a number, for example /api/levels/2.',
            ], 422);
        }

        $query = ControlPoint::query()->where('level', (int) $level);
        $this->applyFilters($query, $request);

        $points = $query->orderBy('bm_code')->get();
        $geoJson = $this->toGeoJson($points);

        return response()->json([
            'success' => true,
            'level'   => (int) $level,
            'data'    => $geoJson,
            'count'   => count($geoJson['features']),
            'summary' => $this->summarise($points),
        ]);
    }

    /**
     * POST /api/levels/filtered
     *
     * Same data, level supplied in the body. Kept so one configurable endpoint
     * can serve every level without rewriting the path.
     */
    public function getFiltered(Request $request)
    {
        $query = ControlPoint::query();

        $level = $request->input('level');
        if (is_numeric($level)) {
            $query->where('level', (int) $level);
        }

        $this->applyFilters($query, $request);

        $points = $query->orderBy('level')->orderBy('bm_code')->get();
        $geoJson = $this->toGeoJson($points);

        return response()->json([
            'success' => true,
            'level'   => is_numeric($level) ? (int) $level : null,
            'data'    => $geoJson,
            'count'   => count($geoJson['features']),
            'summary' => $this->summarise($points),
        ]);
    }

    /**
     * GET /api/levels
     *
     * What is actually loaded — used to populate pickers without guessing.
     */
    public function index()
    {
        $levels = ControlPoint::query()
            ->selectRaw('level, COUNT(*) as total')
            ->groupBy('level')
            ->orderBy('level')
            ->get()
            ->map(function ($row) {
                $points = ControlPoint::where('level', $row->level)->get();

                return [
                    'level'     => (int) $row->level,
                    'label'     => 'Level ' . $row->level,
                    'total'     => (int) $row->total,
                    'districts' => ControlPoint::where('level', $row->level)
                        ->whereNotNull('District')
                        ->distinct()
                        ->orderBy('District')
                        ->pluck('District')
                        ->values(),
                    'summary'   => $this->summarise($points),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $levels,
            'count'   => $levels->count(),
        ]);
    }

    /** Construction status counts, so a caller need not tally them itself. */
    private function summarise($points): array
    {
        $byStatus = $points->groupBy('status')->map->count();

        return [
            'total'       => $points->count(),
            'constructed' => (int) ($byStatus['constructed'] ?? 0),
            'not_started' => (int) ($byStatus['not_started'] ?? 0),
            'in_progress' => (int) ($byStatus['in_progress'] ?? 0),
            'blocked'     => (int) ($byStatus['blocked'] ?? 0),
            'by_status'   => $byStatus,
        ];
    }
}
