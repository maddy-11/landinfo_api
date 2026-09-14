<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlPoint extends Model
{
    use HasFactory;

    protected $table = 'control_point';

    protected $fillable = [
        'level',
        'bm_code',
        'lims_id',
        'District',
        'Tehsil',
        'status_raw',
        'status',
        'execution',
        'within_10k',
        'distance',
        'alt_location',
        'original_x',
        'original_y',
        'altered_x',
        'altered_y',
        'remarks',
        'geometry',
    ];

    protected function casts(): array
    {
        return [
            'geometry'   => 'array',
            'level'      => 'integer',
            'original_x' => 'decimal:8',
            'original_y' => 'decimal:8',
            'altered_x'  => 'decimal:8',
            'altered_y'  => 'decimal:8',
        ];
    }

    /**
     * Collapses the many spellings in the source files onto a small, stable set
     * the dashboards can count on.
     */
    public static function normaliseStatus(?string $raw): string
    {
        $v = strtolower(trim((string) $raw));

        if ($v === '') {
            return 'unknown';
        }
        if (str_contains($v, 'construct') || str_contains($v, 'complete') || str_contains($v, 'done')) {
            return 'constructed';
        }
        if (str_contains($v, 'not_start') || str_contains($v, 'not start') || str_contains($v, 'pending')) {
            return 'not_started';
        }
        if (str_contains($v, 'progress') || str_contains($v, 'ongoing')) {
            return 'in_progress';
        }
        // "scurity risk/helt", access problems, and anything else blocking work.
        if (str_contains($v, 'risk') || str_contains($v, 'secur') || str_contains($v, 'scurity')
            || str_contains($v, 'block') || str_contains($v, 'access')) {
            return 'blocked';
        }

        return 'other';
    }
}
