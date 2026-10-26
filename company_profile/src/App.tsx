import { useState, useEffect } from 'react';
import { Routes, Route } from 'react-router-dom';
import { Header } from './components/Header';
import { TopTicker } from './components/TopTicker';
import { Hero } from './components/Hero';
import { AboutPillars } from './components/AboutPillars';
import { ProductCatalog } from './components/ProductCatalog';
import { DynoBenchmark } from './components/DynoBenchmark';
import { MitraNetwork } from './components/MitraNetwork';
import { MarketplaceHub } from './components/MarketplaceHub';
import { ConsultationSection } from './components/ConsultationSection';
import { Footer } from './components/Footer';
import { BarcodeModal } from './components/BarcodeModal';
import { PartnerModal } from './components/PartnerModal';
import { CmsPage } from './views/CmsPage';

function LandingPage() {
  const [activeSection, setActiveSection] = useState('beranda');
  const [isVerifyModalOpen, setIsVerifyModalOpen] = useState(false);
  const [isPartnerModalOpen, setIsPartnerModalOpen] = useState(false);

  // Scroll spy to highlight current active navigation link
  useEffect(() => {
    const handleScroll = () => {
      const sections = [
        'beranda',
        'tentang-kami',
        'keunggulan',
        'katalog-unggulan',
        'dyno-test',
        'jaringan-mitra',
        'marketplace',
        'kontak',
      ];

      const scrollPosition = window.scrollY + 180;

      for (const section of sections) {
        const el = document.getElementById(section);
        if (el) {
          const top = el.offsetTop;
          const height = el.offsetHeight;
          if (scrollPosition >= top && scrollPosition < top + height) {
            setActiveSection(section);
            break;
          }
        }
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <div className="min-h-screen bg-[#f8fafc] text-slate-900 flex flex-col selection:bg-[#008779] selection:text-white">
      {/* Top Telemetry & Certification Ticker */}
      <TopTicker />

      {/* Main Sticky Header */}
      <Header
        activeSection={activeSection}
        onOpenConsultation={() => {
          const kontakEl = document.getElementById('kontak');
          kontakEl?.scrollIntoView({ behavior: 'smooth' });
        }}
      />

      {/* Main Content Sections */}
      <main className="flex-1 pt-16">
        {/* 1. Hero Section & Quick Telemetry Stats */}
        <Hero
          onOpenVerifyModal={() => setIsVerifyModalOpen(true)}
        />

        {/* 2. Engineered Integrity & 4 Pillars */}
        <AboutPillars />

        {/* 3. Interactive Product Catalog (10 Components) */}
        <ProductCatalog />

        {/* 4. Interactive Dyno Test Simulator */}
        <DynoBenchmark />

        {/* 5. National Workshop & Partner Network */}
        <MitraNetwork
          onOpenPartnerModal={() => setIsPartnerModalOpen(true)}
        />

        {/* 6. Official Marketplace Hub */}
        <MarketplaceHub />

        {/* 7. Technical Consultation & Order Form */}
        <ConsultationSection />
      </main>

      {/* Comprehensive Footer */}
      <Footer
        onOpenVerifyModal={() => setIsVerifyModalOpen(true)}
        onOpenPartnerModal={() => setIsPartnerModalOpen(true)}
      />

      {/* Authenticity Barcode Verification Modal */}
      <BarcodeModal
        isOpen={isVerifyModalOpen}
        onClose={() => setIsVerifyModalOpen(false)}
      />

      {/* Partner Registration Modal */}
      <PartnerModal
        isOpen={isPartnerModalOpen}
        onClose={() => setIsPartnerModalOpen(false)}
      />
    </div>
  );
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/pages/:slug" element={<CmsPage />} />
    </Routes>
  );
}
