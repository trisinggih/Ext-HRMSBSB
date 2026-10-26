import { useState } from 'react';
import { Product } from '../types';

interface ProductModalProps {
  product: Product | null;
  onClose: () => void;
}

export function ProductModal({ product, onClose }: ProductModalProps) {
  const [selectedMotor, setSelectedMotor] = useState<string>('');

  if (!product) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-in fade-in duration-200">
      <div className="relative w-full max-w-2xl bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-1 rounded-full bg-[#008779]/10 text-[#008779] font-label-mono-sm text-[11px] font-bold uppercase">
              {product.categoryLabel}
            </span>
            <span className="text-xs font-mono text-slate-500">// {product.badge}</span>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
            aria-label="Tutup"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Modal Body */}
        <div className="p-6 overflow-y-auto space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
            {/* Image Preview */}
            <div className="relative bg-slate-50 border border-slate-200 rounded-2xl aspect-square flex items-center justify-center p-4 overflow-hidden">
              {product.images.length === 1 ? (
                <img
                  src={product.images[0]}
                  alt={product.name}
                  className="w-full h-full object-contain"
                />
              ) : (
                <div className="grid grid-cols-2 gap-2 w-full h-full">
                  {product.images.map((img, i) => (
                    <img
                      key={i}
                      src={img}
                      alt={`${product.name} ${i + 1}`}
                      className="w-full h-full object-contain"
                    />
                  ))}
                </div>
              )}
              <span className="absolute top-3 left-3 px-2 py-0.5 rounded bg-white/95 border border-slate-200 font-label-mono-sm text-[10px] text-[#008779] font-bold shadow-xs">
                {product.badge}
              </span>
            </div>

            {/* Info */}
            <div className="flex flex-col gap-3">
              <div className="flex items-center justify-between">
                <span className="font-tech-data text-xs text-[#008779] bg-teal-50 px-2 py-0.5 rounded border border-teal-100">
                  {product.highlight}
                </span>
                <span className="font-label-mono-sm text-xs font-bold text-slate-900">
                  {product.specValue}
                </span>
              </div>
              <h3 className="font-headline-sm text-2xl font-bold text-slate-900 leading-tight">
                {product.name}
              </h3>
              <p className="font-body-sm text-slate-600 text-xs sm:text-sm leading-relaxed">
                {product.description}
              </p>

              {/* Quick Spec List */}
              <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
                {product.specs.material && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Material:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.material}</span>
                  </div>
                )}
                {product.specs.rpmOptions && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Pilihan RPM:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.rpmOptions}</span>
                  </div>
                )}
                {product.specs.temperatureRange && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Tahan Panas:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.temperatureRange}</span>
                  </div>
                )}
                {product.specs.weightOptions && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Opsi Gram:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.weightOptions}</span>
                  </div>
                )}
                {product.specs.finish && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Finishing:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.finish}</span>
                  </div>
                )}
                {product.specs.technology && (
                  <div className="flex justify-between">
                    <span className="text-slate-500">Teknologi:</span>
                    <span className="font-semibold text-slate-800 text-right">{product.specs.technology}</span>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Motor Compatibility Checker */}
          <div className="border-t border-slate-200 pt-5">
            <h4 className="font-tech-data text-xs uppercase tracking-wider text-slate-900 mb-2 flex items-center gap-1.5">
              <span className="material-symbols-outlined text-[16px] text-[#008779]">two_wheeler</span>
              Cek Kompatibilitas Motor
            </h4>
            <div className="flex flex-wrap gap-2">
              {product.compatibility.map((motor, idx) => (
                <button
                  key={idx}
                  type="button"
                  onClick={() => setSelectedMotor(motor)}
                  className={`text-xs px-3 py-1.5 rounded-lg border transition-all ${
                    selectedMotor === motor
                      ? 'bg-[#008779] text-white border-[#008779] shadow-xs'
                      : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                  }`}
                >
                  {motor}
                </button>
              ))}
            </div>
            {selectedMotor && (
              <p className="mt-2 text-xs text-teal-700 font-medium flex items-center gap-1 bg-teal-50 p-2 rounded-lg border border-teal-100">
                <span className="material-symbols-outlined text-[16px]">check_circle</span>
                Terkonfirmasi Presisi Plug &amp; Play untuk <strong>{selectedMotor}</strong>.
              </p>
            )}
          </div>

          {/* Direct Purchase Actions */}
          <div className="border-t border-slate-200 pt-5 flex flex-col sm:flex-row items-center gap-3">
            <a
              href="https://shopee.co.id/jangebyofficial"
              target="_blank"
              rel="noopener noreferrer"
              className="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full bg-[#008779] text-white font-tech-data text-xs shadow-sm hover:bg-[#007468] transition-colors"
            >
              <span className="material-symbols-outlined text-[18px]">shopping_bag</span>
              <span>Beli di Shopee Official</span>
            </a>
            <a
              href="https://www.tokopedia.com/jangeby-store"
              target="_blank"
              rel="noopener noreferrer"
              className="w-full sm:flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full bg-white border border-slate-300 text-slate-800 font-tech-data text-xs shadow-xs hover:border-[#008779] hover:text-[#008779] transition-colors"
            >
              <span className="material-symbols-outlined text-[18px] text-teal-600">store</span>
              <span>Beli di Tokopedia</span>
            </a>
            <a
              href={`https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20ingin%20tanya%20stok%20${encodeURIComponent(product.name)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full sm:w-auto p-3 rounded-full bg-slate-100 hover:bg-teal-50 text-slate-700 hover:text-[#008779] border border-slate-200 transition-colors flex items-center justify-center"
              title="Konsultasi WhatsApp"
            >
              <span className="material-symbols-outlined text-[20px]">chat</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
