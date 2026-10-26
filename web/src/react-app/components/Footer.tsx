import { useState, useEffect } from 'react';
import api from '../api';

interface FooterProps {
  onOpenVerifyModal?: () => void;
  onOpenPartnerModal?: () => void;
  settings?: Record<string, string>;
  companyLogo?: string | null;
}

const DEFAULTS = {
  footer_logo: '/assets/images/logo-light.png',
  footer_brand_desc: 'BSB Village - Kawasan hunian dan real estate terpadu dengan konsep Green Living dan Modern Comfort di Semarang. Menghadirkan lingkungan asri, harmonis, dan bernilai investasi tinggi.',
  footer_hq_label: 'Kantor Pemasaran & Pengelola:',
  footer_address: 'BSB Village, Jl. Raya Semarang - Boja, Kec. Mijen, Kota Semarang, Jawa Tengah 50212',
  footer_wa_cs: 'Hotline / WA: +62 811-270-0000',
  footer_nav_label: 'Navigasi Cepat',
  footer_nav1: 'Beranda',
  footer_nav2: 'Tentang BSB Village',
  footer_nav3: 'Fasilitas Kawasan',
  footer_nav4: 'Tipe Hunian',
  footer_nav5: 'Simulasi KPR',
  footer_nav6: 'Lokasi & Akses',
  footer_store_label: 'Layanan Mandiri',
  footer_store1: 'Portal HRMS',
  footer_store2: 'Portal Karyawan',
  footer_store3: 'Karier & Rekrutmen',
  footer_store4: 'Hubungi Marketing',
  footer_svc_label: 'Layanan Estate',
  footer_svc1: 'Customer Care',
  footer_svc2: 'Pengajuan Renovasi',
  footer_svc3: 'Estate Management',
  footer_copyright: '© 2026 BSB Village Real Estate. Hak Cipta Dilindungi Undang-Undang.',
  footer_disclaimer: 'Green Living, Modern Comfort & Sustainable Community.',
};

export function Footer({
  onOpenVerifyModal,
  onOpenPartnerModal,
  settings = {},
  companyLogo
}: FooterProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];

  return (
    <footer className="w-full bg-slate-950 text-white pt-16 pb-12 border-t border-slate-800">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
          {/* Brand & Address Column */}
          <div className="lg:col-span-4 flex flex-col gap-4">
            <div className="flex items-center gap-3">
              <div className="relative">
                <img
                  alt="BSB Village"
                  className="h-9 object-contain"
                  src={companyLogo || "/assets/images/logo-light.png"}
                />
              </div>
            </div>

            <p className="font-body-sm text-slate-400 text-xs sm:text-sm leading-relaxed max-w-sm">
              {s('footer_brand_desc')}
            </p>

            <div className="space-y-1 text-xs text-slate-400 font-mono pt-2">
              <div className="text-slate-300 font-bold uppercase font-tech-data">{s('footer_hq_label')}</div>
              <div>{s('footer_address')}</div>
              <div>{s('footer_wa_cs')}</div>
            </div>
          </div>

          {/* Navigasi Cepat */}
          <div className="lg:col-span-3 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              {s('footer_nav_label')}
            </span>
            <ul className="space-y-2 text-xs text-slate-400">
              <li>
                <a href="#beranda" className="hover:text-teal-400 transition-colors">
                  {s('footer_nav1')}
                </a>
              </li>
              <li>
                <a href="#tentang-kami" className="hover:text-teal-400 transition-colors">
                  {s('footer_nav2')}
                </a>
              </li>
              <li>
                <a href="#keunggulan" className="hover:text-teal-400 transition-colors">
                  {s('footer_nav3')}
                </a>
              </li>
              <li>
                <a href="#katalog-unggulan" className="hover:text-teal-400 transition-colors">
                  {s('footer_nav4')}
                </a>
              </li>
            </ul>
          </div>

          {/* Saluran Resmi Marketplace */}
          <div className="lg:col-span-3 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              {s('footer_store_label')}
            </span>
            <ul className="space-y-2 text-xs text-slate-400">
              <li>
                <a
                  href="https://shopee.co.id/jangebyofficial"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <img src="/shopee.svg" alt="Shopee" className="w-[16px] h-[16px] brightness-0 invert opacity-70" />
                  <span>{s('footer_store1')}</span>
                </a>
              </li>
              <li>
                <a
                  href="https://www.tokopedia.com/jangeby-store"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <img src="/tokopedia.svg" alt="Tokopedia" className="w-[16px] h-[16px] brightness-0 invert opacity-70" />
                  <span>{s('footer_store2')}</span>
                </a>
              </li>
              <li>
                <a
                  href="https://vt.tiktok.com/ZSY3sTxM4/?page=Mall"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <img src="/tiktok.svg" alt="TikTok" className="w-[16px] h-[16px] brightness-0 invert opacity-70" />
                  <span>{s('footer_store3')}</span>
                </a>
              </li>
              <li>
                <a
                  href="https://wa.me/6285770201010"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <img src="/whatsapp.svg" alt="WhatsApp" className="w-[16px] h-[16px] brightness-0 invert opacity-70" />
                  <span>{s('footer_store4')}</span>
                </a>
              </li>
            </ul>
          </div>

          {/* Layanan Keaslian & Mitra */}
          <div className="lg:col-span-2 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              {s('footer_svc_label')}
            </span>
            <div className="space-y-2 text-xs text-slate-400 flex flex-col items-start">
              <button
                type="button"
                onClick={onOpenVerifyModal}
                className="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">qr_code</span>
                <span>{s('footer_svc1')}</span>
              </button>
              <button
                type="button"
                onClick={onOpenPartnerModal}
                className="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">handshake</span>
                <span>{s('footer_svc2')}</span>
              </button>
              <a
                href="https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20ingin%20tanya%20klaim%20garansi"
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-teal-400 transition-colors flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">verified</span>
                <span>{s('footer_svc3')}</span>
              </a>
            </div>
          </div>
        </div>

        {/* Copyright & Disclaimer */}
        <div className="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <div>
            {s('footer_copyright')}
          </div>
          <div className="text-center sm:text-right font-mono text-[11px]">
            {s('footer_disclaimer')}
          </div>
        </div>
      </div>
    </footer>
  );
}
