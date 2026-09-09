(() => {
    const sidebar = document.getElementById('userSidebar');
    const menuToggle = document.getElementById('userMenuToggle');
    const allSidebarLinks = [...document.querySelectorAll('.user-sidebar-nav a')];
    const navLinks = [...document.querySelectorAll('.user-sidebar-nav a[data-section]')];

    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    allSidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            sidebar?.classList.remove('open');
        });
    });

    const setActiveSection = (id) => {
        navLinks.forEach((link) => {
            link.classList.toggle('active', link.dataset.section === id);
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            setActiveSection(link.dataset.section);
        });
    });

    const sections = navLinks
        .map((link) => document.getElementById(link.dataset.section))
        .filter(Boolean);

    const updateSection = () => {
        if (!sections.length) return;

        let current = sections[0].id;
        const offset = 110;

        sections.forEach((section) => {
            if (section.getBoundingClientRect().top <= offset) {
                current = section.id;
            }
        });

        if (
            window.innerHeight + window.scrollY >=
            document.documentElement.scrollHeight - 4
        ) {
            current = sections[sections.length - 1].id;
        }

        setActiveSection(current);
    };

    let ticking = false;

    const requestSectionUpdate = () => {
        if (ticking || !sections.length) return;

        ticking = true;

        requestAnimationFrame(() => {
            updateSection();
            ticking = false;
        });
    };

    if (sections.length) {
        window.addEventListener('scroll', requestSectionUpdate, { passive: true });
        window.addEventListener('resize', requestSectionUpdate);
        updateSection();
    }

    const type = document.getElementById('dashboardMethodType');
    const labelField = document.getElementById('dashboardMethodLabelField');
    const lastFourField = document.getElementById('dashboardLastFourField');

    if (type && labelField && lastFourField) {
        const labelInput = labelField.querySelector('input');
        const lastFourInput = lastFourField.querySelector('input');

        const syncCashFields = () => {
            const isCash = type.value === 'cash';

            labelField.hidden = isCash;
            lastFourField.hidden = isCash;
            labelInput.required = !isCash;
            lastFourInput.required = !isCash;

            if (isCash) {
                labelInput.value = '';
                lastFourInput.value = '';
            }
        };

        type.addEventListener('change', syncCashFields);
        syncCashFields();
    }
})();
