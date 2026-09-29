<x-filament-panels::page>
    @php
        $isLastPage = $this->currentPage >= $this->totalPages;
        $confirmText = $this->unansweredCount > 0
            ? "Masih ada {$this->unansweredCount} soal yang belum dijawab. Yakin ingin menyelesaikan ujian? Jawaban tidak bisa diubah lagi."
            : 'Yakin ingin menyelesaikan ujian? Jawaban tidak bisa diubah lagi.';
    @endphp

    {{-- navigasi nomor soal --}}
    <x-filament::section>
        <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
            @foreach ($this->questions as $soal)
                @php
                    $answered = isset($selected[$soal['id']]);
                    $onPage = $soal['page'] === $this->currentPage;
                @endphp
                <button
                    type="button"
                    wire:key="nav-{{ $soal['id'] }}"
                    wire:click="goToPage({{ $soal['page'] }})"
                    title="{{ $answered ? 'Sudah dijawab' : 'Belum dijawab' }}"
                    style="
                        width: 2.25rem; height: 2.25rem; border-radius: 0.375rem; font-size: 0.875rem;
                        border: {{ $onPage ? '2px solid #f59e0b' : '1px solid #6b7280' }};
                        background: {{ $answered ? '#16a34a' : 'transparent' }};
                        color: {{ $answered ? '#fff' : 'inherit' }};
                    "
                >
                    {{ $soal['no'] }}
                </button>
            @endforeach
        </div>

        <div style="margin-top: 0.75rem; font-size: 0.875rem; opacity: 0.8;">
            Halaman {{ $this->currentPage }} dari {{ $this->totalPages }}
            &middot; Belum dijawab: {{ $this->unansweredCount }} soal
        </div>
    </x-filament::section>

    {{-- soal di halaman aktif --}}
    @php $lastSubjectId = null; @endphp

    @foreach ($this->pageQuestions as $soal)
        @if ($soal['subject_id'] !== $lastSubjectId)
            <div style="font-weight: 700; font-size: 1.125rem; margin-top: 0.5rem;">
                {{ $soal['subject'] }}
            </div>
            @php $lastSubjectId = $soal['subject_id']; @endphp
        @endif

        <div wire:key="soal-{{ $soal['id'] }}" style="margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 0.5rem;">
                <strong>{{ $soal['no'] }}.</strong>
                <div>{!! $soal['payload'] !!}</div>
            </div>

            <div style="margin-top: 0.5rem; margin-left: 1.75rem; display: flex; flex-direction: column; gap: 0.375rem;">
                @foreach ($soal['answers'] as $jawaban)
                    <label
                        wire:key="jawaban-{{ $soal['id'] }}-{{ $jawaban['id'] }}"
                        style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"
                    >
                        <input
                            type="radio"
                            name="jawaban_{{ $soal['id'] }}"
                            value="{{ $jawaban['id'] }}"
                            wire:click="choose({{ $soal['id'] }}, {{ $jawaban['id'] }})"
                            @checked(($selected[$soal['id']] ?? null) == $jawaban['id'])
                        />
                        <span>
                            <strong>{{ chr(65 + $loop->index) }}.</strong>
                            {{ $jawaban['text'] }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- tombol navigasi --}}
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
        <x-filament::button
            wire:click="previousPage"
            color="gray"
            :disabled="$this->currentPage <= 1"
        >
            Sebelumnya
        </x-filament::button>

        @if ($isLastPage)
            <x-filament::button
                wire:click="finish"
                wire:confirm="{{ $confirmText }}"
                color="success"
            >
                Selesai
            </x-filament::button>
        @else
            <x-filament::button wire:click="nextPage">
                Berikutnya
            </x-filament::button>
        @endif
    </div>
</x-filament-panels::page>