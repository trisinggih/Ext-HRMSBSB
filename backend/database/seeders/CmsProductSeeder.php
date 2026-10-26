<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cms\CmsProduct;
use App\Models\Cms\CmsSetting;
use Illuminate\Support\Facades\DB;

class CmsProductSeeder extends Seeder
{
    public function run(): void
    {
        // ===== CMS SETTINGS (Hero, Contact, Company Info) =====
        DB::table('cms_settings')->truncate();

        $settings = [
            // Hero Section
            ['key' => 'hero_badge',        'value' => 'RACING SPECIFICATION & STREET DURABILITY', 'group' => 'hero'],
            ['key' => 'hero_title_line1',  'value' => 'RIDE WITH',                                 'group' => 'hero'],
            ['key' => 'hero_title_line2',  'value' => 'REAL PERFORMANCE',                          'group' => 'hero'],
            ['key' => 'hero_subtitle',     'value' => 'Dengan memilih produk Jangeby, Anda tidak hanya mendapatkan kualitas terbaik tetapi juga investasi yang mendukung kenyamanan dan keamanan berkendara baik harian, touring, maupun balap. Sangat cocok bagi pengendara yang mengutamakan kualitas dan performa nyata.', 'group' => 'hero'],
            ['key' => 'hero_cta_catalog',  'value' => 'Lihat Produk Unggulan',                     'group' => 'hero'],
            ['key' => 'hero_cta_wa_text',  'value' => 'Hubungi via WhatsApp',                      'group' => 'hero'],
            ['key' => 'hero_stat1_label',  'value' => 'Garansi Keaslian',                          'group' => 'hero'],
            ['key' => 'hero_stat1_value',  'value' => '100%',                                      'group' => 'hero'],
            ['key' => 'hero_stat1_desc',   'value' => 'Barcode otentik langsung dari pabrikan resmi', 'group' => 'hero'],
            ['key' => 'hero_stat2_label',  'value' => 'Engineering Parts',                         'group' => 'hero'],
            ['key' => 'hero_stat2_value',  'value' => '15+',                                       'group' => 'hero'],
            ['key' => 'hero_stat2_desc',   'value' => 'Komponen balap, touring & harian matic',    'group' => 'hero'],
            ['key' => 'hero_stat3_label',  'value' => 'Cakupan Nasional',                          'group' => 'hero'],
            ['key' => 'hero_stat3_value',  'value' => '38+',                                       'group' => 'hero'],
            ['key' => 'hero_stat3_desc',   'value' => 'Provinsi didukung ratusan mitra bengkel',   'group' => 'hero'],
            ['key' => 'hero_stat4_label',  'value' => 'Kepuasan Pelanggan',                        'group' => 'hero'],
            ['key' => 'hero_stat4_value',  'value' => '4.9 / 5',                                   'group' => 'hero'],
            ['key' => 'hero_stat4_desc',   'value' => 'Ribuan ulasan positif di Shopee, Tokopedia & TikTok', 'group' => 'hero'],
            // Contact
            ['key' => 'contact_whatsapp',  'value' => 'https://wa.me/6285770201010?text=Halo,%20Saya%20ingin%20bertanya%20terkait%20produk%20Jangeby', 'group' => 'contact'],
            ['key' => 'contact_shopee',    'value' => 'https://shopee.co.id/jangebyofficial',       'group' => 'contact'],
            ['key' => 'contact_tokopedia', 'value' => 'https://www.tokopedia.com/jangeby-store',    'group' => 'contact'],
            ['key' => 'contact_tiktok',    'value' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall','group' => 'contact'],
            // Catalog Section
            ['key' => 'catalog_section_badge',    'value' => 'LINEUP PERFORMA MATIC',                       'group' => 'catalog'],
            ['key' => 'catalog_section_title',    'value' => 'Katalog Produk Pilihan Jangeby',               'group' => 'catalog'],
            ['key' => 'catalog_section_subtitle', 'value' => 'Komponen transmisi CVT, kelistrikan presisi, dan pendingin mesin yang telah melalui uji ketahanan dinamis untuk menjamin akselerasi tanpa getar dan performa puncak.', 'group' => 'catalog'],
        ];

        foreach ($settings as $s) {
            CmsSetting::create(array_merge($s, ['is_active' => true]));
        }

        // ===== CMS PRODUCTS (data dari products.ts) =====
        DB::table('cms_products')->truncate();

        $products = [
            [
                'name'             => 'CVT Spring Jangeby',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'RACING SWOCS-VH',
                'highlight'        => 'Anti-Fatigue',
                'specLabel'        => 'Spek: 1000 / 1500 / 2000 RPM',
                'specValue'        => '100% PRESISI',
                'description'      => 'Menggunakan material SWOCS-VH baja super bersih untuk pegas katup extreme. Menahan kompresi putaran tinggi tanpa kehilangan elastisitas, tahan suhu panas, dan anti karat.',
                'images'           => json_encode(['1.webp']),
                'specs'            => json_encode(['material' => 'Baja SWOCS-VH Grade Valve Racing', 'rpmOptions' => '1000 RPM, 1500 RPM, 2000 RPM', 'temperatureRange' => 'Hingga 220°C Bebas Lelah', 'finish' => 'Anti-Corrosion Black Coat Electrophoresis']),
                'compatibility'    => json_encode(['Honda Vario 125 / 150 / 160', 'Honda PCX 150 / 160 / ADV', 'Honda Beat FI / ESP / Deluxe / Genio / Scoopy', 'Yamaha NMAX 155 Old / New Connected', 'Yamaha Aerox 155 Connected', 'Yamaha Lexi 125 / 155', 'Yamaha Mio M3 / Fazzio / Grand Filano']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20CVT%20Spring%20Jangeby']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Spring Clutch (Per Sentri)',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'CLUTCH PERFORMANCE',
                'highlight'        => 'Akselerasi Bawah',
                'specLabel'        => 'Varian: 1000 - 2000 RPM',
                'specValue'        => 'ANTI GREDEK',
                'description'      => 'Tersedia pilihan 1000 RPM, 1200 RPM, 1500 RPM, 1700 RPM, hingga 2000 RPM. Tingkat elastisitas kawat pegas stabil untuk respon akselerasi spontan anti selip.',
                'images'           => json_encode(['2.webp']),
                'specs'            => json_encode(['material' => 'High-Tensile Carbon Spring Wire', 'rpmOptions' => '1000, 1200, 1500, 1700, 2000 RPM', 'technology' => 'Precision Coil Tension Balance']),
                'compatibility'    => json_encode(['Honda Vario 110 / 125 / 150 / 160', 'Honda Beat / Scoopy / Spacy', 'Yamaha NMAX / Aerox / Lexi', 'Yamaha Mio Sporty / Smile / J / Soul GT']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Spring%20Clutch%20Per%20Sentri']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Mangkok Ganda Custom Anti Gredek',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'CNC DRILL & GROOVE',
                'highlight'        => 'Max Grip',
                'specLabel'        => 'Tipe: Milling / Kartel / GK2',
                'specValue'        => '100% BEBAS GETAR',
                'description'      => 'Permukaan presisi dengan opsi tipe Milling, Kartel, dan GK2 yang disesuaikan kebutuhan motor. Membuang debu sentrifugal dan mengoptimalkan gigitan kampas kopling.',
                'images'           => json_encode(['3.webp']),
                'specs'            => json_encode(['finish' => 'CNC Precision Lathe & Grooving Channel', 'technology' => 'Airflow Centrifugal Dust Ejection Holes', 'rpmOptions' => 'Milling Harian / Kartel Balap / GK2 Hybrid']),
                'compatibility'    => json_encode(['Honda Beat FI / ESP / Deluxe / Genio', 'Honda Vario 125 / 150 / 160', 'Honda PCX 150 / 160 / ADV 150 / 160', 'Yamaha NMAX 155 / Aerox 155 / Lexi', 'Yamaha Mio M3 / Fino 125 / Soul GT']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Mangkok%20Ganda%20Custom']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Kampas Ganda Kevlar Serat Aramid',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'ARAMID FIBER',
                'highlight'        => 'Anti-Slip Formula',
                'specLabel'        => 'Bahan: Kevlar Aramid Asli',
                'specValue'        => 'TAHAN PANAS',
                'description'      => 'Tahan panas superior dan gesekan tinggi tanpa mengikis mangkok kopling. Formulasi kepadatan disesuaikan lebih lembut dari kampas racing murni agar sangat awet harian.',
                'images'           => json_encode(['4.webp']),
                'specs'            => json_encode(['material' => 'Genuine DuPont Kevlar Aramid Friction Compound', 'temperatureRange' => 'Tahan hingga 300°C bebas fading', 'technology' => 'Smooth Engagement Matrix Formula']),
                'compatibility'    => json_encode(['Honda Vario 125 / 150 / 160', 'Honda Beat / Scoopy Karbu & FI', 'Yamaha NMAX / Aerox / Lexi', 'Yamaha Mio / Soul GT / Fazzio']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Kampas%20Ganda%20Kevlar']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Drive Belt Jangeby High Performance',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'EPDM RUBBER & MOTA',
                'highlight'        => 'Heavy Duty',
                'specLabel'        => 'Suhu: -40°C s/d 150°C',
                'specValue'        => 'EKSTREM AWET',
                'description'      => 'Konstruksi 3 lapis: Kain Mota tahan gesek, Kawat Poliester elastisitas tinggi, dan Karet EPDM tahan suhu ekstrem -40°C hingga 150°C. Akselerasi halus bebas selip.',
                'images'           => json_encode(['5.webp']),
                'specs'            => json_encode(['material' => 'Triple-Layer Compound: Mota Fabric + High-Cord + EPDM', 'temperatureRange' => '-40°C s/d 150°C', 'technology' => 'Zero Stretch Tensile Integrity']),
                'compatibility'    => json_encode(['Honda Vario 125 Old & New / Vario 150 / Vario 160', 'Honda Beat FI / ESP / Street / Genio', 'Yamaha NMAX 155 Old / New, Aerox 155', 'Yamaha Mio J / Soul GT / Mio M3 125']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Drive%20Belt%20Jangeby']),
                'is_active'        => true,
            ],
            [
                'name'             => 'High Quality Roller Teflon',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'TEFLON POM',
                'highlight'        => 'Low Friction',
                'specLabel'        => 'Gramatur: 7g s/d 15g Lengkap',
                'specValue'        => 'HEMAT BBM',
                'description'      => 'Dibuat dari material Poly Oxy Methylene (POM) teflon licin dan kuat. Mengurangi keausan jalur roller pada pulley, akselerasi motor bergulir cepat tanpa hambatan energi.',
                'images'           => json_encode(['6.webp']),
                'specs'            => json_encode(['material' => '100% Virgin PolyOxyMethylene (POM) Low Friction Resin', 'weightOptions' => '7g, 8g, 9g, 10g, 11g, 12g, 13g, 14g, 15g (Ready All)', 'technology' => 'Self-Lubricated High Velocity Rolling Core']),
                'compatibility'    => json_encode(['Honda Vario 125 / 150 / 160, PCX 150 / 160, ADV', 'Honda Beat / Scoopy / Genio', 'Yamaha NMAX 155, Aerox 155, Lexi', 'Yamaha Mio Karbu & FI / Fazzio']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Roller%20Teflon%20POM']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Titanium Bushing Pulley',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'TITANIUM ALLOY',
                'highlight'        => 'Ultra Lightweight',
                'specLabel'        => 'Karakter: Ringan & Presisi',
                'specValue'        => 'ROTASI CEPAT',
                'description'      => 'Material Titanium Alloy berbobot ultra-ringan dengan kekerasan tinggi. Mengurangi beban rotasi as kruk sehingga RPM mesin naik drastis dengan respons instan.',
                'images'           => json_encode(['7.webp']),
                'specs'            => json_encode(['material' => 'Aerospace Grade Ti-6Al-4V Titanium Alloy', 'technology' => 'Mirror Polish Low-Drag Micro-Machined ID/OD', 'finish' => 'Micro-Finish Hard Anodized']),
                'compatibility'    => json_encode(['Honda Vario 125 / 150 / 160', 'Honda PCX 150 / 160 / ADV', 'Yamaha NMAX 155 / Aerox 155']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Titanium%20Bushing%20Pulley']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Sliding Plate & Slide Piece S3',
                'category'         => 'cvt',
                'categoryLabel'    => 'TRANSMISI CVT',
                'badge'            => 'DUAL COMPONENT',
                'highlight'        => 'Self Lubricating',
                'specLabel'        => 'Material: PA46 Teflon Self-Lube',
                'specValue'        => 'SMOOTH TRAVEL',
                'description'      => 'Sliding Plate berfinishing halus dari pabrik dipadu Slide Piece material PA46 teflon self-lubricating. Menjaga kestabilan ramp pulley dan melipatgandakan umur roller.',
                'images'           => json_encode(['8.webp', '9.webp']),
                'specs'            => json_encode(['material' => 'Heavy-Duty Stamped Fine Steel + PA46 Nylon Matrix', 'technology' => 'Zero Friction Ramp Guide Surface']),
                'compatibility'    => json_encode(['Honda Beat FI / ESP / Beat Deluxe / Scoopy', 'Honda Vario 125 / 150 / 160 / PCX 150 / 160', 'Yamaha NMAX / Aerox / Lexi 155']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Sliding%20Plate%20dan%20Slide%20Piece']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Injector N21 & Sensor TPS M1',
                'category'         => 'engine',
                'categoryLabel'    => 'SENSOR & MESIN',
                'badge'            => 'FUEL & SENSOR',
                'highlight'        => 'Micro-Atomization',
                'specLabel'        => 'Teknologi: German Chipset',
                'specValue'        => 'PRESISI TINGGI',
                'description'      => 'Pengabutan kabut mikro konsisten sempurna untuk pembakaran efisien bertenaga. Didukung sensor TPS M1 berteknologi chip presisi tinggi anti eror pada bukaan throttle gas.',
                'images'           => json_encode(['10.webp', '11.webp']),
                'specs'            => json_encode(['flowRateOptions' => '120cc, 140cc, 160cc, 180cc, 200cc', 'technology' => 'Laser Micro-Drilled 10-12 Hole Spray Pattern', 'chipset' => 'High-Speed German Automotive Hall Sensor']),
                'compatibility'    => json_encode(['Honda Beat FI / Scoopy / Vario 125 / Vario 150 / CRF 150L', 'Yamaha NMAX 155 / Aerox 155 / WR 155 / R15 V3 V4', 'Universal ECU Compatible (BRT, Juken, Apitech, OEM)']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Injector%20N21%20dan%20Sensor%20TPS']),
                'is_active'        => true,
            ],
            [
                'name'             => 'Soft Steering Ball & Water Pump S13',
                'category'         => 'handling',
                'categoryLabel'    => 'HANDLING & SASIS',
                'badge'            => 'HANDLING & COOLING',
                'highlight'        => 'Soft Control',
                'specLabel'        => 'Fitur: Bearing Cage & Impeller',
                'specValue'        => 'ANTI OVERHEAT',
                'description'      => 'Komstir bantalan pelor presisi cage teflon anti oblak untuk stang kemudi super lincah, serta water pump pendingin sirkulasi cairan debit tinggi anti panas mesin.',
                'images'           => json_encode(['12.webp', '13.webp']),
                'specs'            => json_encode(['bearingType' => 'High-Impact Chrome Steel Balls in Teflon Cage', 'waterPumpImpeller' => 'High-Flow Curved Turbine CNC Machined', 'technology' => 'Anti-Vibration Steering Stability Guarantee']),
                'compatibility'    => json_encode(['Honda Vario 125 / 150 / 160, PCX 150 / 160, Beat, Scoopy', 'Yamaha NMAX 155, Aerox 155, Lexi, Jupiter MX, MX King']),
                'marketplaceLinks' => json_encode(['shopee' => 'https://shopee.co.id/jangebyofficial', 'tokopedia' => 'https://www.tokopedia.com/jangeby-store', 'tiktok' => 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall', 'whatsapp' => 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20tertarik%20dengan%20Komstir%20dan%20Water%20Pump%20S13']),
                'is_active'        => true,
            ],
        ];

        foreach ($products as $product) {
            CmsProduct::create($product);
        }
    }
}
