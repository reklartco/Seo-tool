<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PluginDownloadController extends Controller
{
    /**
     * Zips the bundled seo-connector plugin on the fly.
     */
    public function __invoke(Request $request): BinaryFileResponse
    {
        $source = base_path('wordpress-plugin/seo-connector');
        $target = storage_path('app/seo-connector.zip');

        abort_unless(is_dir($source), 404);

        $zip = new ZipArchive;

        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Eklenti paketi oluşturulamadı.');
        }

        foreach (scandir($source) ?: [] as $file) {
            if (! is_file($source.'/'.$file)) {
                continue;
            }

            $zip->addFile($source.'/'.$file, 'seo-connector/'.$file);
        }

        $zip->close();

        return response()->download($target, 'seo-connector.zip')->deleteFileAfterSend();
    }
}
