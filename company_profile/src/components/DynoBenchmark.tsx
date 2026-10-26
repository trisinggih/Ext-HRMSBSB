import { useState } from 'react';

export function DynoBenchmark() {
  const [activeRpm, setActiveRpm] = useState<number>(6500);
  const [selectedPreset, setSelectedPreset] = useState<'harian' | 'touring' | 'racing'>('harian');

  // Calculation models based on motor transmission dynamics
  const getMetrics = (rpm: number) => {
    // Normalization factor (3000 to 9500)
    const normalized = (rpm - 3000) / 6500;
    
    // OEM power & torque
    const oemHp = (7.5 + 4.8 * Math.sin(normalized * Math.PI)).toFixed(1);
    const oemTorque = (9.2 + 3.1 * Math.sin(normalized * 0.8 * Math.PI)).toFixed(1);

    // Jangeby Enhanced power & torque (+15% to 22% low-mid end boost)
    const jangebyHp = (8.8 + 5.9 * Math.sin(normalized * Math.PI)).toFixed(1);
    const jangebyTorque = (11.4 + 3.8 * Math.sin(normalized * 0.8 * Math.PI)).toFixed(1);

    const hpDiff = (((parseFloat(jangebyHp) - parseFloat(oemHp)) / parseFloat(oemHp)) * 100).toFixed(0);
    const torqueDiff = (((parseFloat(jangebyTorque) - parseFloat(oemTorque)) / parseFloat(oemTorque)) * 100).toFixed(0);

    return { oemHp, oemTorque, jangebyHp, jangebyTorque, hpDiff, torqueDiff };
  };

  const metrics = getMetrics(activeRpm);

  return (
    <section id="dyno-test" className="w-full py-16 sm:py-24 bg-slate-900 text-white relative overflow-hidden border-b border-slate-800">
      {/* Background Gradients */}
      <div className="absolute top-0 right-0 w-96 h-96 bg-teal-500/10 blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-96 h-96 bg-cyan-500/10 blur-3xl pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div className="flex flex-col gap-2 max-w-2xl">
            <span className="font-label-mono-sm text-[11px] text-teal-400 uppercase tracking-widest font-bold">
              DYNO ACCELERATION SIMULATOR &amp; TELEMETRY
            </span>
            <h2 className="font-headline-lg uppercase tracking-tight font-extrabold text-3xl sm:text-4xl text-white">
              Uji Dinamika &amp; Kurva Daya CVT
            </h2>
            <p className="font-body-md text-slate-400 text-sm sm:text-base leading-relaxed">
              Bandingkan respons tenaga (Horsepower) dan torsi kirian transmisi Jangeby Racing Performance berbanding suku cadang standar pabrikan OEM secara interaktif.
            </p>
          </div>

          {/* Preset Buttons */}
          <div className="flex items-center gap-2 bg-slate-800/80 p-1.5 rounded-full border border-slate-700">
            <button
              type="button"
              onClick={() => { setSelectedPreset('harian'); setActiveRpm(5500); }}
              className={`px-3.5 py-1.5 rounded-full text-xs font-tech-data uppercase transition-all ${
                selectedPreset === 'harian'
                  ? 'bg-[#008779] text-white shadow-xs font-bold'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Harian 5.5K
            </button>
            <button
              type="button"
              onClick={() => { setSelectedPreset('touring'); setActiveRpm(7000); }}
              className={`px-3.5 py-1.5 rounded-full text-xs font-tech-data uppercase transition-all ${
                selectedPreset === 'touring'
                  ? 'bg-[#008779] text-white shadow-xs font-bold'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Touring 7K
            </button>
            <button
              type="button"
              onClick={() => { setSelectedPreset('racing'); setActiveRpm(8800); }}
              className={`px-3.5 py-1.5 rounded-full text-xs font-tech-data uppercase transition-all ${
                selectedPreset === 'racing'
                  ? 'bg-[#008779] text-white shadow-xs font-bold'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Sprint 8.8K
            </button>
          </div>
        </div>

        {/* Interactive Benchmark Console */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          {/* Controls & Metrics */}
          <div className="lg:col-span-5 flex flex-col gap-6">
            {/* RPM Slider Card */}
            <div className="p-6 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-xl space-y-4">
              <div className="flex items-center justify-between">
                <span className="font-label-mono-sm text-xs text-slate-400 uppercase font-semibold">
                  Putaran Mesin (RPM)
                </span>
                <span className="font-tech-data text-xl text-teal-300 font-bold">
                  {activeRpm.toLocaleString('id-ID')} RPM
                </span>
              </div>
              <input
                type="range"
                min={3000}
                max={9500}
                step={250}
                value={activeRpm}
                onChange={(e) => setActiveRpm(Number(e.target.value))}
                className="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-[#008779]"
              />
              <div className="flex justify-between text-[10px] font-mono text-slate-500">
                <span>3.000 (Idle)</span>
                <span>6.500 (Cruising)</span>
                <span>9.500 (Top Peak)</span>
              </div>
            </div>

            {/* Metric Comparison Cards */}
            <div className="grid grid-cols-2 gap-4">
              {/* HP Card */}
              <div className="p-5 rounded-2xl bg-slate-800/90 border border-slate-700/80 flex flex-col justify-between gap-3">
                <span className="font-label-mono-sm text-xs text-slate-400 uppercase font-semibold">
                  Tenaga (HP)
                </span>
                <div className="flex items-baseline justify-between">
                  <div>
                    <span className="font-tech-data text-3xl font-extrabold text-teal-300">
                      {metrics.jangebyHp}
                    </span>
                    <span className="text-xs text-slate-400 ml-1">HP</span>
                  </div>
                  <span className="text-xs font-mono text-emerald-400 font-bold">
                    +{metrics.hpDiff}%
                  </span>
                </div>
                <div className="pt-2 border-t border-slate-700/60 flex justify-between text-xs text-slate-400">
                  <span>Standar OEM:</span>
                  <span className="font-mono text-slate-300">{metrics.oemHp} HP</span>
                </div>
              </div>

              {/* Torque Card */}
              <div className="p-5 rounded-2xl bg-slate-800/90 border border-slate-700/80 flex flex-col justify-between gap-3">
                <span className="font-label-mono-sm text-xs text-slate-400 uppercase font-semibold">
                  Torsi (Nm)
                </span>
                <div className="flex items-baseline justify-between">
                  <div>
                    <span className="font-tech-data text-3xl font-extrabold text-cyan-300">
                      {metrics.jangebyTorque}
                    </span>
                    <span className="text-xs text-slate-400 ml-1">Nm</span>
                  </div>
                  <span className="text-xs font-mono text-emerald-400 font-bold">
                    +{metrics.torqueDiff}%
                  </span>
                </div>
                <div className="pt-2 border-t border-slate-700/60 flex justify-between text-xs text-slate-400">
                  <span>Standar OEM:</span>
                  <span className="font-mono text-slate-300">{metrics.oemTorque} Nm</span>
                </div>
              </div>
            </div>

            {/* Benefit Checkers */}
            <div className="p-4 rounded-xl bg-teal-950/40 border border-teal-800/40 space-y-2">
              <div className="flex items-center gap-2 text-xs text-teal-200">
                <span className="material-symbols-outlined text-[18px] text-teal-400">check_circle</span>
                <span><strong>Anti Gredek 100%:</strong> Mangkok kartel/milling membuang kotoran kampas secara otomatis.</span>
              </div>
              <div className="flex items-center gap-2 text-xs text-teal-200">
                <span className="material-symbols-outlined text-[18px] text-teal-400">check_circle</span>
                <span><strong>Nafas Panjang:</strong> Per CVT SWOCS-VH tidak kendur saat temperatur pulley mencapai 150°C.</span>
              </div>
            </div>
          </div>

          {/* Dyno Curve Visualizer */}
          <div className="lg:col-span-7 bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
            <div className="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-4">
              <div className="flex items-center gap-2">
                <span className="w-2.5 h-2.5 rounded-full bg-teal-400" />
                <span className="font-tech-data text-xs text-slate-200 uppercase font-semibold">
                  Jangeby Performance Setup
                </span>
                <span className="w-2.5 h-2.5 rounded-full bg-slate-500 ml-3" />
                <span className="font-tech-data text-xs text-slate-400 uppercase">
                  Pabrikan Standar OEM
                </span>
              </div>
              <span className="font-label-mono-sm text-xs text-slate-400">
                POWER CURVE (RPM vs HP)
              </span>
            </div>

            {/* SVG Visual Graph */}
            <div className="relative w-full h-64 sm:h-72">
              <svg className="w-full h-full" viewBox="0 0 500 240" fill="none">
                {/* Horizontal Grid lines */}
                <line x1="40" y1="30" x2="480" y2="30" stroke="#334155" strokeDasharray="3 3" />
                <line x1="40" y1="80" x2="480" y2="80" stroke="#334155" strokeDasharray="3 3" />
                <line x1="40" y1="130" x2="480" y2="130" stroke="#334155" strokeDasharray="3 3" />
                <line x1="40" y1="180" x2="480" y2="180" stroke="#334155" strokeDasharray="3 3" />
                <line x1="40" y1="210" x2="480" y2="210" stroke="#475569" strokeWidth="1.5" />

                {/* Y-axis labels */}
                <text x="15" y="34" fill="#94a3b8" fontSize="10" fontFamily="Space Grotesk">16 HP</text>
                <text x="15" y="84" fill="#94a3b8" fontSize="10" fontFamily="Space Grotesk">12 HP</text>
                <text x="15" y="134" fill="#94a3b8" fontSize="10" fontFamily="Space Grotesk">8 HP</text>
                <text x="15" y="184" fill="#94a3b8" fontSize="10" fontFamily="Space Grotesk">4 HP</text>

                {/* OEM Curve (gray) */}
                <path
                  d="M 50 195 Q 150 160 250 115 T 450 100"
                  stroke="#64748b"
                  strokeWidth="3"
                  fill="none"
                />

                {/* Jangeby Curve (Teal glow + line) */}
                <path
                  d="M 50 180 Q 150 130 250 80 T 450 60"
                  stroke="#008779"
                  strokeWidth="4"
                  fill="none"
                />

                {/* Current Active RPM Marker */}
                {(() => {
                  const markerX = 50 + ((activeRpm - 3000) / 6500) * 400;
                  return (
                    <g>
                      <line
                        x1={markerX}
                        y1="20"
                        x2={markerX}
                        y2="210"
                        stroke="#2ee5c5"
                        strokeWidth="1.5"
                        strokeDasharray="4 4"
                      />
                      <circle cx={markerX} cy="105" r="5" fill="#2ee5c5" />
                      <circle cx={markerX} cy="105" r="9" stroke="#2ee5c5" strokeWidth="1.5" fill="none" opacity="0.5" />
                    </g>
                  );
                })()}
              </svg>
            </div>

            {/* Dynamic Comparison Summary Footer */}
            <div className="mt-4 pt-3 border-t border-slate-700/60 grid grid-cols-2 sm:grid-cols-3 gap-3 text-center">
              <div className="p-2 rounded-lg bg-slate-900/60">
                <span className="block font-label-mono-sm text-[10px] text-slate-400">Akselerasi 0-60 km/h</span>
                <span className="font-tech-data text-sm font-bold text-teal-300">4.8 Detik <span className="text-[10px] text-emerald-400">(-21%)</span></span>
              </div>
              <div className="p-2 rounded-lg bg-slate-900/60">
                <span className="block font-label-mono-sm text-[10px] text-slate-400">Getaran CVT (Gredek)</span>
                <span className="font-tech-data text-sm font-bold text-cyan-300">0.2 / 10 <span className="text-[10px] text-emerald-400">(Bebas Getar)</span></span>
              </div>
              <div className="p-2 rounded-lg bg-slate-900/60 col-span-2 sm:col-span-1">
                <span className="block font-label-mono-sm text-[10px] text-slate-400">Suhu Kerja Pulley</span>
                <span className="font-tech-data text-sm font-bold text-amber-300">Stabil &lt;85°C</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
