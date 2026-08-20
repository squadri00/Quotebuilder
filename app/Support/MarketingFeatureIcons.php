<?php

namespace App\Support;

/**
 * A curated set of SVG icons for the Features page card picker (Super
 * Admin > Website Management) — a label plus a 24x24 stroke-icon path
 * ("d" attribute) an admin picks from, rather than hand-typing SVG
 * markup. The first six entries match exactly what shipped on the
 * public Features page before it became database-driven.
 */
class MarketingFeatureIcons
{
    private const ICONS = [
        'Box / Builder' => 'M9 3v2m6-2v2M4 7h16M5 7h14v13a1 1 0 01-1 1H6a1 1 0 01-1-1V7z',
        'Trending / Growth' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'Code / Logic' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'Globe / Embed' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10h6m-6 4h4',
        'Document / PDF' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'Inbox / Mail' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'Building / Template' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m8-14h.01',
        'Checkmark / Shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'Lightning / Speed' => 'M13 10V3L4 14h7v7l9-11h-7z',
        'Lock / Security' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'Chart / Analytics' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2a2 2 0 00-2 2v14z',
        'Users / Team' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 110 8 4 4 0 010-8zm6 3a4 4 0 11-8 0 4 4 0 018 0z',
        'Gear / Settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'Puzzle / Integration' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1H3a2 2 0 110-4h1a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1V4z',
    ];

    /**
     * @return array<string, string> label => svg path "d" data
     */
    public static function options(): array
    {
        return self::ICONS;
    }

    /**
     * The stored value is the raw path data itself, not the label —
     * this is what validation checks against so only one of these
     * known-safe paths can ever be saved.
     */
    public static function paths(): array
    {
        return array_values(self::ICONS);
    }
}
