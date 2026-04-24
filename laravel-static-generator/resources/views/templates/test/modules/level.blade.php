<section class="level" id="level">
    <div class="level__inner">
        <div class="level__content">
            <h2 class="level__title">
                <span class="level__title-top">{{ $levelTitleTop ?? 'Our' }}</span>
                <span class="level__title-bottom">{{ $levelTitleBottom ?? 'Address' }}</span>
            </h2>
            <p class="level__description">{{ $levelDescription ?? 'In this article, we will explore the key features of the Aviator casino game.' }}</p>
            <ul class="list list--bulleted" aria-label="Address list">
                @if(isset($addressItems) && is_array($addressItems))
                    @foreach($addressItems as $item)
                        <li class="list__item">{{ $item }}</li>
                    @endforeach
                @else
                    <li class="list__item">Nandan ProBiz 902, Sai Chowk Rd</li>
                    <li class="list__item">Laxman Nagar, Balewadi, Pune</li>
                    <li class="list__item">Maharashtra 411045, India</li>
                @endif
            </ul>

            @if(isset($levelSubheading) && $levelSubheading)
                <h4 class="level__subheading">{{ $levelSubheading }}</h4>
            @endif

            @if(isset($orderedItems) && is_array($orderedItems) && count($orderedItems) > 0)
                <ol class="list list--ordered" aria-label="Gameplay numbered list">
                    @foreach($orderedItems as $item)
                        <li class="list__item">{{ $item }}</li>
                    @endforeach
                </ol>
            @endif
        </div>
        <div class="level__media">
            <iframe class="google-map__iframe" title="Google Map" src="https://maps.google.com/maps?q=Brainstorm%20Force&amp;z=12&amp;hl=en&amp;t=m&amp;output=embed&amp;iwloc=near" width="555" height="555" loading="lazy"></iframe>
        </div>
    </div>
</section>