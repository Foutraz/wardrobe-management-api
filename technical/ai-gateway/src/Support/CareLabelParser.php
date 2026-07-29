<?php

namespace Technical\AiGateway\Support;

class CareLabelParser
{
    private const SIZE_TOKENS = ['XXS', 'XXL', 'XXXL', 'XS', 'XL', 'S', 'M', 'L'];

    /**
     * Care labels name fibres from a bounded vocabulary, which is what keeps
     * "MADE IN PORTUGAL" from being read as one.
     *
     * @var array<string, string>
     */
    private const FIBRES = [
        'COTTON' => 'Cotton',
        'COTON' => 'Coton',
        'POLYESTER' => 'Polyester',
        'POLYAMIDE' => 'Polyamide',
        'ELASTANE' => 'Elastane',
        'ELASTHANNE' => 'Élasthanne',
        'ELASTHANE' => 'Élasthanne',
        'SPANDEX' => 'Spandex',
        'WOOL' => 'Wool',
        'LAINE' => 'Laine',
        'VISCOSE' => 'Viscose',
        'RAYON' => 'Rayon',
        'LINEN' => 'Linen',
        'LIN' => 'Lin',
        'SILK' => 'Silk',
        'SOIE' => 'Soie',
        'ACRYLIC' => 'Acrylic',
        'ACRYLIQUE' => 'Acrylique',
        'NYLON' => 'Nylon',
        'CASHMERE' => 'Cashmere',
        'CACHEMIRE' => 'Cachemire',
        'MODAL' => 'Modal',
        'LYOCELL' => 'Lyocell',
        'LEATHER' => 'Leather',
        'CUIR' => 'Cuir',
    ];

    /**
     * Pull the few facts a care label states reliably out of whatever the OCR produced.
     *
     * @return array<string, mixed>
     */
    public function parse(string $rawText): array
    {
        $normalised = trim(strtoupper(preg_replace('/\s+/', ' ', $rawText) ?? ''));

        return array_filter([
            'material_composition' => $this->composition($normalised),
            'size_label' => $this->size($normalised),
            'style_reference' => $this->styleReference($normalised),
        ], fn (mixed $reading): bool => $reading !== null);
    }

    /**
     * Collect every "68% COTTON" style fragment, which is the most dependable thing on a label.
     */
    private function composition(string $text): ?string
    {
        // Tesseract reads "95%" as "954", so a 4 is accepted where the percent sign belongs.
        // Three guards keep that from inventing shares: the separator is required, the share is
        // capped at 100, and the lookbehind stops the match starting inside a longer number.
        $pattern = sprintf(
            '/(?<!\d)(100|\d{1,2})\s*(?:%%|4)\s*(%s)\b/',
            implode('|', array_keys(self::FIBRES)),
        );

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $parts = [];

        foreach ($matches as $match) {
            $parts[] = sprintf('%d%% %s', (int) $match[1], self::FIBRES[$match[2]]);
        }

        return implode(', ', $parts);
    }

    /**
     * Find a size, preferring an explicit label over a bare letter that could be anything.
     */
    private function size(string $text): ?string
    {
        if (preg_match('/\b(?:SIZE|TAILLE|TALLA|GR(?:ÖSSE|OSSE))\s*[:\-]?\s*([A-Z0-9]{1,4})\b/', $text, $matches) === 1) {
            return $matches[1];
        }

        foreach (self::SIZE_TOKENS as $token) {
            if (preg_match('/(?<![A-Z0-9])'.$token.'(?![A-Z0-9])/', $text) === 1) {
                return $token;
            }
        }

        if (preg_match('/(?<![A-Z0-9\/])(3[0-9]|4[0-9]|5[0-8])(?![A-Z0-9\/%])/', $text, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Find a manufacturer style code, which is the only thing that can later match a catalogue entry.
     */
    private function styleReference(string $text): ?string
    {
        if (preg_match('/\b(?:REF|ART|STYLE|MODEL)\s*[.:\-]?\s*([A-Z0-9\-]{4,20})\b/', $text, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
