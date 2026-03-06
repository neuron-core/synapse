<?php

declare(strict_types=1);

namespace NeuronCore\Synapse\Tests\Rendering\Renderers;

use NeuronCore\Synapse\Rendering\Renderers\SnippetRenderer;
use NeuronCore\Synapse\Rendering\ToolRenderer;
use PHPUnit\Framework\TestCase;

class SnippetRendererTest extends TestCase
{
    public function testImplementsToolRenderer(): void
    {
        $this->assertInstanceOf(ToolRenderer::class, new SnippetRenderer(['key']));
    }

    public function testRenderExtractsSingleKey(): void
    {
        $renderer = new SnippetRenderer(['file_path']);
        $result = $renderer->render('read_file', '{"file_path": "src/Foo.php"}');

        $this->assertSame("● read_file( src/Foo.php )\n", $result);
    }

    public function testRenderExtractsMultipleKeys(): void
    {
        $renderer = new SnippetRenderer(['pattern', 'file_path']);
        $result = $renderer->render('grep_file_content', '{"pattern": "TODO", "file_path": "src/Foo.php"}');

        $this->assertSame("● grep_file_content( TODO, src/Foo.php )\n", $result);
    }

    public function testRenderSkipsMissingKeys(): void
    {
        $renderer = new SnippetRenderer(['pattern', 'file_path']);
        $result = $renderer->render('grep_file_content', '{"pattern": "TODO"}');

        $this->assertSame("● grep_file_content( TODO )\n", $result);
    }

    public function testRenderFallsBackToRawArgumentsWhenNoKeysMatch(): void
    {
        $renderer = new SnippetRenderer(['file_path']);
        $raw = '{"other_key": "value"}';
        $result = $renderer->render('some_tool', $raw);

        $this->assertSame("● some_tool( {$raw} )\n", $result);
    }

    public function testRenderFallsBackToRawArgumentsOnInvalidJson(): void
    {
        $renderer = new SnippetRenderer(['file_path']);
        $raw = 'not-json';
        $result = $renderer->render('some_tool', $raw);

        $this->assertSame("● some_tool( {$raw} )\n", $result);
    }

    public function testRenderEncodesNonStringValues(): void
    {
        $renderer = new SnippetRenderer(['args']);
        $result = $renderer->render('bash', '{"args": ["--flag", "--verbose"]}');

        $this->assertSame("● bash( [\"--flag\",\"--verbose\"] )\n", $result);
    }

    public function testRenderWithEmptyKeysArray(): void
    {
        $renderer = new SnippetRenderer([]);
        $raw = '{"file_path": "foo.php"}';
        $result = $renderer->render('read_file', $raw);

        $this->assertSame("● read_file( {$raw} )\n", $result);
    }
}
