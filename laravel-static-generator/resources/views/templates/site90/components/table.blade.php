@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('table-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(isset($content['heading']))
    <h2>{{ $content['heading'] }}</h2>
    @endif
    
    <table>
        <thead>
            <tr>
                @foreach(($content['headers'] ?? []) as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach(($content['rows'] ?? []) as $row)
            <tr>
                @foreach($row as $cell)
                <td>{{ $cell }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</section>
