@php
    $chartId = 'cal-heatmap-' . $this->getId();
    $legendId = 'cal-legend-' . $this->getId();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            #{{ $chartId }} {
                display: flex;
                justify-content: center;
                margin: 10px 0;
            }

            #{{ $chartId }}-card {
                position: relative;
                width: 100%;
                border: 1px solid #d0d7de;
                border-radius: 8px;
                background: #ffffff;
                padding: 24px;
                overflow-x: auto;
            }

            #{{ $chartId }}-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                gap: 14px;
            }

            .{{ $chartId }}-btn {
                background-color: #f6f8fa;
                border: 1px solid #d0d7de;
                color: #24292f;
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 600;
                border-radius: 6px;
                cursor: pointer;
            }

            .{{ $chartId }}-btn:hover {
                background-color: #f3f4f6;
                border-color: #c6cbd1;
            }

            #{{ $chartId }}-footer {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-top: 24px;
                font-size: 13px;
                color: #57606a;
                gap: 10px;
            }

            #{{ $legendId }} {
                margin: 0 8px;
            }

            #{{ $chartId }} .ch-subdomain-cell {
                rx: 6;
                ry: 6;
            }

            #{{ $chartId }}-tooltip {
                position: fixed;
                z-index: 99999;
                display: none;
                pointer-events: none;
                background: rgba(17, 24, 39, 0.95);
                color: #fff;
                font-size: 12px;
                line-height: 1.3;
                padding: 6px 10px;
                border-radius: 6px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
                white-space: nowrap;
            }
        </style>

        <div
            id="{{ $chartId }}-card"
            x-data="{
                chartId: @js($chartId),
                legendId: @js($legendId),
                sourceData: @js($calendarData),
                startDate: @js($startIso),
                renderToken: 0,
                async init() {
                    await this.ensureLibraries();
                    await this.paint();
                },
                async ensureLibraries() {
                    if (!window.__calHeatmapLoader) {
                        window.__calHeatmapLoader = (async () => {
                            const appendScript = (src) => new Promise((resolve, reject) => {
                                if (document.querySelector(`script[src='${src}']`)) {
                                    resolve();
                                    return;
                                }
                                const s = document.createElement('script');
                                s.src = src;
                                s.async = true;
                                s.onload = () => resolve();
                                s.onerror = () => reject(new Error('Failed to load ' + src));
                                document.head.appendChild(s);
                            });
                            const appendCss = (href) => {
                                if (document.querySelector(`link[href='${href}']`)) return;
                                const l = document.createElement('link');
                                l.rel = 'stylesheet';
                                l.href = href;
                                document.head.appendChild(l);
                            };

                            appendCss('/vendor/cal-heatmap/cal-heatmap.css');
                            await appendScript('/vendor/d3/d3.min.js');
                            await appendScript('/vendor/cal-heatmap/cal-heatmap.min.js');
                            await appendScript('/vendor/popper/popper.min.js');
                            await appendScript('/vendor/cal-heatmap/plugins/Legend.min.js');
                        })();
                    }
                    return window.__calHeatmapLoader;
                },
                async paint() {
                    const chartEl = document.getElementById(this.chartId);
                    if (!chartEl) return;
                    const legendEl = document.getElementById(this.legendId);
                    const token = ++this.renderToken;
                    const chartMountId = `${this.chartId}-mount-${token}`;
                    const legendMountId = `${this.legendId}-mount-${token}`;

                    window.__issueHeatmapInstances = window.__issueHeatmapInstances || {};
                    if (window.__issueHeatmapInstances[this.chartId]) {
                        try { window.__issueHeatmapInstances[this.chartId].destroy(); } catch (_) {}
                        delete window.__issueHeatmapInstances[this.chartId];
                    }
                    chartEl.innerHTML = `<div id='${chartMountId}'></div>`;
                    if (legendEl) legendEl.innerHTML = `<div id='${legendMountId}'></div>`;

                    if (typeof window.CalHeatmap === 'undefined') {
                        chartEl.innerHTML = '<div style=&quot;font-size:12px;color:#6b7280;&quot;>Cal-Heatmap gagal dimuat.</div>';
                        return;
                    }

                    const cal = new window.CalHeatmap();
                    const LegendPlugin = window.Legend;
                    window.__issueHeatmapInstances[this.chartId] = cal;

                    const plugins = [];
                    if (LegendPlugin) {
                        plugins.push([
                            LegendPlugin,
                            {
                                itemSelector: `#${legendMountId}`,
                                width: 220,
                                tickSize: 6,
                            },
                        ]);
                    }

                    await cal.paint(
                        {
                            itemSelector: `#${chartMountId}`,
                            date: { start: new Date(this.startDate) },
                            range: 1,
                            animationDuration: 400,
                            domain: {
                                type: 'month',
                                gutter: 20,
                                label: { text: 'MMMM YYYY', textAlign: 'center', position: 'top' },
                            },
                            subDomain: {
                                type: 'xDay',
                                radius: 6,
                                width: 40,
                                height: 40,
                                gutter: 6,
                            },
                            data: {
                                source: this.sourceData,
                                x: 'date',
                                y: 'value',
                            },
                            scale: {
                                color: {
                                    type: 'threshold',
                                    range: ['#ebedf0', '#9be9a8', '#40c463', '#30a14e', '#216e39'],
                                    domain: [10, 20, 30, 40],
                                },
                            },
                        },
                        plugins
                    );

                    // Prevent stale async paints from creating duplicate month domains.
                    if (token !== this.renderToken) {
                        try { cal.destroy(); } catch (_) {}
                        const staleMount = document.getElementById(chartMountId);
                        if (staleMount) staleMount.remove();
                        const staleLegendMount = document.getElementById(legendMountId);
                        if (staleLegendMount) staleLegendMount.remove();
                        return;
                    }

                    // Ensure only the latest mount remains visible.
                    chartEl.querySelectorAll(`[id^='${this.chartId}-mount-']`).forEach((node) => {
                        if (node.id !== chartMountId) node.remove();
                    });
                    if (legendEl) {
                        legendEl.querySelectorAll(`[id^='${this.legendId}-mount-']`).forEach((node) => {
                            if (node.id !== legendMountId) node.remove();
                        });
                    }

                    this.bindTooltip(chartEl);
                },
                bindTooltip(chartEl) {
                    const tooltipEl = document.getElementById(`${this.chartId}-tooltip`);
                    if (!tooltipEl) return;

                    const valueByDate = (this.sourceData || []).reduce((acc, item) => {
                        if (item?.date) acc[item.date] = Number(item.value ?? 0);
                        return acc;
                    }, {});

                    const toIsoDate = (raw) => {
                        if (raw === undefined || raw === null) return null;
                        const date = new Date(typeof raw === 'number' ? raw : String(raw));
                        if (Number.isNaN(date.getTime())) return null;
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const d = String(date.getDate()).padStart(2, '0');
                        return `${y}-${m}-${d}`;
                    };

                    const cells = chartEl.querySelectorAll('rect');
                    cells.forEach((rect) => {
                        if (rect.dataset.tooltipBound === '1') return;

                        const datum = rect.__data__
                            ?? rect.parentNode?.__data__
                            ?? rect.parentNode?.parentNode?.__data__
                            ?? {};
                        const rawDate = datum.t ?? datum.x ?? datum.date;
                        const isoDate = toIsoDate(rawDate);
                        if (!isoDate) return;

                        const value = Number(valueByDate[isoDate] ?? 0);
                        const dateLabel = new Date(`${isoDate}T00:00:00`).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                        });
                        const label = `${value} aktivitas pada ${dateLabel}`;

                        rect.addEventListener('mouseenter', () => {
                            tooltipEl.textContent = label;
                            tooltipEl.style.display = 'block';
                        });

                        rect.addEventListener('mousemove', (event) => {
                            tooltipEl.style.left = `${event.clientX + 12}px`;
                            tooltipEl.style.top = `${event.clientY + 12}px`;
                        });

                        rect.addEventListener('mouseleave', () => {
                            tooltipEl.style.display = 'none';
                        });

                        rect.dataset.tooltipBound = '1';
                    });
                },
                rebindTooltipAfterNavigation() {
                    const chartEl = document.getElementById(this.chartId);
                    if (!chartEl) return;

                    // Bind once immediately, and once after animation completes.
                    requestAnimationFrame(() => this.bindTooltip(chartEl));
                    setTimeout(() => this.bindTooltip(chartEl), 450);
                },
                async previous() {
                    const cal = window.__issueHeatmapInstances?.[this.chartId];
                    if (!cal) return;
                    await cal.previous();
                    this.rebindTooltipAfterNavigation();
                },
                async next() {
                    const cal = window.__issueHeatmapInstances?.[this.chartId];
                    if (!cal) return;
                    await cal.next();
                    this.rebindTooltipAfterNavigation();
                },
            }"
            x-init="init()"
        >
            <div id="{{ $chartId }}-header">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                    <p class="text-sm text-gray-500">{{ number_format($totalTasks) }} task terjadwal ({{ $startLabel }} - {{ $endLabel }})</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="{{ $chartId }}-btn" @click="previous">&larr; Sebelumnya</button>
                    <button type="button" class="{{ $chartId }}-btn" @click="next">Berikutnya &rarr;</button>
                </div>
            </div>

            <div id="{{ $chartId }}"></div>

            <div id="{{ $chartId }}-footer">
                <span>Kurang</span>
                <div id="{{ $legendId }}"></div>
                <span>Lebih</span>
            </div>

            <div id="{{ $chartId }}-tooltip"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
