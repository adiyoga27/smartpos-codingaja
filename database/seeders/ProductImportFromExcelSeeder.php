<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportFromExcelSeeder extends Seeder
{
    private array $categoryKeywords = [];

    private function initCategories(): array
    {
        return [
            'OB' => ['name' => 'Obat & Vitamin', 'code' => 'OB', 'description' => 'Obat-obatan, vitamin, dan suplemen peternakan'],
            'SP' => ['name' => 'Sparepart', 'code' => 'SP', 'description' => 'Suku cadang, sparepart, dan komponen mesin'],
            'KB' => ['name' => 'Kipas & Blower', 'code' => 'KB', 'description' => 'Kipas, blower, exhaust fan, dan dinamotor'],
            'KD' => ['name' => 'Kandang & Perlengkapan', 'code' => 'KD', 'description' => 'Kandang, gutter, nipple, feeder, egg tray, dan perlengkapan'],
            'PE' => ['name' => 'Panel & Elektrikal', 'code' => 'PE', 'description' => 'Panel box, kontrol elektrikal, dan komponen listrik'],
            'CP' => ['name' => 'Cellpad & Cooling', 'code' => 'CP', 'description' => 'Cellpad, cooling pad, dan sistem pendingin'],
            'BK' => ['name' => 'Bahan Kimia & Disinfektan', 'code' => 'BK', 'description' => 'Bahan kimia, disinfektan, dan cairan pembersih'],
            'LL' => ['name' => 'Lain-lain', 'code' => 'LL', 'description' => 'Produk umum dan lainnya'],
        ];
    }

    private function determineCategory(string $name): string
    {
        $name = strtoupper($name);

        if (preg_match('/^\(O\)/', $name) || preg_match('/^\(0\)/', $name)) {
            return 'OB';
        }
        if (preg_match('/^\(S\)/', $name)) {
            return 'SP';
        }

        $keywordMap = [
            'KB' => ['BLOWER', 'KIPAS', 'DINAMO', 'CHRONOS', 'NOSCH', 'INNOMOTICS', 'SIEMENS', 'WEG',
                'CONE FAN', 'BOX FAN', 'CENTRIFUGAL', 'BALING BALING', 'BALING ECERAN',
                'EXHAUST', 'WALLFAN', 'WALL FAN'],
            'KD' => ['GUTTER', 'NIPEL', 'FEED', 'EGG TRAY', 'EGGTRAY', 'FEEDER', 'CANNOPY',
                'CAPSUL', 'CUP MEKANISME', 'DRUM CHEMICAL', 'DRUM', 'GANTUNGAN',
                'FEEDING', 'PAKAN', 'ALAT POTONG', 'ADJUSTER', 'BRACKET', 'CABANG Y',
                'CLOSE', 'CINCIN', 'ADAPTOR', 'ANEMOMETER', 'AUGER', 'DOSATRON',
                'FILTER AIR', 'FILTER DISCH', 'BOR PIPA', 'GASOLEK', 'GAS BROODER',
                'TABUNG', 'REGULATOR', 'END SET'],
            'PE' => ['PANEL', 'BOX PANEL', 'POWER SUPPLY', 'POWER BOARD', 'DISPLAY BOARD',
                'CONTROL', 'HEATER', 'CHARGER', 'KAPASITOR', 'MCB', 'AVR', 'IGNITOR',
                'IGNITIOR', 'IGNITION', 'COIL', 'PEMANTIK', 'SENSOR', 'DIODA',
                'KIPROK', 'MODUL', 'AFTER TEMPERATUR'],
            'CP' => ['CELLPAD', 'CELPAD', 'COOLING', 'CELL PAD', 'COOL PAD'],
            'BK' => ['EM4', 'DISENFEKTAN', 'DISINFEKTAN', 'CHEMICAL', 'BCF', 'HERO',
                'PUNOS', 'KIMIA'],
        ];

        foreach ($keywordMap as $categoryCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($name, $keyword) !== false) {
                    return $categoryCode;
                }
            }
        }

        return 'LL';
    }

    public function run(): void
    {
        $this->command?->info('Reading Excel file...');

        $spreadsheet = IOFactory::load('c:/Users/Lucakerz/Desktop/STOCK DAN LIST HARGA 2 JUNI_.xlsx');

        $stockSheet = $spreadsheet->getSheet(0);
        $stockRows = $stockSheet->toArray();

        $priceSheet = $spreadsheet->getSheet(1);
        $priceRows = $priceSheet->toArray();

        $priceMap = [];
        for ($i = 5; $i < count($priceRows); $i++) {
            $row = $priceRows[$i];
            if (empty($row[1])) {
                continue;
            }
            $priceMap[trim($row[1])] = [
                'name' => trim($row[2] ?? ''),
                'unit' => trim($row[3] ?? 'PCS'),
                'reseller_price' => (float) ($row[4] ?? 0),
                'store_price' => (float) ($row[5] ?? 0),
            ];
        }

        $categoryDefinitions = $this->initCategories();
        $categories = [];

        foreach ($categoryDefinitions as $code => $def) {
            $categories[$code] = Category::create($def);
            $this->command?->info("  Category: {$def['name']}");
        }

        $supplier = Supplier::create([
            'code' => 'SUP001',
            'name' => 'PT. Sumber Jaya',
            'address' => 'Jl. Sandubaya - Sweta',
            'phone' => '081234567890',
            'email' => 'sumberjaya@example.com',
            'contact_person' => 'Budi Santoso',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $customer = Customer::create([
            'code' => 'CUS001',
            'name' => 'Reseller Member',
            'address' => 'Jl. Merdeka No. 10, Mataram',
            'phone' => '081234567891',
            'email' => 'reseller@example.com',
            'type' => 'wholesale',
            'credit_limit' => 10000000,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $this->command?->info('Importing products...');
        $count = 0;
        $skipped = 0;

        foreach ($stockRows as $i => $row) {
            if ($i < 5) {
                continue;
            }
            if (empty($row[1])) {
                continue;
            }

            $code = trim($row[1]);
            $barcode = trim($row[2] ?? '');
            $name = trim($row[3] ?? '');
            $stock = (int) ($row[4] ?? 0);

            $price = $priceMap[$code] ?? null;
            if (! $price && ! empty($name)) {
                $price = [
                    'name' => $name,
                    'unit' => 'PCS',
                    'reseller_price' => 0,
                    'store_price' => 0,
                ];
            }
            if (! $price) {
                $skipped++;

                continue;
            }

            $categoryCode = $this->determineCategory($name);

            Product::create([
                'code' => $code,
                'name' => $name,
                'barcode' => $barcode,
                'category_id' => $categories[$categoryCode]->id,
                'supplier_id' => $supplier->id,
                'unit' => $price['unit'],
                'purchase_unit' => $price['unit'],
                'conversion_factor' => 1,
                'purchase_price' => 0,
                'selling_price' => $price['store_price'],
                'wholesale_price' => $price['reseller_price'],
                'stock' => $stock,
                'min_stock' => 5,
                'max_stock' => 1000,
                'is_active' => true,
            ]);

            $count++;
        }

        $this->command?->info("  Imported: $count products");
        if ($skipped > 0) {
            $this->command?->warn("  Skipped: $skipped (no price data)");
        }
    }
}
