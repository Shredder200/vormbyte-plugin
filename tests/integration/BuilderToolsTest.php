<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BuilderToolsTest extends TestCase
{
    private const DIVI = '[et_pb_section fb_built="1" _builder_version="4.27.0"][et_pb_row][et_pb_column type="4_4"]'
        . '[et_pb_text admin_label="Intro"]<h1>Welkom</h1><p>Vers brood.</p>[/et_pb_text]'
        . '[et_pb_button button_text="Bel ons" button_url="tel:0612345678" module_id="cta" /]'
        . '[et_pb_divider][/et_pb_column][/et_pb_row][/et_pb_section]';

    protected function setUp(): void
    {
        $GLOBALS['vormbyte_test_posts'] = [];
        if (!function_exists('get_post_meta')) {
            eval('function get_post_meta($id, $key, $single = false) { return $GLOBALS["vormbyte_test_posts"][$id]["meta"][$key] ?? ""; }');
            eval('function get_post_field($field, $id) { return $GLOBALS["vormbyte_test_posts"][$id]["content"] ?? ""; }');
            eval('function get_the_title($id) { return "Post " . $id; }');
            eval('function has_blocks($content) { return str_contains((string) $content, "<!-- wp:"); }');
        }
        require_once __DIR__ . '/../../includes/builder-tools.php';
    }

    public function testShortcodeTreeKeepsNestingAttributesAndOffsets(): void
    {
        $tree = vormbyte_parse_shortcode_tree(self::DIVI);

        self::assertCount(1, $tree);
        self::assertSame('et_pb_section', $tree[0]['tag']);
        $column = $tree[0]['children'][0]['children'][0];
        self::assertSame('4_4', $column['attrs']['type']);
        self::assertSame(['et_pb_text', 'et_pb_button', 'et_pb_divider'], array_column($column['children'], 'tag'));

        $text = $column['children'][0];
        self::assertSame('<h1>Welkom</h1><p>Vers brood.</p>', $text['content']);

        $button = $column['children'][1];
        self::assertSame('tel:0612345678', $button['attrs']['button_url']);
        self::assertStringStartsWith('[et_pb_button ', substr(self::DIVI, $button['offset']));
    }

    public function testUnbalancedShortcodesDoNotFail(): void
    {
        self::assertCount(2, vormbyte_parse_shortcode_tree('[et_pb_section][et_pb_text]half'));
        self::assertSame([], vormbyte_parse_shortcode_tree('no shortcodes here'));
    }

    public function testDetectsBuilderPerPost(): void
    {
        $GLOBALS['vormbyte_test_posts'] = [
            1 => ['content' => self::DIVI, 'meta' => ['_et_pb_use_builder' => 'on']],
            2 => ['content' => '<!-- wp:divi/section --><!-- /wp:divi/section -->', 'meta' => ['_et_pb_use_builder' => 'on']],
            3 => ['content' => '', 'meta' => ['_elementor_edit_mode' => 'builder']],
            4 => ['content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->', 'meta' => []],
            5 => ['content' => '<p>Classic</p>', 'meta' => []],
            6 => ['content' => self::DIVI, 'meta' => []],
        ];

        self::assertSame('divi', vormbyte_builder_of_post(1));
        self::assertSame('divi5', vormbyte_builder_of_post(2));
        self::assertSame('elementor', vormbyte_builder_of_post(3));
        self::assertSame('blocks', vormbyte_builder_of_post(4));
        self::assertSame('classic', vormbyte_builder_of_post(5));
        // Imported Divi layouts without the builder flag are still Divi content.
        self::assertSame('divi', vormbyte_builder_of_post(6));
    }

    public function testOutlinesDiviAndElementor(): void
    {
        $GLOBALS['vormbyte_test_posts'] = [
            1 => ['content' => self::DIVI, 'meta' => ['_et_pb_use_builder' => 'on']],
            2 => ['content' => '', 'meta' => ['_elementor_edit_mode' => 'builder', '_elementor_data' => (string) json_encode([
                ['id' => 'a1b2c3d4', 'elType' => 'container', 'settings' => [], 'elements' => [
                    ['id' => 'e5f6a7b8', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['title' => 'Diensten'], 'elements' => []],
                ]],
            ])]],
        ];

        $divi = vormbyte_builder_outline(1);
        self::assertSame('divi', $divi['builder']);
        $types = array_column($divi['nodes'], 'type');
        self::assertSame(['et_pb_section', 'et_pb_row', 'et_pb_column', 'et_pb_text', 'et_pb_button', 'et_pb_divider'], $types);
        self::assertSame('Welkom Vers brood.', $divi['nodes'][3]['text']);
        self::assertSame('Intro', $divi['nodes'][3]['attrs']['admin_label']);

        $elementor = vormbyte_builder_outline(2);
        self::assertSame('elementor', $elementor['builder']);
        self::assertSame('e5f6a7b8', $elementor['nodes'][1]['id']);
        self::assertSame('heading', $elementor['nodes'][1]['type']);
        self::assertSame('Diensten', $elementor['nodes'][1]['text']);
        self::assertSame(1, $elementor['nodes'][1]['depth']);
    }
}
