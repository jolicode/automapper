window.addEventListener("DOMContentLoaded", function() {
    if (document.querySelector('.md-version')) {
        return
    }

    const originUrl = window.location.origin
    const fullUrl = window.location.href

    const rawDiff = fullUrl.split(originUrl).join('')
    const currentVersionInUrl = rawDiff.substring(1, rawDiff.length - 1)

    fetch(`${originUrl}/versions.json`)
        .then((response) => response.json())

        /**
         * @typedef {Object} Version
         * @property {string} version
         * @property {string} title
         * @property {string[]} aliases
         *
         * @type {Version[]}
         */
        .then((versions) => {
            /**
             * @type {Version}
             */
            let currentVersion = false
            versions.forEach((current) => {
                if (current.version === currentVersionInUrl ||
                    current.aliases.indexOf(currentVersionInUrl) > -1) {
                    currentVersion = current
                }
            })

            if (!currentVersion) {
                return
            }

            let output = '<div class="md-version">'
            output = output.concat(`<button class="md-version__current" aria-label="Select version">${currentVersion.title}</button>`)
            output = output.concat('<ul class="md-version__list">')

            versions.forEach((current) => {
                let title = current.title;
                if (current.aliases.length > 0) {
                    title = title.concat(' (', current.aliases.join(', '), ')');
                }

                output = output.concat(`<li class="md-version__item"><a href="${originUrl}/${current.version}/" class="md-version__link">${title}</a></li>`)
            })

            output = output.concat('</ul></div>')

            const selectContainer = document.createElement('div')
            selectContainer.classList.add('md-version')
            selectContainer.innerHTML = output
            document.querySelector('.md-header__topic').append(selectContainer)
        })
})
