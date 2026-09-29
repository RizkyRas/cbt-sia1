<x-filament-panels::page>
    <x-filament::section>
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: center;">
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Siswa</div>
                <div style="font-weight: 700; font-size: 1.125rem;">{{ $summary['student'] }}</div>
                <div style="font-size: 0.75rem; opacity: 0.7;">NIS: {{ $summary['nis'] }}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Ujian</div>
                <div style="font-weight: 700; font-size: 1.125rem;">{{ $summary['exam'] }}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Nilai</div>
                <div style="font-weight: 700; font-size: 1.5rem;">{{ $summary['score'] }}</div>
                <div style="font-size: 0.75rem; opacity: 0.7;">Batas lulus: {{ $summary['threshold'] }}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Status</div>
                <x-filament::badge :color="$summary['is_passed'] ? 'success' : 'danger'">
                    {{ $summary['is_passed'] ? 'Lulus' : 'Gagal' }}
                </x-filament::badge>
            </div>
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Jawaban benar</div>
                <div style="font-weight: 700;">{{ $summary['correct'] }} dari {{ $summary['total'] }} soal</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; opacity: 0.7;">Selesai</div>
                <div>{{ $summary['finished_at'] }}</div>
            </div>
        </div>
    </x-filament::section>

    <ol>
    @foreach ($collections as $pelajaran)
        <li type="A" style="font-weight: 700">{{ $pelajaran['name'] }}</li>
        <ol>
        @foreach ($pelajaran['soals'] as $pertanyaan)
            <li type="1" wire:key="soal-{{ $pertanyaan['id'] }}">
                {!! $pertanyaan['payload'] !!}

                <ol>
                    @foreach ($pertanyaan['answers'] as $jawaban)
                        @php
                            $color = $jawaban['is_correct']
                                ? '#16a34a'
                                : ($jawaban['is_chosen'] ? '#dc2626' : 'inherit');
                        @endphp
                        <li style="color: {{ $color }}; font-weight: {{ ($jawaban['is_correct'] || $jawaban['is_chosen']) ? '600' : '400' }};">
                            <strong>{{ chr(65 + $loop->index) }}.</strong>
                            {{ $jawaban['text'] }}

                            @if ($jawaban['is_chosen'] && $jawaban['is_correct'])
                                <span>✓ Jawaban siswa (benar)</span>
                            @elseif ($jawaban['is_chosen'])
                                <span>✗ Jawaban siswa (salah)</span>
                            @elseif ($jawaban['is_correct'])
                                <span>✓ Jawaban benar</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @unless ($pertanyaan['is_answered'])
                    <div style="margin-top: 0.25rem; font-size: 0.875rem; color: #d97706;">
                        Soal ini tidak dijawab siswa.
                    </div>
                @endunless

                @if (filled($pertanyaan['description']))
                    <div style="margin-top: 0.5rem; padding: 0.5rem 0.75rem; border-left: 3px solid #f59e0b; white-space: pre-line;">
                        <strong>Keterangan:</strong> {{ $pertanyaan['description'] }}
                    </div>
                @endif
            </li>
            <br>
        @endforeach
        </ol>
    @endforeach
    </ol>
</x-filament-panels::page>