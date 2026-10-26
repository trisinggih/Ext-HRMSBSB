import { useState } from 'react';
import { MITRA_DATA } from '../data/mitra';

interface MitraNetworkProps {
  onOpenPartnerModal?: () => void;
  settings?: Record<string, string>;
}

const DEFAULTS = {
  mitra_badge: 'JARINGAN DISTRIBUSI NASIONAL',
  mitra_title: 'Bengkel & Toko Resmi Jangeby',
  mitra_desc: 'Kunjungi mitra bengkel resmi terdekat untuk pemasangan presisi, konsultasi kirian, dan jaminan produk 100% original di seluruh pelosok Indonesia.',
  mitra_search_placeholder: 'Cari kota, nama bengkel, daerah...',
  mitra_tab_all: 'Semua Wilayah',
  mitra_tab_jawa: 'Jawa & Bali',
  mitra_tab_sumatera: 'Sumatera',
  mitra_tab_kal: 'Kal-Sul-Papua',
  mitra_spec_label: 'Spesialisasi:',
  mitra_contact_btn: 'Hubungi',
  mitra_banner_badge: 'PROGRAM KEMITRAAN RESMI 2026',
  mitra_banner_title: 'Buka Peluang Bisnis Bengkel Bersama Jangeby Official',
  mitra_banner_desc: 'Dapatkan harga grosir distributor, dukungan materi promosi display toko, garansi retur resmi, dan promosi nama bengkel Anda di direktori resmi.',
  mitra_banner_btn_register: 'Daftar Jadi Mitra Bengkel',
  mitra_banner_btn_sales: 'Hubungi Sales Grosir',
  contact_sales_wa: 'https://wa.me/6285770201010?text=Halo%20Tim%20Kemitraan%20Jangeby,%20saya%20ingin%20mendaftar%20sebagai%20agen/bengkel%20resmi'
};

export function MitraNetwork({ onOpenPartnerModal, settings = {} }: MitraNetworkProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];
  const [selectedRegion, setSelectedRegion] = useState<'all' | 'sumatera' | 'jawa-bali' | 'kal-sul-papua'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const filteredMitra = MITRA_DATA.filter((item) => {
    const matchesRegion = selectedRegion === 'all' || item.region === selectedRegion;
    const matchesSearch =
      item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.city.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.address.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.specialization.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesRegion && matchesSearch;
  });

  return (
    <section id="jaringan-mitra" className="w-full py-16 sm:py-24 bg-slate-50 border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              {s('mitra_badge')}
            </span>
            <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              {s('mitra_title')}
            </h2>
            <p className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              {s('mitra_desc')}
            </p>
          </div>

          {/* Search Box */}
          <div className="relative min-w-[280px]">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder={s('mitra_search_placeholder')}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#008779] shadow-xs"
            />
            <span className="material-symbols-outlined absolute left-3 top-3 text-[18px] text-slate-400">
              search
            </span>
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3 top-3 text-slate-400 hover:text-slate-700 text-xs"
              >
                ✕
              </button>
            )}
          </div>
        </div>

        {/* Region Filters */}
        <div className="flex flex-wrap items-center gap-2 mb-8">
          <button
            onClick={() => setSelectedRegion('all')}
            className={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'all'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            {s('mitra_tab_all')} ({MITRA_DATA.length})
          </button>
          <button
            onClick={() => setSelectedRegion('jawa-bali')}
            className={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'jawa-bali'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            {s('mitra_tab_jawa')} (4)
          </button>
          <button
            onClick={() => setSelectedRegion('sumatera')}
            className={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'sumatera'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            {s('mitra_tab_sumatera')} (2)
          </button>
          <button
            onClick={() => setSelectedRegion('kal-sul-papua')}
            className={`px-4 py-2 rounded-full font-tech-data text-xs transition-all ${
              selectedRegion === 'kal-sul-papua'
                ? 'bg-[#008779] text-white font-bold shadow-xs'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
            }`}
          >
            {s('mitra_tab_kal')} (2)
          </button>
        </div>

        {/* Mitra Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {filteredMitra.map((mitra) => (
            <div
              key={mitra.id}
              className="p-6 rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
            >
              <div className="flex flex-col gap-3">
                <div className="flex items-center justify-between">
                  <span className="font-label-mono-sm text-[10px] text-[#008779] bg-teal-50 px-2.5 py-0.5 rounded border border-teal-100 font-bold uppercase">
                    {mitra.regionLabel}
                  </span>
                  {mitra.verified && (
                    <span className="flex items-center gap-1 text-[11px] font-tech-data text-teal-700 font-semibold">
                      <span className="material-symbols-outlined text-[15px] text-[#008779]">verified</span>
                      VERIFIED
                    </span>
                  )}
                </div>

                <h3 className="font-headline-sm text-slate-900 font-bold text-lg leading-snug">
                  {mitra.name}
                </h3>

                <div className="flex items-start gap-1.5 text-xs text-slate-600">
                  <span className="material-symbols-outlined text-[16px] text-slate-400 mt-0.5 flex-shrink-0">
                    location_on
                  </span>
                  <span>{mitra.address}</span>
                </div>

                <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                  <span className="font-label-mono-sm text-[10px] text-slate-400 uppercase block mb-1">
                    {s('mitra_spec_label')}
                  </span>
                  <span className="text-xs font-medium text-slate-800">
                    {mitra.specialization}
                  </span>
                </div>
              </div>

              <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="font-mono text-xs text-slate-500">{mitra.city}</span>
                <a
                  href={`https://wa.me/${mitra.phone.replace(/[^0-9]/g, '')}?text=Halo%20${encodeURIComponent(mitra.name)},%20saya%20ingin%20jadwalkan%20servis%20dan%20pasang%20part%20Jangeby`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-1 text-xs font-tech-data font-bold text-[#008779] hover:text-[#007468]"
                >
                  <span>{s('mitra_contact_btn')}</span>
                  <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>
          ))}
        </div>

        {/* Mitra Registration Callout Banner */}
        <div className="mt-12 p-8 rounded-3xl bg-white border border-teal-200/80 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-6">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-[#008779] flex-shrink-0">
              <span className="material-symbols-outlined text-[32px]">storefront</span>
            </div>
            <div className="flex flex-col">
              <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold tracking-wider">
                {s('mitra_banner_badge')}
              </span>
              <h3 className="font-headline-sm text-xl sm:text-2xl text-slate-900 font-bold">
                {s('mitra_banner_title')}
              </h3>
              <p className="font-body-sm text-slate-600 text-xs sm:text-sm mt-1">
                {s('mitra_banner_desc')}
              </p>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <button
              onClick={onOpenPartnerModal}
              className="px-6 py-3 rounded-full bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs sm:text-sm font-bold shadow-sm transition-all"
            >
              {s('mitra_banner_btn_register')}
            </button>
            <a
              href={s('contact_sales_wa')}
              target="_blank"
              rel="noopener noreferrer"
              className="px-6 py-3 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-tech-data text-xs sm:text-sm font-bold transition-all"
            >
              {s('mitra_banner_btn_sales')}
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
