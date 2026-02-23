{* Renders a single theme customizer control based on $control.type *}
{* Variables: $control (array), $value (current value) *}

{if !empty($control.pro) && !empty($usage) && $usage.is_free}
    {* Pro-only control — show locked state for free plan users *}
    <div class="form-toggle-row customizer-pro-locked">
        <div>
            <div class="form-toggle-label">{$control.label|escape} <span class="pro-badge">PRO</span></div>
            <p class="form-hint" style="margin-top:2px">
                <a href="/dashboard/billing">Upgrade to a paid plan</a> to change this setting
            </p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" disabled {if $value}checked{/if}>
            <span class="toggle-slider"></span>
        </label>
    </div>

{elseif $control.type == 'toggle'}
    <div class="form-toggle-row">
        <div>
            <div class="form-toggle-label">{$control.label|escape}</div>
            {if $control.description}<p class="form-hint" style="margin-top:2px">{$control.description|escape}</p>{/if}
        </div>
        <label class="toggle-switch">
            <input type="checkbox" data-option="{$control.id|escape}" data-type="toggle" {if $value}checked{/if}>
            <span class="toggle-slider"></span>
        </label>
    </div>

{elseif $control.type == 'text'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <input type="text" class="form-control" data-option="{$control.id|escape}" data-type="text"
               value="{$value|escape}"
               {if !empty($control.input_attrs.placeholder)}placeholder="{$control.input_attrs.placeholder|escape}"{/if}
               {if !empty($control.input_attrs.maxlength)}maxlength="{$control.input_attrs.maxlength}"{/if}>
    </div>

{elseif $control.type == 'textarea'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <textarea class="form-control" data-option="{$control.id|escape}" data-type="textarea"
                  rows="{$control.input_attrs.rows|default:3}"
                  {if !empty($control.input_attrs.placeholder)}placeholder="{$control.input_attrs.placeholder|escape}"{/if}>{$value|escape}</textarea>
    </div>

{elseif $control.type == 'number'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <input type="number" class="form-control" data-option="{$control.id|escape}" data-type="number"
               value="{$value|escape}"
               {if isset($control.input_attrs.min)}min="{$control.input_attrs.min}"{/if}
               {if isset($control.input_attrs.max)}max="{$control.input_attrs.max}"{/if}
               {if isset($control.input_attrs.step)}step="{$control.input_attrs.step}"{/if}>
    </div>

{elseif $control.type == 'select'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <select class="form-control" data-option="{$control.id|escape}" data-type="select">
            {foreach $control.choices as $choiceVal => $choiceLabel}
                <option value="{$choiceVal|escape}" {if $value == $choiceVal}selected{/if}>{$choiceLabel|escape}</option>
            {/foreach}
        </select>
    </div>

{elseif $control.type == 'radio'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <div class="customizer-radio-group" data-option="{$control.id|escape}" data-type="radio">
            {foreach $control.choices as $choiceVal => $choiceLabel}
                <button type="button" class="customizer-radio-btn{if $value == $choiceVal} active{/if}" data-value="{$choiceVal|escape}">
                    {$choiceLabel|escape}
                </button>
            {/foreach}
        </div>
    </div>

{elseif $control.type == 'color'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <div class="customizer-color-group">
            <input type="color" class="customizer-color-input" data-option="{$control.id|escape}" data-type="color" value="{$value|default:'#000000'|escape}">
            <input type="text" class="form-control customizer-color-hex" value="{$value|default:'#000000'|escape}" maxlength="7" placeholder="#000000">
        </div>
    </div>

{elseif $control.type == 'image'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <input type="file" class="customizer-image-file" accept="image/*" style="display:none">
        <div class="customizer-image-upload" data-option="{$control.id|escape}" data-type="image">
            <div class="customizer-image-preview" {if !$value}style="display:none"{/if}>
                <img src="{$value|escape}" alt="">
                <div class="customizer-image-change">Change image</div>
            </div>
            <div class="customizer-image-empty" {if $value}style="display:none"{/if}>
                <i class="fa-solid fa-image" style="font-size:24px;opacity:0.3"></i>
                <span>Tap to upload</span>
            </div>
        </div>
        <input type="hidden" class="customizer-image-value" data-option="{$control.id|escape}" data-type="image" value="{$value|escape}">
    </div>

{elseif $control.type == 'repeater'}
    <div class="form-group">
        <label>{$control.label|escape}</label>
        {if $control.description}<p class="form-hint">{$control.description|escape}</p>{/if}
        <div class="customizer-repeater" data-option="{$control.id|escape}" data-type="repeater"
             data-max="{$control.max|default:0}"
             data-fields='{$control.fields|@json_encode}'>
            <div class="customizer-repeater-items">
                {if is_array($value) && count($value) > 0}
                    {foreach $value as $item}
                    <div class="customizer-repeater-item collapsed">
                        <div class="customizer-repeater-item-header">
                            <div class="customizer-repeater-item-toggle">
                                <i class="fa-solid fa-chevron-right customizer-repeater-chevron"></i>
                                <span class="customizer-repeater-item-number">{$item.title|default:''|escape}</span>
                            </div>
                            <button type="button" class="customizer-repeater-remove" title="Remove">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>
                        <div class="customizer-repeater-item-fields">
                            {foreach $control.fields as $fieldKey => $field}
                            <div class="customizer-repeater-field">
                                {if ($field.type|default:'text') == 'image'}
                                    {assign var="imgVal" value=$item[$fieldKey]|default:''}
                                    <span class="customizer-repeater-field-label">{$field.label|escape}</span>
                                    <input type="file" class="repeater-img-file" accept="image/*" style="display:none">
                                    <div class="repeater-img-zone">
                                        <div class="repeater-img-preview" {if !$imgVal}style="display:none"{/if}>
                                            <img src="{$imgVal|escape}" alt="">
                                            <div class="repeater-img-change">Change</div>
                                        </div>
                                        <div class="repeater-img-empty" {if $imgVal}style="display:none"{/if}>
                                            <i class="fa-solid fa-image"></i>
                                            <span>Upload</span>
                                        </div>
                                    </div>
                                    <input type="hidden" class="repeater-img-value" data-field="{$fieldKey|escape}" value="{$imgVal|escape}">
                                {elseif ($field.type|default:'text') == 'icon'}
                                    {assign var="iconVal" value=$item[$fieldKey]|default:''}
                                    <span class="customizer-repeater-field-label">{$field.label|escape}</span>
                                    <button type="button" class="icon-picker-btn" data-field="{$fieldKey|escape}">
                                        {if $iconVal}
                                            <i class="{$iconVal|escape}"></i>
                                            <span>Change icon</span>
                                        {else}
                                            <i class="fa-solid fa-icons" style="opacity:0.3"></i>
                                            <span>Choose icon</span>
                                        {/if}
                                    </button>
                                    <input type="hidden" class="icon-picker-value" data-field="{$fieldKey|escape}" value="{$iconVal|escape}">
                                {else}
                                    <span class="customizer-repeater-field-label">{$field.label|escape}</span>
                                    <input type="text" class="form-control" data-field="{$fieldKey|escape}"
                                           value="{$item[$fieldKey]|default:''|escape}"
                                           placeholder="{$field.placeholder|default:$field.label|escape}">
                                {/if}
                            </div>
                            {/foreach}
                        </div>
                    </div>
                    {/foreach}
                {else}
                    <div class="customizer-repeater-empty">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>No items yet</span>
                    </div>
                {/if}
            </div>
            <button type="button" class="customizer-repeater-add">
                <i class="fa-solid fa-plus"></i> Add item
            </button>
        </div>
    </div>
{/if}
