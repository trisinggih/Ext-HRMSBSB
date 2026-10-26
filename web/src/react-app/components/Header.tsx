import { useState, useEffect } from 'react';
import api from '../api';

interface HeaderProps {
  activeSection?: string;
  onOpenConsultation?: () => void;
  settings?: Record<string, string>;
  companyLogo?: string | null;
}

const DEFAULTS = {
  header_nav_about: 'Tentang Kami',
  header_nav_features: 'Keunggulan',
  header_nav_catalog: 'Katalog Produk',
  header_nav_marketplace: 'Marketplace',
  header_nav_contact: 'Kontak',
  header_cta_wa: 'WHATSAPP NOW',
  header_cta_wa_mobile: 'Konsultasi WhatsApp Langsung',
  contact_whatsapp: 'https://wa.me/6285770201010?text=Halo%20Jangeby%20Official,%20saya%20ingin%20konsultasi%20spek%20performa%20CVT',
};

export function Header({ activeSection = 'beranda', onOpenConsultation, settings = {}, companyLogo }: HeaderProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];
  const [isScrolled, setIsScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const navLinks = [
    { label: s('header_nav_about'), href: '#tentang-kami', id: 'tentang-kami' },
    { label: s('header_nav_features'), href: '#keunggulan', id: 'keunggulan' },
    { label: s('header_nav_catalog'), href: '#katalog-unggulan', id: 'katalog-unggulan' },
    { label: s('header_nav_marketplace'), href: '#marketplace', id: 'marketplace' },
    { label: 'Karier', href: '/karier', id: 'karier' },
    { label: s('header_nav_contact'), href: '#kontak', id: 'kontak' },
  ];

  return (
    <>
      <header
        id="main-header"
        className={`fixed top-0 left-0 w-full z-50 transition-all duration-300 ${isScrolled
          ? 'bg-[#124d32]/95 backdrop-blur-xl border-b border-[#0c3823] shadow-sm py-2'
          : 'bg-[#124d32]/90 backdrop-blur-xl border-b border-[#0c3823]/80 shadow-xs py-3'
          }`}
      >
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
          {/* Brand Logo */}
          <a href="#" className="flex items-center gap-3 group" id="brand-logo-link">
            <div className="relative flex items-center justify-center">
              <div className="flex flex-col">
                <span className="font-headline-sm tracking-tight text-white transition-colors font-bold text-xl leading-none">
                  <img
                    alt="BSB Village"
                    className="h-8 object-contain"
                    src={companyLogo || "/assets/images/logo-light.png"}
                  />
                </span>
              </div>
            </div>
          </a>

          {/* Desktop Navigation */}
          <nav className="hidden lg:flex items-center gap-6" id="desktop-nav">
            {navLinks.map((item) => {
              const isActive = activeSection === item.id;
              return (
                <a
                  key={item.id}
                  href={item.href}
                  className={`font-body-sm text-[12px] uppercase tracking-wider transition-colors font-semibold ${isActive
                    ? 'text-yellow-300 font-bold underline underline-offset-8 decoration-2 decoration-yellow-300'
                    : 'text-slate-100 hover:text-white'
                    }`}
                >
                  {item.label}
                </a>
              );
            })}
          </nav>

          {/* Header Right Actions */}
          <div className="flex items-center gap-3">
            <a
              id="whatsapp-header-btn"
              className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white hover:bg-slate-100 text-[#008779] font-tech-data text-xs shadow-[0_2px_12px_rgba(255,255,255,0.1)] hover:shadow-[0_4px_16px_rgba(255,255,255,0.2)] transition-all duration-200"
              href={s('contact_whatsapp')}
              rel="noopener noreferrer"
              target="_blank"
            >
              <img src="/whatsapp.svg" alt="WhatsApp" className="w-[18px] h-[18px] text-[#008779]" />
              <span>{s('header_cta_wa')}</span>
            </a>

            {/* Mobile menu button */}
            <button
              id="mobile-menu-toggle"
              type="button"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 rounded-lg text-white hover:text-slate-100 hover:bg-[#007468] transition-colors"
              aria-label="Buka Menu"
            >
              <span className="material-symbols-outlined text-[26px]">
                {mobileMenuOpen ? 'close' : 'menu'}
              </span>
            </button>
          </div>
        </div>

        {/* Mobile Menu Dropdown */}
        {mobileMenuOpen && (
          <div className="lg:hidden bg-[#008779]/98 backdrop-blur-xl border-b border-[#007468] px-4 pt-3 pb-6 shadow-xl animate-in slide-in-from-top-2 duration-200">
            <div className="flex flex-col space-y-3">
              {navLinks.map((item) => {
                const isActive = activeSection === item.id;
                return (
                  <a
                    key={item.id}
                    href={item.href}
                    onClick={() => setMobileMenuOpen(false)}
                    className={`font-tech-data text-sm uppercase py-2 px-3 rounded-lg transition-colors ${isActive
                      ? 'bg-white text-[#008779] font-bold shadow-sm'
                      : 'text-slate-100 hover:bg-[#007468] hover:text-white'
                      }`}
                  >
                    {item.label}
                  </a>
                );
              })}
              <div className="pt-2 border-t border-[#007468] flex flex-col gap-2">
                <a
                  className="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-full bg-white text-[#008779] font-tech-data text-xs shadow-sm hover:bg-slate-100 transition-colors"
                  href={s('contact_whatsapp')}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <span className="material-symbols-outlined text-[18px]">chat</span>
                  <span>{s('header_cta_wa_mobile')}</span>
                </a>
              </div>
            </div>
          </div>
        )}
      </header>
    </>
  );
}
