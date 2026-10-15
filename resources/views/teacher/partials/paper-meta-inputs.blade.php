@php
    $skipMeta = [
        'chapter_id', 'topic_id', 'chapter_ids', 'topic_ids',
        'material_id', 'material_topic_id', 'material_ids', 'material_topic_ids',
        'chapter_name', 'topic_name',
    ];
    $chapterIds = array_values(array_filter((array) ($meta['material_ids'] ?? $meta['chapter_ids'] ?? [])));
    $topicIds = array_values(array_filter((array) ($meta['material_topic_ids'] ?? $meta['topic_ids'] ?? [])));
@endphp
@foreach ($meta as $key => $value)
    @if (! in_array($key, $skipMeta, true) && (is_scalar($value) || $value === null))
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
@foreach ($chapterIds as $id)
    <input type="hidden" name="chapter_ids[]" value="{{ $id }}">
@endforeach
@foreach ($topicIds as $id)
    <input type="hidden" name="topic_ids[]" value="{{ $id }}">
@endforeach
