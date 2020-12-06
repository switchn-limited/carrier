<div class="search__container">
    <input class="search__input" type="text" placeholder="Phone number" wire:model="phone" wire:keydown.enter="find">

    <p class="search__title">
        @if($carrier)
            {{ $carrier }}
        @endif

        @if($message)
            {{ $message }}
        @endif
    </p>
</div>
