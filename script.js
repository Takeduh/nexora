/* =========================================================
   FLEET DATA
   ========================================================= */

const cars = [
  { name: "Toyota Vios GR-S", cat: "sedan", label: "Sedan", price: "1,799", image: "Images/fleet/vios-gr.jpg" },
  { name: "Honda Civic RS", cat: "sedan", label: "Sedan", price: "2,799", image: "Images/fleet/civic-rs.jpg" },
  { name: "Mazda 3", cat: "sedan", label: "Sedan", price: "2,399", image: "Images/fleet/mazda3.jpg" },
  { name: "BMW 3 Series", cat: "sedan", label: "Premium", price: "6,499", image: "Images/fleet/3series.avif" },
  { name: "MG 5", cat: "sedan", label: "Sedan", price: "1,799", image: "Images/fleet/mg5.jpg" },
  { name: "Toyota Fortuner", cat: "suv", label: "SUV", price: "3,499", image: "Images/fleet/fortuner.jpg" },
  { name: "Mitsubishi Montero Sport", cat: "suv", label: "SUV", price: "3,599", image: "Images/fleet/montero-sport.jpg" },
  { name: "Toyota RAV4", cat: "suv", label: "SUV", price: "3,899", image: "Images/fleet/rav4.avif" },
  { name: "Hyundai Kona Hybrid", cat: "suv", label: "Hybrid", price: "2,799", image: "Images/fleet/kona.avif" },
  { name: "Kia Sonet", cat: "suv", label: "Crossover", price: "1,999", image: "Images/fleet/sonet.jpg" },
  { name: "BYD Sealion 6 DM-i", cat: "suv", label: "Plug-in Hybrid", price: "3,299", image: "Images/fleet/sealion6.jpg" },
  { name: "Toyota Avanza", cat: "mpv", label: "MPV", price: "2,199", image: "Images/fleet/avanza.jpg" },
  { name: "Toyota Innova", cat: "mpv", label: "MPV", price: "2,599", image: "Images/fleet/innova.jpg" },
  { name: "Toyota Veloz", cat: "mpv", label: "MPV", price: "2,499", image: "Images/fleet/veloz.jpg" },
  { name: "Nissan Urvan", cat: "van", label: "Van", price: "3,499", image: "Images/fleet/urvan.jpg" },
  { name: "Toyota Hiace", cat: "van", label: "Van", price: "3,999", image: "Images/fleet/hiace.jpg" },
  { name: "Toyota Tamaraw Utility Van", cat: "van", label: "Utility Van", price: "3,499", image: "Images/fleet/tamaraw.jpg" },
  { name: "Isuzu D-Max 3.0 LS-E", cat: "pickup", label: "Pickup", price: "3,499", image: "Images/fleet/dmax.jpg" },
  { name: "BYD Shark 6 DMO", cat: "pickup", label: "Hybrid Pickup", price: "4,999", image: "Images/fleet/shark6.jpg" },
  { name: "Toyota GR86", cat: "sport", label: "Sport", price: "5,999", image: "Images/fleet/gr86.avif" },
  { name: "Toyota GR Yaris", cat: "sport", label: "Premium", price: "5,999", image: "Images/fleet/gr-yaris.jpg" },
  { name: "Nissan Z", cat: "sport", label: "Premium", price: "8,999", image: "Images/fleet/nissan-z.avif" },
  { name: "Chevrolet Camaro", cat: "sport", label: "Premium", price: "7,999", image: "Images/fleet/camaro.avif" },
  { name: "Toyota GR Supra", cat: "sport", label: "Premium", price: "8,999", image: "Images/fleet/gr-supra.jpg" },
  { name: "Ford Mustang", cat: "sport", label: "Premium", price: "7,999", image: "Images/fleet/mustang.jpg" }
];

const carIcon = `
<svg viewBox="0 0 64 34" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 24 L8 12 Q10 8 16 8 H44 Q50 8 52 12 L58 24" stroke="#0A1730" stroke-width="2" fill="rgba(10,23,48,0.06)"/>
  <rect x="2" y="22" width="60" height="7" rx="3.5" fill="#0A1730"/>
  <rect x="20" y="10" width="20" height="9" rx="1.5" fill="rgba(255,255,255,.7)"/>
  <circle cx="15" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
  <circle cx="49" cy="29" r="5" fill="#0A1730" stroke="white" stroke-width="1.4"/>
</svg>`;


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
        <p class="car-price">Starting at <b>PHP ${car.price}</b> /day</p>
        <a href="#book" class="btn btn-primary btn-block">Book Now</a>
      </div>
    </div>
  `).join("");
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
  { quote: "Great value for a weekend road trip. No hidden charges at drop-off, exactly as quoted.", name: "Marco P.", loc: "Davao" }
];

const testimonialCard = document.getElementById("tCard");

function getInitials(name) {
  return name.split(" ").map(word => word[0]).join("").slice(0, 2);
}

function renderTestimonials() {
  if (!testimonialCard) return;

  testimonialCard.innerHTML = testimonials.map(testimonial => `
    <article class="t-slide">
      <div class="t-stars">★★★★★</div>
      <p class="t-quote">"${testimonial.quote}"</p>
      <div class="t-person">
        <div class="t-avatar">${getInitials(testimonial.name)}</div>
        <div class="t-name">${testimonial.name}</div>
        <div class="t-loc">${testimonial.loc}</div>
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
