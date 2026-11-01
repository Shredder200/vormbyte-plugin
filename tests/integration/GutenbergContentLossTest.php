<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function Vormbyte\Abilities\Gutenberg\content_loss_message;
use function Vormbyte\Abilities\Gutenberg\content_loss_report;
use function Vormbyte\Abilities\Gutenberg\content_media_count;
use function Vormbyte\Abilities\Gutenberg\content_visible_text_length;

final class GutenbergContentLossTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        require_once dirname(__DIR__, 2) . '/includes/abilities/gutenberg/content-loss.php';
    }

    private static function paragraphs(int $count, string $text = 'Lorem ipsum dolor sit amet consectetur.'): string
    {
        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $out .= "<!-- wp:paragraph {\"className\":\"x\"} -->\n<p class=\"x\">{$text}</p>\n<!-- /wp:paragraph -->\n";
        }

        return $out;
    }

    private static function emptied(int $count): string
    {
        return str_repeat(
            "<!-- wp:paragraph {\"className\":\"x\"} -->\n<p class=\"x\"></p>\n<!-- /wp:paragraph -->\n",
            $count,
        );
    }

    public function testStyleOnlyRoundTripThatEmptiesTextIsReported(): void
    {
        $report = content_loss_report(self::paragraphs(20), self::emptied(20));

        self::assertNotNull($report);
        self::assertGreaterThan(200, $report['text_before']);
        self::assertSame(0, $report['text_after']);
    }

    public function testShortBaseIsNeverBlocked(): void
    {
        self::assertNull(content_loss_report(self::paragraphs(2), self::emptied(2)));
    }

    public function testUnchangedOrGrowingContentIsNotReported(): void
    {
        $base = self::paragraphs(20);

        self::assertNull(content_loss_report($base, $base));
        self::assertNull(content_loss_report($base, $base . self::paragraphs(5)));
    }

    public function testModerateShorteningIsNotReported(): void
    {
        self::assertNull(content_loss_report(self::paragraphs(20), self::paragraphs(12)));
    }

    public function testMediaLossIsReportedEvenWhenTextSurvives(): void
    {
        $text = self::paragraphs(10);
        $images = str_repeat(
            "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"a.jpg\" alt=\"\"/></figure>\n<!-- /wp:image -->\n",
            4,
        );
        $emptyImages = str_repeat(
            "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img alt=\"\"/></figure>\n<!-- /wp:image -->\n",
            4,
        );

        $report = content_loss_report($text . $images, $text . $emptyImages);

        self::assertNotNull($report);
        self::assertSame(4, $report['media_before']);
        self::assertSame(0, $report['media_after']);
    }

    public function testImagesLeftWithoutSourceAreReportedOnShortPages(): void
    {
        $live = "<!-- wp:image {\"id\":5} -->\n<figure class=\"wp-block-image\"><img src=\"a.jpg\" alt=\"\" class=\"wp-image-5\"/></figure>\n<!-- /wp:image -->\n";
        $candidate = "<!-- wp:image {\"id\":5} -->\n<figure class=\"wp-block-image\"><img alt=\"\" class=\"wp-image-5\"/></figure>\n<!-- /wp:image -->\n";

        $report = content_loss_report(str_repeat($live, 2), str_repeat($candidate, 2));

        self::assertNotNull($report);
        self::assertSame(2, $report['media_before']);
        self::assertSame(0, $report['media_after']);
    }

    public function testRemovingOneOrTwoImagesIsNotReported(): void
    {
        $image = "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"a.jpg\" alt=\"\"/></figure>\n<!-- /wp:image -->\n";
        $text = self::paragraphs(10);

        self::assertNull(content_loss_report($text . $image, $text));
        self::assertNull(content_loss_report($text . $image . $image, $text));
    }

    public function testInvalidUtf8DoesNotDisableTheTextGuard(): void
    {
        $live = self::paragraphs(20, "Caf\xE9 lorem ipsum dolor sit amet consectetur.");

        self::assertGreaterThan(200, content_visible_text_length($live));
        self::assertNotNull(content_loss_report($live, self::emptied(20)));
    }

    public function testDelimitersScriptsAndEntitiesAreNotText(): void
    {
        $content =
            "<!-- wp:paragraph {\"content\":\"hidden attribute text\"} -->\n<p>A&amp;B</p>\n<!-- /wp:paragraph -->\n"
            . '<script>var longScriptBody = 1;</script><style>.a{color:red}</style>';

        self::assertSame(3, content_visible_text_length($content));
    }

    public function testMediaCountCoversCommonEmbeds(): void
    {
        $content =
            '<img src="a.jpg"><video src="b.mp4"></video><audio src="c.mp3"></audio><iframe src="d"></iframe><p>imgx</p>'
            . '<img alt=""><img src="" alt="">';

        self::assertSame(4, content_media_count($content));
    }

    public function testMessageCarriesTheNumbersAndTheOverride(): void
    {
        $message = content_loss_message([
            'text_before' => 6338,
            'text_after' => 0,
            'media_before' => 2,
            'media_after' => 2,
        ]);

        self::assertStringContainsString('0 of 6338', $message);
        self::assertStringContainsString('allow_content_loss', $message);
        self::assertStringContainsString('gutenberg-delete-pending-batch', $message);
        self::assertStringContainsString('gutenberg-get-content', $message);
        self::assertStringNotContainsString('—', $message);
        self::assertLessThanOrEqual(300, mb_strlen($message));
    }

    public function testAddPendingChangeExposesTheOverride(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/includes/abilities/gutenberg/add-pending-change.php',
        );

        self::assertStringContainsString("'allow_content_loss' => [", $source);
        self::assertStringContainsString('translating or condensing', $source);
        self::assertStringContainsString("(\$input['allow_content_loss'] ?? false) === true", $source);
    }

    public function testCommitPathRunsTheGuardBeforeWritingUnlessAllowed(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/includes/abilities/gutenberg/bootstrap.php');
        $commit = substr($source, (int) strpos($source, 'function commit_prepared_items('));
        $firstWrite = (int) strpos($commit, 'wp_update_post(');

        self::assertStringContainsString("const META_ALLOW_CONTENT_LOSS = '_vormbyte_gb_allow_content_loss';", $source);
        $allowCheck = strpos($commit, '!item_allows_content_loss($item->ID)');
        $guard = strpos($commit, 'content_loss_report(');
        self::assertNotFalse($allowCheck);
        self::assertNotFalse($guard);
        self::assertLessThan($guard, $allowCheck);
        self::assertLessThan($firstWrite, $guard);
    }

    public function testReadToolAndSkillWarnAgainstRoundTrip(): void
    {
        $root = dirname(__DIR__, 2);
        $getContent = (string) file_get_contents($root . '/includes/abilities/gutenberg/get-content.php');
        $skill = (string) file_get_contents($root . '/includes/skills/built-in/gutenberg-edit-content.md');

        self::assertStringContainsString("'roundtrip_safe' => false,", $getContent);
        self::assertStringContainsString('not a block_spec', $getContent);
        self::assertStringContainsString('not a `block_spec`', $skill);
    }
}
