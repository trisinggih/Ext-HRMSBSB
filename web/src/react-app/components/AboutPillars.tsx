interface AboutPillarsProps {
  settings?: Record<string, string>;
  sections?: any[];
}

const DEFAULTS = {
  about_badge: 'ENGINEERED INTEGRITY',
  about_title: 'Mengenai Jangeby Official',
  about_desc: 'Pelopor inovasi suku cadang motor matic CVT dan performa tinggi di Indonesia. Kami memadukan riset mekanika balap sirkuit dengan durabilitas harian ekstrem demi menghadirkan akselerasi instan tanpa kompromi.',
};

const DEFAULT_PILLARS = [
  {
    title: 'Material Premium Berstandar Tinggi',
    content: 'Memanfaatkan material SWOCS-VH baja super bersih untuk pegas, Teflon PA46 anti aus, serat Kevlar Aramid, hingga Titanium Alloy ringan berpresisi tinggi.',
    badge: 'GRADE: AEROSPACE & RACING',
    icon: 'view_in_ar'
  },
  {
    title: 'Presisi Pabrikasi & Durabilitas Ekstrem',
    content: 'Teknologi interlock terbaru, toleransi micro-finish, serta ketahanan termal ekstrem dari rentang -40°C hingga 150°C untuk mencegah deformasi komponen.',
    badge: 'TOLERANCE: CNC MACHINED',
    icon: 'precision_manufacturing'
  },
  {
    title: 'Teruji Harian, Touring & Balap',
    content: 'Formulasi sudut pulley dan pegas CVT menghilangkan getaran (anti gredek), mempercepat akselerasi putaran bawah, dan menjaga efisiensi konsumsi BBM.',
    badge: 'RESULT: ZERO VIBRATION & ACCEL',
    icon: 'speed'
  },
  {
    title: 'Jaminan Garansi & Keaslian',
    content: 'Seluruh suku cadang resmi dikemas dengan identitas hologram dan sistem verifikasi serial number untuk melindungi konsumen dari produk tiruan berbahaya.',
    badge: 'SECURITY: 100% AUTHENTIC CODE',
    icon: 'verified'
  }
];

export function AboutPillars({ settings = {}, sections = [] }: AboutPillarsProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];

  const aboutSection = sections.find(sec => sec.key === 'about_us');
  const advantagesSections = sections.filter(sec => sec.key === 'advantages');

  const aboutTitle = aboutSection?.title || s('about_title');
  const aboutDesc = aboutSection?.content || s('about_desc');
  const aboutImage = aboutSection?.image_url;

  // Use dynamic advantages if they exist, otherwise fallback to defaults
  const pillars = advantagesSections.length > 0 
    ? advantagesSections.map((adv, idx) => {
        // Fallback to default icons if possible, otherwise use a generic one
        const defaultIcon = DEFAULT_PILLARS[idx]?.icon || 'verified';
        const defaultBadge = DEFAULT_PILLARS[idx]?.badge || 'ADVANTAGE';
        return {
          id: adv.id,
          title: adv.title,
          content: adv.content,
          badge: defaultBadge,
          icon: defaultIcon
        };
      })
    : DEFAULT_PILLARS;

  return (
    <section id="tentang-kami" className="w-full py-16 sm:py-20 bg-slate-50 border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              {s('about_badge')}
            </span>
            <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              {aboutTitle}
            </h2>
            <div 
              className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed whitespace-pre-line quill-content"
              dangerouslySetInnerHTML={{ __html: aboutDesc }}
            />
          </div>
          {aboutImage && (
            <div className="max-w-xs mt-6 md:mt-0">
              <img src={aboutImage} alt={aboutTitle} className="rounded-xl shadow-md w-full h-auto object-cover max-h-64" />
            </div>
          )}
        </div>

        {/* Engineered Pillars Bento Grid */}
        <div id="keunggulan" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {pillars.map((pillar, idx) => (
            <div key={pillar.id || idx} className="p-7 rounded-2xl bg-white border border-slate-200 hover:border-teal-300 hover:shadow-md transition-all duration-300 flex flex-col justify-between h-full group">
              <div className="flex flex-col gap-4">
                <div className="w-12 h-12 rounded-xl bg-teal-50 border border-teal-100 flex items-center justify-center text-[#008779] group-hover:bg-[#008779] group-hover:text-white transition-colors">
                  <span className="material-symbols-outlined text-[26px]">{pillar.icon}</span>
                </div>
                <h3 className="font-headline-sm text-slate-900 font-bold leading-tight text-lg sm:text-xl">
                  {pillar.title}
                </h3>
                <div 
                  className="font-body-md text-slate-600 leading-relaxed text-[15px] quill-content"
                  dangerouslySetInnerHTML={{ __html: pillar.content }}
                />
              </div>
              <div className="mt-6 pt-3 font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold border-t border-slate-100">
                {pillar.badge}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
