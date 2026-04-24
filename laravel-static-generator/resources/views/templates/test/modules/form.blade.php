<section class="form background--characteristics mb50" id="form">
    <div class="form__inner">
        <h2 class="form__title">{{ $formTitle ?? 'Contact Form' }}</h2>
        <p class="form__description">{{ $formDescription ?? 'In this article, we will explore the key features of the Aviator casino game.' }}</p>
        <form class="form__body" action="#" method="post" aria-label="Contact form">
            <div class="form__fields">
                <label class="form__field form__field--name">
                    <img class="form__icon" src="/assets/svg/user.svg" width="40" height="40" loading="lazy" alt="">
                    <input class="form__control" type="text" name="review-name" placeholder="Your name" autocomplete="name" aria-label="Your name">
                </label>
                <label class="form__field form__field--name">
                    <img class="form__icon" src="/assets/svg/email.svg" width="40" height="40" loading="lazy" alt="">
                    <input class="form__control" type="email" name="review-email" placeholder="Your email" autocomplete="email" inputmode="email" required aria-label="Your email">
                </label>
                <label class="form__field form__field--review">
                    <img class="form__icon" src="/assets/svg/chat.svg" width="40" height="40" loading="lazy" alt="">
                    <textarea class="form__control form__control--textarea" name="review-text" placeholder="Your message" rows="3" aria-label="Your message"></textarea>
                </label>
            </div>
            <div class="form__actions">
                <button class="btn__cta btn__cta--hero btn__cta--form" type="button">{{ $formButtonText ?? 'Send' }}</button>
            </div>
        </form>
    </div>
</section>