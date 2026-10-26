import { useState, useEffect } from 'react';
import { Header } from './components/Header';
import { TopTicker } from './components/TopTicker';
import { Hero } from './components/Hero';
import { AboutPillars } from './components/AboutPillars';
import { ProductCatalog } from './components/ProductCatalog';
import { MarketplaceHub } from './components/MarketplaceHub';
import { ConsultationSection } from './components/ConsultationSection';
import { Footer } from './components/Footer';
import { BarcodeModal } from './components/BarcodeModal';
import { PartnerModal } from './components/PartnerModal';
import { Product } from './types';

// Base API URL — gunakan env atau default ke backend dev
const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8011/api/v1';

export interface CmsSettings {
  [key: string]: string;
}

export function LandingPage() {
  const [activeSection, setActiveSection] = useState('beranda');
  const [isVerifyModalOpen, setIsVerifyModalOpen] = useState(false);
  const [isPartnerModalOpen, setIsPartnerModalOpen] = useState(false);

  // ===== CMS STATE =====
  const [cmsSettings, setCmsSettings] = useState<CmsSettings>({});
  const [cmsSections, setCmsSections] = useState<any[]>([]);
  const [cmsProducts, setCmsProducts] = useState<Product[]>([]);
  const [companyData, setCompanyData] = useState<any>(null);
  const [cmsLoaded, setCmsLoaded] = useState(false);
  const [isAppLoading, setIsAppLoading] = useState(true);
  const [isFadingOut, setIsFadingOut] = useState(false);

  // Fetch CMS data saat app pertama di-load
  useEffect(() => {
    const fetchCmsData = async () => {
      try {
        const [siteRes, productsRes, companyRes] = await Promise.all([
          fetch(`${API_BASE}/cms/site`),
          fetch(`${API_BASE}/cms/public/products`),
          fetch(`${API_BASE}/company/logo`),
        ]);

        if (siteRes.ok) {
          const json = await siteRes.json();
          if (json.success && json.data) {
            setCmsSettings(json.data.settings || {});
            setCmsSections(json.data.sections || []);
          }
        }

        if (productsRes.ok) {
          const json = await productsRes.json();
          if (json.status === 'success' && Array.isArray(json.data)) {
            setCmsProducts(json.data);
          }
        }

        if (companyRes.ok) {
          const json = await companyRes.json();
          if (json.success && json.data) {
            setCompanyData(json.data);
          }
        }
      } catch (err) {
        // Jika API tidak tersedia, komponen akan fallback ke data statis
        console.warn('[CMS] Gagal memuat data CMS, menggunakan data statis.', err);
      } finally {
        setCmsLoaded(true);
      }
    };

    fetchCmsData();
  }, []);

  // Update document title and meta tags dynamically
  useEffect(() => {
    if (companyData && companyData.name) {
      const companyName = companyData.name;
      const companyDesc = companyData.description || 'Inovasi suku cadang matic dan transmisi CVT berstandar balap dengan durabilitas harian tinggi di Indonesia.';
      
      document.title = companyName;
      
      const setMeta = (nameAttr: string, propertyAttr: string, content: string) => {
        let el = document.querySelector(`meta[property="${propertyAttr}"]`) || document.querySelector(`meta[name="${nameAttr}"]`);
        if (!el) {
          el = document.createElement('meta');
          if (propertyAttr) el.setAttribute('property', propertyAttr);
          if (nameAttr) el.setAttribute('name', nameAttr);
          document.head.appendChild(el);
        }
        el.setAttribute('content', content);
      };

      setMeta('description', '', companyDesc);
      setMeta('', 'og:title', companyName);
      setMeta('', 'og:description', companyDesc);
      setMeta('', 'og:site_name', companyName);
      setMeta('', 'twitter:title', companyName);
      setMeta('', 'twitter:description', companyDesc);
    }
  }, [companyData]);

  // Wait for images and CMS to fully load before hiding the loader
  useEffect(() => {
    if (!cmsLoaded) return;

    let hasHidden = false;
    const hideLoader = () => {
      if (hasHidden) return;
      hasHidden = true;
      setIsFadingOut(true);
      setTimeout(() => setIsAppLoading(false), 500); // Wait for transition
    };

    const images = Array.from(document.images);
    let loadedCount = 0;

    const imageLoaded = () => {
      loadedCount++;
      if (loadedCount >= images.length) {
        setTimeout(hideLoader, 300);
      }
    };

    if (images.length === 0) {
      setTimeout(hideLoader, 300);
    } else {
      images.forEach(img => {
        if (img.complete) {
          imageLoaded();
        } else {
          img.addEventListener('load', imageLoaded, { once: true });
          img.addEventListener('error', imageLoaded, { once: true });
        }
      });
    }

    const fallbackTimeout = setTimeout(hideLoader, 4000);
    return () => clearTimeout(fallbackTimeout);
  }, [cmsLoaded]);

  // Scroll spy to highlight current active navigation link
  useEffect(() => {
    const handleScroll = () => {
      const sections = [
        'beranda',
        'tentang-kami',
        'keunggulan',
        'katalog-unggulan',
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
      {/* Full Page Loading Screen */}
      {isAppLoading && (
        <div
          className={`fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white transition-opacity duration-500 ${
            isFadingOut ? 'opacity-0' : 'opacity-100'
          }`}
        >
          <div className="w-64 max-w-[80vw] flex flex-col items-center">
            <div className="mb-4 text-[#008779] font-tech-data tracking-widest text-sm animate-pulse font-semibold">
              INITIALIZING PERFORMANCE...
            </div>
            <div className="w-full h-[3px] bg-slate-200 rounded-full overflow-hidden relative shadow-[0_0_15px_rgba(0,135,121,0.1)]">
              <div 
                className="absolute top-0 h-full bg-gradient-to-r from-[#008779] to-teal-400 w-1/2 rounded-full shadow-[0_0_10px_rgba(45,212,191,0.4)]"
                style={{
                  animation: 'indeterminate-progress 1.2s ease-in-out infinite alternate'
                }}
              >
                <style>{`
                  @keyframes indeterminate-progress {
                    0% { left: -10%; }
                    100% { left: 60%; }
                  }
                `}</style>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Top Telemetry & Certification Ticker */}
      <TopTicker settings={cmsSettings} />

      {/* Main Sticky Header */}
      <Header
        activeSection={activeSection}
        onOpenConsultation={() => {
          const kontakEl = document.getElementById('kontak');
          kontakEl?.scrollIntoView({ behavior: 'smooth' });
        }}
        settings={cmsSettings}
        companyLogo={companyData?.logo}
      />

      {/* Main Content Sections */}
      <main className="flex-1 pt-10">
        {/* 1. Hero Section & Quick Telemetry Stats */}
        <Hero
          onOpenVerifyModal={() => setIsVerifyModalOpen(true)}
          settings={cmsSettings}
        />

        {/* 2. Engineered Integrity & 4 Pillars */}
        <AboutPillars settings={cmsSettings} sections={cmsSections} />

        {/* 3. Interactive Product Catalog (10 Components) */}
        <ProductCatalog
          cmsProducts={cmsProducts}
          cmsLoaded={cmsLoaded}
          settings={cmsSettings}
        />

        {/* 4. Official Marketplace Hub */}
        <MarketplaceHub settings={cmsSettings} />

        {/* 7. Technical Consultation & Order Form */}
        <ConsultationSection settings={cmsSettings} />
      </main>

      {/* Comprehensive Footer */}
      <Footer
        onOpenVerifyModal={() => setIsVerifyModalOpen(true)}
        onOpenPartnerModal={() => setIsPartnerModalOpen(true)}
        settings={cmsSettings}
        companyLogo={companyData?.logo}
      />

      {/* Authenticity Barcode Verification Modal */}
      <BarcodeModal
        isOpen={isVerifyModalOpen}
        onClose={() => setIsVerifyModalOpen(false)}
        settings={cmsSettings}
      />

      {/* Partner Registration Modal */}
      <PartnerModal
        isOpen={isPartnerModalOpen}
        onClose={() => setIsPartnerModalOpen(false)}
        settings={cmsSettings}
      />
    </div>
  );
}
