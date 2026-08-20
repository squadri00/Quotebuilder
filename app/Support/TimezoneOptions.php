<?php

namespace App\Support;

use DateTime;
use DateTimeZone;

/**
 * Every real PHP/IANA timezone identifier (e.g. "America/Toronto"),
 * grouped by region and labeled with its current UTC offset — used for
 * the Timezone dropdown in Business Settings. Built from PHP's own
 * timezone database rather than a hand-maintained list, so it never goes
 * stale.
 */
class TimezoneOptions
{
    /**
     * @return array<string, array<string, string>> region => [identifier => label]
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            if (! str_contains($identifier, '/')) {
                continue;
            }

            [$region] = explode('/', $identifier, 2);
            $offset = (new DateTime('now', new DateTimeZone($identifier)))->format('P');
            $label = str_replace('_', ' ', $identifier).' (UTC'.$offset.')';

            $groups[$region][$identifier] = $label;
        }

        ksort($groups);

        return $groups;
    }
}
