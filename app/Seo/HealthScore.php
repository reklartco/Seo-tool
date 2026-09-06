<?php

namespace App\Seo;

/**
 * Spec §4: 100 - (critical*5 + warning*2 + notice*0.5) / pages * 10, clamped.
 */
class HealthScore
{
    public static function calculate(int $critical, int $warning, int $notice, int $pages): int
    {
        if ($pages <= 0) {
            return 0;
        }

        $penalty = ($critical * 5 + $warning * 2 + $notice * 0.5) / $pages * 10;

        return (int) round(max(0, min(100, 100 - $penalty)));
    }
}
