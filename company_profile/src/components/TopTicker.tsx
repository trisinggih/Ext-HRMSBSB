export function TopTicker() {
  return (
    <section id="telemetry-ticker" className="w-full bg-slate-100 text-[#008779] py-2 px-4 sm:px-6 lg:px-8 border-b border-slate-200">
      <div className="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 font-label-mono-sm text-[11px] uppercase tracking-wider">
        <div className="flex items-center gap-3">
          <span className="inline-flex items-center gap-1.5 text-[#008779] font-bold">
            <span className="w-2 h-2 rounded-full bg-[#008779] animate-pulse" />
            OFFICIAL HQ &amp; DISTRIBUTION
          </span>
          <span className="text-slate-300">/</span>
          <span className="text-slate-600 font-semibold">CNC HIGH-PRECISION CVT SYSTEM</span>
        </div>
        <div className="hidden sm:flex items-center gap-6 text-slate-600">
          <span>ISO 9001 MACHINING BENCHMARK</span>
          <span className="text-[#008779] font-bold">100% ORIGINAL JANGEBY PERFORMANCE</span>
        </div>
      </div>
    </section>
  );
}
