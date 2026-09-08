(() => {
'use strict';

/* =========================================================
   FLEET PAGE — MySQL-backed cars + variants.
   Data is injected by fleet.php as window.NEXORA_FLEET.
   ========================================================= */

const cars = Array.isArray(window.NEXORA_FLEET) ? window.NEXORA_FLEET : [];
const resultsGrid = document.getElementById("fleetResultsGrid");
const resultsCount = document.getElementById("fleetResultsCount");
const emptyState = document.getElementById("fleetEmptyState");
const loadMoreBtn = document.getElementById("fleetLoadMore");
const keywordInput = document.getElementById("fleetKeyword");
const fleetFilterTabs = document.querySelectorAll(".filter-tab");
const sortSelect = document.getElementById("fleetSort");

const PAGE_SIZE = 8;
let activeCategory = "all";
let activeKeyword = "";
let visibleCount = PAGE_SIZE;
let activeSort = "recommended";

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

function lowestRate(car) {
  if (!Array.isArray(car.variants) || car.variants.length === 0) return Number.POSITIVE_INFINITY;
  return Math.min(...car.variants.map(variant => Number(variant.dailyRate) || 0));
}

function getMatchingCars() {
  const matches = cars.filter(car => {
    const matchesCategory = activeCategory === "all" || car.cat === activeCategory;
    const haystack = `${car.name} ${car.brand} ${car.model}`.toLowerCase();
    const matchesKeyword = haystack.includes(activeKeyword.trim().toLowerCase());
    return matchesCategory && matchesKeyword;
  });

  return matches.sort((a, b) => {
    switch (activeSort) {
      case "price-asc":
        return lowestRate(a) - lowestRate(b) || a.name.localeCompare(b.name);
      case "price-desc":
        return lowestRate(b) - lowestRate(a) || a.name.localeCompare(b.name);
      case "name-asc":
        return a.name.localeCompare(b.name);
      case "name-desc":
        return b.name.localeCompare(a.name);
      case "seats-desc":
        return (Number(b.seats) || 0) - (Number(a.seats) || 0) || a.name.localeCompare(b.name);
      default:
        return 0;
    }
  });
}

function buildFleetBookingLink(variantId) {
  const params = new URLSearchParams({ variant: variantId });
  const loc = document.getElementById("fleetLoc");
  const pickup = document.getElementById("fleetPickup");
  const ret = document.getElementById("fleetReturn");

  if (loc?.value) params.set("loc", loc.value);
  if (pickup?.value) params.set("pickup", pickup.value);
  if (ret?.value) params.set("return", ret.value);

  return `booking-summary.php?${params.toString()}`;
}

function parseUSDate(value) {
  if (!/^\d{2}\/\d{2}\/\d{4}$/.test(value)) return null;
  const [month, day, year] = value.split("/").map(Number);
  const date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return date;
}

function toISODate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function autoFormatDate(input) {
  const digits = input.value.replace(/\D/g, "").slice(0, 8);
  let value = digits;
  if (digits.length > 2) value = `${digits.slice(0, 2)}/${digits.slice(2)}`;
  if (digits.length > 4) value = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
  input.value = value;
}

function syncFleetDates() {
  const pickupDisplay = document.getElementById("fleetPickupDisplay");
  const returnDisplay = document.getElementById("fleetReturnDisplay");
  const pickup = document.getElementById("fleetPickup");
  const ret = document.getElementById("fleetReturn");
  if (!pickupDisplay || !returnDisplay || !pickup || !ret) return;
  const pickupDate = parseUSDate(pickupDisplay.value.trim());
  const returnDate = parseUSDate(returnDisplay.value.trim());
  pickup.value = pickupDate ? toISODate(pickupDate) : "";
  ret.value = returnDate ? toISODate(returnDate) : "";
}

function refreshBookingLinks() {
  resultsGrid?.querySelectorAll(".car-card").forEach(card => {
    const select = card.querySelector(".variant-select");
    const button = card.querySelector(".variant-book-button");
    if (select && button) button.href = buildFleetBookingLink(select.value);
  });
}

function variantOptions(car) {
  return car.variants.map(variant => `
    <option value="${variant.id}" data-rate="${variant.dailyRate}">
      ${escapeHtml(variant.transmissionLabel)} — PHP ${peso(variant.dailyRate)}/day
    </option>
  `).join("");
}

function renderResults() {
  if (!resultsGrid) return;

  const matches = getMatchingCars();
  const visible = matches.slice(0, visibleCount);

  resultsGrid.innerHTML = visible.map(car => {
    const firstVariant = car.variants[0];
    const categoryClass = String(car.cat || "car").replace(/[^a-z0-9-]/g, "");
    const specs = [
      car.seats ? `${car.seats} seats` : null,
      car.fuel || null
    ].filter(Boolean).join(" · ");

    return `
      <div class="car-card" data-cat="${escapeHtml(car.cat)}" data-car-id="${car.id}">
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
              ${variantOptions(car)}
            </select>
          </label>

          <a href="${buildFleetBookingLink(firstVariant.id)}" class="btn btn-primary btn-block variant-book-button">Select This Car</a>
        </div>
      </div>
    `;
  }).join("");

  resultsGrid.querySelectorAll(".car-card").forEach(card => {
    const select = card.querySelector(".variant-select");
    const price = card.querySelector(".car-price b");
    const bookButton = card.querySelector(".variant-book-button");

    select?.addEventListener("change", () => {
      const option = select.options[select.selectedIndex];
      if (price) price.textContent = `PHP ${peso(option.dataset.rate)}`;
      if (bookButton) bookButton.href = buildFleetBookingLink(select.value);
    });
  });

  if (resultsCount) {
    resultsCount.textContent = matches.length
      ? `Showing ${visible.length} of ${matches.length} vehicle${matches.length === 1 ? "" : "s"}`
      : "";
  }

  emptyState?.classList.toggle("hidden", matches.length !== 0);
  loadMoreBtn?.classList.toggle("hidden", visibleCount >= matches.length);
}

fleetFilterTabs.forEach(tab => {
  tab.addEventListener("click", () => {
    fleetFilterTabs.forEach(button => button.classList.remove("active"));
    tab.classList.add("active");
    activeCategory = tab.dataset.filter;
    visibleCount = PAGE_SIZE;
    renderResults();
  });
});

keywordInput?.addEventListener("input", () => {
  activeKeyword = keywordInput.value;
  visibleCount = PAGE_SIZE;
  renderResults();
});

loadMoreBtn?.addEventListener("click", () => {
  visibleCount += PAGE_SIZE;
  renderResults();
});

sortSelect?.addEventListener("change", () => {
  activeSort = sortSelect.value;
  visibleCount = PAGE_SIZE;
  renderResults();
});

const fleetSearchForm = document.getElementById("fleetSearchForm");
const pickupDisplay = document.getElementById("fleetPickupDisplay");
const returnDisplay = document.getElementById("fleetReturnDisplay");
const formNote = document.getElementById("fleetFormNote");

[pickupDisplay, returnDisplay].forEach(input => {
  input?.addEventListener("input", () => {
    autoFormatDate(input);
    syncFleetDates();
    refreshBookingLinks();
  });
});

fleetSearchForm?.addEventListener("submit", event => {
  event.preventDefault();
  syncFleetDates();

  const pickupDate = pickupDisplay?.value.trim() ? parseUSDate(pickupDisplay.value.trim()) : null;
  const returnDate = returnDisplay?.value.trim() ? parseUSDate(returnDisplay.value.trim()) : null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const hasEitherDate = Boolean(pickupDisplay?.value.trim() || returnDisplay?.value.trim());
  if (hasEitherDate && (!pickupDate || !returnDate)) {
    if (formNote) {
      formNote.textContent = "Enter both dates in MM/DD/YYYY format.";
      formNote.className = "fleet-form-note show error";
    }
    return;
  }
  if (pickupDate && pickupDate < today) {
    if (formNote) {
      formNote.textContent = "Pick-up date cannot be in the past.";
      formNote.className = "fleet-form-note show error";
    }
    return;
  }
  if (pickupDate && returnDate && returnDate <= pickupDate) {
    if (formNote) {
      formNote.textContent = "Return date must be at least one day after pick-up.";
      formNote.className = "fleet-form-note show error";
    }
    return;
  }

  refreshBookingLinks();
  if (formNote) {
    formNote.textContent = pickupDate && returnDate
      ? `Trip details applied: ${pickupDisplay.value} to ${returnDisplay.value}.`
      : "Location applied. You can choose dates during checkout.";
    formNote.className = "fleet-form-note show";
  }
  resultsGrid?.scrollIntoView({ behavior: "smooth", block: "start" });
});

renderResults();

})();
