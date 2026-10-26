import { useState, FormEvent } from 'react';

interface PartnerModalProps {
  isOpen: boolean;
  onClose: () => void;
  settings?: Record<string, string>;
}

const DEFAULTS = {
  partner_modal_title: 'Pendaftaran Mitra Bengkel Resmi',
  partner_modal_heading: 'Bergabung Bersama Jaringan Jangeby',
  partner_modal_desc: 'Isi data bengkel Anda untuk mendapatkan paket tester display suku cadang, sertifikat mitra resmi, dan harga grosir tingkat distributor.',
  partner_modal_success: 'Data terkirim! Tim kemitraan kami akan segera menghubungi Anda di WhatsApp.',
  partner_modal_shop_label: 'Nama Bengkel / Toko *',
  partner_modal_shop_placeholder: 'Contoh: Maju Jaya Motor Sport',
  partner_modal_owner_label: 'Nama Pemilik',
  partner_modal_owner_placeholder: 'Contoh: Anton',
  partner_modal_city_label: 'Kota & Provinsi',
  partner_modal_city_placeholder: 'Contoh: Semarang, Jateng',
  partner_modal_phone_label: 'Nomor WhatsApp Aktif *',
  partner_modal_phone_placeholder: 'Contoh: 08123456789',
  partner_modal_btn: 'Kirim Formulir Mitra ke Tim Sales',
};

export function PartnerModal({ isOpen, onClose, settings = {} }: PartnerModalProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];
  const [bengkelName, setBengkelName] = useState('');
  const [ownerName, setOwnerName] = useState('');
  const [city, setCity] = useState('');
  const [phone, setPhone] = useState('');
  const [submitted, setSubmitted] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!bengkelName || !phone) return;
    setSubmitted(true);
    const msg = encodeURIComponent(
      `Halo Tim Kemitraan Jangeby (mitrajangeby.com),\nNama Bengkel: ${bengkelName}\nPemilik: ${ownerName}\nKota/Provinsi: ${city}\nNo. WhatsApp: ${phone}\nSaya berminat menjadi mitra resmi / distributor produk Jangeby.`
    );
    window.open(`https://wa.me/6285770201010?text=${msg}`, '_blank');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-in fade-in duration-200">
      <div className="relative w-full max-w-lg bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[20px] text-[#008779]">handshake</span>
            <span className="font-tech-data text-xs uppercase font-bold text-slate-900 tracking-wider">
              {s('partner_modal_title')}
            </span>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-4">
          <div>
            <h3 className="font-headline-sm text-xl font-bold text-slate-900">
              {s('partner_modal_heading')}
            </h3>
            <p className="text-xs text-slate-600 mt-1">
              {s('partner_modal_desc')}
            </p>
          </div>

          {submitted && (
            <div className="p-3.5 rounded-xl bg-teal-50 border border-teal-200 text-xs text-teal-900">
              {s('partner_modal_success')}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-3">
            <div>
              <label className="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                {s('partner_modal_shop_label')}
              </label>
              <input
                type="text"
                required
                placeholder={s('partner_modal_shop_placeholder')}
                value={bengkelName}
                onChange={(e) => setBengkelName(e.target.value)}
                className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                  {s('partner_modal_owner_label')}
                </label>
                <input
                  type="text"
                  placeholder={s('partner_modal_owner_placeholder')}
                  value={ownerName}
                  onChange={(e) => setOwnerName(e.target.value)}
                  className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                />
              </div>
              <div>
                <label className="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                  {s('partner_modal_city_label')}
                </label>
                <input
                  type="text"
                  placeholder={s('partner_modal_city_placeholder')}
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                {s('partner_modal_phone_label')}
              </label>
              <input
                type="tel"
                required
                placeholder={s('partner_modal_phone_placeholder')}
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
              />
            </div>

            <button
              type="submit"
              className="w-full py-3 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs font-bold transition-colors shadow-sm mt-2"
            >
              {s('partner_modal_btn')}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
