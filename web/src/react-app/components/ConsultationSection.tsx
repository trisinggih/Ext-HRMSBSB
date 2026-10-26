import { useState, FormEvent } from 'react';
import { ConsultationFormData } from '../types';

interface ConsultationSectionProps {
  settings?: Record<string, string>;
}

const DEFAULTS = {
  consult_badge: 'TECHNICAL ADVISOR & CS',
  consult_title: 'Diskusikan Kebutuhan Kirian Motor Anda',
  consult_desc: 'Setiap motor dan pengendara memiliki karakter berbeda. Konsultasikan racikan per CVT, bobot roller, hingga mangkok ganda yang paling cocok untuk motor Anda bersama tim teknisi Jangeby.',
  consult_wa_label: 'WhatsApp CS & Konsultasi',
  consult_wa_number: '+62 857-7020-1010',
  consult_hours_label: 'Jam Operasional Teknisi',
  consult_hours_val: 'Senin - Sabtu: 08:30 - 17:30 WIB',
  consult_verify_label: 'Verifikasi Barcode Produk',
  consult_verify_val: 'Resmi Terdata Pabrikan',
  consult_form_badge: 'FORMULIR KONSULTASI CEPAT',
  consult_form_title: 'Kirim Data & Langsung Terhubung ke WhatsApp',
  consult_form_btn: 'Kirim Konsultasi ke WhatsApp CS',
};

export function ConsultationSection({ settings = {} }: ConsultationSectionProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];
  const [formData, setFormData] = useState<ConsultationFormData>({
    fullName: '',
    phone: '',
    motorType: 'Vario 125/150/160',
    category: 'Konsultasi CVT Anti Gredek',
    message: '',
  });

  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!formData.fullName.trim() || !formData.phone.trim()) {
      return;
    }
    setIsSubmitted(true);

    const text = encodeURIComponent(
      `Halo Jangeby Official,\nNama: ${formData.fullName}\nNo. WA: ${formData.phone}\nMotor: ${formData.motorType}\nKategori: ${formData.category}\nKeluhan/Pesan: ${formData.message || 'Mohon rekomendasi racikan kirian terbaik.'}`
    );
    window.open(`https://wa.me/6285770201010?text=${text}`, '_blank');
  };

  return (
    <section id="kontak" className="w-full py-16 sm:py-24 bg-slate-50 border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          {/* Info Side */}
          <div className="lg:col-span-5 flex flex-col gap-6">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 w-fit">
              <span className="material-symbols-outlined text-[16px] text-[#008779]">support_agent</span>
              <span className="font-label-mono-sm text-[11px] text-[#008779] tracking-widest uppercase font-bold">
                {s('consult_badge')}
              </span>
            </div>

            <div className="flex flex-col gap-2">
              <h2 className="font-headline-lg text-slate-900 uppercase tracking-tight font-extrabold text-3xl sm:text-4xl">
                {s('consult_title')}
              </h2>
              <p className="font-body-md text-slate-600 text-sm sm:text-base leading-relaxed">
                {s('consult_desc')}
              </p>
            </div>

            {/* Contact details */}
            <div className="space-y-4 pt-2">
              <div className="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div className="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <img src="/whatsapp.svg" alt="WhatsApp" className="w-[22px] h-[22px]" />
                </div>
                <div className="flex flex-col">
                  <span className="font-label-mono-sm text-[10px] text-slate-400 uppercase">{s('consult_wa_label')}</span>
                  <span className="font-tech-data text-slate-900 text-sm font-bold">{s('consult_wa_number')}</span>
                </div>
              </div>

              <div className="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div className="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <span className="material-symbols-outlined text-[22px]">schedule</span>
                </div>
                <div className="flex flex-col">
                  <span className="font-label-mono-sm text-[10px] text-slate-400 uppercase">{s('consult_hours_label')}</span>
                  <span className="font-tech-data text-slate-900 text-sm font-bold">{s('consult_hours_val')}</span>
                </div>
              </div>

              <div className="flex items-center gap-3 p-4 rounded-xl bg-white border border-slate-200">
                <div className="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-[#008779]">
                  <span className="material-symbols-outlined text-[22px]">verified_user</span>
                </div>
                <div className="flex flex-col">
                  <span className="font-label-mono-sm text-[10px] text-slate-400 uppercase">{s('consult_verify_label')}</span>
                  <span className="font-tech-data text-slate-900 text-sm font-bold">{s('consult_verify_val')}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Form Side */}
          <div className="lg:col-span-7">
            <div className="p-8 sm:p-10 rounded-3xl bg-white border border-slate-200 shadow-xl">
              <div className="mb-6 pb-4 border-b border-slate-100">
                <span className="font-label-mono-sm text-[11px] text-[#008779] uppercase font-bold tracking-wider">
                  {s('consult_form_badge')}
                </span>
                <h3 className="font-headline-sm text-xl sm:text-2xl text-slate-900 font-bold mt-1">
                  {s('consult_form_title')}
                </h3>
              </div>

              {isSubmitted && (
                <div className="mb-6 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-900 text-sm flex items-center gap-3">
                  <span className="material-symbols-outlined text-[24px] text-[#008779]">check_circle</span>
                  <div>
                    <strong className="block font-tech-data">Formulir Terkirim!</strong>
                    <span>WhatsApp telah terbuka secara otomatis untuk memulai obrolan dengan CS kami.</span>
                  </div>
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="flex flex-col gap-1.5">
                    <label className="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Nama Lengkap *
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="Contoh: Budi Pratama"
                      value={formData.fullName}
                      onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                    />
                  </div>

                  <div className="flex flex-col gap-1.5">
                    <label className="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Nomor WhatsApp *
                    </label>
                    <input
                      type="tel"
                      required
                      placeholder="Contoh: 08123456789"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="flex flex-col gap-1.5">
                    <label className="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Model / Tipe Motor
                    </label>
                    <select
                      value={formData.motorType}
                      onChange={(e) => setFormData({ ...formData, motorType: e.target.value })}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779] bg-white"
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

                  <div className="flex flex-col gap-1.5">
                    <label className="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                      Kategori Konsultasi / Minat
                    </label>
                    <select
                      value={formData.category}
                      onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                      className="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779] bg-white"
                    >
                      <option value="Konsultasi CVT Anti Gredek">Konsultasi CVT Anti Gredek</option>
                      <option value="Setting Akselerasi Harian">Setting Akselerasi Harian Hemat BBM</option>
                      <option value="Setting Touring & Tanjakan">Setting Touring &amp; Tanjakan Extreme</option>
                      <option value="Setting Bore-Up & Racing">Setting Balap / Bore-Up Sirkuit</option>
                      <option value="Pendaftaran Mitra Bengkel">Pendaftaran Mitra Bengkel / Grosir</option>
                    </select>
                  </div>
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="font-tech-data text-xs uppercase text-slate-700 font-semibold">
                    Keluhan Motor atau Catatan Tambahan
                  </label>
                  <textarea
                    rows={3}
                    placeholder="Contoh: Tarikan awal bergetar saat pelan, butuh rekomendasi per CVT dan roller yang cocok..."
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    className="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                  />
                </div>

                <button
                  type="submit"
                  className="w-full inline-flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-sm font-bold shadow-md transition-all duration-200 mt-2"
                >
                  <span className="material-symbols-outlined text-[20px]">chat</span>
                  <span>{s('consult_form_btn')}</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
