<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBroadcastImageContractTest extends TestCase
{
    public function test_platform_broadcast_image_upload_contract_is_wired_end_to_end(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/PlatformBroadcastController.php'));
        $webController = file_get_contents(app_path('Http/Controllers/WebPlatformBroadcastController.php'));
        $view = file_get_contents(resource_path('views/super/broadcasts.blade.php'));
        $noticeView = file_get_contents(resource_path('views/announcements/index.blade.php'));
        $mobileRoutes = file_get_contents(base_path('routes/mobile-platform-broadcasts.php'));

        $this->assertStringContainsString("'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:body']", $controller);
        $this->assertStringContainsString("'body' => ['nullable', 'string', 'max:5000', 'required_without:image']", $controller);
        $this->assertStringContainsString('$body = trim((string) ($data[\'body\'] ?? \'\'))', $controller);
        $this->assertStringContainsString("store('platform-broadcasts', 'public')", $controller);
        $this->assertStringContainsString('Storage::disk(\'public\')->delete($imagePath)', $controller);
        $this->assertStringContainsString("'image_path' => \$imagePath", $controller);

        $this->assertStringContainsString('enctype="multipart/form-data"', $view);
        $this->assertStringContainsString('name="image"', $view);
        $this->assertStringContainsString('accept="image/jpeg,image/png,image/webp"', $view);
        $this->assertStringNotContainsString('name="body" class="fc" rows="4" placeholder="Write your broadcast message here..." required', $view);
        $this->assertStringContainsString('image-only broadcast', $view);
        $this->assertStringContainsString('$bc->image_path', $view);
        $this->assertStringContainsString('$ann->image_path', $noticeView);

        $this->assertStringContainsString('extends PlatformBroadcastController', $webController);
        $this->assertStringContainsString("PlatformBroadcastController::class, 'store'", $mobileRoutes);
    }

    public function test_platform_broadcast_rejects_unsupported_or_oversized_images_by_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/PlatformBroadcastController.php'));

        $this->assertStringContainsString('mimes:jpg,jpeg,png,webp', $controller);
        $this->assertStringContainsString("'max:5120'", $controller);
        $this->assertStringNotContainsString('mimes:gif', $controller);
        $this->assertStringNotContainsString('mimes:pdf', $controller);
    }
}
