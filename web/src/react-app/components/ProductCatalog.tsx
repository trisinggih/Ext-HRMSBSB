import { useState } from 'react';
import { PRODUCTS_DATA } from '../data/products';
import { Product, ProductCategory } from '../types';
import { ProductModal } from './ProductModal';
import { CmsSettings } from '../App';

interface ProductCatalogProps {
  cmsProducts?: Product[];
  cmsLoaded?: boolean;
  settings?: CmsSettings;
}

// Default teks katalog
const CATALOG_DEFAULTS = {
  catalog_section_badge: 'LINEUP PERFORMA MATIC',
  catalog_section_title: 'Katalog Produk Pilihan Jangeby',
  catalog_section_subtitle: 'Komponen transmisi CVT, kelistrikan presisi, dan pendingin mesin yang telah melalui uji ketahanan dinamis untuk menjamin akselerasi tanpa getar dan performa puncak.',
};

export function ProductCatalog({ cmsProducts = [], cmsLoaded = false, settings = {} }: ProductCatalogProps) {
  const [selectedCategory, setSelectedCategory] = useState<ProductCategory>('all');
  const [activeModalProduct, setActiveModalProduct] = useState<Product | null>(null);

  // Gunakan data CMS jika sudah load dan ada data, fallback ke PRODUCTS_DATA statis
  const products: Product[] = (cmsLoaded && cmsProducts.length > 0) ? cmsProducts : PRODUCTS_DATA;

  const s = (key: keyof typeof CATALOG_DEFAULTS) => settings[key] ?? CATALOG_DEFAULTS[key];

  const filteredProducts = products.filter((product) => {
    if (selectedCategory === 'all') return true;
    return product.category === selectedCategory;
  });

  const uniqueCategoriesMap = new Map<string, { id: ProductCategory, label: string, count: number }>();

  products.forEach(p => {
    if (!uniqueCategoriesMap.has(p.category)) {
      uniqueCategoriesMap.set(p.category, {
        id: p.category,
        label: p.categoryLabel || p.category,
        count: 0
      });
    }
    uniqueCategoriesMap.get(p.category)!.count += 1;
  });

  const categories = [
    { id: 'all', label: 'Semua Komponen', count: products.length },
    ...Array.from(uniqueCategoriesMap.values())
  ];

  return (
    <section id="katalog-unggulan" className="w-full py-16 sm:py-24 bg-white border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              {s('catalog_section_badge')}
            </span>
            <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              {s('catalog_section_title')}
            </h2>
            <p className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              {s('catalog_section_subtitle')}
            </p>
          </div>
          <div className="font-tech-data text-slate-400 text-xs sm:text-sm tracking-wider font-semibold">
            TOTAL {products.length} KOMPONEN RACING &amp; HARIAN
          </div>
        </div>

        {/* Filter Navigation Tabs */}
        <div className="flex flex-wrap items-center gap-2 mb-10 pb-2 border-b border-slate-100">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id)}
              className={`px-4 py-2 rounded-full font-tech-data text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 ${selectedCategory === cat.id
                  ? 'bg-[#008779] text-white shadow-sm font-bold'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
            >
              <span>{cat.label}</span>
              <span
                className={`text-[10px] px-1.5 py-0.2 rounded-full ${selectedCategory === cat.id ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'
                  }`}
              >
                {cat.count}
              </span>
            </button>
          ))}
        </div>

        {/* Loading state */}
        {!cmsLoaded && (
          <div className="flex items-center justify-center py-16">
            <div className="flex flex-col items-center gap-3">
              <div className="w-10 h-10 border-2 border-[#008779] border-t-transparent rounded-full animate-spin" />
              <span className="text-slate-500 text-sm font-tech-data">Memuat katalog...</span>
            </div>
          </div>
        )}

        {/* Product Grid */}
        {cmsLoaded && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            {filteredProducts.map((product, index) => {
              return (
                <div
                  key={product.id}
                  className="group rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden relative"
                >
                  {/* Product Image Area */}
                  <div
                    onClick={() => setActiveModalProduct(product)}
                    className="cursor-pointer relative aspect-[4/3] bg-slate-50 border-b border-slate-100 overflow-hidden flex items-center justify-center p-6"
                  >
                    <div className="absolute top-3 left-3 z-20 flex items-center gap-1.5">
                      <span className="font-label-mono-sm text-[10px] bg-white/95 text-[#008779] border border-slate-200 px-2.5 py-1 rounded shadow-xs font-bold tracking-wider">
                        {product.badge}
                      </span>
                    </div>

                    <div className="absolute top-3 right-3 z-20">
                      <span className="font-tech-data text-[10px] bg-teal-50 text-[#008779] border border-teal-200 px-2 py-0.5 rounded font-semibold">
                        {product.highlight}
                      </span>
                    </div>

                    <img
                      src={`/assets/images/company/${(index % 16) + 2}.webp`}
                      alt={product.name}
                      className="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>

                  {/* Card Content Area */}
                  <div className="p-6 flex flex-col flex-1 justify-between gap-5">
                    <div className="flex flex-col gap-2">
                      <div className="flex items-center justify-between text-xs text-slate-500 font-label-mono-sm">
                        <span className="text-[#008779] font-bold">{product.categoryLabel}</span>
                        <span className="text-slate-700 font-semibold">{product.specValue}</span>
                      </div>

                      <h3
                        onClick={() => setActiveModalProduct(product)}
                        className="font-headline-sm text-slate-900 font-bold text-lg sm:text-xl group-hover:text-[#008779] transition-colors cursor-pointer"
                      >
                        {product.name}
                      </h3>

                      <p className="font-body-sm text-slate-600 line-clamp-2 text-xs sm:text-sm leading-relaxed">
                        {product.description}
                      </p>

                      <div className="mt-2 py-1.5 px-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 font-label-mono-sm text-[11px] flex items-center justify-between">
                        <span className="text-slate-500">{product.specLabel}</span>
                        <span className="text-teal-700 font-bold">100% ORIGINAL</span>
                      </div>
                    </div>

                    {/* Card Action CTAs */}
                    <div className="flex items-center gap-2 pt-2 border-t border-slate-100">
                      <a
                        href={product.marketplaceLinks?.shopee ?? 'https://shopee.co.id/jangebyofficial'}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs shadow-xs transition-colors"
                      >
                        <span className="material-symbols-outlined text-[16px]">shopping_cart</span>
                        <span>Beli Resmi</span>
                      </a>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* Bottom Banner inside Catalog */}
        <div className="mt-14 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
          <div className="flex items-center gap-4">
            <div className="flex flex-col">
              <span className="font-tech-data text-lg font-bold">Bingung Menentukan Gram Roller &amp; RPM Per CVT?</span>
              <span className="text-sm text-slate-300">Konsultasikan kebutuhan spek harian, touring, atau bore-up Anda ke tim teknis resmi Jangeby.</span>
            </div>
          </div>
          <a
            href={settings['contact_whatsapp'] ?? 'https://wa.me/6285770201010?text=Halo%20Jangeby,%20mau%20konsultasi%20spek%20roller%20dan%20per%20CVT%20untuk%20motor%20saya'}
            target="_blank"
            rel="noopener noreferrer"
            className="whitespace-nowrap px-6 py-3 rounded-full bg-[#008779] hover:bg-teal-500 text-white font-tech-data text-xs sm:text-sm font-bold shadow-lg transition-all"
          >
            Konsultasi Gratis Sekarang
          </a>
        </div>
      </div>

      {/* Modal Detail */}
      {activeModalProduct && (
        <ProductModal
          product={activeModalProduct}
          onClose={() => setActiveModalProduct(null)}
        />
      )}
    </section>
  );
}
