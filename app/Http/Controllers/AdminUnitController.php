<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Administrative boundaries (division → district → tehsil → mouza) straight from
 * the client's administrative_boundaries tables. Every parent filter is optional
 * and matched by name, case-insensitively.
 */
class AdminUnitController extends Controller
{
    private const LEVELS = [
        'division' => ['table' => 'division', 'name' => 'division_name', 'keys' => ['province_code', 'division_code']],
        'district' => ['table' => 'district', 'name' => 'district_name', 'keys' => ['province_code', 'division_code', 'district_code']],
        'tehsil'   => ['table' => 'tehsil',   'name' => 'tehsil_name',   'keys' => ['province_code', 'division_code', 'district_code', 'tehsil_code']],
        'mouza'    => ['table' => 'mouza',    'name' => 'mouza_name',    'keys' => ['province_code', 'division_code', 'district_code', 'tehsil_code', 'mouza_code']],
    ];

    /**
     * GET|POST /api/admin-units/{level}
     * Payload (all optional): division, district, tehsil, name, geometry (false = names only)
     */
    public function index(Request $request, string $level)
    {
        if (!isset(self::LEVELS[$level])) {
            return response()->json([
                'success' => false,
                'message' => 'Level must be one of: ' . implode(', ', array_keys(self::LEVELS)) . '.',
            ], 422);
        }

        $cfg = self::LEVELS[$level];
        $joins = [];
        $where = [];
        $bindings = [];

        // Parent names live in their own tables; join each parent the caller filters by.
        foreach (['division', 'district', 'tehsil'] as $parent) {
            $value = $request->input($parent);
            if ($parent === $level || !is_string($value) || trim($value) === '') {
                continue;
            }
            $p = self::LEVELS[$parent];
            if (count($p['keys']) >= count($cfg['keys'])) {
                continue; // not a parent of this level
            }
            $on = implode(' AND ', array_map(fn ($k) => "{$parent}_p.{$k} = u.{$k}", $p['keys']));
            $joins[] = "JOIN administrative_boundaries.{$p['table']} {$parent}_p ON {$on}";
            $where[] = "LOWER({$parent}_p.{$p['name']}) = ?";
            $bindings[] = mb_strtolower(trim($value));
        }

        $name = $request->input('name');
        if (is_string($name) && trim($name) !== '') {
            $where[] = "LOWER(u.{$cfg['name']}) = ?";
            $bindings[] = mb_strtolower(trim($name));
        }

        $from = "FROM administrative_boundaries.{$cfg['table']} u " . implode(' ', $joins)
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

        if (filter_var($request->input('geometry', true), FILTER_VALIDATE_BOOLEAN) === false) {
            $names = collect(DB::select("SELECT DISTINCT u.{$cfg['name']} AS name {$from} ORDER BY 1", $bindings))
                ->pluck('name')->filter()->values();

            return response()->json(['success' => true, 'data' => $names]);
        }

        // Parent names are attached so a loaded layer is self-describing.
        $nameCols = [];
        foreach (array_keys(self::LEVELS) as $l) {
            $nameCols[] = "(SELECT x.{$l}_name FROM administrative_boundaries.{$l} x WHERE "
                . implode(' AND ', array_map(fn ($k) => "x.{$k} = u.{$k}", self::LEVELS[$l]['keys']))
                . " LIMIT 1) AS \"" . ucfirst($l) . '"';
            if ($l === $level) {
                break;
            }
        }

        $rows = DB::select(
            'SELECT ' . implode(', ', $nameCols) . ', '
            . implode(', ', array_map(fn ($k) => "u.{$k}", $cfg['keys'])) . ', '
            . 'round((u.shape_area / 1000000)::numeric, 3) AS area_sq_km, '
            . "ST_AsGeoJSON(ST_Transform(u.geom, 4326), 7) AS geometry {$from} ORDER BY u.{$cfg['name']}",
            $bindings
        );

        $features = array_map(function ($row) {
            $props = (array) $row;
            $geometry = json_decode($props['geometry'], true);
            unset($props['geometry']);

            return ['type' => 'Feature', 'properties' => $props, 'geometry' => $geometry];
        }, $rows);

        return response()->json([
            'success' => true,
            'level'   => $level,
            'data'    => ['type' => 'FeatureCollection', 'features' => $features],
            'count'   => count($features),
        ]);
    }
}
