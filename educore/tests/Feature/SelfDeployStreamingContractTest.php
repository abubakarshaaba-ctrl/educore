<?php

namespace Tests\Feature;

use Tests\TestCase;

class SelfDeployStreamingContractTest extends TestCase
{
    public function test_self_deployer_streams_zip_entries_without_full_tree_extraction(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/SelfDeployController.php')
        );

        $this->assertStringContainsString(
            'syncZipToLiveTree(',
            $controller
        );
        $this->assertStringContainsString(
            "'deploy_mode' => 'zip-stream'",
            $controller
        );
        $this->assertStringContainsString(
            'filesystemDiagnostics(',
            $controller
        );

        $this->assertStringNotContainsString(
            'extractZipSafely(',
            $controller
        );
        $this->assertStringNotContainsString(
            'copyTree(',
            $controller
        );
        $this->assertStringNotContainsString(
            "'prepare-extract-dir'",
            $controller
        );
    }

    public function test_streaming_deployer_preserves_live_apk_and_path_whitelist(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/SelfDeployController.php')
        );

        $this->assertStringContainsString(
            'PRESERVED_LIVE_PATHS',
            $controller
        );
        $this->assertStringContainsString(
            'shouldExtractRepoPath($repoRelative)',
            $controller
        );
        $this->assertStringContainsString(
            "in_array($repoRelative, self::PRESERVED_LIVE_PATHS, true)",
            $controller
        );
    }

    public function test_download_space_check_no_longer_requires_three_archive_copies(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/SelfDeployController.php')
        );

        $this->assertStringContainsString(
            '$minimumFree = $bytes + (16 * 1024 * 1024);',
            $controller
        );
        $this->assertStringNotContainsString(
            '$bytes * 3',
            $controller
        );
    }
}
