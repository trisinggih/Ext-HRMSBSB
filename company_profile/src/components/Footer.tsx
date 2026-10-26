export function Footer({
  onOpenVerifyModal,
  onOpenPartnerModal,
}: {
  onOpenVerifyModal?: () => void;
  onOpenPartnerModal?: () => void;
}) {
  return (
    <footer className="w-full bg-slate-950 text-white pt-16 pb-12 border-t border-slate-800">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
          {/* Brand & Address Column */}
          <div className="lg:col-span-4 flex flex-col gap-4">
            <div className="flex items-center gap-3">
              <div className="relative">
                <img
                  alt="Jangeby Official"
                  className="w-10 h-10 rounded-full object-cover ring-2 ring-[#008779]"
                  src="https://lh3.googleusercontent.com/aida/AEtjO1WxqDs5B3WuE3rXvIlXAHnID883SKeIMXyA1r0IHVg-M-5LCf_dCvCHlQDDl6AHJHtWDNhauUYIS1JmpTBp0YqUzuS5dGieNwU5oFkkOYz8aAAoVXgVM7QprYyuXh1stHWzajtdaSU89-h3BPoKt7TRcSIeEax6tPTdCmybB6yEZaz6OBwgJOMGxOVdsUvYz6IuIjWMAYX8URsDptu1qx2r9Kjj8jBSHKZjCT8wAMAwNg_XEuo-GsfZPVk"
                />
              </div>
              <div className="flex flex-col">
                <span className="font-headline-sm tracking-tight text-white font-bold text-xl leading-none">
                  JANGEBY
                </span>
                <span className="font-label-mono-sm text-teal-400 tracking-widest uppercase font-semibold text-[10px] mt-0.5">
                  Performance Parts
                </span>
              </div>
            </div>

            <p className="font-body-sm text-slate-400 text-xs sm:text-sm leading-relaxed max-w-sm">
              Inovasi suku cadang matic dan transmisi CVT berstandar balap dengan durabilitas harian tinggi di Indonesia. Rasakan sensasi berkendara bebas getar dan responsif setiap saat.
            </p>

            <div className="space-y-1 text-xs text-slate-400 font-mono pt-2">
              <div className="text-slate-300 font-bold uppercase font-tech-data">HQ &amp; Pusat Riset:</div>
              <div>Jl. Otomotif Presisi No. 88, Cakung, Jakarta Timur, DKI Jakarta 13910</div>
              <div>WhatsApp CS: +62 857-7020-1010</div>
            </div>
          </div>

          {/* Navigasi Cepat */}
          <div className="lg:col-span-3 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Navigasi Cepat
            </span>
            <ul className="space-y-2 text-xs text-slate-400">
              <li>
                <a href="#beranda" className="hover:text-teal-400 transition-colors">
                  Beranda
                </a>
              </li>
              <li>
                <a href="#tentang-kami" className="hover:text-teal-400 transition-colors">
                  Tentang Jangeby
                </a>
              </li>
              <li>
                <a href="#keunggulan" className="hover:text-teal-400 transition-colors">
                  4 Keunggulan Material
                </a>
              </li>
              <li>
                <a href="#katalog-unggulan" className="hover:text-teal-400 transition-colors">
                  Katalog 10 Komponen
                </a>
              </li>
              <li>
                <a href="#dyno-test" className="hover:text-teal-400 transition-colors">
                  Simulasi Dyno Test
                </a>
              </li>
              <li>
                <a href="#jaringan-mitra" className="hover:text-teal-400 transition-colors">
                  Jaringan Bengkel Resmi
                </a>
              </li>
            </ul>
          </div>

          {/* Saluran Resmi Marketplace */}
          <div className="lg:col-span-3 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Toko Resmi Online
            </span>
            <ul className="space-y-2 text-xs text-slate-400">
              <li>
                <a
                  href="https://shopee.co.id/jangebyofficial"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span className="material-symbols-outlined text-[16px] text-orange-400">shopping_bag</span>
                  <span>Shopee Mall Official</span>
                </a>
              </li>
              <li>
                <a
                  href="https://www.tokopedia.com/jangeby-store"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span className="material-symbols-outlined text-[16px] text-emerald-400">store</span>
                  <span>Tokopedia Official Store</span>
                </a>
              </li>
              <li>
                <a
                  href="https://vt.tiktok.com/ZSY3sTxM4/?page=Mall"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span className="material-symbols-outlined text-[16px] text-rose-400">live_tv</span>
                  <span>TikTok Shop Jangeby</span>
                </a>
              </li>
              <li>
                <a
                  href="https://wa.me/6285770201010"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="hover:text-teal-400 transition-colors flex items-center gap-1.5"
                >
                  <span className="material-symbols-outlined text-[16px] text-teal-400">forum</span>
                  <span>WhatsApp Grosir &amp; CS</span>
                </a>
              </li>
            </ul>
          </div>

          {/* Layanan Keaslian & Mitra */}
          <div className="lg:col-span-2 flex flex-col gap-3">
            <span className="font-tech-data text-xs uppercase font-bold text-teal-400 tracking-wider">
              Layanan &amp; Mitra
            </span>
            <div className="space-y-2 text-xs text-slate-400 flex flex-col items-start">
              <button
                type="button"
                onClick={onOpenVerifyModal}
                className="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">qr_code</span>
                <span>Cek Barcode Otentik</span>
              </button>
              <button
                type="button"
                onClick={onOpenPartnerModal}
                className="hover:text-teal-400 transition-colors text-left flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">handshake</span>
                <span>Daftar Mitra Bengkel</span>
              </button>
              <a
                href="https://wa.me/6285770201010?text=Halo%20Jangeby,%20saya%20ingin%20tanya%20klaim%20garansi"
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-teal-400 transition-colors flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[15px] text-[#008779]">verified</span>
                <span>Klaim Garansi Resmi</span>
              </a>
            </div>
          </div>
        </div>

        {/* Copyright & Disclaimer */}
        <div className="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <div>
            © 2026 Jangeby Performance Parts. Hak Cipta Dilindungi Undang-Undang.
          </div>
          <div className="text-center sm:text-right font-mono text-[11px]">
            Designed for Real Performance &amp; Extreme Durability.
          </div>
        </div>
      </div>
    </footer>
  );
}
