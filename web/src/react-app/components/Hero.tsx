import { CmsSettings } from '../App';

interface HeroProps {
  onOpenVerifyModal?: () => void;
  settings?: CmsSettings;
}

// Default teks fallback jika CMS belum load / API tidak tersedia
const DEFAULTS = {
  hero_badge: 'RACING SPECIFICATION & STREET DURABILITY',
  hero_title_line1: 'RIDE WITH',
  hero_title_line2: 'REAL PERFORMANCE',
  hero_subtitle: 'Dengan memilih produk Jangeby, Anda tidak hanya mendapatkan kualitas terbaik tetapi juga investasi yang mendukung kenyamanan dan keamanan berkendara baik harian, touring, maupun balap. Sangat cocok bagi pengendara yang mengutamakan kualitas dan performa nyata.',
  hero_cta_catalog: 'Lihat Produk Unggulan',
  hero_cta_wa_text: 'Hubungi via WhatsApp',
  hero_stat1_label: 'Garansi Keaslian',
  hero_stat1_value: '100%',
  hero_stat1_desc: 'Barcode otentik langsung dari pabrikan resmi',
  hero_stat2_label: 'Engineering Parts',
  hero_stat2_value: '15+',
  hero_stat2_desc: 'Komponen balap, touring & harian matic',
  hero_stat3_label: 'Cakupan Nasional',
  hero_stat3_value: '38+',
  hero_stat3_desc: 'Provinsi didukung ratusan mitra bengkel',
  hero_stat4_label: 'Kepuasan Pelanggan',
  hero_stat4_value: '4.9 / 5',
  hero_stat4_desc: 'Ribuan ulasan positif di Shopee, Tokopedia & TikTok',
  contact_whatsapp: 'https://wa.me/6285770201010?text=Halo,%20Saya%20ingin%20bertanya%20terkait%20produk%20Jangeby',
};

export function Hero({ onOpenVerifyModal, settings = {} }: HeroProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];

  return (
    <section id="beranda" className="relative w-full overflow-hidden bg-gradient-to-b from-white via-slate-50 to-slate-100/70 pb-12 pt-6 sm:pt-10 border-b border-slate-200">
      {/* Atmospheric Glow */}
      <div className="absolute -top-32 left-1/4 w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none" />
      <div className="absolute top-1/2 -right-24 w-[30rem] h-[30rem] rounded-full bg-cyan-500/10 blur-[120px] pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
          {/* Hero Left Column */}
          <div className="lg:col-span-7 flex flex-col gap-6">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">

              <span className="font-label-mono-sm text-[11px] text-[#008779] tracking-widest uppercase font-bold">
                {s('hero_badge')}
              </span>
            </div>

            <div className="flex flex-col gap-2">
              <h1 className="font-display-hero text-slate-900 tracking-tight uppercase font-extrabold text-4xl sm:text-5xl lg:text-6xl leading-tight">
                {s('hero_title_line1')} <br />
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#008779] via-teal-600 to-cyan-600">
                  {s('hero_title_line2')}
                </span>
              </h1>
              <div 
                className="font-body-lg text-slate-600 leading-relaxed max-w-2xl mt-2 text-base sm:text-lg quill-content"
                dangerouslySetInnerHTML={{ __html: s('hero_subtitle') as string }}
              />
            </div>

            {/* Dual High Impact CTAs */}
            <div className="flex flex-wrap items-center gap-4 pt-1">
              <a
                id="hero-catalog-cta"
                className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-[#008779] text-white font-tech-data text-sm sm:text-base shadow-[0_4px_16px_rgba(0,135,121,0.25)] hover:shadow-[0_6px_22px_rgba(0,135,121,0.35)] hover:bg-[#007468] transition-all duration-200"
                href="#katalog-unggulan"
              >
                <img src="/whatsapp.svg" alt="WhatsApp" className="w-[20px] h-[20px]" />
                <span>{s('hero_cta_catalog')}</span>
              </a>
            </div>

            {/* Official Verification Notice Badge */}

          </div>

          {/* Hero Right Column: Graphic Display Poster */}
          <div className="lg:col-span-5 relative">
            <div className="relative rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-[0_12px_36px_rgba(15,23,42,0.12)] group">
              <div className="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent z-10 pointer-events-none" />
              <img
                alt="Jangeby Original Performance Network Map and Lineup"
                className="w-full h-auto object-cover transform group-hover:scale-105 transition-transform duration-500"
                src={settings.hero_image_url || "/assets/images/company/1.webp"}
              />
              <div className="absolute bottom-4 left-4 right-4 z-20 p-4 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-lg flex items-center justify-between">
                <div className="flex flex-col">
                  <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold">
                    Jaringan Bengkel &amp; Toko Resmi
                  </span>
                  <span className="font-tech-data text-slate-900 text-xs sm:text-sm">
                    Tersedia di Seluruh Pelosok Indonesia
                  </span>
                </div>
                <a
                  className="p-2 rounded-full bg-[#008779] hover:bg-[#007468] text-white transition-colors shadow-sm"
                  href="#jaringan-mitra"
                  aria-label="Lihat Jaringan Mitra"
                >
                  <span className="material-symbols-outlined text-[20px]">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        {/* Telemetry Stats Bar Section */}
        <div className="mt-10 sm:mt-14 grid grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { label: s('hero_stat1_label'), value: s('hero_stat1_value'), desc: s('hero_stat1_desc'), color: 'text-[#008779]' },
            { label: s('hero_stat2_label'), value: s('hero_stat2_value'), desc: s('hero_stat2_desc'), color: 'text-teal-600' },
            { label: s('hero_stat3_label'), value: s('hero_stat3_value'), desc: s('hero_stat3_desc'), color: 'text-sky-600' },
            { label: s('hero_stat4_label'), value: s('hero_stat4_value'), desc: s('hero_stat4_desc'), color: 'text-[#008779]' },
          ].map((stat, i) => (
            <div key={i} className="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col gap-1 hover:border-teal-300 transition-colors">
              <span className={`font-label-mono-sm text-[11px] ${stat.color} uppercase tracking-widest font-bold`}>
                {stat.label}
              </span>
              <span className="font-headline-lg text-slate-900 font-extrabold text-3xl sm:text-4xl">
                {stat.value}
              </span>
              <span className="font-body-sm text-slate-600 text-xs">
                {stat.desc}
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
