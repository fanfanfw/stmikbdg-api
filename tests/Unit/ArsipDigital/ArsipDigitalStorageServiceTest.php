<?php

namespace Tests\Unit\ArsipDigital;

use App\Services\ArsipDigital\ArsipDigitalSettingsService;
use App\Services\ArsipDigital\ArsipDigitalStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArsipDigitalStorageServiceTest extends TestCase
{
    public function test_it_uploads_private_file_to_configured_disk(): void
    {
        Storage::fake('arsip_digital_test');

        $service = new ArsipDigitalStorageService($this->settingsService());
        $file = UploadedFile::fake()->create('Akta Kelahiran.PDF', 2, 'application/pdf');

        $metadata = $service->uploadPrivate($file, 'personal', ['owner_user_id' => 10]);

        $this->assertSame('arsip_digital_test', $metadata['storage_disk']);
        $this->assertSame('akta-kelahiran.pdf', $metadata['display_filename']);
        $this->assertSame('pdf', $metadata['extension']);
        $this->assertNotEmpty($metadata['checksum_sha256']);
        Storage::disk('arsip_digital_test')->assertExists($metadata['storage_path']);
    }

    public function test_it_streams_private_download(): void
    {
        Storage::fake('arsip_digital_test');
        Storage::disk('arsip_digital_test')->put('arsip-digital/testing/private.txt', 'arsip-ok');

        $service = new ArsipDigitalStorageService($this->settingsService());
        $response = $service->downloadPrivate('arsip_digital_test', 'arsip-digital/testing/private.txt', 'private.txt');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('arsip-ok', $content);
    }

    private function settingsService(): ArsipDigitalSettingsService
    {
        return new class extends ArsipDigitalSettingsService {
            public function getDefaults(): array
            {
                return [
                    'default_max_file_size_mb' => 10,
                    'default_allowed_extensions' => ['pdf'],
                    'storage_disk' => 'arsip_digital_test',
                ];
            }
        };
    }
}
