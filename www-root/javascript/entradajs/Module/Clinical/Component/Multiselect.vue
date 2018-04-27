<template>
    <div
            :tabindex="searchable ? -1 : tabindex"
            :class="{ 'multiselect--active': isOpen, 'multiselect--disabled': disabled, 'multiselect--above': isAbove }"
            @focus="activate()"
            @blur="searchable ? false : deactivate()"
            @keydown.self.down.prevent="pointerForward()"
            @keydown.self.up.prevent="pointerBackward()"
            @keydown.enter.tab.stop.self="addPointerElement($event)"
            @keyup.esc="deactivate()"
            class="multiselect">
        <slot name="caret" :toggle="toggle">
            <div v-show="!searchable" @mousedown.prevent.stop="toggle()" class="multiselect_select"></div>
        </slot>
        <slot name="clear" :search="search"></slot>
        <div ref="tags" class="multiselect_tags">
            <template v-if="internalValue && internalValue.length > limit">
                <strong class="multiselect_strong" v-text="limitText(internalValue.length - limit)"></strong>
            </template>
            <transition name="multiselect_loading">
                <slot name="loading"><div v-show="loading" class="multiselect_spinner"></div></slot>
            </transition>
            <input
                    ref="search"
                    :name="name"
                    :id="id"
                    type="text"
                    autocomplete="off"
                    :placeholder="placeholder"
                    v-if="searchable"
                    :style="inputStyle"
                    :value="isOpen ? search : currentOptionLabel"
                    :disabled="disabled"
                    :tabindex="tabindex"
                    @input="updateSearch($event.target.value)"
                    @focus.prevent="activate()"
                    @blur.prevent="deactivate()"
                    @keyup.esc="deactivate()"
                    @keydown.down.prevent="pointerForward()"
                    @keydown.up.prevent="pointerBackward()"
                    @keydown.enter.prevent.stop.self="addPointerElement($event)"
                    @keydown.delete.stop="removeLastElement()"
                    class="multiselect_input"/>
            <span
                    v-if="!searchable"
                    class="multiselect_single"
                    @mousedown.prevent="toggle"
                    v-text="currentOptionLabel">

            </span>
        </div>
        <transition name="multiselect">
            <div
                    class="multiselect_content-wrapper"
                    v-show="isOpen && (searchable ? search.length > minSearch : true)"
                    @focus="activate"
                    @mousedown.prevent
                    :style="{ maxHeight: optimizedHeight + 'px' }"
                    ref="list">
                <ul class="multiselect_content" :style="contentStyle">
                    <slot name="beforeList"></slot>
                    <li v-if="multiple && max === internalValue.length">
              <span class="multiselect_option">
                <slot name="maxElements">Maximum of {{ max }} options selected. First remove a selected option to select another.</slot>
              </span>
                    </li>
                    <template v-if="!max || internalValue.length < max">
                        <li class="multiselect_element" v-for="(option, index) of filteredOptions" :key="index">
                <span
                        v-if="!(option && (option.$isLabel || option.$isDisabled))"
                        :class="optionHighlight(index, option)"
                        @click.stop="select(option)"
                        @mouseenter.self="pointerSet(index)"
                        :data-select="option && option.isTag ? tagPlaceholder : selectLabelText"
                        :data-selected="selectedLabelText"
                        :data-deselect="deselectLabelText"
                        class="multiselect_option">
                    <slot name="option" :option="option" :search="search">
                      <span>{{ getOptionLabel(option) }}</span>
                    </slot>
                </span>
                            <span
                                    v-if="option && (option.$isLabel || option.$isDisabled)"
                                    :class="optionHighlight(index, option)"
                                    class="multiselect_option multiselect_option--disabled">
                    <slot name="option" :option="option" :search="search">
                      <span>{{ getOptionLabel(option) }}</span>
                    </slot>
                </span>
                        </li>
                    </template>
                    <li v-show="showNoResults && (filteredOptions.length === 0 && search && !loading)">
              <span class="multiselect_option">
                <slot name="noResult">No elements found. Consider changing the search query.</slot>
              </span>
                    </li>
                    <slot name="afterList"></slot>
                </ul>
            </div>
        </transition>
        <div class="multiselect_tags-wrap" v-show="visibleValue.length > 0">
            <template v-for="option in visibleValue" @mousedown.prevent>
                <slot name="tag" :option="option" :search="search" :remove="removeElement">
                      <span class="multiselect_tag">
                          <i aria-hidden="true" tabindex="1" @keydown.enter.prevent="removeElement(option)"  @mousedown.prevent="removeElement(option)" class="multiselect_tag-icon"></i><span v-text="getOptionLabel(option)"></span>
                      </span>
                </slot>
            </template>
        </div>
    </div>
