@php
    $fieldId = str_replace(['[', ']'], ['-', ''], $fieldName);
@endphp
<div class="annotation-field {{ !empty($definition['multiple']) ? 'annotation-field-wide' : '' }}">
    @if(!empty($definition['multiple']))
        <fieldset class="annotation-options"><legend>{{ $definition['label'] }} <span class="annotation-answer-state" data-answer-state>Not answered</span></legend>
        <div class="annotation-checkboxes">
        @foreach($definition['options'] as $option)
            <label><input type="checkbox" name="{{ $fieldName }}[]" value="{{ $option }}"> <span>{{ $option }}</span></label>
        @endforeach
        </div></fieldset>
    @elseif(($definition['type'] ?? '') === 'number')
        <label for="{{ $fieldId }}">{{ $definition['label'] }} <span class="annotation-answer-state" data-answer-state>Not answered</span></label>
        <div class="annotation-number"><input id="{{ $fieldId }}" type="number" name="{{ $fieldName }}" min="0" max="100" step="any" placeholder="Not measured"><span>mm</span></div>
    @else
        <label for="{{ $fieldId }}">{{ $definition['label'] }} <span class="annotation-answer-state" data-answer-state>Not answered</span></label>
        <select id="{{ $fieldId }}" name="{{ $fieldName }}"><option value="">Select finding</option>
        @foreach($definition['options'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
        </select>
    @endif
</div>
