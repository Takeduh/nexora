/* =========================================================
   FLEET PAGE — search, keyword + category filters, and
   "Load More" pagination over the shared `cars` data.
   ========================================================= */

const resultsGrid = document.getElementById("fleetResultsGrid");
const resultsCount = document.getElementById("fleetResultsCount");
const emptyState = document.getElementById("fleetEmptyState");
const loadMoreBtn = document.getElementById("fleetLoadMore");
const keywordInput = document.getElementById("fleetKeyword");
const fleetFilterTabs = document.querySelectorAll(".filter-tab");

const PAGE_SIZE = 8;

let activeCategory = "all";
let activeKeyword = "";
let visibleCount = PAGE_SIZE;

function getMatchingCars() {
  return cars.filter(car => {
    const matchesCategory = activeCategory === "all" || car.cat === activeCategory;
    const matchesKeyword = car.name.toLowerCase().includes(activeKeyword.trim().toLowerCase());
    return matchesCategory && matchesKeyword;
  });
}

function buildFleetBookingLink(carId) {
  const params = new URLSearchParams({ car: carId });

  const loc = document.getElementById("fleetLoc");
  const pickup = document.getElementById("fleetPickup");
  const ret = document.getElementById("fleetReturn");

  if (loc && loc.value) params.set("loc", loc.value);
  if (pickup && pickup.value) params.set("pickup", pickup.value);
  if (ret && ret.value) params.set("return", ret.value);

  return `booking-summary.php?${params.toString()}`;
}

function renderResults() {
  if (!resultsGrid) return;

  const matches = getMatchingCars();
  const visible = matches.slice(0, visibleCount);

  resultsGrid.innerHTML = visible.map(car => `
    <div class="car-card" data-cat="${car.cat}">
      <div class="car-media media-${car.cat}">
        <span class="car-tag cat-${car.cat}">${car.label}</span>
        <img src="${car.image}" alt="${car.name}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <div style="display:none">${carIcon}</div>
      </div>
      <div class="car-body">
        <h4>${car.name}</h4>
        <p class="car-price">Starting at <b>PHP ${car.price.toLocaleString()}</b> /day</p>
        <a href="${buildFleetBookingLink(car.id)}" class="btn btn-primary btn-block">Select This Car</a>
      </div>
    </div>
  `).join("");

  if (resultsCount) {
    resultsCount.textContent = matches.length
      ? `Showing ${visible.length} of ${matches.length} vehicle${matches.length === 1 ? "" : "s"}`
      : "";
  }

  if (emptyState) emptyState.classList.toggle("hidden", matches.length !== 0);
  if (loadMoreBtn) loadMoreBtn.classList.toggle("hidden", visibleCount >= matches.length);
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

if (keywordInput) {
  keywordInput.addEventListener("input", () => {
    activeKeyword = keywordInput.value;
    visibleCount = PAGE_SIZE;
    renderResults();
  });
}

if (loadMoreBtn) {
  loadMoreBtn.addEventListener("click", () => {
    visibleCount += PAGE_SIZE;
    renderResults();
  });
}

const fleetSearchForm = document.getElementById("fleetSearchForm");
if (fleetSearchForm) {
  fleetSearchForm.addEventListener("submit", event => {
    event.preventDefault();
    if (resultsGrid) resultsGrid.scrollIntoView({ behavior: "smooth", block: "start" });
    /* Pick-up location and dates carry into whichever car is
       selected below via buildFleetBookingLink() — real
       availability filtering against these dates is Phase 3. */
  });
}

renderResults();
