<template>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="relative w-full max-w-lg bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden flex flex-col">
    {{ /* Header */ }}
  <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-[#008779]">handshake</span>
            <span class="font-tech-data text-xs uppercase font-bold text-slate-900 tracking-wider">
              Pendaftaran Mitra Bengkel Resmi
            </span>
          </div>
          <button
            @click="onClose"
            class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors"
          >
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>
    {{ /* Content */ }}
  <div class="p-6 space-y-4">
          <div>
            <h3 class="font-headline-sm text-xl font-bold text-slate-900">
              Bergabung Bersama Jaringan Jangeby
            </h3>
            <p class="text-xs text-slate-600 mt-1">
              Isi data bengkel Anda untuk mendapatkan paket tester display suku cadang, sertifikat mitra resmi, dan harga grosir tingkat distributor.
            </p>
          </div>
    {{ submitted && (
            <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-200 text-xs text-teal-900">
              Data terkirim! Tim kemitraan kami akan segera menghubungi Anda di WhatsApp.
            </div>
          ) }}
  <form onSubmit={handleSubmit} class="space-y-3">
            <div>
              <label class="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                Nama Bengkel / Toko *
              </label>
              <input
                type="text"
                required
                placeholder="Contoh: Maju Jaya Motor Sport"
                value={bengkelName}
                onChange={(e) => setBengkelName(e.target.value)}
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                  Nama Pemilik
                </label>
                <input
                  type="text"
                  placeholder="Contoh: Anton"
                  value={ownerName}
                  onChange={(e) => setOwnerName(e.target.value)}
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                />
              </div>
              <div>
                <label class="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                  Kota &amp; Provinsi
                </label>
                <input
                  type="text"
                  placeholder="Contoh: Semarang, Jateng"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-tech-data uppercase text-slate-700 font-semibold mb-1">
                Nomor WhatsApp Aktif *
              </label>
              <input
                type="tel"
                required
                placeholder="Contoh: 08123456789"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:border-[#008779]"
              />
            </div>

            <button
              type="submit"
              class="w-full py-3 rounded-xl bg-[#008779] hover:bg-[#007468] text-white font-tech-data text-xs font-bold transition-colors shadow-sm mt-2"
            >
              Kirim Formulir Mitra ke Tim Sales
            </button>
          </form>
        </div>
      </div>
    </div>
</template>
<script setup></script>