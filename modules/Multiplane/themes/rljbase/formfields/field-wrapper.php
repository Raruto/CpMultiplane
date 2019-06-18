<?php $slugName = cockpit('multiplane')->slugName; ?>

<div class="width-{{ $field['width'] ?? '1-1' }}">
    {{ $content_for_layout }}
@if(!empty($field['error']))
    <p class="message error">@lang($field['error'])</p>
@endif
@if(!empty($field['info']))
    <p class="message info">@lang($field['info'])</p>
@endif
@if(!empty($field['link']))
    <p class="message info link">
  @if(!empty($field['link']['text_before']))
      {{ $field['link']['text_before'] }}
  @endif
        <a href="@base($field['link'][$slugName])">{{ $field['link']['title'] ?? 'link' }}</a>
  @if(!empty($field['link']['text_after']))
      {{ $field['link']['text_after'] }}
  @endif
    </p>
@endif
</div>