</template>

<script>
    const multiselectMixin = use('./multiselectMixin.js');
    const pointerMixin = use('./pointerMixin.js');

    module.exports = {
        name: 'vue-multiselect',
        mixins: [multiselectMixin, pointerMixin],
        props: {
            /**
             * name attribute to match optional label element
             * @default ''
             * @type {String}
             */
            name: {
                type: String,
                default: ''
            },
            /**
             * String to show when pointing to an option
             * @default 'Press enter to select'
             * @type {String}
             */
            selectLabel: {
                type: String,
                default: 'Press enter to select'
            },
            /**
             * String to show next to selected option
             * @default 'Selected'
             * @type {String}
             */
            selectedLabel: {
                type: String,
                default: 'Selected'
            },
            /**
             * String to show when pointing to an alredy selected option
             * @default 'Press enter to remove'
             * @type {String}
             */
            deselectLabel: {
                type: String,
                default: 'Press enter to remove'
            },
            /**
             * Decide whether to show pointer labels
             * @default true
             * @type {Boolean}
             */
            showLabels: {
                type: Boolean,
                default: true
            },
            /**
             * Limit the display of selected options. The rest will be hidden within the limitText string.
             * @default 99999
             * @type {Integer}
             */
            limit: {
                type: Number,
                default: 99999
            },
            /**
             * Sets maxHeight style value of the dropdown
             * @default 300
             * @type {Integer}
             */
            maxHeight: {
                type: Number,
                default: 300
            },
            /**
             * Function that process the message shown when selected
             * elements pass the defined limit.
             * @default 'and * more'
             * @param {Int} count Number of elements more than limit
             * @type {Function}
             */
            limitText: {
                type: Function,
                default: count => `and ${count} more`
            },
            /**
             * Set true to trigger the loading spinner.
             * @default False
             * @type {Boolean}
             */
            loading: {
                type: Boolean,
                default: false
            },
            /**
             * Disables the multiselect if true.
             * @default false
             * @type {Boolean}
             */
            disabled: {
                type: Boolean,
                default: false
            },
            /**
             * Fixed opening direction
             * @default ''
             * @type {String}
             */
            openDirection: {
                type: String,
                default: ''
            },
            showNoResults: {
                type: Boolean,
                default: true
            },
            tabindex: {
                type: Number,
                default: 0
            }
        },
        computed: {
            visibleValue () {
                return this.multiple
                    ? this.internalValue.slice(0, this.limit)
                    : []
            },
            deselectLabelText () {
                return this.showLabels
                    ? this.deselectLabel
                    : ''
            },
            selectLabelText () {
                return this.showLabels
                    ? this.selectLabel
                    : ''
            },
            selectedLabelText () {
                return this.showLabels
                    ? this.selectedLabel
                    : ''
            },
            inputStyle () {
                if (this.multiple && this.value && this.value.length) {
                    // Hide input by setting the width to 0 allowing it to receive focus
                    return this.isOpen ? { 'width': 'auto' } : { 'width': '0', 'position': 'absolute' }
                }
            },
            contentStyle () {
                return this.options.length
                    ? { 'display': 'inline-block' }
                    : { 'display': 'block' }
            },
            isAbove () {
                if (this.openDirection === 'above' || this.openDirection === 'top') {
                    return true
                } else if (this.openDirection === 'below' || this.openDirection === 'bottom') {
                    return false
                } else {
                    return this.prefferedOpenDirection === 'above'
                }
            }
        }
    }
</script>

