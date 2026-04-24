    private const MODULE_DEFAULTS = [
        'hero' => [
            'class' => 'hero hero--has-breadcrumbs',
            'id' => 'hero',
            'raw_html' => '<header class=\"header\" id=\"header\">
            <div class=\"header__inner\">
                <div class=\"header__logo\">
                    <a class=\"header__logo-wrapper\" href=\"/\" aria-label=\"To the main page\">
                        <img src=\"/assets/images/logo/logo.webp\" width=\"141\" height=\"41\" alt=\"Aviator\">
                    </a>
                </div>

                <nav class=\"header__nav menu\" aria-label=\"Main navigation\">
                    <ul class=\"menu__list\">
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"app.html\">App</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"demo.html\">Demo</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"tips.html\">Tips</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"bonuses.html\">Bonuses</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"reviews.html\">Reviews</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"contact-us.html\">Contact Us</a>
                        </li>
                        <li class=\"menu__item menu__item--has-submenu\">
                            <a class=\"menu__link\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>New
                                Versions</a>
                            <ul class=\"menu__submenu\" aria-label=\"New Versions submenu\">
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"authors.html\">Author\'s</a></li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"1win.html\">1WIN</a>
                                </li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"comparison.html\">Comparison</a>
                                </li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"sitemap.html\">Sitemap</a>
                                </li>
                            </ul>
                        </li>
                        <li class=\"menu__item menu__item--has-submenu menu__item--lang lang-item lang-item-en\">
                            <a class=\"menu__link menu__link--lang\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>
                                <span class=\"menu__lang\">
                                    <img class=\"menu__lang-flag\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\">
                                    <span class=\"menu__lang-text\">English</span>
                                </span>
                            </a>
                            <ul class=\"menu__submenu\" aria-label=\"Language submenu\">
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"/es/\" hreflang=\"es-ES\" lang=\"es-ES\"><span class=\"menu__lang\"><img class=\"menu__lang-flag\" src=\"data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%2711%27%20fill%3D%27%23AA151B%27%2F%3E%3Crect%20y%3D%272.75%27%20width%3D%2716%27%20height%3D%275.5%27%20fill%3D%27%23F1BF00%27%2F%3E%3C%2Fsvg%3E\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\"><span class=\"menu__lang-text\">Español</span></span></a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <a class=\"btn__cta\" href=\"#play-now\">Play now!</a>
                </nav>
            </div>
        </header>

        <nav class=\"breadcrumbs-container\" aria-label=\"Breadcrumb\">
            <div class=\"breadcrumbs\">
                <a class=\"breadcrumbs__item\" href=\"/\">Home page</a>
                <span class=\"breadcrumbs__separator\" aria-hidden=\"true\">→</span>
                <span class=\"breadcrumbs__item breadcrumbs__item--active\" aria-current=\"page\">1WIN</span>
            </div>
        </nav>
        <div class=\"hero__inner\">
            <div class=\"hero__content\">
                <div class=\"hero__text\">
                    <h1 class=\"hero__title\">1WIN Aviator Game</h1>
                    <p class=\"hero__description\">Play 1WIN Aviator Game — a thrilling legal online game
                        with a maximum win of 1000x your bet. Created by Spribe, this crash
                        game has.</p>
                </div>

                <a class=\"btn__cta btn__cta--hero\" href=\"#play-now\">Play now!</a>
            </div>

            <div class=\"hero__media\">
                <img class=\"hero__image\" src=\"/assets/images/hero/aviator.webp\" width=\"560\" height=\"582\" alt=\"Aviator\">
            </div>
        </div>',
        ],
        'header' => [
            'class' => 'header',
            'id' => 'header',
            'raw_html' => '<div class=\"header__inner\">
                <div class=\"header__logo\">
                    <a class=\"header__logo-wrapper\" href=\"/\" aria-label=\"To the main page\">
                        <img src=\"/assets/images/logo/logo.webp\" width=\"141\" height=\"41\" alt=\"Aviator\">
                    </a>
                </div>

                <nav class=\"header__nav menu\" aria-label=\"Main navigation\">
                    <ul class=\"menu__list\">
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"app.html\">App</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"demo.html\">Demo</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"tips.html\">Tips</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"bonuses.html\">Bonuses</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"reviews.html\">Reviews</a>
                        </li>
                        <li class=\"menu__item\">
                            <a class=\"menu__link\" href=\"contact-us.html\">Contact Us</a>
                        </li>
                        <li class=\"menu__item menu__item--has-submenu\">
                            <a class=\"menu__link\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>New
                                Versions</a>
                            <ul class=\"menu__submenu\" aria-label=\"New Versions submenu\">
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"authors.html\">Author\'s</a></li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"1win.html\">1WIN</a>
                                </li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"comparison.html\">Comparison</a>
                                </li>
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"sitemap.html\">Sitemap</a>
                                </li>
                            </ul>
                        </li>
                        <li class=\"menu__item menu__item--has-submenu menu__item--lang lang-item lang-item-en\">
                            <a class=\"menu__link menu__link--lang\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>
                                <span class=\"menu__lang\">
                                    <img class=\"menu__lang-flag\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\">
                                    <span class=\"menu__lang-text\">English</span>
                                </span>
                            </a>
                            <ul class=\"menu__submenu\" aria-label=\"Language submenu\">
                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"/es/\" hreflang=\"es-ES\" lang=\"es-ES\"><span class=\"menu__lang\"><img class=\"menu__lang-flag\" src=\"data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%2711%27%20fill%3D%27%23AA151B%27%2F%3E%3Crect%20y%3D%272.75%27%20width%3D%2716%27%20height%3D%275.5%27%20fill%3D%27%23F1BF00%27%2F%3E%3C%2Fsvg%3E\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\"><span class=\"menu__lang-text\">Español</span></span></a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <a class=\"btn__cta\" href=\"#play-now\">Play now!</a>
                </nav>
            </div>',
        ],
        'casino' => [
            'class' => 'casino',
            'id' => 'bonuses',
            'raw_html' => '<nav class=\"casino__menu\" aria-label=\"Section navigation\" data-casino-menu>
                    <ul class=\"casino__menu-list\" data-casino-menu-list>

                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#bonuses\">Bonuses and promo
                                codes</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#details\">Details about
                                casino</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#comparison\">Comparison</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#gameplay\">Gameplay</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#payments\">Payment Methods</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#characteristics\">Characteristics</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#review\">Review</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#pros\">Pros and cons</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#steps\">Steps to Gamble</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#feature\">All Feature Buy
                                Slots</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#level\">Level</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#conclusion\">Conclusion</a>
                        </li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#other-reviews\">Other
                                reviews</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#screenshots\">Game
                                Screenshots</a></li>
                        <li class=\"casino__menu-item\"><a class=\"casino__menu-link\" href=\"#faq\">FAQ</a></li>
                    </ul>

                    <div class=\"casino__menu-dropdown\" data-casino-menu-dropdown hidden aria-hidden=\"true\">
                        <ul class=\"casino__menu-dropdown-list\" data-casino-menu-overflow></ul>
                    </div>
                </nav>

                <div class=\"bonuses__inner\">
                    <h2 class=\"bonuses__title\">Bonuses and<br>promo <img class=\"bonuses__title-icon\" src=\"/assets/svg/percent.svg\" width=\"60\" height=\"60\" loading=\"lazy\" alt=\"\"> <span class=\"title--accent\">codes</span>
                    </h2>
                    <p class=\"bonuses__description\">By making use of exclusive bonus codes, you may
                        increase your initial betting budget for the Aviator game. Because of
                        that, you will be able to take more risks and that might help you walk
                        away with larger sums in the bank.</p>

                    <div class=\"bonuses__list\" aria-label=\"Bonuses and promo codes cards\">
                        <div class=\"card card--bonus\" aria-label=\"Bonus card 1\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/pari-match.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"PariMatch\">
                            <div class=\"card__title\">PariMatch</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>

                        <div class=\"card card--bonus\" aria-label=\"Bonus card 2\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/1win.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"1win\">
                            <div class=\"card__title\">1win</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>

                        <div class=\"card card--bonus\" aria-label=\"Bonus card 3\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/2bet.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"2bet\">
                            <div class=\"card__title\">2bet</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class=\"text text--limited text--pt20\">In this article, we will explore the
                        key features
                        of the
                        Aviator casino game, discuss its gameplay mechanics, user interface, and
                        overall
                        experience.</p>
                </div>',
        ],
        'symbols' => [
            'class' => 'symbols symbols-mt0',
            'id' => 'details',
            'raw_html' => '<div class=\"symbols__card\">
                    <div class=\"symbols__content\">
                        <h2 class=\"symbols__title\">Details about casino Name</h2>

                        <p class=\"symbols__description\">Big Bass Bonanza is a captivating slot
                            game born from the collaboration between Pragmatic Play and Reel
                            Kingdom. As the waves of anticipation rise and the reels spin to
                            a nautical rhythm, this slot promises not only the chance to win
                            big prizes but also an immersive aquatic adventure. In this
                            review, we will navigate through the game\'s vibrant visuals, its
                            unique features, and explore why it has made waves in the world
                            of online casinos. Whether you are an experienced angler or a
                            novice explorer, Big Bass Bonanza lures you in with promises of
                            hidden treasures beneath its waves. Join us to uncover the
                            details of this underwater marvel.</p>

                        <div class=\"symbols__tables\" aria-label=\"Symbols and paytable list\">
                            <table class=\"symbols__table\" aria-label=\"Paytable group 1\">
                                <tbody>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">🏢 Operator</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Pan de Bono Consulting Limited</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">🛡️ Licence</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Curacao 8048/JAZ</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">🎰 Types of games</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Slots, Table, Live Games</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">🎁 Welcome Bonus</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">200% up to €2,000</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">📅 Year of foundation</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">2019</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class=\"symbols__table\" aria-label=\"Paytable group 2\">
                                <tbody>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">💱 Currencies</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">GBP, USD, EUR, Crypto</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">💰 Bonus Offers</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Reloads, Cashback, Comp Points</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">💳 Payment Methods</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Cards, E-wallets, Crypto</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">📱 Mobile App</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">Browser-based Mobile</span>
                                        </td>
                                    </tr>
                                    <tr class=\"symbols__row\">
                                        <th class=\"symbols__cell symbols__cell--label\" scope=\"row\"><span class=\"symbols__label\">💬 Customer Support</span></th>
                                        <td class=\"symbols__cell symbols__cell--value\">
                                            <span class=\"symbols__value\">24/7 Live Chat</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class=\"text text--pt20\">In this article, we will explore the key
                            features of the
                            Aviator casino game, discuss its gameplay mechanics, user
                            interface, and overall
                            experience. In this article, we will explore the key features of
                            the
                            Aviator casino game, discuss its gameplay mechanics, user
                            interface, and overall
                            experience.</p>
                    </div>
                </div>',
        ],
        'comparison' => [
            'class' => 'comparison',
            'id' => 'comparison',
            'raw_html' => '<div class=\"comparison__inner\">
                    <h2 class=\"comparison__title\"><span class=\"title--accent\">Comparison</span>
                        with the web version</h2>
                    <p class=\"text text--light text--limited text--pb40\">The Aviator App has lots of
                        great things for gamers. It is simple to use and fun.</p>

                    <div class=\"comparison__table\">
                        <table class=\"comparison__matrix\" aria-label=\"Comparison table\">
                            <thead>
                                <tr class=\"comparison__head\" aria-label=\"Comparison table headers\">
                                    <th class=\"comparison__head-item comparison__head-item--mobile\" scope=\"col\">
                                        <span class=\"comparison__head-icon-wrap\" aria-hidden=\"true\">
                                            <img class=\"comparison__head-icon\" src=\"/assets/svg/mobile.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                        </span>
                                        <span class=\"comparison__head-label\">Mobile
                                            Edition</span>
                                    </th>

                                    <th class=\"comparison__head-item comparison__head-item--web\" scope=\"col\">
                                        <span class=\"comparison__head-icon-wrap\" aria-hidden=\"true\">
                                            <img class=\"comparison__head-icon\" src=\"/assets/svg/web.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                        </span>
                                        <span class=\"comparison__head-label\">Web
                                            Version</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class=\"comparison__rows\" aria-label=\"Comparison rows\">
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Error during
                                        installation</th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Make sure your device meets the needs.
                                        Update your system if needed.</td>
                                </tr>
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Aviator bet app
                                        download
                                        fails or is slow</th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Check your internet. Try using the
                                        online
                                        version while downloading.</td>
                                </tr>
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Software not
                                        working with
                                        your
                                        system</th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Make sure the software works with your
                                        system. Find a different version if
                                        needed.</td>
                                </tr>
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Installation stops
                                        halfway
                                    </th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Restart your device and try again. Check
                                        if you have enough space.</td>
                                </tr>
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Corrupted file
                                    </th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Turn off or uninstall any other software
                                        causing problems. Turn it back after.
                                    </td>
                                </tr>
                                <tr class=\"comparison__row\">
                                    <th class=\"comparison__cell comparison__cell--mobile\" scope=\"row\">Permission issues
                                    </th>
                                    <td class=\"comparison__cell comparison__cell--web\">
                                        Run the installer with the right
                                        permissions.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class=\"text text--light text--limited text--pt20\">In this article, we will explore
                    the key
                    features of the
                    Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                    experience.</p>',
        ],
        'gameplay' => [
            'class' => 'gameplay',
            'id' => 'gameplay',
            'raw_html' => '<div class=\"gameplay__inner\">
                    <h2 class=\"gameplay__title\"><span class=\"title--accent\">Gameplay</span>
                        in the game</h2>

                    <p class=\"gameplay__description\">When approaching the Aviator game, you have to
                        understand certain rules to maximize your potential wins and excitement
                        levels. Although the game is uncomplicated, there is a lot to take into
                        consideration when you try to have a rewarding experience. We have put
                        all the basic rules into a detailed guide below.</p>

                    <div class=\"gameplay__list\" aria-label=\"Gameplay steps\">
                        <div class=\"gameplay__item\" aria-label=\"Gameplay item 1\">
                            <div class=\"gameplay__text\">
                                <div class=\"gameplay__item-title\">Aviator Game</div>
                                <p class=\"gameplay__item-description\">Aviator is an
                                    arcade aviation game where the player must
                                    control a flying airplane. The goal of the game
                                    is to fly as many kilometers as possible while
                                    avoiding obstacles and other dangers. The player
                                    must maneuver the airplane using a keyboard or
                                    joystick to dodge obstacles and collect bonuses
                                    to earn extra points. The game can be played
                                    solo or in multiplayer mode.</p>
                            </div>

                            <figure class=\"gameplay__media\" aria-hidden=\"true\">
                                <img class=\"gameplay__image\" src=\"/assets/images/phone-aviator.webp\" width=\"443\" height=\"428\" loading=\"lazy\" alt=\"\">
                                <figcaption class=\"level__caption\">Description Level
                                    Image</figcaption>
                            </figure>
                        </div>

                        <div class=\"gameplay__item gameplay__item--reverse\" aria-label=\"Gameplay item 2\">
                            <div class=\"gameplay__text\">
                                <div class=\"gameplay__item-title\">Aviator Game</div>
                                <p class=\"gameplay__item-description\">First and
                                    foremost, it is essential to understand the
                                    factors that can affect the multiplier and take
                                    measures to minimize them. For example, one
                                    should constantly monitor market news, analyze
                                    asset prices, and take action when they change.
                                    It is important to avoid overextending oneself
                                    and exceeding one\'s limits. Understanding when
                                    to retreat and accept losses is crucial to
                                    prevent larger losses in the future.</p>
                            </div>

                            <figure class=\"gameplay__media\" aria-hidden=\"true\">
                                <img class=\"gameplay__image\" src=\"/assets/images/phone-aviator.webp\" width=\"443\" height=\"428\" loading=\"lazy\" alt=\"\">
                                <figcaption class=\"level__caption\">Description Level
                                    Image</figcaption>
                            </figure>
                        </div>

                        <div class=\"gameplay__item\" aria-label=\"Gameplay item 3\">
                            <div class=\"gameplay__text\">
                                <div class=\"gameplay__item-title\">Aviator Game</div>
                                <p class=\"gameplay__item-description\">The game offers
                                    players numerous opportunities for investment
                                    and earning money. Players can purchase aviation
                                    goods and services, and also participate in
                                    various aviation competitions. The game provides
                                    many avenues for players to invest and earn
                                    money. Players can use their funds to acquire
                                    aviation products and services, as well as take
                                    part in different aviation contests.</p>
                            </div>

                            <figure class=\"gameplay__media\" aria-hidden=\"true\">
                                <img class=\"gameplay__image\" src=\"/assets/images/phone-aviator.webp\" width=\"443\" height=\"428\" loading=\"lazy\" alt=\"\">
                                <figcaption class=\"level__caption\">Description Level
                                    Image</figcaption>
                            </figure>
                        </div>
                    </div>
                </div>',
        ],
        'rtp' => [
            'class' => 'rtp',
            'id' => 'payments',
            'raw_html' => '<div class=\"rtp__card\">
                    <div class=\"rtp__inner\">
                        <div class=\"symbols__content\">
                            <h2 class=\"symbols__title\"><span class=\"title--accent\">Payment</span> Methods</h2>

                            <p class=\"symbols__description\">Big Bass Bonanza is a captivating slot
                                game born from the collaboration between Pragmatic Play and Reel
                                Kingdom. As the waves of anticipation rise and the reels spin to
                                a nautical rhythm, this slot promises not only the chance to win
                                big prizes but also an immersive aquatic adventure.</p>

                            <div class=\"payments__tables\" aria-label=\"Payment methods list\">
                                <div class=\"payments__table-wrapper\">
                                    <table class=\"payments__table\" aria-label=\"Payment methods table\">
                                        <thead class=\"payments__table-head\">
                                            <tr class=\"payments__row\">
                                                <th class=\"payments__cell payments__cell--header\" scope=\"col\">Method
                                                </th>
                                                <th class=\"payments__cell payments__cell--header\" scope=\"col\">Withdrawal
                                                    Availability</th>
                                                <th class=\"payments__cell payments__cell--header\" scope=\"col\">Min
                                                    Deposit/Withdrawal</th>
                                                <th class=\"payments__cell payments__cell--header\" scope=\"col\">Withdrawal
                                                    Time</th>
                                                <th class=\"payments__cell payments__cell--header\" scope=\"col\">Fees</th>
                                            </tr>
                                        </thead>
                                        <tbody class=\"payments__table-body\">
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Visa</td>
                                                <td class=\"payments__cell\">Yes (limited)</td>
                                                <td class=\"payments__cell\">€25/€50</td>
                                                <td class=\"payments__cell\">1-3 days</td>
                                                <td class=\"payments__cell\">3% on deposit</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Mastercard</td>
                                                <td class=\"payments__cell\">Yes (limited)</td>
                                                <td class=\"payments__cell\">€25/€50</td>
                                                <td class=\"payments__cell\">1-3 days</td>
                                                <td class=\"payments__cell\">3% on deposit</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Skrill</td>
                                                <td class=\"payments__cell\">Yes</td>
                                                <td class=\"payments__cell\">€10/€20</td>
                                                <td class=\"payments__cell\">24-48 hours</td>
                                                <td class=\"payments__cell\">None</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Neteller</td>
                                                <td class=\"payments__cell\">Yes</td>
                                                <td class=\"payments__cell\">€10/€20</td>
                                                <td class=\"payments__cell\">24-48 hours</td>
                                                <td class=\"payments__cell\">None</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">EcoPayz</td>
                                                <td class=\"payments__cell\">Yes</td>
                                                <td class=\"payments__cell\">€10/€20</td>
                                                <td class=\"payments__cell\">Instant-24 hours</td>
                                                <td class=\"payments__cell\">None</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Bitcoin</td>
                                                <td class=\"payments__cell\">Yes</td>
                                                <td class=\"payments__cell\">€20 equiv/€100</td>
                                                <td class=\"payments__cell\">1-3 hours</td>
                                                <td class=\"payments__cell\">None</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Paysafecard</td>
                                                <td class=\"payments__cell\">No</td>
                                                <td class=\"payments__cell\">€5/N/A</td>
                                                <td class=\"payments__cell\">N/A</td>
                                                <td class=\"payments__cell\">None</td>
                                            </tr>
                                            <tr class=\"payments__row\">
                                                <td class=\"payments__cell\">Bank Transfer</td>
                                                <td class=\"payments__cell\">Yes</td>
                                                <td class=\"payments__cell\">€100/€100</td>
                                                <td class=\"payments__cell\">3-7 days</td>
                                                <td class=\"payments__cell\">Varies by bank</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <p class=\"text text--pt20\">In this article, we will explore the key
                                features of the
                                Aviator casino game, discuss its gameplay mechanics, user
                                interface, and overall
                                experience. In this article, we will explore the key features of
                                the
                                Aviator casino game, discuss its gameplay mechanics, user
                                interface, and overall
                                experience.</p>
                        </div>
                    </div>
                </div>',
        ],
        'steps' => [
            'class' => 'steps steps--1win background--characteristics',
            'id' => 'steps',
            'raw_html' => '<div class=\"steps__inner\">
                    <h2 class=\"steps__title\">How to Access</h2>
                    <p class=\"steps__description\">There are several clear-cut, easy-to-understand
                        steps we have described that you must take to enjoy the online casino
                        Aviator gaming experience.</p>

                    <div class=\"steps__list\" aria-label=\"Steps cards\">
                        <div class=\"steps__card\" aria-label=\"Step 1\">
                            <img class=\"steps__card-image\" src=\"/assets/images/steps/step.webp\" width=\"408\" height=\"171\" loading=\"lazy\" alt=\"\">
                            <div class=\"steps__card-content\">
                                <span class=\"steps__card-step\">Step 1</span>
                                <div class=\"steps__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"steps__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure
                                    pleasant gambling. You need to pick a gambling
                                    site that has all the necessary.</p>
                            </div>
                        </div>

                        <div class=\"steps__card\" aria-label=\"Step 2\">
                            <img class=\"steps__card-image\" src=\"/assets/images/steps/step.webp\" width=\"408\" height=\"171\" loading=\"lazy\" alt=\"\">
                            <div class=\"steps__card-content\">
                                <span class=\"steps__card-step\">Step 2</span>
                                <div class=\"steps__card-title\">Sign Up for An Account
                                    and Make a Deposit</div>
                                <p class=\"steps__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure
                                    pleasant gambling. You need to pick a gambling
                                    site that has all the necessary.</p>
                            </div>
                        </div>

                        <div class=\"steps__card\" aria-label=\"Step 3\">
                            <img class=\"steps__card-image\" src=\"/assets/images/steps/step.webp\" width=\"408\" height=\"171\" loading=\"lazy\" alt=\"\">
                            <div class=\"steps__card-content\">
                                <span class=\"steps__card-step\">Step 3</span>
                                <div class=\"steps__card-title\">Place a Bet</div>
                                <p class=\"steps__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure
                                    pleasant gambling. You need to pick a gambling
                                    site that has.</p>
                            </div>
                        </div>

                        <div class=\"steps__card\" aria-label=\"Step 4\">
                            <img class=\"steps__card-image\" src=\"/assets/images/steps/step.webp\" width=\"408\" height=\"171\" loading=\"lazy\" alt=\"\">
                            <div class=\"steps__card-content\">
                                <span class=\"steps__card-step\">Step 4</span>
                                <div class=\"steps__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"steps__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure
                                    pleasant gambling. You need to pick a gambling
                                    site that has all the.</p>
                            </div>
                        </div>

                        <div class=\"steps__card\" aria-label=\"Step 5\">
                            <img class=\"steps__card-image\" src=\"/assets/images/steps/step.webp\" width=\"408\" height=\"171\" loading=\"lazy\" alt=\"\">
                            <div class=\"steps__card-content\">
                                <span class=\"steps__card-step\">Step 5</span>
                                <div class=\"steps__card-title\">Sign Up for An Account
                                    and Make a Deposit</div>
                                <p class=\"steps__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure
                                    pleasant gambling.</p>
                            </div>
                        </div>
                    </div>
                    <div class=\"text--pt20\">
                        <h4 class=\"level__subheading\">
                            How to Deposit
                        </h4>
                        <p class=\"text text--accent text--left text--pb10\">
                            TrueFortune processes deposits after account verification. To deposit, follow these
                            steps:</p>
                        <ol class=\"list list--ordered\" aria-label=\"How to deposit numbered list\">
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Visit the Website:
                                Go to truefortune-casino.uk from any
                                device to begin sign up.</li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Click the
                                Registration Button: Find and select \"Join
                                Now\" or \"Register\" in the top-right corner of the homepage.</li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Enter Personal
                                Details: Provide email, username,
                                password, name, date of birth, and phone number for the
                                TrueFortune account.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Add Address
                                Information: Fill in residential address,
                                city, postal code, and country details.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Select Country and
                                Currency: Choose United Kingdom and
                                preferred currency such as GBP for convenience.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Complete the Sign
                                Up: Confirm age over 18, accept terms
                                and conditions, then submit to activate the account and qualify
                                for the True Fortune bonus.
                            </li>
                        </ol>
                    </div>

                    <div class=\"text--pt20\">
                        <h4 class=\"level__subheading\">
                            How to Withdraw
                        </h4>
                        <p class=\"text text--accent text--left text--pb10\">
                            TrueFortune processes withdrawals after account verification. To withdraw, follow
                            these
                            steps:</p>
                        <ol class=\"list list--ordered\" aria-label=\"How to withdraw numbered list\">
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Open Cashier: Select
                                wallet or payments section in the account menu.</li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Choose Method: Pick
                                an available withdrawal option, preferably the same as deposit.</li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Enter Amount: Input
                                value above minimum and within balance limits.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Submit Request:
                                Review details and confirm the withdrawal.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Complete ID Check:
                                Upload documents if first withdrawal requires verification.
                            </li>
                            <li class=\"list__item\"><span class=\"text--primary\">Step:</span> Wait for Approval:
                                Receive funds according to method processing time.
                            </li>
                        </ol>
                    </div>

                    <p class=\"text text--limited text--pt20\">In this article, we will explore the
                        key features of the
                        Aviator casino game, discuss its gameplay mechanics, user interface, and
                        overall
                        experience.</p>
                </div>',
        ],
        'review' => [
            'class' => 'review',
            'id' => 'mobile-app',
            'raw_html' => '<div class=\"review__inner\">
                    <div class=\"review__content\">
                        <h2 class=\"review__title\">
                            <span class=\"review__title-top\">Mobile</span>
                            <span class=\"review__title-bottom\">App</span>
                        </h2>

                        <p class=\"review__description\">In this article, we will explore the key
                            features of the Aviator casino game, discuss its gameplay
                            mechanics, user interface, and overall experience.</p>

                        <p class=\"review__accent\">In this article, we will explore the key
                            features of the Aviator casino game, discuss its gameplay
                            mechanics, user interface, and overall experience.</p>
                    </div>

                    <figure class=\"review__media\" aria-hidden=\"true\">
                        <img class=\"review__image\" src=\"/assets/images/phone-aviator.webp\" width=\"443\" height=\"428\" loading=\"lazy\" alt=\"\">
                        <figcaption class=\"level__caption\">Description Level Image</figcaption>
                    </figure>

                </div>',
        ],
        'characteristics' => [
            'class' => 'characteristics background--characteristics',
            'id' => 'characteristics',
            'raw_html' => '<div class=\"characteristics__inner\">
                    <h2 class=\"characteristics__title\">
                        <span class=\"characteristics__title-top\">The Basic
                            Characteristics</span>
                        <span class=\"characteristics__title-bottom title--accent\">of The Aviator
                            Game</span>
                    </h2>

                    <p class=\"characteristics__description\">In this article, we will explore the key
                        features of the Aviator casino game, discuss its gameplay mechanics,
                        user interface, and overall experience.</p>

                    <div class=\"characteristics__list\" aria-label=\"The basic characteristics list\">
                        <div class=\"characteristics__card\" role=\"group\" aria-label=\"Characteristics group 1\">
                            <table class=\"characteristics__rows\">
                                <tbody>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/pencil.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Official
                                                    Title</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Aviator</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/cubes.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">RTP</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            97%</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/chart.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Volatility</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Medium</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/airplane.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Theme</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Aviation</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/code.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Work
                                                    Algorithm</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Provably Fair</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class=\"characteristics__card\" role=\"group\" aria-label=\"Characteristics group 2\">
                            <table class=\"characteristics__rows\">
                                <tbody>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/folder-search.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Results
                                                    History</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Accessible for everyone</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/dollar.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">INR
                                                    support</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Yes</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/management.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Trial
                                                    Mode</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Availability depends on the
                                            casino</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/live-chat.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Live
                                                    Chat</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Yes (not available for demo
                                            gaming)</td>
                                    </tr>
                                    <tr class=\"characteristics__row\">
                                        <th class=\"characteristics__cell characteristics__cell--heading\" scope=\"row\">
                                            <div class=\"characteristics__row-heading\">
                                                <span class=\"characteristics__icon-wrapper\" aria-hidden=\"true\">
                                                    <img class=\"characteristics__icon\" src=\"/assets/svg/characteristics/star.svg\" width=\"22\" height=\"22\" alt=\"\">
                                                </span>
                                                <span class=\"characteristics__label\">Features</span>
                                            </div>
                                        </th>
                                        <td class=\"characteristics__cell characteristics__value\">
                                            Auto bet, auto cash-out, live
                                            statistics</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class=\"text text--limited\">In this article, we will explore the key features of the
                    Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                    experience.</p>',
        ],
        'pros' => [
            'class' => 'pros',
            'id' => 'pros',
            'raw_html' => '<div class=\"pros__inner\">
                    <h2 class=\"pros__title\">Pros and <span class=\"title--accent\">cons</span>
                    </h2>
                    <p class=\"pros__description\">In this article, we will explore the key features
                        of the Aviator casino game, discuss its gameplay mechanics, user
                        interface, and overall experience.</p>

                    <div class=\"pros__tables\" aria-label=\"Pros and cons lists\">
                        <ul class=\"pros__table\" aria-label=\"Pros list\">
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--plus\" aria-hidden=\"true\">✅</span><span class=\"pros__text\">Bono de 10 giros gratis en
                                    Aviator</span></li>
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--plus\" aria-hidden=\"true\">✅</span><span class=\"pros__text\">Generoso bono de
                                    bienvenida</span></li>
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--plus\" aria-hidden=\"true\">✅</span><span class=\"pros__text\">Atención al cliente
                                    multilingüe</span></li>
                        </ul>

                        <ul class=\"pros__table\" aria-label=\"Cons list\">
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--minus\" aria-hidden=\"true\">🚫</span><span class=\"pros__text\">Sin opción de retransmisión
                                    en directo</span></li>
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--minus\" aria-hidden=\"true\">🚫</span><span class=\"pros__text\">No hay chat en directo</span>
                            </li>
                            <li class=\"pros__row\"><span class=\"pros__emoji pros__emoji--minus\" aria-hidden=\"true\">🚫</span><span class=\"pros__text\">Puede requerir alta atención
                                    para jugar</span></li>
                        </ul>
                    </div>
                </div>
                <p class=\"text text--limited text--pt20\">In this article, we will explore the key
                    features of the
                    Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                    experience.</p>',
        ],
        'feature' => [
            'class' => 'feature',
            'id' => 'feature',
            'raw_html' => '<div class=\"feature__inner\">
                    <h2 class=\"feature__title\">All Feature Buy Slots:<br>Reviews, Videos &amp; <img class=\"feature__title-icon\" src=\"/assets/svg/video.svg\" width=\"70\" height=\"70\" loading=\"lazy\" alt=\"\"> Demo Play</h2>
                    <p class=\"feature__description\">When approaching the Aviator game, you have to
                        understand certain rules to maximize your potential wins and excitement
                        levels.</p>

                    <div class=\"feature__list\" aria-label=\"Feature cards\">
                        <div class=\"feature__card\" aria-label=\"Feature card 1\">
                            <img class=\"feature__card-image\" src=\"/assets/images/feature/feature-1.webp\" width=\"298\" height=\"172\" loading=\"lazy\" alt=\"\">
                            <div class=\"feature__card-content\">
                                <div class=\"feature__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"feature__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure</p>

                                <div class=\"feature__card-badge\" aria-label=\"1500\">
                                    <img class=\"feature__card-badge-icon\" src=\"/assets/svg/diamond.svg\" width=\"17\" height=\"16\" loading=\"lazy\" alt=\"\">
                                    <span class=\"feature__card-badge-text\">1500</span>
                                </div>
                            </div>
                        </div>

                        <div class=\"feature__card\" aria-label=\"Feature card 2\">
                            <img class=\"feature__card-image\" src=\"/assets/images/feature/feature-2.webp\" width=\"298\" height=\"172\" loading=\"lazy\" alt=\"\">
                            <div class=\"feature__card-content\">
                                <div class=\"feature__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"feature__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure</p>

                                <div class=\"feature__card-badge\" aria-label=\"1500\">
                                    <img class=\"feature__card-badge-icon\" src=\"/assets/svg/diamond.svg\" width=\"17\" height=\"16\" loading=\"lazy\" alt=\"\">
                                    <span class=\"feature__card-badge-text\">1500</span>
                                </div>
                            </div>
                        </div>

                        <div class=\"feature__card\" aria-label=\"Feature card 3\">
                            <img class=\"feature__card-image\" src=\"/assets/images/feature/feature-3.webp\" width=\"298\" height=\"172\" loading=\"lazy\" alt=\"\">
                            <div class=\"feature__card-content\">
                                <div class=\"feature__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"feature__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure</p>

                                <div class=\"feature__card-badge\" aria-label=\"1500\">
                                    <img class=\"feature__card-badge-icon\" src=\"/assets/svg/diamond.svg\" width=\"17\" height=\"16\" loading=\"lazy\" alt=\"\">
                                    <span class=\"feature__card-badge-text\">1500</span>
                                </div>
                            </div>
                        </div>

                        <div class=\"feature__card\" aria-label=\"Feature card 4\">
                            <img class=\"feature__card-image\" src=\"/assets/images/feature/feature-4.webp\" width=\"298\" height=\"172\" loading=\"lazy\" alt=\"\">
                            <div class=\"feature__card-content\">
                                <div class=\"feature__card-title\">Choose An Online Casino
                                </div>
                                <p class=\"feature__card-description\">Find a trustworthy
                                    online casino that hosts Aviator to ensure</p>

                                <div class=\"feature__card-badge\" aria-label=\"1500\">
                                    <img class=\"feature__card-badge-icon\" src=\"/assets/svg/diamond.svg\" width=\"17\" height=\"16\" loading=\"lazy\" alt=\"\">
                                    <span class=\"feature__card-badge-text\">1500</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class=\"text text--limited\">In this article, we will explore the key features
                        of the
                        Aviator casino game, discuss its gameplay mechanics, user interface, and
                        overall
                        experience.</p>
                </div>',
        ],
        'level' => [
            'class' => 'level',
            'id' => 'level',
            'raw_html' => '<div class=\"level__inner\">
                    <div class=\"level__content\">
                        <h2 class=\"level__title\">
                            <span class=\"level__title-top\">Second</span>
                            <span class=\"level__title-bottom\">Level Header</span>
                        </h2>

                        <p class=\"level__description\">In this article, we will explore the key
                            features of the Aviator
                            casino game.</p>

                        <h3 class=\"level__subtitle\">
                            Gameplay Mechanics
                        </h3>

                        <p class=\"level__accent\">In this article, we will explore the key
                            features of the Aviator
                            casino game, discuss its gameplay mechanics.</p>
                        <h4 class=\"level__subheading\">
                            List Gameplay
                        </h4>

                        <ul class=\"list list--bulleted\" aria-label=\"Gameplay bullet list\">
                            <li class=\"list__item\">We will explore the key features of the
                                Aviator</li>
                            <li class=\"list__item\">Casino game, discuss its gameplay
                                mechanics</li>
                            <li class=\"list__item\">User interface and overall experience
                            </li>
                        </ul>

                        <h4 class=\"level__subheading\">
                            Gameplay List Numbered
                        </h4>

                        <ol class=\"list list--ordered\" aria-label=\"Gameplay numbered list\">
                            <li class=\"list__item\">We will explore the key features of the
                                Aviator</li>
                            <li class=\"list__item\">Casino game, discuss its gameplay
                                mechanics</li>
                            <li class=\"list__item\">User interface and overall experience
                            </li>
                        </ol>

                        <h4 class=\"level__subheading\">
                            Gameplay Table
                        </h4>

                        <table class=\"table\" aria-label=\"Gameplay table\">
                            <tbody class=\"table__body\">
                                <tr class=\"table__row\">
                                    <th class=\"table__cell table__cell--label\" scope=\"row\"><span class=\"table__label\">Release
                                            Date</span></th>
                                    <td class=\"table__cell table__cell--value\"><span class=\"table__value\">December
                                            2020</span></td>
                                </tr>
                                <tr class=\"table__row\">
                                    <th class=\"table__cell table__cell--label\" scope=\"row\"><span class=\"table__label\">December</span>
                                    </th>
                                    <td class=\"table__cell table__cell--value\"><span class=\"table__value\">Video
                                            Slot</span></td>
                                </tr>
                                <tr class=\"table__row\">
                                    <th class=\"table__cell table__cell--label\" scope=\"row\"><span class=\"table__label\">Game
                                            Type</span></th>
                                    <td class=\"table__cell table__cell--value\"><span class=\"table__value\">Underwater
                                            World, Ocean, Fish</span></td>
                                </tr>
                                <tr class=\"table__row\">
                                    <th class=\"table__cell table__cell--label\" scope=\"row\"><span class=\"table__label\">Design</span>
                                    </th>
                                    <td class=\"table__cell table__cell--value\"><span class=\"table__value\">5</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <figure class=\"level__media\">
                        <img class=\"level__image\" src=\"/assets/images/phone-aviator.webp\" width=\"443\" height=\"428\" loading=\"lazy\" alt=\"Aviator game interface on a phone\">
                        <figcaption class=\"level__caption\">Description Level Image</figcaption>
                    </figure>
                </div>',
        ],
        'conclusion' => [
            'class' => 'conclusion',
            'id' => 'conclusion',
            'raw_html' => '<div class=\"conclusion__card\">
                    <div class=\"conclusion__inner\">
                        <h2 class=\"conclusion__title\">Conclusion</h2>
                        <p class=\"conclusion__description\">In conclusion, we want to say the
                            game’s success can be attributed to the high RTP of 97%,
                            user-friendly interface, availability of demo mode for practice,
                            and engaging aviation-themed design. Additionally, players have
                            access to various Aviator airplane game strategies, tips, and
                            tricks to enhance their gameplay experience and potentially
                            increase their winnings.However, while Aviator offers numerous
                            advantages.</p>

                        <div class=\"conclusion__actions\" aria-label=\"Conclusion actions\">
                            <a class=\"btn__cta btn__cta--hero\" href=\"#play-now\">Play
                                now!</a>

                            <div class=\"conclusion__rating\" aria-label=\"Rating 3.7 out of 5\">
                                <div class=\"conclusion__stars\" aria-hidden=\"true\">
                                    <img class=\"conclusion__star\" src=\"/assets/svg/stars/star.svg\" width=\"26\" height=\"26\" loading=\"lazy\" alt=\"\">
                                    <img class=\"conclusion__star\" src=\"/assets/svg/stars/star.svg\" width=\"26\" height=\"26\" loading=\"lazy\" alt=\"\">
                                    <img class=\"conclusion__star\" src=\"/assets/svg/stars/star.svg\" width=\"26\" height=\"26\" loading=\"lazy\" alt=\"\">
                                    <img class=\"conclusion__star\" src=\"/assets/svg/stars/star-half.svg\" width=\"26\" height=\"26\" loading=\"lazy\" alt=\"\">
                                    <img class=\"conclusion__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"26\" height=\"26\" loading=\"lazy\" alt=\"\">
                                </div>
                                <span class=\"conclusion__rating-value\">3.7</span>
                            </div>
                        </div>
                    </div>
                </div>',
        ],
        'other-reviews' => [
            'class' => 'other-reviews',
            'id' => 'other-reviews',
            'raw_html' => '<div class=\"other-reviews__inner\">
                    <div class=\"other-reviews__media\" aria-hidden=\"true\">
                        <img class=\"other-reviews__image\" src=\"/assets/images/kishor-singha.webp\" width=\"137\" height=\"161\" loading=\"lazy\" alt=\"\">

                    </div>

                    <div class=\"other-reviews__content\">
                        <h2 class=\"other-reviews__title\">Kishor Singha</h2>
                        <p class=\"other-reviews__description\">Kishor Singha is a renowned name
                            in the world of online gambling, particularly renowned for his
                            expertise in the Aviator crash game. Born in the vibrant city of
                            Delhi, India, Kishor\'s passion for gaming.</p>

                        <div class=\"other-reviews__actions\" aria-label=\"Actions and social media\">
                            <a class=\"btn__cta btn__cta--hero btn__cta--card\" href=\"#\">More...</a>

                            <div class=\"other-reviews__social\" aria-label=\"Social media links\">
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Facebook\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/facebook.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Instagram\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/instagram.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Twitter X\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/twitter-x.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"WhatsApp\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/whatsapp.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Telegram\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/telegram.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"VKontakte\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/vkontakte.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"TikTok\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/tiktok.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"YouTube\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/youtube.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Snapchat\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/snapchat.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                                <a class=\"other-reviews__social-link\" href=\"#\" aria-label=\"Discord\">
                                    <img class=\"other-reviews__social-icon\" src=\"/assets/svg/sosial-media/discord.svg\" width=\"32\" height=\"32\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>',
        ],
        'screenshots' => [
            'class' => 'screenshots',
            'id' => 'screenshots',
            'raw_html' => '<div class=\"screenshots__inner\">
                    <h2 class=\"screenshots__title\">Game <img class=\"screenshots__title-icon\" src=\"/assets/svg/image.svg\" width=\"44\" height=\"53\" loading=\"lazy\" alt=\"\"> Screenshots</h2>

                    <div class=\"screenshots__list\" data-lightbox-gallery aria-label=\"Game screenshots\">
                        <button class=\"screenshots__item\" type=\"button\" data-lightbox-item data-lightbox-src=\"/assets/images/screenshots/screenshot-1.webp\" data-lightbox-alt=\"Game screenshot 1\" aria-label=\"Open screenshot 1\">
                            <img class=\"screenshots__image\" src=\"/assets/images/screenshots/screenshot-1.webp\" width=\"234\" height=\"420\" loading=\"lazy\" alt=\"\">
                        </button>

                        <button class=\"screenshots__item\" type=\"button\" data-lightbox-item data-lightbox-src=\"/assets/images/screenshots/screenshot-2.webp\" data-lightbox-alt=\"Game screenshot 2\" aria-label=\"Open screenshot 2\">
                            <img class=\"screenshots__image\" src=\"/assets/images/screenshots/screenshot-2.webp\" width=\"233\" height=\"420\" loading=\"lazy\" alt=\"\">
                        </button>

                        <button class=\"screenshots__item\" type=\"button\" data-lightbox-item data-lightbox-src=\"/assets/images/screenshots/screenshot-3.webp\" data-lightbox-alt=\"Game screenshot 3\" aria-label=\"Open screenshot 3\">
                            <img class=\"screenshots__image\" src=\"/assets/images/screenshots/screenshot-3.webp\" width=\"233\" height=\"420\" loading=\"lazy\" alt=\"\">
                        </button>

                        <button class=\"screenshots__item\" type=\"button\" data-lightbox-item data-lightbox-src=\"/assets/images/screenshots/screenshot-4.webp\" data-lightbox-alt=\"Game screenshot 4\" aria-label=\"Open screenshot 4\">
                            <img class=\"screenshots__image\" src=\"/assets/images/screenshots/screenshot-4.webp\" width=\"234\" height=\"420\" loading=\"lazy\" alt=\"\">
                        </button>

                        <button class=\"screenshots__item\" type=\"button\" data-lightbox-item data-lightbox-src=\"/assets/images/screenshots/screenshot-5.webp\" data-lightbox-alt=\"Game screenshot 5\" aria-label=\"Open screenshot 5\">
                            <img class=\"screenshots__image\" src=\"/assets/images/screenshots/screenshot-5.webp\" width=\"233\" height=\"420\" loading=\"lazy\" alt=\"\">
                        </button>
                    </div>
                </div>',
        ],
        'faq' => [
            'class' => 'faq',
            'id' => 'faq',
            'raw_html' => '<div class=\"faq__inner\">
                    <h2 class=\"faq__title\">FAQ</h2>
                    <p class=\"faq__description\">In this article, we will explore the key features of
                        the Aviator casino game, discuss its gameplay mechanics, user interface,
                        and overall experience.</p>

                    <div class=\"faq__list\" aria-label=\"Frequently asked questions\">
                        <details class=\"faq__item\">
                            <summary class=\"faq__question\">
                                <span class=\"faq__question-text\">What is the Aviator
                                    game?</span>
                                <span class=\"faq__toggle\" aria-hidden=\"true\"></span>
                            </summary>
                            <div class=\"faq__answer\">
                                <p class=\"faq__answer-text\">Aviator is an arcade
                                    aviation game where the player must control a
                                    flying airplane. The goal of the game is to fly
                                    as many kilometers as possible while avoiding
                                    obstacles and other dangers. The player must
                                    maneuver the airplane using a keyboard or
                                    joystick to dodge obstacles and collect bonuses
                                    to earn extra points. The game can be played
                                    solo or in multiplayer mode.</p>
                            </div>
                        </details>

                        <details class=\"faq__item\">
                            <summary class=\"faq__question\">
                                <span class=\"faq__question-text\">How do I start playing
                                    Aviator?</span>
                                <span class=\"faq__toggle\" aria-hidden=\"true\"></span>
                            </summary>
                            <div class=\"faq__answer\">
                                <p class=\"faq__answer-text\">Aviator is an arcade
                                    aviation game where the player must control a
                                    flying airplane. The goal of the game is to fly
                                    as many kilometers as possible while avoiding
                                    obstacles and other dangers. The player must
                                    maneuver the airplane using a keyboard or
                                    joystick to dodge obstacles and collect bonuses
                                    to earn extra points. The game can be played
                                    solo or in multiplayer mode.</p>
                            </div>
                        </details>

                        <details class=\"faq__item\">
                            <summary class=\"faq__question\">
                                <span class=\"faq__question-text\">Can I play Aviator for
                                    real money?</span>
                                <span class=\"faq__toggle\" aria-hidden=\"true\"></span>
                            </summary>
                            <div class=\"faq__answer\">
                                <p class=\"faq__answer-text\">Aviator is an arcade
                                    aviation game where the player must control a
                                    flying airplane. The goal of the game is to fly
                                    as many kilometers as possible while avoiding
                                    obstacles and other dangers. The player must
                                    maneuver the airplane using a keyboard or
                                    joystick to dodge obstacles and collect bonuses
                                    to earn extra points. The game can be played
                                    solo or in multiplayer mode.</p>
                            </div>
                        </details>

                        <details class=\"faq__item\">
                            <summary class=\"faq__question\">
                                <span class=\"faq__question-text\">Is there a demo mode
                                    available?</span>
                                <span class=\"faq__toggle\" aria-hidden=\"true\"></span>
                            </summary>
                            <div class=\"faq__answer\">
                                <p class=\"faq__answer-text\">Aviator is an arcade
                                    aviation game where the player must control a
                                    flying airplane. The goal of the game is to fly
                                    as many kilometers as possible while avoiding
                                    obstacles and other dangers. The player must
                                    maneuver the airplane using a keyboard or
                                    joystick to dodge obstacles and collect bonuses
                                    to earn extra points. The game can be played
                                    solo or in multiplayer mode.</p>
                            </div>
                        </details>
                    </div>
                </div>',
        ],
        'footer' => [
            'class' => 'footer',
            'id' => 'footer',
            'raw_html' => '<div class=\"footer__inner\">
            <div class=\"footer__main\" aria-label=\"Footer navigation\">
                <div class=\"footer__col footer__col--brand\">
                    <div class=\"footer__logo\">
                        <a class=\"footer__logo-wrapper\" href=\"/\" aria-label=\"To the main page\">
                            <img src=\"/assets/images/logo/logo.webp\" width=\"141\" height=\"41\" loading=\"lazy\" alt=\"Aviator\">
                        </a>
                    </div>

                    <a class=\"btn__cta\" href=\"#play-now\">Play now!</a>
                </div>

                <nav class=\"footer__col footer__col--links\" aria-label=\"Footer column 1\">
                    <ul class=\"footer__links\">
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#where-to-play\">Where to
                                play</a></li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#characteristics\">Characteristics</a>
                        </li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#review\">Review</a></li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#symbols\">Symbols</a>
                        </li>
                    </ul>
                </nav>

                <nav class=\"footer__col footer__col--links\" aria-label=\"Footer column 2\">
                    <ul class=\"footer__links\">
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#gameplay\">Gameplay</a>
                        </li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#rtp\">RTP</a>
                        </li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#bonuses\">Bonuses</a>
                        </li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"/#conclusion\">Conclusion</a></li>
                    </ul>
                </nav>

                <nav class=\"footer__col footer__col--links\" aria-label=\"Footer column 3\">
                    <ul class=\"footer__links\">
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"terms-and-conditions.html\">Terms and
                                Conditions</a></li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"cookie-policy.html\">Cookie
                                Policy</a></li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"privacy-policy.html\">Privacy
                                Policy</a></li>
                        <li class=\"footer__item\"><a class=\"footer__link\" href=\"sitemap.html\">Sitemap</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class=\"footer__info\" aria-label=\"Footer disclaimer\">
                <svg class=\"footer__age-icon\" width=\"63\" height=\"63\" viewbox=\"0 0 34 34\" role=\"img\" aria-label=\"18+\">
                    <circle cx=\"17\" cy=\"17\" r=\"16\" fill=\"none\" stroke=\"var(--color-white)\" stroke-width=\"2\">
                    </circle>
                    <text x=\"17\" y=\"22\" text-anchor=\"middle\" font-size=\"14\" font-family=\"Inter, Arial, sans-serif\" font-weight=\"700\" fill=\"var(--color-white)\">18+</text>
                </svg>

                <div class=\"footer__info-text\">
                    <span class=\"footer__info-copy\">site.com is one of Spribe’s independent
                        affiliates. We are experts in presenting accurate, objective information
                        about cutting-edge casino games and iGaming products. Please go over our
                        terms and conditions and privacy policy. Please be aware that the
                        activities of users on third-party sites are not under the control of
                        our organization.</span>
                </div>

                <div class=\"footer__payments\" aria-label=\"Payment systems\">
                    <img class=\"footer__payment\" src=\"/assets/images/payment-systems/visa.webp\" width=\"80\" height=\"40\" loading=\"lazy\" alt=\"Visa\">
                    <img class=\"footer__payment\" src=\"/assets/images/payment-systems/mc.webp\" width=\"80\" height=\"40\" loading=\"lazy\" alt=\"Mastercard\">
                    <img class=\"footer__payment\" src=\"/assets/images/payment-systems/ae.webp\" width=\"80\" height=\"40\" loading=\"lazy\" alt=\"American Express\">
                    <img class=\"footer__payment\" src=\"/assets/images/payment-systems/paypal.webp\" width=\"80\" height=\"40\" loading=\"lazy\" alt=\"PayPal\">
                </div>
            </div>

            <div class=\"footer__copyright\" aria-label=\"Copyright\">© Copyright 2024-2026</div>
        </div>',
        ],
        'download' => [
            'class' => 'download background--characteristics',
            'id' => 'download',
            'raw_html' => '<div class=\"download__inner\">
                                        <h2 class=\"download__title\"><span class=\"title--accent\">Download</span> App</h2>
                                        <p class=\"download__description\">In this article, we will explore the
                                                key features of the Aviator casino game, discuss its gameplay
                                                mechanics, user interface, and overall experience.</p>

                                        <div class=\"download__list\" aria-label=\"Download options\">
                                                <div class=\"download__card\" aria-label=\"Android download\">
                                                        <img class=\"download__os-icon\" src=\"/assets/svg/os/android.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"Android\">

                                                        <div class=\"download__card-head\">
                                                                <h3 class=\"download__card-title\">Android</h3>
                                                                <p class=\"download__card-subtitle\">Description
                                                                        of the site in free form</p>
                                                        </div>

                                                        <ul class=\"download__specs\" aria-label=\"Android app details\">
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Official
                                                                                Title</span>
                                                                        <span class=\"download__spec-value\">Aviator</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">RTP</span>
                                                                        <span class=\"download__spec-value\">97%</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Volatility</span>
                                                                        <span class=\"download__spec-value\">Medium</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Theme</span>
                                                                        <span class=\"download__spec-value\">Aviation</span>
                                                                </li>
                                                                <li class=\"download__spec download__spec--last\">
                                                                        <span class=\"download__spec-label\">Work
                                                                                Algorithm</span>
                                                                        <span class=\"download__spec-value\">Provably
                                                                                Fair</span>
                                                                </li>
                                                        </ul>

                                                        <a class=\"btn__cta btn__cta--hero\" href=\"#\">Download</a>
                                                </div>

                                                <div class=\"download__card\" aria-label=\"iOS download\">
                                                        <img class=\"download__os-icon\" src=\"/assets/svg/os/ios.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"iOS\">

                                                        <div class=\"download__card-head\">
                                                                <h3 class=\"download__card-title\">IOS</h3>
                                                                <p class=\"download__card-subtitle\">Description
                                                                        of the site in free form</p>
                                                        </div>

                                                        <ul class=\"download__specs\" aria-label=\"iOS app details\">
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Official
                                                                                Title</span>
                                                                        <span class=\"download__spec-value\">Aviator</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">RTP</span>
                                                                        <span class=\"download__spec-value\">97%</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Volatility</span>
                                                                        <span class=\"download__spec-value\">Medium</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Theme</span>
                                                                        <span class=\"download__spec-value\">Aviation</span>
                                                                </li>
                                                                <li class=\"download__spec download__spec--last\">
                                                                        <span class=\"download__spec-label\">Work
                                                                                Algorithm</span>
                                                                        <span class=\"download__spec-value\">Provably
                                                                                Fair</span>
                                                                </li>
                                                        </ul>

                                                        <a class=\"btn__cta btn__cta--hero\" href=\"#\">Download</a>
                                                </div>

                                                <div class=\"download__card\" aria-label=\"Windows download\">
                                                        <img class=\"download__os-icon\" src=\"/assets/svg/os/windows.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"Windows\">

                                                        <div class=\"download__card-head\">
                                                                <h3 class=\"download__card-title\">Windows</h3>
                                                                <p class=\"download__card-subtitle\">Description
                                                                        of the site in free form</p>
                                                        </div>

                                                        <ul class=\"download__specs\" aria-label=\"Windows app details\">
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Official
                                                                                Title</span>
                                                                        <span class=\"download__spec-value\">Aviator</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">RTP</span>
                                                                        <span class=\"download__spec-value\">97%</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Volatility</span>
                                                                        <span class=\"download__spec-value\">Medium</span>
                                                                </li>
                                                                <li class=\"download__spec\">
                                                                        <span class=\"download__spec-label\">Theme</span>
                                                                        <span class=\"download__spec-value\">Aviation</span>
                                                                </li>
                                                                <li class=\"download__spec download__spec--last\">
                                                                        <span class=\"download__spec-label\">Work
                                                                                Algorithm</span>
                                                                        <span class=\"download__spec-value\">Provably
                                                                                Fair</span>
                                                                </li>
                                                        </ul>

                                                        <a class=\"btn__cta btn__cta--hero\" href=\"#\">Download</a>
                                                </div>
                                        </div>
                                </div>
                                <p class=\"text text--limited text--pt20\">In this article, we will explore the key
                                        features of the
                                        Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                                        experience.</p>',
        ],
        'installation' => [
            'class' => 'installation',
            'id' => 'installation',
            'raw_html' => '<div class=\"installation__inner\">
                                        <h2 class=\"installation__title\"><span class=\"title--accent\">Installation</span>
                                                Process</h2>
                                        <p class=\"installation__description\">In this article, we will explore the key
                                                features of the Aviator casino game, discuss its gameplay mechanics,
                                                user interface, and overall experience.</p>

                                        <div class=\"installation__steps\" aria-label=\"Installation process steps\">
                                                <div class=\"installation__step\">
                                                        <div class=\"installation__step-media\">
                                                                <img class=\"installation__step-image\" src=\"/assets/images/installation.webp\" width=\"207\" height=\"424\" loading=\"lazy\" alt=\"Download the Aviator app package\">
                                                                <div class=\"installation__step-content\">
                                                                        <div class=\"installation__step-title\">1. Step
                                                                                name</div>
                                                                        <p class=\"installation__step-text\">In this
                                                                                div, we will explore the key
                                                                                features of the Aviator casino game,
                                                                                discuss its gameplay</p>
                                                                </div>
                                                        </div>
                                                </div>

                                                <div class=\"installation__step\">
                                                        <div class=\"installation__step-media\">
                                                                <img class=\"installation__step-image\" src=\"/assets/images/installation.webp\" width=\"207\" height=\"424\" loading=\"lazy\" alt=\"Open the installer on your device\">
                                                                <div class=\"installation__step-content\">
                                                                        <div class=\"installation__step-title\">2. Step
                                                                                name</div>
                                                                        <p class=\"installation__step-text\">In this
                                                                                div, we will explore the key
                                                                                features of the Aviator casino game,
                                                                                discuss its gameplay</p>
                                                                </div>
                                                        </div>
                                                </div>

                                                <div class=\"installation__step\">
                                                        <div class=\"installation__step-media\">
                                                                <img class=\"installation__step-image\" src=\"/assets/images/installation.webp\" width=\"207\" height=\"424\" loading=\"lazy\" alt=\"Allow permissions for installation\">
                                                                <div class=\"installation__step-content\">
                                                                        <div class=\"installation__step-title\">3. Step
                                                                                name</div>
                                                                        <p class=\"installation__step-text\">In this
                                                                                div, we will explore the key
                                                                                features of the Aviator casino game,
                                                                                discuss its gameplay</p>
                                                                </div>
                                                        </div>
                                                </div>

                                                <div class=\"installation__step\">
                                                        <div class=\"installation__step-media\">
                                                                <img class=\"installation__step-image\" src=\"/assets/images/installation.webp\" width=\"207\" height=\"424\" loading=\"lazy\" alt=\"Launch the app and sign in\">
                                                                <div class=\"installation__step-content\">
                                                                        <div class=\"installation__step-title\">4. Step
                                                                                name</div>
                                                                        <p class=\"installation__step-text\">In this
                                                                                article, we will explore the key
                                                                                features of the Aviator casino game,
                                                                                discuss its gameplay</p>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>

                                        <h3 class=\"installation__issues-title\">Possible Issues</h3>
                                        <table class=\"installation__issues\" aria-label=\"Possible installation issues\">
                                                <tbody>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">Error
                                                                        during installation</th>
                                                                <td class=\"installation__issue-text\">Make sure your
                                                                        device meets the requirements. Update your
                                                                        system if needed.</td>
                                                        </tr>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">
                                                                        Aviator
                                                                        bet app download fails or is slow</th>
                                                                <td class=\"installation__issue-text\">Check your internet
                                                                        connection. Try the online mode if the download
                                                                        is too slow.</td>
                                                        </tr>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">
                                                                        Software not working with your system</th>
                                                                <td class=\"installation__issue-text\">Make sure the
                                                                        software is compatible with your system. Find a
                                                                        different version if needed.</td>
                                                        </tr>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">
                                                                        Installation stops halfway</th>
                                                                <td class=\"installation__issue-text\">Restart your device
                                                                        and try again. Check if you have enough
                                                                        space.</td>
                                                        </tr>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">
                                                                        Corrupted file</th>
                                                                <td class=\"installation__issue-text\">Turn off or
                                                                        uninstall any other software causing problems.
                                                                        Turn it back on after installation.</td>
                                                        </tr>
                                                        <tr class=\"installation__issue\">
                                                                <th class=\"installation__issue-title\" scope=\"row\">
                                                                        Permission issues</th>
                                                                <td class=\"installation__issue-text\">Run the installer
                                                                        with the right permissions.</td>
                                                        </tr>
                                                </tbody>
                                        </table>
                                </div>
                                <p class=\"text text--limited text--pt20\">In this article, we will explore the key
                                        features of the
                                        Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                                        experience.</p>',
        ],
        'benefits' => [
            'class' => 'benefits',
            'id' => 'benefits',
            'raw_html' => '<div class=\"benefits__card\">
                                        <div class=\"benefits__content\">
                                                <h2 class=\"benefits__title\">Benefits of Using the Application</h2>
                                                <p class=\"benefits__description\">The Aviator App has lots of great
                                                        things for gamers. It is simple to use and fun.</p>

                                                <div class=\"benefits__list\" aria-label=\"Benefits of using the Aviator application\">
                                                        <div class=\"benefits__item\">
                                                                <img class=\"benefits__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                <div class=\"benefits__item-content\">
                                                                        <div class=\"benefits__item-title\">Works with
                                                                        </div>
                                                                        <p class=\"benefits__item-text\">Our app works
                                                                                even if the internet is slow. This helps
                                                                                you keep playing without stopping.</p>
                                                                </div>
                                                        </div>

                                                        <div class=\"benefits__item\">
                                                                <img class=\"benefits__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                <div class=\"benefits__item-content\">
                                                                        <div class=\"benefits__item-title\">Works with
                                                                        </div>
                                                                        <p class=\"benefits__item-text\">Our app works
                                                                                even if the internet is slow. This helps
                                                                                you keep playing without stopping.</p>
                                                                </div>
                                                        </div>

                                                        <div class=\"benefits__item\">
                                                                <img class=\"benefits__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                <div class=\"benefits__item-content\">
                                                                        <div class=\"benefits__item-title\">Works with
                                                                        </div>
                                                                        <p class=\"benefits__item-text\">Our app works
                                                                                even if the internet is slow. This helps
                                                                                you keep playing without stopping.</p>
                                                                </div>
                                                        </div>

                                                        <div class=\"benefits__item\">
                                                                <img class=\"benefits__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                <div class=\"benefits__item-content\">
                                                                        <div class=\"benefits__item-title\">Works with
                                                                        </div>
                                                                        <p class=\"benefits__item-text\">Our app works
                                                                                even if the internet is slow. This helps
                                                                                you keep playing without stopping.</p>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>
                                        <p class=\"text text--limited text--pt20\">In this article, we will explore the
                                                key features
                                                of the
                                                Aviator casino game, discuss its gameplay mechanics, user interface, and
                                                overall
                                                experience.</p>
                                </div>',
        ],
        'game' => [
            'class' => 'game',
            'id' => 'game',
            'raw_html' => '<div class=\"game__card\">
                                        <div class=\"game__inner\">
                                                <div class=\"game__content\">
                                                        <h2 class=\"game__title\">
                                                                <span class=\"game__title-line\">Game</span>
                                                                <span class=\"game__title-line\">
                                                                        <img class=\"game__title-icon\" src=\"/assets/svg/image.svg\" width=\"44\" height=\"53\" loading=\"lazy\" alt=\"\">
                                                                        <span class=\"title--accent\">Screenshots</span>
                                                                </span>
                                                        </h2>

                                                        <p class=\"game__description\">Return to Player (RTP for short) is
                                                                a mathematical value that indicates what proportion of
                                                                bets will be repaid to the player over time. The
                                                                Aviator game has a 97% RTP, meaning that for every
                                                                2,000 INR spent, you can expect to get a payout of
                                                                1,940 INR. Remember that this value cannot be true for
                                                                everyone or within a specific timeframe. The
                                                                calculations apply to long-term gambling; moreover, it
                                                                is about all players.</p>
                                                </div>

                                                <div class=\"game__slider\" data-game-slider>
                                                        <div class=\"game__track\" data-game-track>
                                                                <figure class=\"game__slide\" data-game-slide>
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"Aviator game screenshot 1\">
                                                                </figure>
                                                                <figure class=\"game__slide\" data-game-slide>
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"Aviator game screenshot 2\">
                                                                </figure>
                                                                <figure class=\"game__slide\" data-game-slide>
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"Aviator game screenshot 3\">
                                                                </figure>
                                                                <figure class=\"game__slide\" data-game-slide>
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"Aviator game screenshot 4\">
                                                                </figure>
                                                                <figure class=\"game__slide game__slide--clone\" aria-hidden=\"true\">
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"\">
                                                                </figure>
                                                                <figure class=\"game__slide game__slide--clone\" aria-hidden=\"true\">
                                                                        <img class=\"game__slide-image\" src=\"/assets/images/installation.webp\" width=\"253\" height=\"532\" loading=\"lazy\" alt=\"\">
                                                                </figure>
                                                        </div>

                                                        <button class=\"game__next\" type=\"button\" aria-label=\"Next screenshot\" data-game-next>
                                                                <span class=\"game__next-icon\" aria-hidden=\"true\">→</span>
                                                        </button>
                                                </div>
                                        </div>
                                </div>',
        ],
        'authors' => [
            'class' => 'authors',
            'id' => 'authors',
            'raw_html' => '<h2 class=\"visually-hidden\">Authors</h2>
                                <div class=\"authors__inner\">
                                        <div class=\"authors__row\">
                                                <h3 class=\"authors__title\">Education</h3>
                                                <div class=\"authors__content\">
                                                        <p class=\"authors__text\">I am from Mumbai, India. Since I was a
                                                                teenager, I have been keen on computer technology.
                                                                Following this love, I studied at Mumbai University,
                                                                Department of Information Technology, and graduated from
                                                                it in 2018 with a bachelor’s degree. During my studies,
                                                                I had a part-time job as an editor and copywriter for a
                                                                review site about gambling.</p>
                                                        <p class=\"authors__text\">Progressively, I became interested in
                                                                online casino games. It began during my time at Mumbai
                                                                University when I stumbled upon the slots and table
                                                                games
                                                                and spent countless hours in them. Then, when crash
                                                                games became popular, I focused on them and Aviator game
                                                                in particular.</p>
                                                </div>
                                        </div>

                                        <div class=\"authors__row\">
                                                <h3 class=\"authors__title\">Experience</h3>
                                                <div class=\"authors__content\">
                                                        <p class=\"authors__text\">Besides the working experience of 7
                                                                years
                                                                as a copywriter, editor, and editor-in-chief, I have
                                                                also taken part in some iGaming conventions like Asean
                                                                Gaming Summit 2023 and SIGMA 2023.</p>
                                                        <p class=\"authors__lead\">My working strengths extend to:</p>
                                                        <ul class=\"list list--bulleted authors__list\">
                                                                <li class=\"list__item\">Betting strategies;</li>
                                                                <li class=\"list__item\">Casino analysis;</li>
                                                                <li class=\"list__item\">Responsible gambling.</li>
                                                        </ul>
                                                        <p class=\"authors__text\">I do my best to help you to fill in the
                                                                gaps in your knowledge about gambling. The content I
                                                                produce is packed with valuable insights into the games,
                                                                exciting tips and tricks, and meaningful rules and
                                                                mechanics explanations.</p>

                                                        <div class=\"authors__social\" aria-label=\"Author social links\">
                                                                <a class=\"authors__social-link\" href=\"#\" aria-label=\"Author profile link\">
                                                                        <img class=\"authors__social-icon\" src=\"/assets/svg/sosial-media/link.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                </a>
                                                                <a class=\"authors__social-link\" href=\"#\" aria-label=\"Author Instagram profile\">
                                                                        <img class=\"authors__social-icon\" src=\"/assets/svg/sosial-media/instagram-color.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                </a>
                                                                <a class=\"authors__social-link\" href=\"#\" aria-label=\"Author Facebook profile\">
                                                                        <img class=\"authors__social-icon\" src=\"/assets/svg/sosial-media/facebook-color.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                                                </a>
                                                        </div>
                                                </div>
                                        </div>
                                </div>',
        ],
        'bonuses' => [
            'class' => 'bonuses',
            'id' => 'bonuses',
            'raw_html' => '<div class=\"bonuses__inner\">
                    <h2 class=\"bonuses__title\">Bonuses and<br>promo <img class=\"bonuses__title-icon\" src=\"/assets/svg/percent.svg\" width=\"60\" height=\"60\" loading=\"lazy\" alt=\"\"> <span class=\"title--accent\">codes</span>
                    </h2>
                    <p class=\"bonuses__description\">By making use of exclusive bonus codes, you may
                        increase your initial betting budget for the Aviator game. Because of
                        that, you will be able to take more risks and that might help you walk
                        away with larger sums in the bank.</p>

                    <div class=\"bonuses__list\" aria-label=\"Bonuses and promo codes cards\">
                        <div class=\"card card--bonus\" aria-label=\"Bonus card 1\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/pari-match.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"PariMatch\">
                            <div class=\"card__title\">PariMatch</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>

                        <div class=\"card card--bonus\" aria-label=\"Bonus card 2\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/1win.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"1win\">
                            <div class=\"card__title\">1win</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>

                        <div class=\"card card--bonus\" aria-label=\"Bonus card 3\">
                            <img class=\"card__logo\" src=\"/assets/images/casino/2bet.webp\" width=\"170\" height=\"63\" loading=\"lazy\" alt=\"2bet\">
                            <div class=\"card__title\">2bet</div>
                            <p class=\"card__subtitle\">Description of the site in free form
                            </p>

                            <div class=\"card__divider\" aria-hidden=\"true\"></div>

                            <div class=\"card__actions\">
                                <button class=\"btn btn__promo-code\" type=\"button\" aria-label=\"Promo code\">
                                    <span class=\"btn__promo-code-text\">FREE30</span>
                                    <span class=\"btn__promo-code-icon\" aria-hidden=\"true\"></span>
                                </button>

                                <a class=\"btn btn__cta btn__cta--promo\" href=\"#\" aria-label=\"Use promo code\">
                                    <span class=\"btn__cta-text\">Use promo
                                        code</span>
                                    <img class=\"btn__cta-gift\" src=\"/assets/svg/gift.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class=\"text text--limited text--pt20\">In this article, we will explore the
                        key features
                        of the
                        Aviator casino game, discuss its gameplay mechanics, user interface, and
                        overall
                        experience.</p>
                </div>',
        ],
        'strategies' => [
            'class' => 'strategies',
            'id' => 'strategies',
            'raw_html' => '<div class=\"strategies__inner\">
                    <h2 class=\"strategies__title\">Best Common <span class=\"title--accent\">Strategies</span> for Aviator
                        Gambling</h2>
                    <p class=\"strategies__description\">In this article, we will explore the key
                        features of the Aviator
                        casino game, discuss its gameplay mechanics, user interface, and overall
                        experience.</p>

                    <ul class=\"strategies__list\">
                        <li class=\"strategies__item\">
                            <img class=\"strategies__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                            <div class=\"strategies__content\">
                                <h3 class=\"strategies__card-title\">Tactics for a Single
                                    Bet</h3>
                                <p class=\"strategies__card-text\">For players in India
                                    who prefer simplicity and
                                    low risk, the one-bet strategy is recommended.
                                    Choose a small amount to
                                    bet on a specific coefficient and do not change.
                                </p>
                            </div>
                        </li>
                        <li class=\"strategies__item\">
                            <img class=\"strategies__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                            <div class=\"strategies__content\">
                                <h3 class=\"strategies__card-title\">Works with</h3>
                                <p class=\"strategies__card-text\">For players in India
                                    who prefer simplicity and
                                    low risk, the one-bet strategy is recommended.
                                    Choose a small amount to
                                    bet on a specific coefficient and do not change.
                                </p>
                            </div>
                        </li>
                        <li class=\"strategies__item\">
                            <img class=\"strategies__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                            <div class=\"strategies__content\">
                                <h3 class=\"strategies__card-title\">Works with</h3>
                                <p class=\"strategies__card-text\">For players in India
                                    who prefer simplicity and
                                    low risk, the one-bet strategy is recommended.
                                    Choose a small amount to
                                    bet on a specific coefficient and do not change.
                                </p>
                            </div>
                        </li>
                        <li class=\"strategies__item\">
                            <img class=\"strategies__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                            <div class=\"strategies__content\">
                                <h3 class=\"strategies__card-title\">Tactics for a Single
                                    Bet</h3>
                                <p class=\"strategies__card-text\">For players in India
                                    who prefer simplicity and
                                    low risk, the one-bet strategy is recommended.
                                    Choose a small amount to
                                    bet on a specific coefficient and do not change.
                                </p>
                            </div>
                        </li>
                        <li class=\"strategies__item\">
                            <img class=\"strategies__icon\" src=\"/assets/svg/approval.svg\" width=\"50\" height=\"50\" loading=\"lazy\" alt=\"\">
                            <div class=\"strategies__content\">
                                <h3 class=\"strategies__card-title\">Tactics for a Single
                                    Bet</h3>
                                <p class=\"strategies__card-text\">For players in India
                                    who prefer simplicity and
                                    low risk, the one-bet strategy is recommended.
                                    Choose a small amount to
                                    bet on a specific coefficient and do not change.
                                    For players in India
                                    who prefer simplicity and low risk, the one-bet
                                    strategy is recommended.
                                    Choose a small amount to bet on a specific
                                    coefficient and do not
                                    change.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <p class=\"text text--limited text--pt20\">In this article, we will explore the key
                    features of the
                    Aviator casino game, discuss its gameplay mechanics, user interface, and overall
                    experience.</p>',
        ],
        'promo' => [
            'class' => 'promo',
            'id' => 'promo',
            'raw_html' => '<div class=\"promo__inner\">
                    <h2 class=\"promo__title\">Using Promo Codes for Maximum Profits in Aviator</h2>
                    <p class=\"promo__description\">Each casino provides special codes that grant
                        different benefits. Using the best strategy to win Aviator, players can
                        maximize their rewards by applying the right promo codes at the optimal
                        time.</p>

                    <div class=\"promo__table\">
                        <div class=\"promo__header\">
                            <div class=\"promo__header-cell promo__header-cell--casino\">
                                Casino</div>
                            <div class=\"promo__header-cell promo__header-cell--code\">Promo
                                code</div>
                            <div class=\"promo__header-cell promo__header-cell--bonus\">Bonus
                            </div>
                        </div>

                        <div class=\"promo__row\">
                            <div class=\"promo__cell promo__cell--casino\">
                                <img class=\"promo__casino-logo\" src=\"/assets/images/casino/pari-match.webp\" width=\"40\" height=\"15\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__casino-name\">Mostbet</span>
                            </div>
                            <div class=\"promo__cell promo__cell--code\">
                                <img class=\"promo__icon\" src=\"/assets/svg/paste.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__code\">77DGT83</span>
                            </div>
                            <div class=\"promo__cell promo__cell--bonus\">
                                <span class=\"promo__bonus\">Check your internet. Try
                                    using the online деп tools Aviator free download
                                    when.</span>
                            </div>
                        </div>

                        <div class=\"promo__row promo__row--white\">
                            <div class=\"promo__cell promo__cell--casino\">
                                <img class=\"promo__casino-logo\" src=\"/assets/images/casino/2bet.webp\" width=\"40\" height=\"15\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__casino-name\">1xBet</span>
                            </div>
                            <div class=\"promo__cell promo__cell--code\">
                                <img class=\"promo__icon\" src=\"/assets/svg/paste.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__code\">77DGT83</span>
                            </div>
                            <div class=\"promo__cell promo__cell--bonus\">
                                <span class=\"promo__bonus\">МаКе sure your device meets
                                    the needs. Update your system if needed.</span>
                            </div>
                        </div>

                        <div class=\"promo__row\">
                            <div class=\"promo__cell promo__cell--casino\">
                                <img class=\"promo__casino-logo\" src=\"/assets/images/casino/1win.webp\" width=\"40\" height=\"15\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__casino-name\">1Win</span>
                            </div>
                            <div class=\"promo__cell promo__cell--code\">
                                <img class=\"promo__icon\" src=\"/assets/svg/paste.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <span class=\"promo__code\">77DGT83</span>
                            </div>
                            <div class=\"promo__cell promo__cell--bonus\">
                                <span class=\"promo__bonus\">Get exclusive bonus when you
                                    register using this promo code.</span>
                            </div>
                        </div>
                    </div>

                    <p class=\"promo__footer-text\">Choosing the right promo code can drastically
                        affect your gameplay by providing more resources to play with.</p>
                </div>',
        ],
        'feedback' => [
            'class' => 'feedback',
            'id' => 'feedback',
            'raw_html' => '<div class=\"feedback__inner\">
                    <h2 class=\"faq__title\">Feedback</h2>

                    <div class=\"feedback__intro\" aria-label=\"Feedback summary\">
                        <div class=\"feedback__score\" aria-label=\"Overall rating 4,7 out of 5\">
                            <img class=\"feedback__score-icon\" src=\"/assets/svg/stars/star.svg\" width=\"70\" height=\"70\" loading=\"lazy\" alt=\"\">
                            <span class=\"feedback__score-value\">4,7</span>
                        </div>

                        <p class=\"feedback__description\">In this article, we will explore the
                            key features of the Aviator casino game, discuss its gameplay
                        </p>
                    </div>

                    <div class=\"feedback__summary\" aria-label=\"Rating summary\">
                        <div class=\"feedback__chip\" aria-label=\"5 stars: 24 reviews\">
                            <div class=\"feedback__chip-stars\" aria-hidden=\"true\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                            </div>
                            <span class=\"feedback__chip-value\">24</span>
                        </div>

                        <div class=\"feedback__chip\" aria-label=\"4 stars: 12 reviews\">
                            <div class=\"feedback__chip-stars\" aria-hidden=\"true\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                            </div>
                            <span class=\"feedback__chip-value\">12</span>
                        </div>

                        <div class=\"feedback__chip\" aria-label=\"3 stars: 5 reviews\">
                            <div class=\"feedback__chip-stars\" aria-hidden=\"true\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                            </div>
                            <span class=\"feedback__chip-value\">5</span>
                        </div>

                        <div class=\"feedback__chip feedback__chip--muted\" aria-label=\"2 stars: 2 reviews\">
                            <div class=\"feedback__chip-stars\" aria-hidden=\"true\">
                                <img class=\"feedback__chip-star feedback__chip-star--dark\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                                <img class=\"feedback__chip-star feedback__chip-star--dark\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                            </div>
                            <span class=\"feedback__chip-value\">2</span>
                        </div>

                        <div class=\"feedback__chip feedback__chip--muted\" aria-label=\"1 star: 1 review\">
                            <div class=\"feedback__chip-stars\" aria-hidden=\"true\">
                                <img class=\"feedback__chip-star feedback__chip-star--dark\" src=\"/assets/svg/stars/star.svg\" width=\"16\" height=\"16\" loading=\"lazy\" alt=\"\">
                            </div>
                            <span class=\"feedback__chip-value\">1</span>
                        </div>
                    </div>

                    <div class=\"feedback__list\" aria-label=\"Player feedback\">
                        <div class=\"feedback__card\">
                            <header class=\"feedback__card-head\">
                                <div class=\"feedback__author\">

                                    <div class=\"feedback__avatar feedback__avatar--blue\" aria-hidden=\"true\">
                                        <span class=\"feedback__avatar-initials\">AR</span>
                                    </div>
                                    <div class=\"feedback__author-meta\">
                                        <div class=\"feedback__author-name\">
                                            Aleksander R.</div>
                                        <time class=\"feedback__author-time\" datetime=\"2026-03-07\">6 days
                                            ago</time>
                                    </div>
                                </div>

                                <div class=\"feedback__card-stars\" aria-label=\"Rating: 5 out of 5\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                </div>
                            </header>

                            <div class=\"feedback__card-body\">
                                <div class=\"feedback__card-title\">Whenever I need
                                    assistance</div>
                                <p class=\"feedback__card-text\">Whenever I need
                                    assistance, Aviator-Game. Its support team is
                                    always there. They\'re friendly, professional,
                                    and truly care about players. It\'s reassuring to
                                    know I have a team of real experts by my side,
                                    as Aviator player!</p>
                            </div>
                        </div>

                        <div class=\"feedback__card feedback__card--tall\">
                            <header class=\"feedback__card-head\">
                                <div class=\"feedback__author\">
                                    <div class=\"feedback__avatar\" aria-hidden=\"true\">
                                        <img class=\"feedback__avatar-image\" src=\"/assets/images/feedback.webp\" width=\"60\" height=\"60\" loading=\"lazy\" alt=\"\">
                                    </div>
                                    <div class=\"feedback__author-meta\">
                                        <div class=\"feedback__author-name\">
                                            Aleksander R.</div>
                                        <time class=\"feedback__author-time\" datetime=\"2026-03-07\">6 days
                                            ago</time>
                                    </div>
                                </div>

                                <div class=\"feedback__card-stars\" aria-label=\"Rating: 4 out of 5\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                </div>
                            </header>

                            <div class=\"feedback__card-body\">
                                <div class=\"feedback__card-title\">Best support team
                                </div>
                                <p class=\"feedback__card-text\">Whenever I need
                                    assistance, Aviator-Game. Its support team is
                                    always there. They\'re friendly, professional,
                                    and truly care about players. It\'s reassuring to
                                    know I have a team of real experts by my side,
                                    as Aviator player! Whenever I need assistance,
                                    Aviator-Game. Its support team is always there.
                                    They\'re friendly, professional, and truly care
                                    about players. It\'s reassuring to know I have a
                                    team of real experts by my side, as Aviator
                                    player!</p>
                            </div>
                        </div>

                        <div class=\"feedback__card\">
                            <header class=\"feedback__card-head\">
                                <div class=\"feedback__author\">
                                    <div class=\"feedback__avatar\" aria-hidden=\"true\">
                                        <img class=\"feedback__avatar-image\" src=\"/assets/images/feedback.webp\" width=\"60\" height=\"60\" loading=\"lazy\" alt=\"\">
                                    </div>
                                    <div class=\"feedback__author-meta\">
                                        <div class=\"feedback__author-name\">
                                            Aleksander R.</div>
                                        <time class=\"feedback__author-time\" datetime=\"2026-03-07\">6 days
                                            ago</time>
                                    </div>
                                </div>

                                <div class=\"feedback__card-stars\" aria-label=\"Rating: 3 out of 5\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                </div>
                            </header>

                            <div class=\"feedback__card-body\">
                                <div class=\"feedback__card-title\">Quick payouts</div>
                                <p class=\"feedback__card-text\">Whenever I need
                                    assistance, Aviator-Game. Its support team is
                                    always there. They\'re friendly, professional,
                                    and truly care about players.</p>
                            </div>
                        </div>

                        <div class=\"feedback__card\">
                            <header class=\"feedback__card-head\">
                                <div class=\"feedback__author\">
                                    <div class=\"feedback__avatar feedback__avatar--green\" aria-hidden=\"true\">
                                        <span class=\"feedback__avatar-initials\">AR</span>
                                    </div>
                                    <div class=\"feedback__author-meta\">
                                        <div class=\"feedback__author-name\">
                                            Aleksander R.</div>
                                        <time class=\"feedback__author-time\" datetime=\"2026-03-07\">6 days
                                            ago</time>
                                    </div>
                                </div>

                                <div class=\"feedback__card-stars\" aria-label=\"Rating: 5 out of 5\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                </div>
                            </header>

                            <div class=\"feedback__card-body\">
                                <div class=\"feedback__card-title\">Smooth gameplay</div>
                                <p class=\"feedback__card-text\">Whenever I need
                                    assistance, Aviator-Game. Its support team is
                                    always there. They\'re friendly, professional,
                                    and truly care about players. It\'s reassuring to
                                    know I have a team of real experts by my side,
                                    as Aviator player!</p>
                            </div>
                        </div>

                        <div class=\"feedback__card\">
                            <header class=\"feedback__card-head\">
                                <div class=\"feedback__author\">
                                    <div class=\"feedback__avatar feedback__avatar--blue-deep\" aria-hidden=\"true\">
                                        <span class=\"feedback__avatar-initials\">AR</span>
                                    </div>
                                    <div class=\"feedback__author-meta\">
                                        <div class=\"feedback__author-name\">
                                            Aleksander R.</div>
                                        <time class=\"feedback__author-time\" datetime=\"2026-03-07\">6 days
                                            ago</time>
                                    </div>
                                </div>

                                <div class=\"feedback__card-stars\" aria-label=\"Rating: 3 out of 5\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                    <img class=\"feedback__star\" src=\"/assets/svg/stars/star-empty.svg\" width=\"24\" height=\"24\" loading=\"lazy\" alt=\"\">
                                </div>
                            </header>

                            <div class=\"feedback__card-body\">
                                <div class=\"feedback__card-title\">Whenever I need
                                    assistance</div>
                                <p class=\"feedback__card-text\">Whenever I need
                                    assistance, Aviator-Game. Its support team is
                                    always there. They\'re friendly, professional,
                                    and truly care about players. It\'s reassuring to
                                    know I have a team of real experts by my side,
                                    as Aviator player!</p>
                            </div>
                        </div>
                    </div>
                    <div class=\"feedback__btn\">
                        <a class=\"btn__cta btn__cta--hero btn__cta--card\" href=\"#\">View all
                            reviews</a>
                    </div>
                </div>',
        ],
        'form' => [
            'class' => 'form background--characteristics mb50',
            'id' => 'form',
            'raw_html' => '<div class=\"form__inner\">
                                        <h2 class=\"form__title\">Contact Form</h2>
                                        <p class=\"form__description\">In this article, we will explore the key features
                                                of the Aviator casino game, discuss its gameplay mechanics, user
                                                interface, and overall experience.</p>

                                        <form class=\"form__body\" action=\"#\" method=\"post\" aria-label=\"Contact form\">
                                                <div class=\"form__fields\">
                                                        <label class=\"form__field form__field--name\">
                                                                <img class=\"form__icon\" src=\"/assets/svg/user.svg\" width=\"40\" height=\"40\" loading=\"lazy\" alt=\"\">
                                                                <input class=\"form__control\" type=\"text\" name=\"review-name\" placeholder=\"Your name\" autocomplete=\"name\" aria-label=\"Your name\">
                                                        </label>

                                                        <label class=\"form__field form__field--name\">
                                                                <img class=\"form__icon\" src=\"/assets/svg/email.svg\" width=\"40\" height=\"40\" loading=\"lazy\" alt=\"\">
                                                                <input class=\"form__control\" type=\"email\" name=\"review-email\" placeholder=\"Your email\" autocomplete=\"email\" inputmode=\"email\" required aria-label=\"Your email\">
                                                        </label>

                                                        <label class=\"form__field form__field--review\">
                                                                <img class=\"form__icon\" src=\"/assets/svg/chat.svg\" width=\"40\" height=\"40\" loading=\"lazy\" alt=\"\">
                                                                <textarea class=\"form__control form__control--textarea\" name=\"review-text\" placeholder=\"Your message\" rows=\"3\" aria-label=\"Your message \"></textarea>
                                                        </label>
                                                </div>

                                                <div class=\"form__actions\">
                                                        <button class=\"btn__cta btn__cta--hero btn__cta--form\" type=\"button\">Send</button>
                                                        <div class=\"form__captcha\" aria-hidden=\"true\">
                                                                <span class=\"form__captcha-check\"></span>
                                                                <span class=\"form__captcha-text\">I\'m not a robot</span>
                                                                <span class=\"form__captcha-brand\">
                                                                        <span class=\"form__captcha-logo\"></span>
                                                                        <span class=\"form__captcha-name\">reCAPTCHA</span>
                                                                        <span class=\"form__captcha-links\">Privacy -
                                                                                Terms</span>
                                                                </span>
                                                        </div>
                                                </div>
                                        </form>
                                </div>',
        ],
        'sitemap' => [
            'class' => 'sitemap',
            'id' => 'sitemap',
            'raw_html' => '<div class=\"sitemap__inner\">
                                        <div class=\"sitemap__list\">
                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"/\">Home</a>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"app.html\">Download App</a>
                                                </div>

                                                <div class=\"sitemap__item sitemap__item--expanded\">
                                                        <div class=\"sitemap__link\">About Us</div>
                                                        <div class=\"sitemap__sublist\">
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Responsible
                                                                        Gambling</a>
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Terms and
                                                                        Conditions</a>
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Responsible</a>
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Terms</a>
                                                        </div>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Cookie Policy</a>
                                                </div>

                                                <div class=\"sitemap__item sitemap__item--expanded\">
                                                        <div class=\"sitemap__link\">Privacy Policy</div>
                                                        <div class=\"sitemap__sublist\">
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Responsible
                                                                        Gambling</a>
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Terms and
                                                                        Conditions</a>
                                                                <a class=\"sitemap__sublink\" href=\"#\">⋅ Responsible</a>
                                                        </div>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Responsible Gambling</a>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Terms and Conditions</a>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Sportybet</a>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Betway</a>
                                                </div>

                                                <div class=\"sitemap__item\">
                                                        <a class=\"sitemap__link\" href=\"#\">Betfox</a>
                                                </div>
                                        </div>
                                </div>',
        ],
        'hero-sitemap' => [
            'class' => 'hero hero--has-breadcrumbs hero--simple',
            'id' => 'hero',
            'raw_html' => '<header class=\"header\" id=\"header\">
                        <div class=\"header__inner\">
                                <div class=\"header__logo\">
                                        <a class=\"header__logo-wrapper\" href=\"/\" aria-label=\"To the main page\">
                                                <img src=\"/assets/images/logo/logo.webp\" width=\"141\" height=\"41\" alt=\"Aviator\">
                                        </a>
                                </div>

                                <nav class=\"header__nav menu\" aria-label=\"Main navigation\">
                                        <ul class=\"menu__list\">
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"app.html\">App</a>
                                                </li>
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"demo.html\">Demo</a>
                                                </li>
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"tips.html\">Tips</a>
                                                </li>
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"bonuses.html\">Bonuses</a>
                                                </li>
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"reviews.html\">Reviews</a>
                                                </li>
                                                <li class=\"menu__item\">
                                                        <a class=\"menu__link\" href=\"contact-us.html\">Contact Us</a>
                                                </li>
                                                <li class=\"menu__item menu__item--has-submenu\">
                                                        <a class=\"menu__link\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>New
                                                                Versions</a>
                                                        <ul class=\"menu__submenu\" aria-label=\"New Versions submenu\">
                                                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"authors.html\">Author\'s</a></li>
                                                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"1win.html\">1WIN</a>
                                                                </li>
                                                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"comparison.html\">Comparison</a>
                                                                </li>
                                                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"sitemap.html\">Sitemap</a></li>
                                                        </ul>
                                                </li>
                                                <li class=\"menu__item menu__item--has-submenu menu__item--lang lang-item lang-item-en\">
                                                        <a class=\"menu__link menu__link--lang\" href=\"#\" aria-haspopup=\"true\" aria-expanded=\"false\" data-desktop-submenu-trigger>
                                                                <span class=\"menu__lang\">
                                                                        <img class=\"menu__lang-flag\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\">
                                                                        <span class=\"menu__lang-text\">English</span>
                                                                </span>
                                                        </a>
                                                        <ul class=\"menu__submenu\" aria-label=\"Language submenu\">
                                                                <li class=\"menu__submenu-item\"><a class=\"menu__submenu-link\" href=\"/es/\" hreflang=\"es-ES\" lang=\"es-ES\"><span class=\"menu__lang\"><img class=\"menu__lang-flag\" src=\"data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%2711%27%20fill%3D%27%23AA151B%27%2F%3E%3Crect%20y%3D%272.75%27%20width%3D%2716%27%20height%3D%275.5%27%20fill%3D%27%23F1BF00%27%2F%3E%3C%2Fsvg%3E\" alt=\"\" width=\"16\" height=\"11\" loading=\"lazy\"><span class=\"menu__lang-text\">Español</span></span></a>
                                                                </li>
                                                        </ul>
                                                </li>
                                        </ul>

                                        <a class=\"btn__cta\" href=\"#play-now\">Play now!</a>
                                </nav>
                        </div>
                </header>

                <nav class=\"breadcrumbs-container\" aria-label=\"Breadcrumb\">
                        <div class=\"breadcrumbs\">
                                <a class=\"breadcrumbs__item\" href=\"/\">Home page</a>
                                <span class=\"breadcrumbs__separator\" aria-hidden=\"true\">→</span>
                                <span class=\"breadcrumbs__item breadcrumbs__item--active\" aria-current=\"page\">Sitemap</span>
                        </div>
                </nav>
                <div class=\"hero__inner--reviews\">
                        <div class=\"hero__content\">
                                <div class=\"hero__text\">
                                        <h1 class=\"hero__title\">Sitemap</h1>
                                </div>
                        </div>
                </div>',
        ],
    ];
