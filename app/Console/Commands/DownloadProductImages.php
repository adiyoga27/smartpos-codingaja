<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

#[Signature('product:download-images {--limit=10 : Max products to process} {--force : Re-download even if photo exists}')]
#[Description('Download product images from web search')]
class DownloadProductImages extends Command
{
    private array $categoryColors = [
        'OB' => [46, 125, 50],
        'SP' => [21, 101, 192],
        'KB' => [230, 81, 0],
        'KD' => [56, 142, 60],
        'PE' => [173, 20, 87],
        'CP' => [0, 131, 143],
        'BK' => [255, 111, 0],
        'LL' => [97, 97, 97],
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = Product::query()
            ->when(! $force, fn ($q) => $q->whereNull('photo'));

        $products = $query->take($limit)->get();

        $this->info("Processing {$products->count()} products...");

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            $this->line("  [{$product->code}] {$product->name} ...");

            $photoPath = $this->downloadImage($product);

            if ($photoPath) {
                $product->update(['photo' => $photoPath]);
                $this->info("    OK -> {$photoPath}");
                $success++;
            } else {
                $this->warn('    FAILED (no image found)');
                $failed++;
            }

            usleep(50000);
        }

        $this->info("\nDone. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }

    private function downloadImage(Product $product): ?string
    {
        $searchQuery = $this->buildSearchQuery($product);

        $imageUrl = $this->searchBingImage($searchQuery);

        if (! $imageUrl) {
            $imageUrl = $this->searchDuckDuckGoImage($searchQuery);
        }

        if (! $imageUrl) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($imageUrl);

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $ext = $this->getExtensionFromContentType($contentType);
                $filename = 'products/'.$product->code.'_'.uniqid().'.'.$ext;

                Storage::disk('public')->put($filename, $response->body());

                return $filename;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function buildSearchQuery(Product $product): string
    {
        $name = preg_replace('/^\(\w+\)\s*/', '', $product->name);

        $category = $product->category?->name ?? '';
        if (str_contains($category, 'Kipas')) {
            return $name.' fan blower poultry farm';
        } elseif (str_contains($category, 'Kandang')) {
            return $name.' poultry farming equipment';
        } elseif (str_contains($category, 'Obat')) {
            return $name.' poultry medicine vitamin';
        } elseif (str_contains($category, 'Sparepart')) {
            return $name.' poultry farm spare part';
        } elseif (str_contains($category, 'Panel')) {
            return $name.' electrical panel poultry farm';
        } elseif (str_contains($category, 'Cellpad')) {
            return $name.' cooling pad poultry farm';
        } elseif (str_contains($category, 'Kimia')) {
            return $name.' disinfectant poultry farm';
        }

        return $name.' produk peternakan';
    }

    private function searchBingImage(string $query): ?string
    {
        try {
            $url = 'https://www.bing.com/images/search?q='.urlencode($query).'&first=1&FORM=HDRSC2';

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                preg_match('/murl&quot;:&quot;(https?:\/\/[^&]+\.(?:jpg|jpeg|png|webp))/i', $response->body(), $matches);

                if (! empty($matches[1])) {
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function searchDuckDuckGoImage(string $query): ?string
    {
        try {
            $url = 'https://api.duckduckgo.com/?q='.urlencode($query).'&format=json&no_html=1&t=smartpos';

            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data['Image']) && filter_var($data['Image'], FILTER_VALIDATE_URL)) {
                    return $data['Image'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function getExtensionFromContentType(?string $contentType): string
    {
        return match (true) {
            str_contains($contentType ?? '', 'webp') => 'webp',
            str_contains($contentType ?? '', 'png') => 'png',
            str_contains($contentType ?? '', 'jpeg') => 'jpg',
            default => 'jpg',
        };
    }
}