<style>
    fieldset[disabled] .multiselect {
        pointer-events: none;
    }
    .multiselect_spinner {
        position: absolute;
        right: 1px;
        top: 1px;
        width: 48px;
        height: 35px;
        background: #fff;
        display: block;
    }
    .multiselect_spinner:before,
    .multiselect_spinner:after {
        position: absolute;
        content: "";
        top: 50%;
        left: 50%;
        margin: -8px 0 0 -8px;
        width: 16px;
        height: 16px;
        border-radius: 100%;
        border-color: #41B883 transparent transparent;
        border-style: solid;
        border-width: 2px;
        box-shadow: 0 0 0 1px transparent;
    }
    .multiselect_spinner:before {
        animation: spinning 2.4s cubic-bezier(0.41, 0.26, 0.2, 0.62);
        animation-iteration-count: infinite;
    }
    .multiselect_spinner:after {
        animation: spinning 2.4s cubic-bezier(0.51, 0.09, 0.21, 0.8);
        animation-iteration-count: infinite;
    }
    .multiselect_loading-enter-active,
    .multiselect_loading-leave-active {
        transition: opacity 0.4s ease-in-out;
        opacity: 1;
    }
    .multiselect_loading-enter,
    .multiselect_loading-leave-active {
        opacity: 0;
    }
    .multiselect .multiselect_input {
        top: 2px !important;
        left: 4px;
        border-left: none;
        border-right: none;
        border-top: none;
        border-radius: 0;
        width: 90% !important;
        border-bottom: none;
        position: absolute !important;
    }
    .multiselect,
    .multiselect_input,
    .multiselect_single {
        font-family: inherit;
        font-size: 14px;
        touch-action: manipulation;
    }
    .multiselect {
        box-sizing: content-box;
        display: block;
        position: relative;
        width: 100%;
        min-height: 40px;
        text-align: left;
        color: #35495E;
    }
    .multiselect * {
        box-sizing: border-box;
    }
    .multiselect:focus {
        outline: none;
    }
    .multiselect--disabled {
        pointer-events: none;
        opacity: 0.6;
    }
    .multiselect--active {
        z-index: 50;
    }
    .multiselect--active:not(.multiselect--above) .multiselect_current,
    .multiselect--active:not(.multiselect--above) .multiselect_input,
    .multiselect--active:not(.multiselect--above) .multiselect_tags {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }
    .multiselect--active .multiselect_select {
        transform: rotateZ(180deg);
    }
    .multiselect--above.multiselect--active .multiselect_current,
    .multiselect--above.multiselect--active .multiselect_input,
    .multiselect--above.multiselect--active .multiselect_tags {
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    .multiselect_input,
    .multiselect_single {
        position: relative;
        display: inline-block;
        min-height: 20px;
        line-height: 20px;
        border: none;
        border-radius: 5px;
        background: #fff;
        padding: 0 0 0 5px;
        width: calc(100%);
        transition: border 0.1s ease;
        box-sizing: border-box;
        margin-bottom: 8px;
        vertical-align: top;
    }
    .multiselect_tag ~ .multiselect_input,
    .multiselect_tag ~ .multiselect_single {
        width: auto;
    }
    .multiselect_input:hover,
    .multiselect_single:hover {
        border-color: #cfcfcf;
    }
    .multiselect_input:focus,
    .multiselect_single:focus {
        border-color: #a8a8a8;
        outline: none;
    }
    .multiselect_single {
        padding-left: 6px;
        margin-bottom: 8px;
    }
    .multiselect_tags-wrap {
        overflow: hidden;
        padding: 5px;
    }
    .multiselect_tags {
        min-height: 40px;
        display: block;
        padding: 8px 40px 0 8px;
        border-radius: 5px;
        border: 1px solid #E8E8E8;
        background: #fff;
    }
    .multiselect_tag {
        display: inline-block;
        color: #777;
        line-height: 12px;
        margin: 2px 2px;
        max-width: 100%;
        background: #f7f7f7;
        font-size: 11px;
        border-radius: 3px;
        overflow: hidden;
        float: left;
        border: 1px solid #DDD;
    }
    .multiselect_tag span {
        padding: 4px 8px;
        display: inline-block;
        vertical-align: middle;
    }
    .multiselect_tag-icon {
        color: #999;
        background-color: rgba(0,0,0,0.07);
        font-style: normal;
        cursor: pointer;
        display: inline-block;
        padding: 4px 6px;
        vertical-align: middle;
        font-size: 10px;
        border-right: 1px solid #CCC;
        font-weight: 500;
        box-shadow: 0px 0px 4px rgba(0,0,0,0.2);
        -webkit-box-shadow: 0px 0px 4px rgba(0,0,0,0.2);
        -webkit-transition: all 0.2s ease-in-out;
        -moz-transition: all 0.2s ease-in-out;
        -ms-transition: all 0.2s ease-in-out;
        -o-transition: all 0.2s ease-in-out;
        transition: all 0.2s ease-in-out;

    }
    .multiselect_tag-icon:hover{
        background-color: rgba(0,0,0,0.14);
    }
    .multiselect_tag-icon::after {
        content: 'x';
    }
    .multiselect_current {
        line-height: 16px;
        min-height: 40px;
        box-sizing: border-box;
        display: block;
        overflow: hidden;
        padding: 8px 12px 0;
        padding-right: 30px;
        white-space: nowrap;
        margin: 0;
        text-decoration: none;
        border: 1px solid #E8E8E8;
        cursor: pointer;
    }
    .multiselect_select {
        line-height: 16px;
        display: block;
        position: absolute;
        box-sizing: border-box;
        width: 40px;
        height: 38px;
        right: 1px;
        top: 1px;
        padding: 4px 8px;
        margin: 0;
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .multiselect_select:before {
        position: relative;
        right: 0;
        top: 65%;
        color: #999;
        margin-top: 4px;
        border-style: solid;
        border-width: 5px 5px 0 5px;
        border-color: #999999 transparent transparent transparent;
        content: "";
    }
    .multiselect_placeholder {
        color: #ADADAD;
        display: inline-block;
        margin-bottom: 10px;
        padding-top: 2px;
    }
    .multiselect--active .multiselect_placeholder {
        display: none;
    }
    .multiselect_content-wrapper {
        position: absolute;
        display: block;
        background: #fff;
        width: 100%;
        max-height: 240px;
        overflow: auto;
        border: 1px solid #E8E8E8;
        border-top: none;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
        z-index: 50;
        -webkit-overflow-scrolling: touch;
    }
    .multiselect_content {
        list-style: none;
        display: inline-block;
        padding: 0;
        margin: 0;
        min-width: 100%;
        vertical-align: top;
    }
    .multiselect--above .multiselect_content-wrapper {
        bottom: 100%;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        border-bottom: none;
        border-top: 1px solid #E8E8E8;
    }
    .multiselect_content::webkit-scrollbar {
        display: none;
    }
    .multiselect_element {
        display: block;
    }
    .multiselect_option {
        display: block;
        padding: 10px;
        text-decoration: none;
        text-transform: none;
        vertical-align: middle;
        position: relative;
        cursor: pointer;
        white-space: nowrap;
        -webkit-transition: all 0.2s ease-in-out;
        -moz-transition: all 0.2s ease-in-out;
        -ms-transition: all 0.2s ease-in-out;
        -o-transition: all 0.2s ease-in-out;
        transition: all 0.2s ease-in-out;
    }
    .multiselect_option:after {
        top: 0;
        right: 0;
        position: absolute;
        line-height: 40px;
        padding-right: 12px;
        padding-left: 20px;
    }
    .multiselect_option--highlight {
        background: #F7F7F7;
        outline: none;
        color: #838383;
    }
    .multiselect_option--highlight:after {
        content: attr(data-select);
        color: #bbb;
    }
    .multiselect_option--selected {
        background: #E6E6E6;
        color: #35495E;
        font-weight: bold;
    }
    .multiselect_option--selected:after {
        content: attr(data-selected);
        color: #8b95a0;
    }
    .multiselect_option--selected.multiselect_option--highlight {
        background: #F7F7F7;
        color: #838383;
    }
    .multiselect_option--selected.multiselect_option--highlight:after {
        content: attr(data-deselect);
        color: #838383;
    }
    .multiselect--disabled {
        background: #ededed;
        pointer-events: none;
    }
    .multiselect--disabled .multiselect_current,
    .multiselect--disabled .multiselect_select {
        background: #ededed;
        color: #a6a6a6;
    }
    .multiselect_option--disabled {
        background: #ededed;
        color: #a6a6a6;
        cursor: text;
        pointer-events: none;
    }
    .multiselect_option--disabled.multiselect_option--highlight {
        background: #dedede !important;
    }
    .multiselect-enter-active,
    .multiselect-leave-active {
        transition: all 0.15s ease;
    }
    .multiselect-enter,
    .multiselect-leave-active {
        opacity: 0;
    }
    .multiselect_strong {
        margin-bottom: 8px;
        line-height: 20px;
        display: inline-block;
        vertical-align: top;
    }
    *[dir="rtl"] .multiselect {
        text-align: right;
    }
    *[dir="rtl"] .multiselect_select {
        right: auto;
        left: 1px;
    }
    *[dir="rtl"] .multiselect_tags {
        padding: 8px 8px 0px 40px;
    }
    *[dir="rtl"] .multiselect_content {
        text-align: right;
    }
    *[dir="rtl"] .multiselect_option:after {
        right: auto;
        left: 0;
    }
    *[dir="rtl"] .multiselect_clear {
        right: auto;
        left: 12px;
    }
    *[dir="rtl"] .multiselect_spinner {
        right: auto;
        left: 1px;
    }
    @keyframes spinning {
        from { transform:rotate(0) }
        to { transform:rotate(2turn) }
    }
</style>