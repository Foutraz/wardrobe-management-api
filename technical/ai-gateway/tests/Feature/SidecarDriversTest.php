<?php

namespace Technical\AiGateway\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Technical\AiGateway\Drivers\HttpCareLabelReader;
use Technical\AiGateway\Drivers\HttpCutoutDriver;
use Technical\AiGateway\Enums\AiOperationStatus;
use Technical\AiGateway\Support\CareLabelParser;
use Tests\TestCase;

class SidecarDriversTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://ai:9100';

    public function test_the_cutout_driver_reports_an_unreachable_sidecar_as_unavailable(): void
    {
        Http::fake([self::BASE_URL.'/health' => Http::response(status: 500)]);

        $this->assertFalse($this->cutoutDriver()->isAvailable());
    }

    public function test_the_cutout_driver_sees_a_healthy_sidecar(): void
    {
        Http::fake([self::BASE_URL.'/health' => Http::response(['status' => 'ok'])]);

        $this->assertTrue($this->cutoutDriver()->isAvailable());
    }

    public function test_the_cutout_driver_writes_the_png_the_sidecar_returned(): void
    {
        Http::fake([self::BASE_URL.'/cutout' => Http::response($this->transparentPng())]);

        $source = $this->scratchJpeg();
        $destination = tempnam(sys_get_temp_dir(), 'cutout-').'.png';

        $outcome = $this->cutoutDriver()->removeBackground($source, $destination);

        $this->assertSame(AiOperationStatus::Succeeded, $outcome->status);
        $this->assertSame('image/png', (string) mime_content_type($destination));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/cutout'
            && $request->isMultipart());
    }

    public function test_the_cutout_driver_reports_the_status_a_failing_sidecar_answered(): void
    {
        Http::fake([self::BASE_URL.'/cutout' => Http::response(status: 503)]);

        $outcome = $this->cutoutDriver()->removeBackground($this->scratchJpeg(), '/tmp/unused.png');

        $this->assertSame(AiOperationStatus::Failed, $outcome->status);
        $this->assertStringContainsString('503', (string) $outcome->failureReason);
    }

    public function test_the_label_reader_turns_ocr_text_into_attributes(): void
    {
        Http::fake([self::BASE_URL.'/ocr' => Http::response([
            'text' => "95% VISCOSE 5% ELASTANE\nTAILLE: M\nREF. AB1234\n",
        ])]);

        $reading = $this->labelReader()->read($this->scratchJpeg());

        $this->assertSame(AiOperationStatus::Succeeded, $reading->status);
        $this->assertSame('95% Viscose, 5% Elastane', $reading->attributes['material_composition']);
        $this->assertSame('M', $reading->attributes['size_label']);
        $this->assertSame('AB1234', $reading->attributes['style_reference']);
        $this->assertSame(100, $reading->confidence);
    }

    public function test_the_label_reader_rates_a_partial_reading_below_full_confidence(): void
    {
        Http::fake([self::BASE_URL.'/ocr' => Http::response(['text' => '100% COTTON'])]);

        $reading = $this->labelReader()->read($this->scratchJpeg());

        $this->assertSame(AiOperationStatus::Succeeded, $reading->status);
        $this->assertSame(33, $reading->confidence);
    }

    public function test_an_unreadable_label_fails_rather_than_returning_nothing(): void
    {
        Http::fake([self::BASE_URL.'/ocr' => Http::response(['text' => 'ø ø ø'])]);

        $reading = $this->labelReader()->read($this->scratchJpeg());

        $this->assertSame(AiOperationStatus::Failed, $reading->status);
        $this->assertSame([], $reading->attributes);
    }

    /**
     * Build the cutout driver pointed at the faked sidecar.
     */
    private function cutoutDriver(): HttpCutoutDriver
    {
        return new HttpCutoutDriver(self::BASE_URL, 30);
    }

    /**
     * Build the label reader pointed at the faked sidecar.
     */
    private function labelReader(): HttpCareLabelReader
    {
        return new HttpCareLabelReader(new CareLabelParser, self::BASE_URL, 30);
    }

    /**
     * Write a throwaway JPEG for the driver to send.
     */
    private function scratchJpeg(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'label-').'.jpg';
        $canvas = imagecreatetruecolor(32, 32);
        imagejpeg($canvas, $path);
        imagedestroy($canvas);

        return $path;
    }

    /**
     * Build the bytes of a transparent PNG, standing in for a real cutout.
     */
    private function transparentPng(): string
    {
        $canvas = imagecreatetruecolor(32, 32);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

        if ($transparent !== false) {
            imagefill($canvas, 0, 0, $transparent);
        }

        ob_start();
        imagepng($canvas);
        $bytes = (string) ob_get_clean();
        imagedestroy($canvas);

        return $bytes;
    }
}
