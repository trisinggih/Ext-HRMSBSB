<template>
<section id="kontak" class="w-full py-16 sm:py-24 bg-slate-50 border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
    {{ /* Info Side */ }}
  <div class="lg:col-span-5 flex flex-col gap-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">
              <span class="material-symbols-outlined text-[16px] text-[#008779]">support_agent</span>
              <span class="font-label-mono-sm text-[11px] text-[#008779] tracking-widest uppercase font-bold">
                TECHNICAL ADVISOR &amp; CS
              </span>
            </div>

            <div class="flex flex-col gap-2">
              <h2 class="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
                Diskusikan Kebutuhan Kirian Motor Anda
              </h2>
              <p class="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
                Setiap motor dan pengendara memiliki karakter berbeda. Konsultasikan racikan per CVT, bobot roller, hingga mangkok ganda yang paling cocok untuk motor Anda bersama tim teknisi Jangeby.
              </p>
            </div>
    {{ /* Contact details */ }}
  <div class="space-y-4 pt-2">
              <div class="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <span class="material-symbols-outlined text-[22px]">phone_in_talk</span>
                </div>
                <div class="flex flex-col">
                  <span class="font-label-mono-sm text-[10px] text-slate-400 uppercase">WhatsApp CS &amp; Konsultasi</span>
                  <span class="font-tech-data text-slate-900 text-sm font-bold">+62 857-7020-1010</span>
                </div>
              </div>

              <div class="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <span class="material-symbols-outlined text-[22px]">schedule</span>
                </div>
                <div class="flex flex-col">
                  <span class="font-label-mono-sm text-[10px] text-slate-400 uppercase">Jam Operasional Teknisi</span>
                  <span class="font-tech-data text-slate-900 text-sm font-bold">Senin - Sabtu: 08:30 - 17:30 WIB</span>
                </div>
              </div>

              <div class="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <span class="material-symbols-outlined text-[22px]">verified_user</span>
                </div>
                <div class="flex flex-col">
                  <span class="font-label-mono-sm text-[10px] text-slate-400 uppercase">Verifikasi Barcode Produk</span>
                  <span class="font-tech-data text-slate-900 text-sm font-bold">Resmi Terdata Pabrikan</span>
                </div>
              </div>
            </div>
          </div>
    {{ /* Form Side */ }}
  <div class="lg:col-span-7">
            <div class="p-8 sm:p-10 rounded-3xl bg-white border border-slate-200 shadow-xl">
              <div class="mb-6 pb-4 border-b border-slate-100">
                <span class="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold tracking-wider">
                  FORMULIR KONSULTASI CEPAT
                </span>
                <h3 class="font-headline-sm text-xl sm:text-2xl text-slate-900 font-bold mt-1">
                  Kirim Data &amp; Langsung Terhubung ke WhatsApp
                </h3>
              </div>
    {{ isSubmitted && (
                <div class="mb-6 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-900 text-sm flex items-center gap-3">
                  <span class="material-symbols-outlined text-[24px] text-[#008779]">check_circle</span>
                  <div>
                    <strong class="block font-tech-data">Formulir Terkirim!</strong>
                    <span>WhatsApp telah terbuka secara otomatis untuk memulai obrolan dengan CS kami.</span>
                  </div>
                </div>
              ) }}
  <form onSubmit={handleSubmit} class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Nama Lengkap *
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="Contoh: Budi Pratama"
                      value={formData.fullName}
                      onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                      class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                    />
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label class="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Nomor WhatsApp *
                    </label>
                    <input
                      type="tel"
                      required
                      placeholder="Contoh: 08123456789"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Model / Tipe Motor
                    </label>
                    <select
                      value={formData.motorType}
                      onChange={(e) => setFormData({ ...formData, motorType: e.target.value })}
                      class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779] bg-white"
                    >
                      <option value="Vario 125/150/160">Honda Vario 125 / 150 / 160</option>
                      <option value="NMAX 155 Old/New">Yamaha NMAX 155 Old / New</option>
                      <option value="Aerox 155 Connected">Yamaha Aerox 155</option>
                      <option value="PCX 150/160 & ADV">Honda PCX 150 / 160 &amp; ADV</option>
                      <option value="Beat FI/ESP/Deluxe">Honda Beat / Scoopy / Genio</option>
                      <option value="Mio M3/Fazzio/Filano">Yamaha Mio M3 / Fazzio / Filano</option>
                      <option value="Lainnya">Model Lainnya</option>
                    </select>
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label class="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Kategori Konsultasi / Minat
                    </label>
                    <select
                      value={formData.category}
                      onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                      class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779] bg-white"
                    >
                      <option value="Konsultasi CVT Anti Gredek">Konsultasi CVT Anti Gredek</option>
                      <option value="Setting Akselerasi Harian">Setting Akselerasi Harian Hemat BBM</option>
                      <option value="Setting Touring & Tanjakan">Setting Touring &amp; Tanjakan Extreme</option>
                      <option value="Setting Bore-Up & Racing">Setting Balap / Bore-Up Sirkuit</option>
                      <option value="Pendaftaran Mitra Bengkel">Pendaftaran Mitra Bengkel / Grosir</option>
                    </select>
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                    Keluhan Motor atau Catatan Tambahan
                  </label>
                  <textarea
                    rows={3}
                    placeholder="Contoh: Tarikan awal bergetar saat pelan, butuh rekomendasi per CVT dan roller yang cocok..."
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                  />
                </div>

                <button
                  type="submit"
                  class="w-full inline-flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-sm font-bold shadow-md transition-all duration-200 mt-2"
                >
                  <span class="material-symbols-outlined text-[20px]">chat</span>
                  <span>Kirim Konsultasi ke WhatsApp CS</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
</template>
<script setup></script>