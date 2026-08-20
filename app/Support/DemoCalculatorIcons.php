<?php

namespace App\Support;

/**
 * A curated set of SVG icons for the Demo Calculators picker (Super
 * Admin > Website Management) — a label plus a 24x24 stroke-icon path
 * ("d" attribute) an admin picks from, rather than hand-typing SVG
 * markup. The six original entries match exactly what shipped on the
 * public Demo page before it became database-driven; the rest are
 * generic enough to fit most future industries.
 */
class DemoCalculatorIcons
{
    private const ICONS = [
        'Printing' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h14z',
        'HVAC / Climate' => 'M12 8v8m-4-5v5m8-9v9M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z',
        'Cabinets / Woodwork' => 'M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm0 6h16M10 6v12',
        'Manufacturing / Fabrication' => 'M13 10V3L4 14h7v7l9-11h-7z',
        'Remodeling / Home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'Furniture' => 'M4 6h16M4 12h16M4 18h7',
        'Building / Construction' => 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h.01M9 13h.01M9 17h.01',
        'Tools / Trades' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'Vehicle / Automotive' => 'M8 17h8m-8 0a2 2 0 11-4 0m4 0a2 2 0 10-4 0m12 0a2 2 0 104 0m-4 0a2 2 0 114 0m-16-5l1.5-4.5A2 2 0 016.4 6h11.2a2 2 0 011.9 1.5L21 12v5H3v-5z',
        'General / Calculator' => 'M9 7h6m-6 4h6m-6 4h3m5 5H7a2 2 0 01-2-2V6a2 2 0 012-2h6l6 6v8a2 2 0 01-2 2z',
    ];

    /**
     * @return array<string, string> label => svg path "d" data
     */
    public static function options(): array
    {
        return self::ICONS;
    }

    /**
     * The stored value is the raw path data itself (see demo_calculators
     * migration), not the label — this is what validation checks against
     * so only one of these known-safe paths can ever be saved.
     */
    public static function paths(): array
    {
        return array_values(self::ICONS);
    }
}
