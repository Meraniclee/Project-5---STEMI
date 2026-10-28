(() => {
    'use strict';

    const body = document.body;

    const mobileMenuButton =
        document.getElementById('mobileMenuButton');

    const sidebarOverlay =
        document.getElementById('sidebarOverlay');

    const profileButton =
        document.getElementById('profileButton');

    const profileDropdown =
        document.getElementById('profileDropdown');

    const desktopBreakpoint = 900;

    const sidebarStorageKey =
        'stemiSidebarCollapsed';


    /*
    |--------------------------------------------------------------------------
    | Shared Sidebar Button State
    |--------------------------------------------------------------------------
    */

    const isDesktop = () =>
        window.innerWidth > desktopBreakpoint;


    const getSavedDesktopState = () => {
        try {
            return (
                window.localStorage.getItem(
                    sidebarStorageKey
                ) === 'true'
            );
        } catch (error) {
            return false;
        }
    };


    const saveDesktopState = (collapsed) => {
        try {
            window.localStorage.setItem(
                sidebarStorageKey,
                collapsed
                    ? 'true'
                    : 'false'
            );
        } catch (error) {
            /*
             * Storage can be unavailable in hardened/private browser modes.
             * Sidebar behaviour should still continue for the current page.
             */
        }
    };


    const updateSidebarButtonState = () => {
        if (!mobileMenuButton) {
            return;
        }

        if (isDesktop()) {
            const collapsed =
                body.classList.contains(
                    'sidebar-collapsed'
                );

            mobileMenuButton.setAttribute(
                'aria-expanded',
                collapsed
                    ? 'false'
                    : 'true'
            );

            mobileMenuButton.setAttribute(
                'aria-label',
                collapsed
                    ? 'Expand navigation'
                    : 'Collapse navigation'
            );

            mobileMenuButton.setAttribute(
                'title',
                collapsed
                    ? 'Expand sidebar'
                    : 'Collapse sidebar'
            );

            return;
        }

        const opened =
            body.classList.contains(
                'sidebar-open'
            );

        mobileMenuButton.setAttribute(
            'aria-expanded',
            opened
                ? 'true'
                : 'false'
        );

        mobileMenuButton.setAttribute(
            'aria-label',
            opened
                ? 'Close navigation'
                : 'Open navigation'
        );

        mobileMenuButton.setAttribute(
            'title',
            opened
                ? 'Close navigation'
                : 'Open navigation'
        );
    };


    /*
    |--------------------------------------------------------------------------
    | Mobile Sidebar
    |--------------------------------------------------------------------------
    */

    const openSidebar = () => {
        body.classList.add('sidebar-open');

        updateSidebarButtonState();
    };


    const closeSidebar = () => {
        body.classList.remove('sidebar-open');

        updateSidebarButtonState();
    };


    /*
    |--------------------------------------------------------------------------
    | Desktop Sidebar Collapse
    |--------------------------------------------------------------------------
    */

    const applySavedDesktopSidebarState = () => {
        if (!isDesktop()) {
            updateSidebarButtonState();
            return;
        }

        body.classList.toggle(
            'sidebar-collapsed',
            getSavedDesktopState()
        );

        updateSidebarButtonState();
    };


    const toggleDesktopSidebar = () => {
        const willCollapse =
            !body.classList.contains(
                'sidebar-collapsed'
            );

        body.classList.toggle(
            'sidebar-collapsed',
            willCollapse
        );

        saveDesktopState(
            willCollapse
        );

        updateSidebarButtonState();
    };


    if (mobileMenuButton) {
        mobileMenuButton.addEventListener(
            'click',
            () => {
                if (isDesktop()) {
                    toggleDesktopSidebar();
                    return;
                }

                if (
                    body.classList.contains(
                        'sidebar-open'
                    )
                ) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
        );
    }


    if (sidebarOverlay) {
        sidebarOverlay.addEventListener(
            'click',
            closeSidebar
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Profile Dropdown
    |--------------------------------------------------------------------------
    */

    const closeProfileDropdown = () => {
        if (
            !profileButton ||
            !profileDropdown
        ) {
            return;
        }

        profileDropdown.hidden = true;

        profileButton.setAttribute(
            'aria-expanded',
            'false'
        );
    };


    const toggleProfileDropdown = () => {
        if (
            !profileButton ||
            !profileDropdown
        ) {
            return;
        }

        const shouldOpen =
            profileDropdown.hidden;

        profileDropdown.hidden =
            !shouldOpen;

        profileButton.setAttribute(
            'aria-expanded',
            shouldOpen
                ? 'true'
                : 'false'
        );
    };


    if (profileButton) {
        profileButton.addEventListener(
            'click',
            (event) => {
                event.stopPropagation();

                toggleProfileDropdown();
            }
        );
    }


    document.addEventListener(
        'click',
        (event) => {
            if (
                profileDropdown &&
                profileButton &&
                !profileDropdown.contains(event.target) &&
                !profileButton.contains(event.target)
            ) {
                closeProfileDropdown();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Escape Key
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (!isDesktop()) {
                closeSidebar();
            }

            closeProfileDropdown();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Responsive Cleanup
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        () => {
            if (isDesktop()) {
                closeSidebar();
            }

            updateSidebarButtonState();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    applySavedDesktopSidebarState();

})();
