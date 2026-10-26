import { useState, FormEvent } from 'react';

interface BarcodeModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function BarcodeModal({ isOpen, onClose }: BarcodeModalProps) {
  const [code, setCode] = useState('');
  const [status, setStatus] = useState<'idle' | 'checking' | 'verified' | 'invalid'>('idle');
  const [verifiedItem, setVerifiedItem] = useState<{ name: string; batch: string; date: string } | null>(null);

  if (!isOpen) return null;

  const handleVerify = (e: FormEvent) => {
    e.preventDefault();
    if (!code.trim()) return;

    setStatus('checking');
    setTimeout(() => {
      // Simulate real verification check
      const cleaned = code.trim().toUpperCase();
      if (cleaned.startsWith('JGB') || cleaned.length >= 6) {
        setStatus('verified');
        setVerifiedItem({
          name: 'Jangeby Original Verified Component',
          batch: 'BATCH-2026-QC-' + Math.floor(1000 + Math.random() * 9000),
          date: 'QC Passed 2026 - Garansi Resmi Aktif',
        });
      } else {
        setStatus('invalid');
      }
    }, 800);
  };

  const sampleCodes = ['JGB-CVT-8891', 'JGB-ROLR-4421', 'JGB-CLUTCH-7703'];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-in fade-in duration-200">
      <div className="relative w-full max-w-md bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[20px] text-[#008779]">qr_code_scanner</span>
            <span className="font-tech-data text-xs uppercase font-bold text-slate-900 tracking-wider">
              Verifikasi Barcode &amp; Serial Number
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
        <div className="p-6 space-y-5">
          <div className="text-center space-y-1">
            <h3 className="font-headline-sm text-xl font-bold text-slate-900">
              Cek Keaslian Produk JANGEBY
            </h3>
            <p className="text-xs text-slate-600 leading-relaxed">
              Masukkan 8-12 digit kode unik barcode / serial number yang tertera pada kemasan kotak hologram produk Anda.
            </p>
          </div>

          <form onSubmit={handleVerify} className="space-y-3">
            <div className="relative">
              <input
                type="text"
                value={code}
                onChange={(e) => {
                  setCode(e.target.value);
                  setStatus('idle');
                }}
                placeholder="Contoh: JGB-CVT-8891"
                className="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-300 font-mono text-center text-sm font-bold tracking-wider uppercase focus:outline-none focus:border-[#008779]"
              />
              <span className="material-symbols-outlined absolute right-3 top-3.5 text-[20px] text-slate-400">
                barcode
              </span>
            </div>

            <button
              type="submit"
              disabled={status === 'checking'}
              className="w-full py-3 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs font-bold transition-colors shadow-sm disabled:opacity-50"
            >
              {status === 'checking' ? 'Memverifikasi...' : 'Verifikasi Keaslian Sekarang'}
            </button>
          </form>

          {/* Preset Samples */}
          <div className="flex items-center justify-center gap-1 text-[11px] text-slate-500">
            <span>Coba kode contoh:</span>
            {sampleCodes.map((s) => (
              <button
                key={s}
                onClick={() => {
                  setCode(s);
                  setStatus('idle');
                }}
                className="underline text-teal-700 hover:text-[#008779] font-mono mx-1"
              >
                {s}
              </button>
            ))}
          </div>

          {/* Result box */}
          {status === 'verified' && verifiedItem && (
            <div className="p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-950 space-y-2 animate-in zoom-in-95 duration-200">
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-teal-600 text-[24px]">verified</span>
                <span className="font-tech-data text-sm font-bold text-teal-800">
                  PRODUK 100% ASLI &amp; TERDAFTAR
                </span>
              </div>
              <div className="text-xs space-y-0.5 border-t border-teal-200/60 pt-2 font-mono">
                <div><span className="text-slate-500">Item:</span> {verifiedItem.name}</div>
                <div><span className="text-slate-500">Nomor Batch:</span> {verifiedItem.batch}</div>
                <div><span className="text-slate-500">Status:</span> {verifiedItem.date}</div>
              </div>
            </div>
          )}

          {status === 'invalid' && (
            <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 space-y-1 animate-in zoom-in-95 duration-200 text-xs">
              <div className="flex items-center gap-2 font-bold text-rose-700">
                <span className="material-symbols-outlined text-[20px]">warning</span>
                <span>Kode Barcode Tidak Ditemukan</span>
              </div>
              <p>
                Pastikan Anda memasukkan kode yang sesuai di kemasan hologram. Hubungi WhatsApp CS jika mendapati indikasi pemalsuan produk.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
