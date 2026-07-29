<?php

namespace Technical\AiGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Technical\AiGateway\Support\CareLabelParser;

class CareLabelParserTest extends TestCase
{
    private CareLabelParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CareLabelParser;
    }

    public function test_it_reads_a_single_fibre_composition(): void
    {
        $parsed = $this->parser->parse('100% COTTON  MADE IN PORTUGAL');

        $this->assertSame('100% Cotton', $parsed['material_composition']);
    }

    public function test_it_survives_ocr_misreading_the_percent_sign(): void
    {
        // Observed from a real tesseract run: "95%" came back as "954".
        $parsed = $this->parser->parse('954 VISCOSE 5% ELASTANE');

        $this->assertSame('95% Viscose, 5% Elastane', $parsed['material_composition']);
    }

    public function test_it_refuses_to_invent_a_share_from_a_longer_number(): void
    {
        $parsed = $this->parser->parse('REF 954321 COTTON JACKET');

        $this->assertArrayNotHasKey('material_composition', $parsed);
    }

    public function test_it_does_not_mistake_a_wash_temperature_for_a_share(): void
    {
        // A degree sign is a washing instruction, never a proportion.
        $parsed = $this->parser->parse('LAVER A 60° LAINE');

        $this->assertArrayNotHasKey('material_composition', $parsed);
    }

    public function test_it_reads_a_blended_composition_in_order(): void
    {
        $parsed = $this->parser->parse('68% COTTON 29% POLYAMIDE 3% ELASTANE');

        $this->assertSame('68% Cotton, 29% Polyamide, 3% Elastane', $parsed['material_composition']);
    }

    public function test_it_reads_a_labelled_size(): void
    {
        $this->assertSame('M', $this->parser->parse('SIZE: M')['size_label']);
        $this->assertSame('40', $this->parser->parse('TAILLE 40')['size_label']);
    }

    public function test_it_falls_back_to_a_bare_size_token(): void
    {
        $this->assertSame('XL', $this->parser->parse('COTTON XL WASH COLD')['size_label']);
    }

    public function test_it_does_not_mistake_a_letter_inside_a_word_for_a_size(): void
    {
        $parsed = $this->parser->parse('MADE IN ITALY WASH SEPARATELY');

        $this->assertArrayNotHasKey('size_label', $parsed);
    }

    public function test_it_reads_a_style_reference(): void
    {
        $this->assertSame('AB12-993', $this->parser->parse('REF. AB12-993')['style_reference']);
        $this->assertSame('SS24BLZ01', $this->parser->parse('STYLE: SS24BLZ01')['style_reference']);
    }

    public function test_it_returns_nothing_it_could_not_find(): void
    {
        $parsed = $this->parser->parse('WASH AT 30 DEGREES DO NOT BLEACH');

        $this->assertArrayNotHasKey('material_composition', $parsed);
        $this->assertArrayNotHasKey('style_reference', $parsed);
    }

    public function test_it_survives_the_noise_ocr_produces(): void
    {
        $parsed = $this->parser->parse("  \n 95%  VISCOSE   5%\tELASTANE \n\n SIZE : S \n ");

        $this->assertSame('95% Viscose, 5% Elastane', $parsed['material_composition']);
        $this->assertSame('S', $parsed['size_label']);
    }

    public function test_it_returns_an_empty_reading_for_empty_input(): void
    {
        $this->assertSame([], $this->parser->parse(''));
    }
}
