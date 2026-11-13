{{-- One Limo answer as a printable page (rendered to PDF by mpdf). Everything is escaped. --}}
@php
    $lang = $reply['lang'] ?? 'en';
    $cell = fn ($c) => is_array($c) ? ($c['text'] ?? '') : (string) $c;
@endphp
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 10.5pt; color: #1e293b; }
    .brand { color: #0f766e; font-size: 9pt; font-weight: bold; letter-spacing: .5pt; }
    .meta { color: #64748b; font-size: 8.5pt; }
    h1 { font-size: 15pt; margin: 4pt 0 2pt; color: #0f2a3d; }
    .rule { border-bottom: 1.5pt solid #14b8a6; margin: 6pt 0 12pt; }
    h3 { font-size: 9pt; color: #64748b; text-transform: uppercase; letter-spacing: .6pt; margin: 12pt 0 5pt; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 8pt; }
    th { background: #f1f5f9; color: #475569; font-size: 8.5pt; text-align: left; padding: 5pt 6pt; border-bottom: 1pt solid #e2e8f0; }
    td { padding: 5pt 6pt; border-bottom: .5pt solid #e5ebf1; font-size: 9.5pt; }
    .stats td { border: .5pt solid #e5ebf1; background: #f8fafc; width: 33%; vertical-align: top; }
    .stat-label { color: #64748b; font-size: 8pt; }
    .stat-value { font-size: 13pt; font-weight: bold; }
    .stat-sub { color: #94a3b8; font-size: 7.5pt; }
    .good { color: #059669; } .bad { color: #e0525a; } .warn { color: #d97706; } .info { color: #0284c7; }
    .note { background: #eaf6ff; padding: 6pt 8pt; color: #475569; font-size: 9pt; margin: 6pt 0; }
    .bar { background: #2dd4bf; height: 8pt; }
    ol, ul { margin: 0 0 6pt 14pt; padding: 0; } li { margin-bottom: 4pt; line-height: 1.5; }
    .foot { margin-top: 18pt; color: #94a3b8; font-size: 8pt; border-top: .5pt solid #e5ebf1; padding-top: 6pt; }
</style>
</head>
<body>
    <div class="brand">LIMO · LIBRARO AI</div>
    <div class="meta">{{ $branch }} · {{ $generatedAt }}</div>
    <h1>{{ $reply['title'] ?? '' }}</h1>
    <div class="rule"></div>

    @foreach (($reply['blocks'] ?? []) as $b)
        @switch($b['type'] ?? '')
            @case('text')
                <p>{{ $b['text'] }}</p>
                @break
            @case('note')
                <div class="note">{{ $b['text'] }}</div>
                @break
            @case('stats')
                <table class="stats">
                    @foreach (array_chunk($b['items'], 3) as $row)
                        <tr>
                            @foreach ($row as $s)
                                <td>
                                    <div class="stat-label">{{ $s['label'] }}</div>
                                    <div class="stat-value {{ $s['tone'] ?? '' }}">{{ $s['value'] }}</div>
                                    @if (!empty($s['sub']))<div class="stat-sub">{{ $s['sub'] }}</div>@endif
                                </td>
                            @endforeach
                            @for ($i = count($row); $i < 3; $i++)<td style="background:#fff;border:0"></td>@endfor
                        </tr>
                    @endforeach
                </table>
                @break
            @case('table')
                @if (!empty($b['caption']))<h3>{{ $b['caption'] }}</h3>@endif
                <table>
                    <tr>@foreach ($b['columns'] as $c)<th>{{ $c }}</th>@endforeach</tr>
                    @foreach ($b['rows'] as $r)
                        <tr>@foreach ($r as $c)<td class="{{ is_array($c) ? ($c['tone'] ?? '') : '' }}">{{ $cell($c) }}</td>@endforeach</tr>
                    @endforeach
                </table>
                @if (!empty($b['moreLabel']))<div class="meta">{{ $b['moreLabel'] }}</div>@endif
                @break
            @case('bars')
                <h3>{{ $b['caption'] }}</h3>
                @php($max = max(array_map(fn ($i) => $i['value'], $b['items'])) ?: 1)
                <table>
                    @foreach ($b['items'] as $i)
                        <tr>
                            <td style="width:22%">{{ $i['label'] }}</td>
                            @php($pct = $i['value'] > 0 ? max(2, round($i['value'] / $max * 100)) : 0)
                            <td style="width:58%">
                                <svg width="260" height="10" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="260" height="10" rx="3" fill="#f1f5f9"/>@if ($pct > 0)<rect x="0" y="0" width="{{ round($pct * 2.6) }}" height="10" rx="3" fill="#2dd4bf"/>@endif</svg>
                            </td>
                            <td style="width:20%; text-align:right">{{ $i['display'] }}</td>
                        </tr>
                    @endforeach
                </table>
                @break
            @case('chips')
                @if (!empty($b['caption']))<h3>{{ $b['caption'] }}</h3>@endif
                <p>{{ implode(', ', $b['items']) }} {{ $b['more'] ?? '' }}</p>
                @break
            @case('checks')
                @if (!empty($b['caption']))<h3>{{ $b['caption'] }}</h3>@endif
                @foreach ($b['items'] as $c)
                    <div class="note" style="background:{{ ['good' => '#e9f9f1', 'bad' => '#fff0f1', 'warn' => '#fff6e6'][$c['tone'] ?? ''] ?? '#eaf6ff' }}">{{ ['good' => '✓', 'bad' => '✗', 'warn' => '!'][$c['tone'] ?? ''] ?? '•' }} {{ $c['text'] }}</div>
                @endforeach
                @break
            @case('bullets')
                <ul>@foreach ($b['items'] as $i)<li>{{ $i }}</li>@endforeach</ul>
                @break
            @case('guide')
                @php($steps = !empty($b[$lang]) ? $b[$lang] : ($b['en'] ?: ($b['hi'] ?? [])))
                <ol>@foreach ($steps as $s)<li>{{ $s }}</li>@endforeach</ol>
                @break
        @endswitch
    @endforeach

    <div class="foot">Generated by Limo, the Libraro AI assistant.</div>
</body>
</html>
