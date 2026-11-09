@if($proposals)
    <section class="my-4 rounded-lg border border-gray-200 bg-white p-4">
        <h3 class="font-semibold text-gray-900">{{ count($proposals) === 1 ? 'Proposed Research Title' : 'Proposed Research Titles' }}</h3>
        <ul class="mt-2 space-y-3">
            @foreach($proposals as $proposal)
                <li class="break-words">
                    <p class="text-sm font-semibold text-gray-900">{{ $proposal['title'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endif
