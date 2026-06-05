(function () {
    const config = window.BPP_ADOPTION || {};
    const apiPet = config.apiPet;
    const apiAdopt = config.apiAdopt;

    const detailModalEl = document.getElementById('petDetailModal');
    const adoptModalEl = document.getElementById('adoptFormModal');

    let currentPet = null;

    function openModal(overlay) {
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal-overlay.open')) {
            document.body.style.overflow = '';
        }
    }

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(btn.closest('.modal-overlay'));
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(closeModal);
        }
    });

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function showAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text, confirmButtonColor: '#8B3A3A' });
        } else {
            alert(title + (text ? '\n' + text : ''));
        }
    }

    function renderDetail(pet) {
        currentPet = pet;
        const mainImg = document.getElementById('petDetailMainImg');
        const thumbs = document.getElementById('petDetailThumbs');
        const body = document.getElementById('petDetailBody');
        const adoptBtn = document.getElementById('petDetailAdoptBtn');

        if (!mainImg || !body) return;

        const images = pet.images && pet.images.length ? pet.images : [config.placeholder];
        mainImg.src = images[0];
        mainImg.alt = pet.name;

        thumbs.innerHTML = images.map((src, i) =>
            `<img src="${esc(src)}" class="pet-detail-thumb${i === 0 ? ' active' : ''}" data-src="${esc(src)}" alt="">`
        ).join('');

        thumbs.querySelectorAll('.pet-detail-thumb').forEach((thumb) => {
            thumb.addEventListener('click', function () {
                mainImg.src = this.dataset.src;
                thumbs.querySelectorAll('.pet-detail-thumb').forEach((t) => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        body.innerHTML = `
            <dl class="pet-detail-list">
                <div><dt>Name</dt><dd>${esc(pet.name)}</dd></div>
                <div><dt>Breed</dt><dd>${esc(pet.breed)}</dd></div>
                <div><dt>Age</dt><dd>${esc(pet.age)}</dd></div>
                <div><dt>Gender</dt><dd>${esc(pet.gender)}</dd></div>
                <div><dt>Vaccination</dt><dd>${esc(pet.vaccination_status) || '—'}</dd></div>
                <div><dt>Rescue Date</dt><dd>${esc(pet.rescue_date)}</dd></div>
                <div class="pet-detail-full"><dt>Health Condition</dt><dd>${esc(pet.health_condition) || '—'}</dd></div>
                <div class="pet-detail-full"><dt>Personality / Description</dt><dd>${esc(pet.description) || '—'}</dd></div>
                <div class="pet-detail-full"><dt>Adoption Requirements</dt><dd>${esc(pet.adoption_requirements) || '—'}</dd></div>
            </dl>`;

        if (adoptBtn) {
            adoptBtn.style.display = pet.can_adopt ? '' : 'none';
            adoptBtn.disabled = !pet.can_adopt;
        }

        document.getElementById('petDetailModalLabel').textContent = pet.name;
    }

    document.querySelectorAll('[data-pet-info]').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.petId;
            try {
                const res = await fetch(`${apiPet}?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (!res.ok || data.error) {
                    showAlert('error', 'Error', data.error || 'Could not load pet details.');
                    return;
                }
                renderDetail(data.pet);
                openModal(detailModalEl);
            } catch (e) {
                showAlert('error', 'Error', 'Network error. Please try again.');
            }
        });
    });

    document.getElementById('petDetailAdoptBtn')?.addEventListener('click', function () {
        if (!currentPet || !currentPet.can_adopt) return;
        document.getElementById('adoptPetId').value = currentPet.id;
        document.getElementById('adoptPetNameLabel').textContent = currentPet.name;
        closeModal(detailModalEl);
        openModal(adoptModalEl);
    });

    document.querySelectorAll('[data-pet-adopt]').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.petId;
            if (this.dataset.available !== '1') {
                showAlert('info', 'Unavailable', 'This pet has already been adopted.');
                return;
            }
            try {
                const res = await fetch(`${apiPet}?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (!res.ok || !data.pet) {
                    showAlert('error', 'Error', data.error || 'Could not load pet.');
                    return;
                }
                currentPet = data.pet;
                if (!data.pet.can_adopt) {
                    showAlert('info', 'Unavailable', 'This pet is not accepting new applications.');
                    return;
                }
                document.getElementById('adoptPetId').value = data.pet.id;
                document.getElementById('adoptPetNameLabel').textContent = data.pet.name;
                openModal(adoptModalEl);
            } catch (e) {
                showAlert('error', 'Error', 'Network error.');
            }
        });
    });

    const adoptForm = document.getElementById('adoptionApplicationForm');
    adoptForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);

        if (!fd.get('agreement')) {
            showAlert('warning', 'Agreement required', 'Please accept the adoption terms.');
            return;
        }

        const contactField = form.querySelector('[name="contact_number"]');
        if (contactField && window.BPPPhone) {
            const phoneErr = window.BPPPhone.validatePhoneInput(contactField);
            if (phoneErr) {
                showAlert('warning', 'Invalid phone number', phoneErr);
                return;
            }
            contactField.value = window.BPPPhone.digitsOnly(contactField.value);
        }

        const submitBtn = form.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await fetch(apiAdopt, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                closeModal(adoptModalEl);
                form.reset();
                showAlert('success', 'Application Submitted', data.message);
                setTimeout(() => location.reload(), 1800);
            } else {
                showAlert('error', 'Submission Failed', data.message || 'Please check your entries.');
            }
        } catch (err) {
            showAlert('error', 'Error', 'Could not submit application.');
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
