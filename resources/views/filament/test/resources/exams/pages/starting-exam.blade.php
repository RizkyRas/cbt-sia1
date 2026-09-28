<x-filament-panels::page>
    <ol>
    @foreach ($collections as $pelajaran)
        <li type="A" style="font-weight: 700">{{ $pelajaran['name'] }}</li>
        <ol>
        @foreach ($pelajaran['soals'] as $pertanyaan)
            <li type="1">
                {!! $pertanyaan['payload'] !!}
                <ol>
                    @foreach ($pertanyaan['answers'] as $jawaban)
                        <li>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <x-filament::input.radio name="jawaban_{{ $pertanyaan['id'] }}" />
                                <span>
                                    <strong>{{ chr(65 + $loop->index) }}.</strong>
                                    {{ $jawaban['text'] }}
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ol>
            </li>
            <br>
        @endforeach
        </ol>
    @endforeach
    </ol>
</x-filament-panels::page>