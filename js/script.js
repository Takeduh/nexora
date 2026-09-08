(() => {


const cars = Array.isArray(window.NEXORA_FLEET) ? window.NEXORA_FLEET : [];
const fleetGrid = document.getElementById("fleetGrid");
const defaultFleetLimit = 8;

const carIcon = `
<svg viewBox="0 0 64 34" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 24 L8 12 Q10 8 16 8 H44 Q50 8 52 12 L58 24" stroke="#0A1730" stroke-width="2" fill="rgba(10,23,48,0.06)"/>
  <rect x="2" y="22" width="60" height="7" rx="3.5" fill="#0A1730"/>
  <rect x="20" y="10" width="20" height="9" rx="1.5" fill="rgba(255,255,255,.7)"/>
  <circle cx="15" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
  <circle cx="49" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
</svg>`;

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function peso(amount) {
  return Number(amount).toLocaleString("en-PH", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
}

function buildBookingLink(variantId) {
  const params = new URLSearchParams({ variant: variantId });
  const loc = document.getElementById("loc");
  const pickup = document.getElementById("pickup");
  const ret = document.getElementById("return");

  if (loc?.value) params.set("loc", loc.value);
  if (pickup?.value) params.set("pickup", pickup.value);
  if (ret?.value) params.set("return", ret.value);

  const bookingUrl = `booking/booking-summary.php?${params.toString()}`;
  if (window.NEXORA_IS_AUTHENTICATED) return bookingUrl;

  const next = `../${bookingUrl}`;
  return `auth/login.php?next=${encodeURIComponent(next)}`;
}

function renderFleet(filter = "all") {
  if (!fleetGrid) return;

  const visibleCars = filter === "all"
    ? cars.slice(0, defaultFleetLimit)
    : cars.filter(car => car.cat === filter).slice(0, defaultFleetLimit);

  if (visibleCars.length === 0) {
    const label = filter === "all" ? "vehicles" : `${filter.toUpperCase()} vehicles`;
    fleetGrid.innerHTML = `
      <div class="fleet-empty-state" style="grid-column: 1 / -1; text-align:center; padding:48px 20px;">
        <h4 style="font-size:20px; margin-bottom:8px;">No ${label} available</h4>
        <p style="color:#5B6478;">Add an active car and an available transmission variant in phpMyAdmin.</p>
      </div>`;
    return;
  }

  fleetGrid.innerHTML = visibleCars.map(car => {
    const firstVariant = car.variants[0];
    if (!firstVariant) return "";
    const categoryClass = String(car.cat || "car").replace(/[^a-z0-9-]/g, "");
    const specs = [car.seats ? `${car.seats} seats` : null, car.fuel || null].filter(Boolean).join(" · ");
    const options = car.variants.map(variant => `
      <option value="${variant.id}" data-rate="${variant.dailyRate}">
        ${escapeHtml(variant.transmissionLabel)} — PHP ${peso(variant.dailyRate)}/day
      </option>
    `).join("");

    return `
      <div class="car-card" data-cat="${escapeHtml(car.cat)}">
        <div class="car-media media-${categoryClass}">
          <span class="car-tag cat-${categoryClass}">${escapeHtml(car.label)}</span>
          ${car.image ? `<img src="${escapeHtml(car.image)}" alt="${escapeHtml(car.name)}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">` : ""}
          <div style="${car.image ? "display:none" : "display:block"}">${carIcon}</div>
        </div>
        <div class="car-body">
          <h4>${escapeHtml(car.name)}</h4>
          <p class="car-specs">${escapeHtml(specs)}</p>
          <p class="car-price">Starting at <b>PHP ${peso(firstVariant.dailyRate)}</b> /day</p>
          <label class="car-variant-field">
            <span>Transmission</span>
            <select class="variant-select" aria-label="Choose transmission for ${escapeHtml(car.name)}">
              ${options}
            </select>
          </label>
          <a href="${buildBookingLink(firstVariant.id)}" class="btn btn-primary btn-block variant-book-button">Book Now</a>
        </div>
      </div>
    `;
  }).join("");

  fleetGrid.querySelectorAll(".car-card").forEach(card => {
    const select = card.querySelector(".variant-select");
    const price = card.querySelector(".car-price b");
    const bookButton = card.querySelector(".variant-book-button");

    select?.addEventListener("change", () => {
      const option = select.options[select.selectedIndex];
      if (price) price.textContent = `PHP ${peso(option.dataset.rate)}`;
      if (bookButton) bookButton.href = buildBookingLink(select.value);
    });
  });
}

renderFleet();

const filterTabs = document.querySelectorAll(".filter-tab");
filterTabs.forEach(tab => {
  tab.addEventListener("click", () => {
    filterTabs.forEach(button => button.classList.remove("active"));
    tab.classList.add("active");
    renderFleet(tab.dataset.filter);
  });
});


const bookingForm = document.getElementById("book");
const bookingNote = document.getElementById("bookingNote");
const locationInput = document.getElementById("loc");
const pickupInput = document.getElementById("pickup");
const returnInput = document.getElementById("return");
const pickupDisplay = document.getElementById("pickupDisplay");
const returnDisplay = document.getElementById("returnDisplay");

function homepageParseUSDate(value) {
  if (!/^\d{2}\/\d{2}\/\d{4}$/.test(value)) return null;
  const [month, day, year] = value.split("/").map(Number);
  const date = new Date(year, month - 1, day);
  if (
    date.getFullYear() !== year ||
    date.getMonth() !== month - 1 ||
    date.getDate() !== day
  ) return null;
  return date;
}

function homepageToISODate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function homepageAutoFormatDate(input) {
  const digits = input.value.replace(/\D/g, "").slice(0, 8);
  let value = digits;
  if (digits.length > 2) value = `${digits.slice(0, 2)}/${digits.slice(2)}`;
  if (digits.length > 4) value = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
  input.value = value;
}

function homepageSyncDate(displayInput, hiddenInput) {
  const parsed = homepageParseUSDate(displayInput.value.trim());
  hiddenInput.value = parsed ? homepageToISODate(parsed) : "";
  return parsed;
}

function updateHomepageBookingDates() {
  if (!pickupDisplay || !returnDisplay || !pickupInput || !returnInput) return;
  homepageSyncDate(pickupDisplay, pickupInput);
  homepageSyncDate(returnDisplay, returnInput);
}

[pickupDisplay, returnDisplay].forEach((input) => {
  if (!input) return;
  input.addEventListener("input", () => {
    homepageAutoFormatDate(input);
    updateHomepageBookingDates();
  });
});

if (bookingForm) {
  bookingForm.addEventListener("submit", function (event) {
    event.preventDefault();
    updateHomepageBookingDates();

    const location = locationInput.value;
    const pickupDate = homepageParseUSDate(pickupDisplay.value.trim());
    const returnDate = homepageParseUSDate(returnDisplay.value.trim());
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (!pickupDate || !returnDate) {
      bookingNote.textContent = "Please enter both dates in MM/DD/YYYY format.";
      bookingNote.classList.add("show");
      return;
    }

    if (pickupDate < today) {
      bookingNote.textContent = "Pick-up date cannot be in the past.";
      bookingNote.classList.add("show");
      return;
    }

    if (returnDate <= pickupDate) {
      bookingNote.textContent = "Return date must be at least one day after the pick-up date.";
      bookingNote.classList.add("show");
      return;
    }

    bookingNote.textContent = `Showing available cars in ${location} from ${pickupDisplay.value} to ${returnDisplay.value} ↓`;
    bookingNote.classList.add("show");

    const fleet = document.getElementById("fleet");
    if (fleet) fleet.scrollIntoView({ behavior: "smooth", block: "start" });
  });
}


const testimonials = [
  { quote: "Nexora made our trips easier! The car was clean, the process was fast, and the staff were very helpful.", name: "Rad A.B.", loc: "Cagayan de Oro" },
  { quote: "Best car rental experience I've ever had. Transparent pricing and great service!", name: "Harvey M.", loc: "Dumaguete" },
  { quote: "Highly recommended for business trips. Reliable cars, on-time service.", name: "Sara D.", loc: "Manila" },
  { quote: "Booking took less than five minutes and the car was waiting for us at the airport. Seamless.", name: "Jill T.", loc: "Cebu City" },
  { quote: "Great value for a weekend road trip. No hidden charges at drop-off, exactly as quoted.", name: "Marco P.", loc: "Davao" },
  { quote: "I said I would leave early, and Nexora had the car ready before I finished my coffee. Suspiciously efficient.", name: "R. U. Ready", loc: "Manila" },
  { quote: "The booking was so smooth that I arrived on time. My friends are still processing this development.", name: "Mai B. Late", loc: "Cebu City" },
  { quote: "The car was clean, comfortable, and had enough space for my luggage and my questionable snack decisions.", name: "Carrie O. Key", loc: "Dumaguete" },
  { quote: "No hidden fees, no surprise detours, and no arguments with the GPS. A genuinely peaceful road trip.", name: "Will B. Back", loc: "Davao" }
];

const testimonialCard = document.getElementById("tCard");

function renderTestimonials() {
  if (!testimonialCard) return;

  testimonialCard.innerHTML = testimonials.map(testimonial => `
    <article class="t-slide">
      <div class="t-stars">★★★★★</div>
      <p class="t-quote">"${testimonial.quote}"</p>
      <div class="t-person">
        <div class="t-name">${testimonial.name}</div>
        <div class="t-loc">${testimonial.loc}</div>
      </div>
    </article>
  `).join("");
}

renderTestimonials();


const newsletterForm = document.getElementById("newsletterForm");
const newsletterMessage = document.getElementById("newsletterMessage");

if (newsletterForm) {
  newsletterForm.addEventListener("submit", event => {
    event.preventDefault();

    const email = newsletterForm.querySelector("input[type='email']");
    if (!email.value.trim()) return;

    email.value = "";
    if (newsletterMessage) {
      newsletterMessage.textContent = "Thank you for subscribing!";
      newsletterMessage.classList.remove("hidden");
    }
  });
}
})();
