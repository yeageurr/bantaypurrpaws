<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/adoption.php';

requireLogin();

if (isAdmin()) {
    header('Location: ' . url('admin/pets.php'));
    exit;
}

$pageTitle     = 'Pet Adoption';
$useSweetAlert = true;
$extraCss      = ['css/adoption.css'];

$pets = db_select('pets', 'order=created_at.desc') ?: [];
$user = currentUser();
$userId = (int) $user['id'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="adoption-page">
    <div class="page-header">
        <h2>Find Your New Companion</h2>
        <p>Browse pets ready for adoption. View details and submit an application when you're ready.</p>
    </div>

    <?php if (empty($pets)): ?>
        <div class="empty-state">
            <div class="empty-icon">🐱</div>
            <h3>No pets listed yet</h3>
            <p>Check back soon — new rescues will appear here when available.</p>
        </div>
    <?php else: ?>
        <div class="pet-gallery-grid">
            <?php foreach ($pets as $pet):
                $status = formatPetStatus($pet['status']);
                $canAdopt = $pet['status'] === 'available'
                    && !userHasPendingApplication($userId, (int) $pet['id']);
            ?>
            <article class="pet-card">
                <div class="pet-card-img-wrap">
                    <img src="<?= petImageUrl($pet['image'] ?? null) ?>" alt="<?= sanitize($pet['name']) ?>" loading="lazy">
                </div>
                <div class="pet-card-body">
                    <h3 class="pet-card-title"><?= sanitize($pet['name']) ?></h3>
                    <div class="pet-meta">
                        <span><?= sanitize($pet['age']) ?></span>
                        <span><?= sanitize($pet['breed']) ?></span>
                        <span><?= sanitize($pet['gender']) ?></span>
                    </div>
                    <span class="pet-status-badge <?= $status['class'] ?>"><?= $status['label'] ?></span>
                    <div class="pet-card-actions">
                        <button type="button" class="btn btn-ghost btn-sm" data-pet-info data-pet-id="<?= (int) $pet['id'] ?>">
                            More Info
                        </button>
                        <?php if ($canAdopt): ?>
                            <button type="button" class="btn btn-accent btn-sm" data-pet-adopt data-pet-id="<?= (int) $pet['id'] ?>" data-available="1">
                                Adopt Now
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Pet Detail Modal -->
<div class="modal-overlay" id="petDetailModal" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-labelledby="petDetailModalLabel">
        <div class="modal-header">
            <h2 class="modal-title" id="petDetailModalLabel">Pet Details</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">✕</button>
        </div>
        <div class="modal-scroll-body">
            <img id="petDetailMainImg" class="pet-detail-main" src="" alt="">
            <div id="petDetailThumbs" class="pet-detail-gallery"></div>
            <div id="petDetailBody"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-modal-close>Close</button>
            <button type="button" class="btn btn-accent" id="petDetailAdoptBtn">Adopt Now</button>
        </div>
    </div>
</div>

<!-- Adoption Application Modal -->
<div class="modal-overlay" id="adoptFormModal" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-labelledby="adoptFormModalLabel">
        <form id="adoptionApplicationForm">
            <div class="modal-header">
                <h2 class="modal-title" id="adoptFormModalLabel">Adoption Application — <span id="adoptPetNameLabel"></span></h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">✕</button>
            </div>
            <div class="modal-scroll-body">
                <input type="hidden" name="pet_id" id="adoptPetId" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="adopt_full_name">Full Name <span class="req">*</span></label>
                        <input type="text" id="adopt_full_name" name="full_name" class="form-control" required value="<?= sanitize($user['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adopt_contact">Contact Number <span class="req">*</span></label>
                        <input type="tel" id="adopt_contact" name="contact_number" class="form-control" required
                               data-phone-numeric placeholder="09XXXXXXXXX"
                               value="<?= sanitize($user['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adopt_email">Email Address <span class="req">*</span></label>
                        <input type="email" id="adopt_email" name="email" class="form-control" required value="<?= sanitize($user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adopt_occupation">Occupation <span class="req">*</span></label>
                        <input type="text" id="adopt_occupation" name="occupation" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="adopt_address">Complete Address <span class="req">*</span></label>
                    <textarea id="adopt_address" name="address" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="adopt_home_type">Type of Home <span class="req">*</span></label>
                        <select id="adopt_home_type" name="home_type" class="form-control" required>
                            <option value="">Select…</option>
                            <option>House</option>
                            <option>Apartment</option>
                            <option>Condominium</option>
                            <option>Townhouse</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="adopt_existing_pets">Existing Pets <span class="req">*</span></label>
                        <select id="adopt_existing_pets" name="existing_pets" class="form-control" required>
                            <option value="">Select…</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="adopt_reason">Reason for Adoption <span class="req">*</span></label>
                    <textarea id="adopt_reason" name="reason_for_adoption" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group form-check-adopt">
                    <label class="form-check-label">
                        <input type="checkbox" name="agreement" id="adoptAgreement" value="1" required>
                        <span>I agree to provide proper care, shelter, and veterinary attention for this pet. <span class="req">*</span></span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-accent">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<script>
window.BPP_ADOPTION = {
    apiPet: <?= json_encode(url('api/pet.php')) ?>,
    apiAdopt: <?= json_encode(url('api/adopt.php')) ?>,
    placeholder: <?= json_encode(petImageUrl(null)) ?>
};
</script>

<?php
$extraJs = ['js/phone-input.js', 'js/adoption.js'];
require_once __DIR__ . '/includes/footer.php';
?>
