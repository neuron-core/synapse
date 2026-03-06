<?php

declare(strict_types=1);

namespace NeuronCore\Synapse\Rendering\Renderers;

use NeuronCore\Synapse\Rendering\DiffRenderer;
use NeuronCore\Synapse\Rendering\ToolRenderer;

use function escapeshellarg;
use function fclose;
use function fwrite;
use function json_decode;
use function shell_exec;
use function sprintf;
use function stream_get_meta_data;
use function tmpfile;

class EditFileRenderer implements ToolRenderer
{
    public function __construct(private readonly DiffRenderer $diffRenderer)
    {
    }

    public function render(string $toolName, string $arguments): string
    {
        $args = json_decode($arguments, true) ?? [];
        $path = $args['file_path'] ?? null;
        $search = $args['search'] ?? null;
        $replace = $args['replace'] ?? null;

        if ($path === null || $search === null || $replace === null) {
            return (new GenericRenderer())->render($toolName, $arguments);
        }

        $header = sprintf("● %s( %s )\n\n", $toolName, $path);

        // Generate a diff between search and replace strings
        $diff = $this->generateSearchReplaceDiff($search, $replace);

        if ($diff === '') {
            return $header . "<info>No changes (search and replace are identical)</info>\n";
        }

        return $header . $this->diffRenderer->render($diff);
    }

    private function generateSearchReplaceDiff(string $search, string $replace): string
    {
        $oldFile = tmpfile();
        $newFile = tmpfile();

        fwrite($oldFile, $search);
        fwrite($newFile, $replace);

        $oldPath = escapeshellarg(stream_get_meta_data($oldFile)['uri']);
        $newPath = escapeshellarg(stream_get_meta_data($newFile)['uri']);

        // Use --label to show what the diff represents (standard format with a/ and b/ prefixes)
        $diff = shell_exec("diff -u --label 'a/search' --label 'b/replace' {$oldPath} {$newPath}") ?? '';

        fclose($oldFile);
        fclose($newFile);

        return $diff;
    }
}
