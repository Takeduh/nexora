/* =========================================================
   FLEET DATA now lives in vehicles-data.js (loaded before this
   file) so the homepage and the full fleet page share one
   source of truth: the `cars` array and `carIcon` markup.
   ========================================================= */


/* =========================================================
   RENDER FLEET
   ========================================================= */

const fleetGrid = document.getElementById("fleetGrid");
const defaultFleetLimit = 8;

function renderFleet(filter = "all") {
  if (!fleetGrid) return;

  const visibleCars = filter === "all"
    ? cars.slice(0, defaultFleetLimit)
    : cars.filter(car => car.cat === filter);

  fleetGrid.innerHTML = visibleCars.map(car => `
    <div class="car-card" data-cat="${car.cat}">
      <div class="car-media media-${car.cat}">
        <span class="car-tag cat-${car.cat}">${car.label}</span>
        <img src="${car.image}" alt="${car.name}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
        <div style="display:none">${carIcon}</div>
      </div>
      <div class="car-body">
        <h4>${car.name}</h4>
        <p class="car-price">Starting at <b>PHP ${car.price.toLocaleString()}</b> /day</p>
        <a href="${buildBookingLink(car.id)}" class="btn btn-primary btn-block">Book Now</a>
      </div>
    </div>
  `).join("");
}

/* Carries whatever the hero search widget currently has (location,
   pick-up, return) into the booking summary link for a given car,
   so selecting a car doesn't lose the search the user already did. */
function buildBookingLink(carId) {
  const params = new URLSearchParams({ car: carId });

  const loc = document.getElementById("loc");
  const pickup = document.getElementById("pickup");
  const ret = document.getElementById("return");

  if (loc && loc.value) params.set("loc", loc.value);
  if (pickup && pickup.value) params.set("pickup", pickup.value);
  if (ret && ret.value) params.set("return", ret.value);

  return `booking-summary.php?${params.toString()}`;
}

renderFleet();


/* =========================================================
   FLEET FILTER
   ========================================================= */

const filterTabs = document.querySelectorAll(".filter-tab");

filterTabs.forEach(tab => {
  tab.addEventListener("click", () => {
    filterTabs.forEach(button => button.classList.remove("active"));
    tab.classList.add("active");

    renderFleet(tab.dataset.filter);
  });
});


/* =========================================================
   BOOKING WIDGET
   ========================================================= */

const bookingForm = document.getElementById("book");
const bookingNote = document.getElementById("bookingNote");
const locationInput = document.getElementById("loc");
const pickupInput = document.getElementById("pickup");
const returnInput = document.getElementById("return");

/* Prevent selecting dates in the past */
const today = new Date().toISOString().split("T")[0];
if (pickupInput) pickupInput.min = today;
if (returnInput) returnInput.min = today;

/* Return date follows pick-up date */
if (pickupInput && returnInput) {
  pickupInput.addEventListener("change", () => {
    returnInput.min = pickupInput.value;
    if (returnInput.value && returnInput.value < pickupInput.value) {
      returnInput.value = "";
    }
  });
}

if (bookingForm) {
  bookingForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const location = locationInput.value;
    const pickup = pickupInput.value;
    const returnDate = returnInput.value;

    if (!pickup || !returnDate) {
      bookingNote.textContent = "Please select both a pick-up and return date.";
      bookingNote.classList.add("show");
      return;
    }

    if (returnDate < pickup) {
      bookingNote.textContent = "Return date cannot be earlier than the pick-up date.";
      bookingNote.classList.add("show");
      return;
    }

    bookingNote.textContent = `Showing available cars in ${location} from ${pickup} to ${returnDate} ↓`;
    bookingNote.classList.add("show");

    const fleet = document.getElementById("fleet");
    if (fleet) fleet.scrollIntoView({ behavior: "smooth", block: "start" });
  });
}


/* =========================================================
  TESTIMONIALS
   ========================================================= */

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
const testimonialLocations = [
  "Cebu City",
  "Manila",
  "Davao",
  "Cagayan de Oro",
  "Dumaguete",
  "Palawan",
  "Bohol"
];

function renderTestimonials() {
  if (!testimonialCard) return;

  const randomizedLocations = [...testimonialLocations].sort(() => Math.random() - 0.5);

  testimonialCard.innerHTML = testimonials.map((testimonial, index) => `
    <article class="t-slide">
      <div class="t-stars">★★★★★</div>
      <p class="t-quote">"${testimonial.quote}"</p>
      <div class="t-person">
        <div class="t-name">${testimonial.name}</div>
        <div class="t-loc">${randomizedLocations[index] || testimonialLocations[Math.floor(Math.random() * testimonialLocations.length)]}</div>
      </div>
    </article>
  `).join("");
}

renderTestimonials();


/* =========================================================
   NEWSLETTER
   ========================================================= */

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


/* =========================================================
   PREVENT PLACEHOLDER LINKS FROM JUMPING
   ========================================================= */

document.querySelectorAll("a[href='#']").forEach(link => {
  link.addEventListener("click", event => event.preventDefault());
});
