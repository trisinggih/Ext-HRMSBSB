interface TopTickerProps {
  settings?: Record<string, string>;
}

const DEFAULTS = {
  ticker_text1: 'OFFICIAL HQ & DISTRIBUTION',
  ticker_text2: 'CNC HIGH-PRECISION CVT SYSTEM',
  ticker_text3: 'ISO 9001 MACHINING BENCHMARK',
  ticker_text4: '100% ORIGINAL JANGEBY PERFORMANCE',
};

export function TopTicker({ settings = {} }: TopTickerProps) {
  const s = (key: keyof typeof DEFAULTS) => settings[key] ?? DEFAULTS[key];

  return (
    <section id="telemetry-ticker" className="w-full bg-slate-100 text-[#008779] py-2 px-4 sm:px-6 lg:px-8 border-b border-slate-200">
      <div className="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 font-label-mono-sm text-[11px] uppercase tracking-wider">
        <div className="flex items-center gap-3">
          <span className="inline-flex items-center gap-1.5 text-[#008779] font-bold">
            <span className="w-2 h-2 rounded-full bg-[#008779] animate-pulse" />
            {s('ticker_text1')}
          </span>
          <span className="text-slate-300">/</span>
          <span className="text-slate-600 font-semibold">{s('ticker_text2')}</span>
        </div>
        <div className="hidden sm:flex items-center gap-6 text-slate-600">
          <span>{s('ticker_text3')}</span>
          <span className="text-[#008779] font-bold">{s('ticker_text4')}</span>
        </div>
      </div>
    </section>
  );
}
