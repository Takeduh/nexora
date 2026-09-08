NEXORA is a web-based car rental management system built by me!

The system allows fellow filipino customers to browse available vehicles, choose rental dates, create bookings, manage payment methods, and view their booking history.
It also includes an admin dashboard for managing vehicles, customers, bookings, payments, inventory, and contact messages.

Features:

Customer
- Register and log in
- Browse available vehicles
- Search, filter, and sort cars
- Choose Automatic or Manual transmission
- Check vehicle availability by rental dates
- Book a vehicle
- View booking details and history
- Cancel eligible bookings
- Manage profile information
- Save payment methods
- View booking and payment status
- Send contact messages

Administrator
- View dashboard statistics
- Manage vehicles and variants
- Manage inventory quantity and status
- View booked and available units
- Manage customers
- Manage bookings
- Update booking status
- Manage payments
- Manage contact messages

Booking Workflow

Bookings follow this status flow:

pending → confirmed → active → completed

A booking may also be cancelled while it is still pending or confirmed.
The admin manually changes the booking status depending on the actual rental process.

Availability System
NEXORA checks the selected vehicle variant, quantity, and booking dates before allowing a reservation.

Bookings with these statuses reserve inventory:
- pending
- confirmed
- active

Cancelled and completed bookings no longer count against availability.

Payment Methods
Supported payment methods:
- GCash
- Card
- Bank Transfer
- Cash

The system does not store full card numbers, CVV, PINs, or banking passwords.
Only basic information such as the payment method label and last four digits is stored for saved payment methods.

Security
NEXORA includes:
- PDO prepared statements
- Password hashing using `password_hash()`
- Password checking using `password_verify()`
- Session-based authentication
- User and admin role checking
- Server-side form validation
- Reusable validation functions
- CSRF protection on important actions
- Protected booking and admin pages

Technologies Used
- PHP
- MySQL / MariaDB
- PDO
- HTML5
- CSS3
- JavaScript
- XAMPP
- phpMyAdmin
- Git
- GitHub
