<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\Carbon;

class NipGeneratorService
{
    public function generateNip(Carbon $joinDate = null): string
    {
        $joinDate = $joinDate ?? now();

        $formatSetting = SystemSetting::where('key', 'nip_format')->first();
        $counterSetting = SystemSetting::where('key', 'nip_counter')->first();

        $format = $formatSetting ? $formatSetting->value : 'EMP-[YYYY][MM]-[ID]';
        $counter = $counterSetting ? (int)$counterSetting->value : 1;

        // Parse variables
        $nip = $format;
        $nip = str_replace('[YYYY]', $joinDate->format('Y'), $nip);
        $nip = str_replace('[YY]', $joinDate->format('y'), $nip);
        $nip = str_replace('[MM]', $joinDate->format('m'), $nip);
        $nip = str_replace('[DD]', $joinDate->format('d'), $nip);

        // Replace [ID] with padded counter
        // First count how many [ID] or if it's just one [ID] we pad it to 4 digits default
        // But for dynamic, maybe we just pad it to 4 digits always
        $nip = str_replace('[ID]', str_pad($counter, 4, '0', STR_PAD_LEFT), $nip);

        // Increment counter
        if ($counterSetting) {
            $counterSetting->update(['value' => $counter + 1]);
        } else {
            SystemSetting::create(['key' => 'nip_counter', 'value' => $counter + 1]);
        }

        return $nip;
    }
}
