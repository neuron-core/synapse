<?php

declare(strict_types=1);

namespace NeuronCore\Synapse\Tests\Rendering;

use NeuronCore\Synapse\Rendering\ToolRenderer;
use NeuronCore\Synapse\Rendering\ToolRendererMap;
use PHPUnit\Framework\TestCase;

class ToolRendererMapTest extends TestCase
{
    public function testRegisterReturnsSelf(): void
    {
        $fallback = $this->createMock(ToolRenderer::class);
        $map = new ToolRendererMap($fallback);
        $renderer = $this->createMock(ToolRenderer::class);

        $result = $map->register('some_tool', $renderer);

        $this->assertSame($map, $result);
    }

    public function testRenderUsesRegisteredRenderer(): void
    {
        $fallback = $this->createMock(ToolRenderer::class);
        $fallback->expects($this->never())->method('render');

        $registered = $this->createMock(ToolRenderer::class);
        $registered->expects($this->once())
            ->method('render')
            ->with('my_tool', '{}')
            ->willReturn('registered output');

        $map = (new ToolRendererMap($fallback))->register('my_tool', $registered);

        $this->assertSame('registered output', $map->render('my_tool', '{}'));
    }

    public function testRenderUsesFallbackForUnknownTool(): void
    {
        $fallback = $this->createMock(ToolRenderer::class);
        $fallback->expects($this->once())
            ->method('render')
            ->with('unknown_tool', '{}')
            ->willReturn('fallback output');

        $map = new ToolRendererMap($fallback);

        $this->assertSame('fallback output', $map->render('unknown_tool', '{}'));
    }

    public function testDefaultReturnsToolRendererMapInstance(): void
    {
        $map = ToolRendererMap::default();

        $this->assertInstanceOf(ToolRendererMap::class, $map);
    }

    public function testDefaultHasReadFileToolRegistered(): void
    {
        $map = ToolRendererMap::default();

        $result = $map->render('read_file', '{"file_path": "foo.php"}');

        $this->assertSame("● read_file( foo.php )\n", $result);
    }

    public function testDefaultHasBashToolRegistered(): void
    {
        $map = ToolRendererMap::default();

        $result = $map->render('bash', '{"command": "ls -la"}');

        $this->assertSame("● bash( ls -la )\n", $result);
    }

    public function testDefaultFallsBackToGenericRendererForUnknownTool(): void
    {
        $map = ToolRendererMap::default();
        $args = '{"some": "args"}';

        $result = $map->render('unknown_tool', $args);

        $this->assertSame("● unknown_tool( {$args} )\n", $result);
    }

    public function testDefaultHasWriteFileRegistered(): void
    {
        $map = ToolRendererMap::default();

        $result = $map->render('write_file', '{"file_path": "/tmp/test.php", "content": "<?php"}');

        $this->assertStringContainsString('write_file', $result);
        $this->assertStringContainsString('/tmp/test.php', $result);
    }
}
