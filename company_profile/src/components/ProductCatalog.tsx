import { useState } from 'react';
import { PRODUCTS_DATA } from '../data/products';
import { Product, ProductCategory } from '../types';
import { ProductModal } from './ProductModal';

export function ProductCatalog() {
  const [selectedCategory, setSelectedCategory] = useState<ProductCategory>('all');
  const [activeModalProduct, setActiveModalProduct] = useState<Product | null>(null);

  const filteredProducts = PRODUCTS_DATA.filter((product) => {
    if (selectedCategory === 'all') return true;
    return product.category === selectedCategory;
  });

  const categories = [
    { id: 'all' as ProductCategory, label: 'Semua Komponen', count: PRODUCTS_DATA.length },
    { id: 'cvt' as ProductCategory, label: 'Transmisi CVT', count: PRODUCTS_DATA.filter((p) => p.category === 'cvt').length },
    { id: 'engine' as ProductCategory, label: 'Sensor & Mesin', count: PRODUCTS_DATA.filter((p) => p.category === 'engine').length },
    { id: 'handling' as ProductCategory, label: 'Handling & Sasis', count: PRODUCTS_DATA.filter((p) => p.category === 'handling').length },
  ];

  return (
    <section id="katalog-unggulan" className="w-full py-16 sm:py-24 bg-white border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              LINEUP PERFORMA MATIC
            </span>
            <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              Katalog Produk Pilihan Jangeby
            </h2>
            <p className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              Komponen transmisi CVT, kelistrikan presisi, dan pendingin mesin yang telah melalui uji ketahanan dinamis untuk menjamin akselerasi tanpa getar dan performa puncak.
            </p>
          </div>
          <div className="font-tech-data text-slate-400 text-xs sm:text-sm tracking-wider font-semibold">
            TOTAL 10 KOMPONEN RACING &amp; HARIAN
          </div>
        </div>

        {/* Filter Navigation Tabs */}
        <div className="flex flex-wrap items-center gap-2 mb-10 pb-2 border-b border-slate-100">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id)}
              className={`px-4 py-2 rounded-full font-tech-data text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 ${
                selectedCategory === cat.id
                  ? 'bg-[#008779] text-white shadow-sm font-bold'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              <span>{cat.label}</span>
              <span
                className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                  selectedCategory === cat.id ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'
                }`}
              >
                {cat.count}
              </span>
            </button>
          ))}
        </div>

        {/* Product Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          {filteredProducts.map((product) => {
            const hasMultipleImages = product.images.length > 1;

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

                  {!hasMultipleImages ? (
                    <img
                      src={product.images[0]}
                      alt={product.name}
                      className="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                    />
                  ) : (
                    <div className="grid grid-cols-2 gap-3 w-full h-full">
                      <div className="relative flex items-center justify-center bg-white/60 rounded-xl p-2 border border-slate-100">
                        <img
                          src={product.images[0]}
                          alt={`${product.name} part 1`}
                          className="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                        />
                      </div>
                      <div className="relative flex items-center justify-center bg-white/60 rounded-xl p-2 border border-slate-100">
                        <img
                          src={product.images[1]}
                          alt={`${product.name} part 2`}
                          className="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500"
                        />
                      </div>
                    </div>
                  )}
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
                    <button
                      type="button"
                      onClick={() => setActiveModalProduct(product)}
                      className="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-tech-data text-xs transition-colors"
                    >
                      <span className="material-symbols-outlined text-[16px]">info</span>
                      <span>Spesifikasi</span>
                    </button>
                    <a
                      href="https://shopee.co.id/jangebyofficial"
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

        {/* Bottom Banner inside Catalog */}
        <div className="mt-14 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-teal-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 rounded-2xl bg-[#008779]/30 border border-teal-400/40 flex items-center justify-center text-teal-300">
              <span className="material-symbols-outlined text-[28px]">verified_user</span>
            </div>
            <div className="flex flex-col">
              <span className="font-tech-data text-lg font-bold">Bingung Menentukan Gram Roller &amp; RPM Per CVT?</span>
              <span className="text-sm text-slate-300">Konsultasikan kebutuhan spek harian, touring, atau bore-up Anda ke tim teknis resmi Jangeby.</span>
            </div>
          </div>
          <a
            href="https://wa.me/6285770201010?text=Halo%20Jangeby,%20mau%20konsultasi%20spek%20roller%20dan%20per%20CVT%20untuk%20motor%20saya"
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
