import { useState, useEffect } from 'react';

interface HeaderProps {
  activeSection?: string;
  onOpenConsultation?: () => void;
}

export function Header({ activeSection = 'beranda', onOpenConsultation }: HeaderProps) {
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
    { label: 'Tentang Kami', href: '#tentang-kami', id: 'tentang-kami' },
    { label: 'Keunggulan', href: '#keunggulan', id: 'keunggulan' },
    { label: 'Katalog Produk', href: '#katalog-unggulan', id: 'katalog-unggulan' },
    { label: 'Dyno Test', href: '#dyno-test', id: 'dyno-test' },
    { label: 'Jaringan Mitra', href: '#jaringan-mitra', id: 'jaringan-mitra' },
    { label: 'Marketplace', href: '#marketplace', id: 'marketplace' },
    { label: 'Kontak', href: '#kontak', id: 'kontak' },
  ];

  return (
    <header
      id="main-header"
      className={`fixed top-0 left-0 w-full z-50 transition-all duration-300 ${
        isScrolled
          ? 'bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm py-2'
          : 'bg-white/90 backdrop-blur-xl border-b border-slate-200/80 shadow-xs py-3'
      }`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
        {/* Brand Logo */}
        <a href="#" className="flex items-center gap-3 group" id="brand-logo-link">
          <div className="relative flex items-center justify-center">
            <div className="absolute -inset-1 rounded-full bg-[#008779]/20 blur-sm group-hover:bg-[#008779]/40 transition-all duration-300" />
            <img
              alt="Jangeby Official Profile"
              className="w-9 h-9 rounded-full object-cover relative ring-2 ring-[#008779]/40"
              src="https://lh3.googleusercontent.com/aida/AEtjO1WxqDs5B3WuE3rXvIlXAHnID883SKeIMXyA1r0IHVg-M-5LCf_dCvCHlQDDl6AHJHtWDNhauUYIS1JmpTBp0YqUzuS5dGieNwU5oFkkOYz8aAAoVXgVM7QprYyuXh1stHWzajtdaSU89-h3BPoKt7TRcSIeEax6tPTdCmybB6yEZaz6OBwgJOMGxOVdsUvYz6IuIjWMAYX8URsDptu1qx2r9Kjj8jBSHKZjCT8wAMAwNg_XEuo-GsfZPVk"
            />
          </div>
          <div className="flex flex-col">
            <span className="font-headline-sm tracking-tight text-slate-900 group-hover:text-[#008779] transition-colors font-bold text-xl leading-none">
              JANGEBY
            </span>
            <span className="font-label-mono-sm text-[#008779] tracking-widest uppercase font-semibold text-[10px] mt-0.5">
              Performance Parts
            </span>
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
                className={`font-body-sm text-[12px] uppercase tracking-wider transition-colors font-semibold ${
                  isActive
                    ? 'text-[#008779] font-bold underline underline-offset-8 decoration-2 decoration-[#008779]'
                    : 'text-slate-600 hover:text-[#008779]'
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
            className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs shadow-[0_2px_12px_rgba(0,135,121,0.25)] hover:shadow-[0_4px_16px_rgba(0,135,121,0.35)] transition-all duration-200"
            href="https://wa.me/6285770201010?text=Halo%20Jangeby%20Official,%20saya%20ingin%20konsultasi%20spek%20performa%20CVT"
            rel="noopener noreferrer"
            target="_blank"
          >
            <span className="material-symbols-outlined text-[18px]">chat</span>
            <span>Konsultasi WhatsApp</span>
          </a>

          {/* Mobile menu button */}
          <button
            id="mobile-menu-toggle"
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
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
        <div className="lg:hidden bg-white/98 backdrop-blur-xl border-b border-slate-200 px-4 pt-3 pb-6 shadow-xl animate-in slide-in-from-top-2 duration-200">
          <div className="flex flex-col space-y-3">
            {navLinks.map((item) => (
              <a
                key={item.id}
                href={item.href}
                onClick={() => setMobileMenuOpen(false)}
                className="font-tech-data text-sm uppercase py-2 px-3 rounded-lg text-slate-700 hover:bg-teal-50 hover:text-[#008779] transition-colors"
              >
                {item.label}
              </a>
            ))}
            <div className="pt-2 border-t border-slate-100 flex flex-col gap-2">
              <a
                className="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-full bg-[#008779] text-white font-tech-data text-xs shadow-sm"
                href="https://wa.me/6285770201010?text=Halo%20Jangeby%20Official"
                target="_blank"
                rel="noopener noreferrer"
              >
                <span className="material-symbols-outlined text-[18px]">chat</span>
                <span>Konsultasi WhatsApp Langsung</span>
              </a>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
