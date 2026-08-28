<?php

/**
 * Architecture guard: the customer module must stay fully independent from the
 * party module. No composer requirement and no source reference may exist.
 */
function customerBoundaryScan(array $needles): array
{
    $hits = [];
    $root = dirname(__DIR__, 2);

    foreach (['src'] as $dir) {
        $path = $root . '/' . $dir;
        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! in_array($file->getExtension(), ['php', 'yml', 'json'], true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            foreach ($needles as $needle) {
                if (str_contains($content, $needle)) {
                    $hits[] = $file->getPathname() . ' contains ' . $needle;
                }
            }
        }
    }

    return $hits;
}

it('does not require noerd/party in composer.json', function (): void {
    $composer = json_decode(
        file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
        true,
    );

    expect($composer['require'])->not->toHaveKey('noerd/party');
});

it('has no source references to the party module', function (): void {
    expect(customerBoundaryScan(['Noerd\\Party\\']))->toBe([]);
});
