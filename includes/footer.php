<?php

$siteFooterNewsletter = $siteFooterNewsletter ?? false;
$siteRoot = $siteRoot ?? ''; 
?>
<footer class="footer-revamp" id="footer">
    <div class="footer-inner max-w-[1180px] mx-auto">
        <div class="footer-topline">
            <span class="footer-kicker">NEXORA / ROAD AHEAD</span>
            <p>Good cars, clear roads, and a better way to get there.</p>
        </div>
        <div class="footer-grid">
            <section class="footer-column footer-brand-column" aria-labelledby="footer-brand-title">
                <h2 id="footer-brand-title">Drive with confidence.</h2>
                <p>Reliable rentals, straightforward support, and vehicles ready for the journeys that matter.</p>
                <div class="social-list" aria-label="Social media links">
                    <a href="https://www.facebook.com" aria-label="Facebook" class="social-link" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 8h3V4h-3c-3.3 0-5 2-5 5v2H6v4h3v5h4v-5h3.2l.8-4H13V9c0-.7.3-1 1-1Z" fill="currentColor"/></svg>
                        <span>Facebook</span>
                    </a>
                    <a href="https://www.instagram.com" aria-label="Instagram" class="social-link" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.8" r="1" fill="currentColor"/></svg>
                        <span>Instagram</span>
                    </a>
                    <a href="https://x.com" aria-label="X" class="social-link" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h4.2l3.6 5.1L17 4h2l-5.3 6.6L20 20h-4.2l-4-5.7L7.2 20H5l5.9-7.3L5 4Zm3.1 1.8 8.7 12.4h1.1L9.2 5.8H8.1Z" fill="currentColor"/></svg>
                        <span>X</span>
                    </a>
                    <a href="https://www.linkedin.com" aria-label="LinkedIn" class="social-link" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="9" width="4" height="11" rx="1" fill="currentColor"/><circle cx="6" cy="5.8" r="2" fill="currentColor"/><path d="M11 9h4v1.6c1-1.3 2.3-2 4-2 3 0 5 1.9 5 6V20h-4v-5c0-1.8-.7-2.8-2.1-2.8-1.7 0-2.9 1.1-2.9 3.5V20h-4V9Z" fill="currentColor" transform="translate(-2 0)"/></svg>
                        <span>LinkedIn</span>
                    </a>
                </div>
            </section>
            <section class="footer-column" aria-labelledby="footer-explore-title">
                <h2 id="footer-explore-title" class="footer-heading">Explore</h2>
                <nav class="footer-links" aria-label="Footer navigation">
                    <a href="<?= $siteRoot ?>fleet.php">Our Fleet</a>
                    <a href="<?= $siteRoot ?>index.php#services">Services</a>
                    <a href="<?= $siteRoot ?>index.php#difference">About Nexora</a>
                    <a href="<?= $siteRoot ?>index.php#blog">Customer Stories</a>
                </nav>
            </section>
            <section class="footer-column" aria-labelledby="footer-contact-title">
                <h2 id="footer-contact-title" class="footer-heading">Contact</h2>
                <p><a href="<?= $siteRoot ?>contact.php" style="font-weight:700">Send us a message</a></p>
                <ul class="contact-list">
                    <li class="contact-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="m5 7 7 6 7-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <a href="mailto:hello@nexora-rentals.com">hello@nexora-rentals.com</a>
                    </li>
                    <li class="contact-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.7 3.5 9.5 7l-1.8 2.2c1.2 2.6 3.3 4.7 5.9 5.9l2.2-1.8 3.5 2.8c.5.4.7 1.1.4 1.7l-1.1 2.2c-.3.6-.9 1-1.6 1C9.3 20.4 3.6 14.7 3 7c0-.7.4-1.3 1-1.6l2.2-1.1c.6-.3 1.3-.1 1.7.4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        <a href="tel:+63325550147">+63 32 555 0147</a>
                    </li>
                    <li class="contact-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                        <span>Cebu Business Park<br>Cebu City, Philippines</span>
                    </li>
                </ul>
                <p class="footer-hours"><strong>Open daily</strong><br>6:00 AM – 10:00 PM</p>
            </section>
            <?php if ($siteFooterNewsletter): ?>
            <section class="newsletter-panel" aria-labelledby="newsletter-title">
                <span class="footer-kicker">STAY UPDATED</span>
                <h2 id="newsletter-title">Keep your next trip close.</h2>
                <p>Get the latest offers, updates, and travel tips.</p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" placeholder="Enter your email" aria-label="Email address" required>
                    <button type="submit">Subscribe</button>
                </form>
                <p id="newsletterMessage" class="newsletter-message hidden" aria-live="polite"></p>
            </section>
            <?php endif; ?>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> Nexora Car Rentals. All rights reserved.</span>
            <div class="footer-meta"><span>Secure payments</span><span>Privacy</span><span>Terms</span></div>
        </div>
    </div>
</footer>
