export function MarketplaceHub() {
  const marketplaces = [
    {
      id: 'shopee',
      name: 'Shopee Official Store',
      tag: 'OFFICIAL STORE',
      badgeColor: 'bg-orange-50 text-orange-600 border-orange-200',
      iconName: 'shopping_bag',
      iconBg: 'bg-orange-500 text-white',
      desc: 'Koleksi suku cadang CVT terlengkap dengan voucher gratis ongkir XTRA, cashback koin, dan jaminan originalitas.',
      link: 'https://shopee.co.id/jangebyofficial',
      ctaText: 'Kunjungi Shopee Mall',
    },
    {
      id: 'tokopedia',
      name: 'Tokopedia Official Store',
      tag: 'POWER MERCHANT PRO',
      badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      iconName: 'store',
      iconBg: 'bg-emerald-600 text-white',
      desc: 'Bebas proteksi produk mekanik, opsi pengiriman instan/sameday, serta metode pembayaran cicilan 0%.',
      link: 'https://www.tokopedia.com/jangeby-store',
      ctaText: 'Buka di Tokopedia',
    },
    {
      id: 'tiktok',
      name: 'TikTok Shop Official',
      tag: 'VERIFIED LIVE COMMERCE',
      badgeColor: 'bg-rose-50 text-rose-600 border-rose-200',
      iconName: 'live_tv',
      iconBg: 'bg-slate-950 text-white',
      desc: 'Saksikan live demo pemasangan kirian motor setiap hari, voucher flash-sale live, dan review mekanik balap.',
      link: 'https://vt.tiktok.com/ZSY3sTxM4/?page=Mall',
      ctaText: 'Lihat TikTok Shop',
    },
    {
      id: 'whatsapp',
      name: 'WhatsApp CS & Grosir',
      tag: 'DIRECT ORDER & GROSIR',
      badgeColor: 'bg-teal-50 text-[#008779] border-teal-200',
      iconName: 'forum',
      iconBg: 'bg-[#008779] text-white',
      desc: 'Layanan konsultasi teknis spesifikasi motor khusus, pemesanan partai besar/reseller, dan cek stok cepat.',
      link: 'https://wa.me/6285770201010?text=Halo%20Jangeby%20Official,%20saya%20ingin%20order%20langsung',
      ctaText: 'Chat WhatsApp CS',
    },
  ];

  return (
    <section id="marketplace" className="w-full py-16 sm:py-24 bg-white border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase tracking-widest font-bold">
              SALURAN PEMBELIAN RESMI
            </span>
            <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
              Toko Resmi &amp; Marketplace Jangeby
            </h2>
            <p className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
              Dapatkan kepastian jaminan produk 100% original, bebas penipuan, garansi pengiriman aman, dan promo diskon ongkir setiap hari melalui saluran resmi kami.
            </p>
          </div>
          <div className="font-tech-data text-emerald-600 text-xs sm:text-sm tracking-wider font-semibold flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-200">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
            LIVE ORDER &amp; FAST SHIPPING
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {marketplaces.map((item) => (
            <div
              key={item.id}
              className="p-6 rounded-2xl bg-white border border-slate-200 hover:border-teal-400 hover:shadow-lg transition-all duration-300 flex flex-col justify-between group"
            >
              <div className="flex flex-col gap-4">
                <div className="flex items-center justify-between">
                  <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${item.iconBg} shadow-sm group-hover:scale-110 transition-transform`}>
                    <span className="material-symbols-outlined text-[24px]">
                      {item.iconName}
                    </span>
                  </div>
                  <span className={`text-[10px] font-mono font-bold px-2 py-0.5 rounded border ${item.badgeColor}`}>
                    {item.tag}
                  </span>
                </div>

                <div className="flex flex-col gap-1.5">
                  <h3 className="font-headline-sm text-slate-900 font-bold text-lg group-hover:text-[#008779] transition-colors">
                    {item.name}
                  </h3>
                  <p className="font-body-sm text-slate-600 text-xs sm:text-sm leading-relaxed">
                    {item.desc}
                  </p>
                </div>
              </div>

              <div className="mt-6 pt-4 border-t border-slate-100">
                <a
                  href={item.link}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-[#008779] text-slate-800 hover:text-white font-tech-data text-xs font-bold transition-all duration-200"
                >
                  <span>{item.ctaText}</span>
                  <span className="material-symbols-outlined text-[16px]">open_in_new</span>
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
