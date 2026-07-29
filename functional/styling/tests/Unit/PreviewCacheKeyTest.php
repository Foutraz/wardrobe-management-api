<?php

namespace Functional\Styling\Tests\Unit;

use Functional\Styling\Enums\PreviewMode;
use Functional\Styling\Values\PreviewCacheKey;
use PHPUnit\Framework\TestCase;

class PreviewCacheKeyTest extends TestCase
{
    public function test_the_order_the_items_arrive_in_does_not_change_the_key(): void
    {
        $first = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['b', 'a', 'c']);
        $second = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['c', 'a', 'b']);

        $this->assertSame($first->value, $second->value);
    }

    public function test_a_repeated_item_does_not_change_the_key(): void
    {
        $once = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a', 'b']);
        $twice = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a', 'b', 'a']);

        $this->assertSame($once->value, $twice->value);
    }

    public function test_a_different_set_of_items_yields_a_different_key(): void
    {
        $first = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a', 'b']);
        $second = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a', 'b', 'c']);

        $this->assertNotSame($first->value, $second->value);
    }

    public function test_each_mode_gets_its_own_key(): void
    {
        $flatLay = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a']);
        $tryOn = PreviewCacheKey::for(PreviewMode::AvatarTryOn, null, ['a']);

        $this->assertNotSame($flatLay->value, $tryOn->value);
    }

    public function test_a_new_avatar_version_invalidates_the_key(): void
    {
        $first = PreviewCacheKey::for(PreviewMode::AvatarTryOn, 'version-one', ['a']);
        $second = PreviewCacheKey::for(PreviewMode::AvatarTryOn, 'version-two', ['a']);

        $this->assertNotSame($first->value, $second->value);
    }

    public function test_the_key_fits_the_column_it_is_stored_in(): void
    {
        $cacheKey = PreviewCacheKey::for(PreviewMode::FlatLay, null, ['a', 'b', 'c']);

        $this->assertSame(64, strlen($cacheKey->value));
    }
}
