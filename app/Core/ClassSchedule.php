<?php

final class ClassSchedule
{
    public static function parse(?string $schedule): ?array
    {
        $schedule = trim((string)$schedule);
        if ($schedule === '' || preg_match('/chưa\s+xếp|chua\s+xep/i', $schedule)) {
            return null;
        }

        $normalized = mb_strtolower($schedule, 'UTF-8');
        preg_match_all('/tiết/u', $normalized, $periodLabels);
        if (count($periodLabels[0]) !== 1) {
            return null;
        }

        $days = [];
        preg_match_all('/thứ\s*([2-7])/u', $normalized, $numericDays);
        foreach ($numericDays[1] as $day) {
            $days[] = (int)$day;
        }

        $namedDays = [
            'hai' => 2,
            'ba' => 3,
            'tư' => 4,
            'tu' => 4,
            'năm' => 5,
            'sáu' => 6,
            'sau' => 6,
            'bảy' => 7,
            'bay' => 7,
        ];
        preg_match_all('/thứ\s*(hai|ba|tư|tu|năm|sáu|sau|bảy|bay)\b/u', $normalized, $words);
        foreach ($words[1] as $day) {
            $days[] = $namedDays[$day];
        }

        if (preg_match('/\b(?:chủ\s*nhật|chu\s*nhat|cn)\b/u', $normalized)) {
            $days[] = 8;
        }

        preg_match_all('/\bt([2-7])\b/u', $normalized, $shortDays);
        foreach ($shortDays[1] as $day) {
            $days[] = (int)$day;
        }

        $periods = [];
        preg_match_all('/tiết\s*(\d+)\s*(?:-|–|—|đến|den|to)\s*(\d+)/u', $normalized, $ranges, PREG_SET_ORDER);
        foreach ($ranges as $range) {
            $start = (int)$range[1];
            $end = (int)$range[2];
            if ($start > $end || $start < 1 || $end > 20) {
                return null;
            }
            $periods = array_merge($periods, range($start, $end));
        }

        $withoutRanges = preg_replace('/tiết\s*\d+\s*(?:-|–|—|đến|den|to)\s*\d+/u', '', $normalized);
        preg_match_all('/tiết\s*(\d+)/u', (string)$withoutRanges, $singlePeriods);
        foreach ($singlePeriods[1] as $period) {
            $period = (int)$period;
            if ($period < 1 || $period > 20) {
                return null;
            }
            $periods[] = $period;
        }

        $days = array_values(array_unique($days));
        $periods = array_values(array_unique($periods));
        sort($days);
        sort($periods);

        if ($days === [] || $periods === []) {
            return null;
        }

        return ['days' => $days, 'periods' => $periods];
    }

    public static function overlaps(?string $first, ?string $second): bool
    {
        $firstSchedule = self::parse($first);
        $secondSchedule = self::parse($second);
        if ($firstSchedule === null || $secondSchedule === null) {
            return false;
        }

        return array_intersect($firstSchedule['days'], $secondSchedule['days']) !== []
            && array_intersect($firstSchedule['periods'], $secondSchedule['periods']) !== [];
    }
}
