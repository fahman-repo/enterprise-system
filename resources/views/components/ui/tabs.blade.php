@props(['default'])

<div
    x-data="{
        id: $id('tabs'),
        tab: @js($default),
        select(value) {
            this.tab = value
        },
        moveFocus(position) {
            const triggers = [...this.$el.querySelectorAll('[role=tab]')]
            const index = triggers.indexOf(document.activeElement)

            if (index === -1) {
                return
            }

            const last = triggers.length - 1

            triggers[position === 'first' ? 0 : position === 'last' ? last : (index + position + triggers.length) % triggers.length]?.focus()
        },
    }"
    {{ $attributes }}
>
    {{ $slot }}
</div>
