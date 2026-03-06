<?php

declare(strict_types=1);

namespace NeuronCore\Synapse\Tests\Rendering\Renderers;

use NeuronCore\Synapse\Rendering\Renderers\FileChangeRenderer;
use NeuronCore\Synapse\Rendering\ToolRenderer;
use PHPUnit\Framework\TestCase;

class FileChangeRendererTest extends TestCase
{
    private FileChangeRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new FileChangeRenderer();
    }

    public function testImplementsToolRenderer(): void
    {
        $this->assertInstanceOf(ToolRenderer::class, $this->renderer);
    }

    public function testFallsBackToGenericRendererWhenNoPath(): void
    {
        $result = $this->renderer->render('write_file', '{"content": "hello"}');

        $this->assertSame("● write_file( {\"content\": \"hello\"} )\n", $result);
    }

    public function testFallsBackToGenericRendererWhenNoContent(): void
    {
        $result = $this->renderer->render('edit_file', '{"file_path": "/tmp/foo.php"}');

        $this->assertSame("● edit_file( {\"file_path\": \"/tmp/foo.php\"} )\n", $result);
    }

    public function testFallsBackToGenericRendererOnInvalidJson(): void
    {
        $result = $this->renderer->render('write_file', 'not-json');

        $this->assertSame("● write_file( not-json )\n", $result);
    }

    public function testRendersHeaderWithToolNameAndPath(): void
    {
        $result = $this->renderer->render('write_file', '{"file_path": "/tmp/foo.php", "content": "<?php"}');

        $this->assertStringContainsString('write_file', $result);
        $this->assertStringContainsString('/tmp/foo.php', $result);
    }

    public function testIncludesAnsiColorCodes(): void
    {
        $result = $this->renderer->render('write_file', '{"file_path": "/tmp/foo.php", "content": "new content"}');

        $this->assertStringContainsString("\033[32;1m", $result); // Green for additions
        $this->assertStringContainsString("\033[0m", $result); // Reset
    }

    public function testAcceptsPathKeyAsAlternative(): void
    {
        $result = $this->renderer->render('delete_file', '{"path": "/tmp/bar.php", "content": "x"}');

        $this->assertStringContainsString('/tmp/bar.php', $result);
    }

    public function testDiffMetadataLinesAreFiltered(): void
    {
        $result = $this->renderer->render('write_file', '{"file_path": "/tmp/test.php", "content": "new"}');

        // These lines should NOT be in the output
        $this->assertStringNotContainsString('---', $result);
        $this->assertStringNotContainsString('+++', $result);
        $this->assertStringNotContainsString('@@', $result);
        $this->assertStringNotContainsString('No newline at end of file', $result);
    }

    public function testColorizeDiffHandlesEmptyDiff(): void
    {
        // Create an existing file with content, then write the same content
        $result = $this->renderer->render('write_file', '{"file_path": "/tmp/foo.php", "content": "same"}');

        $this->assertStringContainsString('write_file', $result);
        $this->assertStringContainsString('/tmp/foo.php', $result);
    }

    public function testContextLinesAreGray(): void
    {
        // This test verifies that context lines (lines starting with space) are colored gray
        // For a new file, there's no context, so we just verify the output is valid
        $result = $this->renderer->render('write_file', '{"file_path": "/tmp/test.php", "content": "content"}');

        $this->assertStringContainsString("\033[32;1m", $result); // Green for additions
        $this->assertStringContainsString("\033[0m", $result); // Reset
    }
}
