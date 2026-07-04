@php
    $data = $this->getChartData();
    $stats = $this->getStatsData();
@endphp

{{-- 3 grafieken naast elkaar --}}
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Openstaande offertes</p>
        <canvas id="chart-openstaand" style="max-height: 160px;"></canvas>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Offertes verstuurd</p>
        <canvas id="chart-verstuurd" style="max-height: 160px;"></canvas>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Gem. offerte waarde</p>
        <canvas id="chart-gemwaarde" style="max-height: 160px;"></canvas>
    </div>

</div>

{{-- Stats blokken --}}
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
    @foreach($stats as $stat)
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</span>
            @if(!empty($stat['icon']))
            <x-filament::icon :icon="$stat['icon']" style="width:1.25rem;height:1.25rem;color:{{ $stat['iconColor'] ?? '#6b7280' }}" />
            @endif
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
        @if(!empty($stat['description']))
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stat['description'] }}</p>
        @endif
    </div>
    @endforeach
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initCharts() {
        if (typeof Chart === 'undefined') { setTimeout(initCharts, 100); return; }

        const labels = @json($data['labels']);
        const opts = { plugins: { legend: { display: false } } };

        new Chart(document.getElementById('chart-openstaand'), {
            type: 'line',
            data: { labels, datasets: [{ data: @json($data['openstaand']), borderColor: 'rgba(59,130,246,1)', backgroundColor: 'rgba(59,130,246,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3 }] },
            options: { ...opts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('chart-verstuurd'), {
            type: 'bar',
            data: { labels, datasets: [{ data: @json($data['verstuurd']), backgroundColor: 'rgba(168,85,247,0.7)', borderRadius: 5 }] },
            options: { ...opts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('chart-gemwaarde'), {
            type: 'line',
            data: { labels, datasets: [{ data: @json($data['gemWaarde']), borderColor: 'rgba(34,197,94,1)', backgroundColor: 'rgba(34,197,94,0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3 }] },
            options: { ...opts, scales: { y: { beginAtZero: true, ticks: { callback: v => '€ ' + v.toLocaleString('nl-NL', { minimumFractionDigits: 0 }) } } } }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initCharts); } else { initCharts(); }
})();
</script>
