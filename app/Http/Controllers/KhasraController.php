<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Karam;
use Illuminate\Http\Request;

class KhasraController extends Controller
{
    public function getKhasraWithKarams($khasraNo)
    {
        $parcel = Parcel::where('Khassra_No', $khasraNo)->first();

        if (!$parcel) {
            return view('khasra.notfound', ['khasra_no' => $khasraNo]);
        }

        $allKarams = Karam::all();
        $matchingKarams = $allKarams->filter(function($karam) use ($parcel) {
            return $this->lineIntersectsOrContainedInPolygon(
                $karam->geometry,
                $parcel->geometry
            );
        });

        $parcelFeature = [
            'type' => 'Feature',
            'properties' => [
                'id' => $parcel->id,
                'Khassra_No' => $parcel->Khassra_No,
                'Mauza_Name' => $parcel->Mauza_Name,
                'PC_Name' => $parcel->PC_Name,
            ],
            'geometry' => $parcel->geometry
        ];

        $karamFeatures = $matchingKarams->map(function($karam) {
            $length = $this->calculateLineLength($karam->geometry);
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $karam->id,
                    'Karam' => $karam->Karam,
                    'Karam_Urdu' => $karam->Karam_Urdu,
                    'length' => $length,
                    'length_meters' => round($length, 2),
                    'length_feet' => round($length * 3.28084, 2),
                ],
                'geometry' => $karam->geometry
            ];
        })->values();

        return view('khasra.diagram', [
            'parcel' => $parcelFeature,
            'karams' => $karamFeatures,
            'khasra_no' => $khasraNo,
            'count' => $matchingKarams->count()
        ]);
    }

    private function lineIntersectsOrContainedInPolygon($lineString, $polygon)
    {
        if (!isset($lineString['coordinates']) || !isset($polygon['coordinates'])) {
            return false;
        }

        $lineCoords = $lineString['coordinates'];
        $polygonRings = $polygon['coordinates'];
        $outerRing = $polygonRings[0] ?? [];

        if (empty($outerRing) || empty($lineCoords) || count($lineCoords) < 2) {
            return false;
        }

        $polygonBounds = $this->getBoundingBox($outerRing);
        $lineBounds = $this->getBoundingBox($lineCoords);

        if (!$this->boundingBoxesOverlap($polygonBounds, $lineBounds)) {
            return false;
        }

        $hasPointInside = false;
        foreach ($lineCoords as $point) {
            if ($this->pointInPolygon($point, $outerRing)) {
                $hasPointInside = true;
                break;
            }
        }

        if ($hasPointInside) {
            return true;
        }

        for ($i = 0; $i < count($lineCoords) - 1; $i++) {
            $p1 = $lineCoords[$i];
            $p2 = $lineCoords[$i + 1];
            
            if (!is_array($p1) || !is_array($p2) || count($p1) < 2 || count($p2) < 2) {
                continue;
            }

            for ($j = 0; $j < count($outerRing) - 1; $j++) {
                $p3 = $outerRing[$j];
                $p4 = $outerRing[$j + 1];
                
                if (!is_array($p3) || !is_array($p4) || count($p3) < 2 || count($p4) < 2) {
                    continue;
                }

                if ($this->segmentsIntersect($p1, $p2, $p3, $p4)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getBoundingBox($coords)
    {
        $minX = PHP_FLOAT_MAX;
        $minY = PHP_FLOAT_MAX;
        $maxX = PHP_FLOAT_MIN;
        $maxY = PHP_FLOAT_MIN;

        foreach ($coords as $coord) {
            if (!is_array($coord) || count($coord) < 2) continue;
            $x = is_numeric($coord[0]) ? (float)$coord[0] : 0;
            $y = is_numeric($coord[1]) ? (float)$coord[1] : 0;
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }

        return ['minX' => $minX, 'minY' => $minY, 'maxX' => $maxX, 'maxY' => $maxY];
    }

    private function boundingBoxesOverlap($box1, $box2)
    {
        return !($box1['maxX'] < $box2['minX'] || 
                 $box2['maxX'] < $box1['minX'] || 
                 $box1['maxY'] < $box2['minY'] || 
                 $box2['maxY'] < $box1['minY']);
    }

    private function calculateLineLength($geometry)
    {
        if (!isset($geometry['coordinates']) || $geometry['type'] !== 'LineString') {
            return 0;
        }

        $coords = $geometry['coordinates'];
        if (count($coords) < 2) {
            return 0;
        }

        $totalLength = 0;
        for ($i = 0; $i < count($coords) - 1; $i++) {
            $p1 = $coords[$i];
            $p2 = $coords[$i + 1];
            
            if (!is_array($p1) || !is_array($p2) || count($p1) < 2 || count($p2) < 2) {
                continue;
            }

            $lon1 = is_numeric($p1[0]) ? (float)$p1[0] : 0;
            $lat1 = is_numeric($p1[1]) ? (float)$p1[1] : 0;
            $lon2 = is_numeric($p2[0]) ? (float)$p2[0] : 0;
            $lat2 = is_numeric($p2[1]) ? (float)$p2[1] : 0;

            $totalLength += $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
        }

        return $totalLength;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function pointInPolygon($point, $polygon)
    {
        if (!is_array($point) || count($point) < 2) {
            return false;
        }

        $x = is_numeric($point[0]) ? (float)$point[0] : 0;
        $y = is_numeric($point[1]) ? (float)$point[1] : 0;
        $inside = false;

        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            if (!is_array($polygon[$i]) || !is_array($polygon[$j]) || count($polygon[$i]) < 2 || count($polygon[$j]) < 2) {
                continue;
            }

            $xi = is_numeric($polygon[$i][0]) ? (float)$polygon[$i][0] : 0;
            $yi = is_numeric($polygon[$i][1]) ? (float)$polygon[$i][1] : 0;
            $xj = is_numeric($polygon[$j][0]) ? (float)$polygon[$j][0] : 0;
            $yj = is_numeric($polygon[$j][1]) ? (float)$polygon[$j][1] : 0;

            if (abs($yj - $yi) < 1e-10) {
                continue;
            }

            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    private function lineIntersectsPolygonBoundary($lineCoords, $polygonRing)
    {
        if (!is_array($lineCoords) || !is_array($polygonRing)) {
            return false;
        }

        $n = count($lineCoords);
        $m = count($polygonRing);

        if ($n < 2 || $m < 2) {
            return false;
        }

        for ($i = 0; $i < $n - 1; $i++) {
            if (!is_array($lineCoords[$i]) || !is_array($lineCoords[$i + 1])) {
                continue;
            }

            $p1 = $lineCoords[$i];
            $p2 = $lineCoords[$i + 1];

            for ($j = 0; $j < $m - 1; $j++) {
                if (!is_array($polygonRing[$j]) || !is_array($polygonRing[$j + 1])) {
                    continue;
                }

                $p3 = $polygonRing[$j];
                $p4 = $polygonRing[$j + 1];

                if ($this->segmentsIntersect($p1, $p2, $p3, $p4)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function segmentsIntersect($p1, $p2, $p3, $p4)
    {
        if (!is_array($p1) || !is_array($p2) || !is_array($p3) || !is_array($p4)) {
            return false;
        }

        $x1 = is_numeric($p1[0]) ? (float)$p1[0] : 0;
        $y1 = is_numeric($p1[1]) ? (float)$p1[1] : 0;
        $x2 = is_numeric($p2[0]) ? (float)$p2[0] : 0;
        $y2 = is_numeric($p2[1]) ? (float)$p2[1] : 0;
        $x3 = is_numeric($p3[0]) ? (float)$p3[0] : 0;
        $y3 = is_numeric($p3[1]) ? (float)$p3[1] : 0;
        $x4 = is_numeric($p4[0]) ? (float)$p4[0] : 0;
        $y4 = is_numeric($p4[1]) ? (float)$p4[1] : 0;

        $d = ($x2 - $x1) * ($y4 - $y3) - ($y2 - $y1) * ($x4 - $x3);
        if (abs($d) < 1e-10) {
            return false;
        }

        $t = (($x3 - $x1) * ($y4 - $y3) - ($y3 - $y1) * ($x4 - $x3)) / $d;
        $u = (($x3 - $x1) * ($y2 - $y1) - ($y3 - $y1) * ($x2 - $x1)) / $d;

        return $t >= 0 && $t <= 1 && $u >= 0 && $u <= 1;
    }
}

