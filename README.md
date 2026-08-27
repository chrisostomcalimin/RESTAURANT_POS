# Restaurant POS V2

## Login
Username: `admin`
Password: `Mikumi@2026`

## XAMPP
1. Extract `restaurant_pos` to `C:\xampp\htdocs\restaurant_pos`.
2. Start Apache and MySQL.
3. Open phpMyAdmin and import `database.sql` into the `restaurant_pos` database.
4. Open `http://localhost/restaurant_pos/`.

## V2 modules
- Login and role-based access (Admin/Cashier)
- Product management
- Table management
- Stock / inventory with stock movements
- Expenses
- Sales reports and daily report
- User management
- Bill + payment + receipt printing
- Kitchen Order Ticket (KOT): open `/restaurant_pos/kitchen/kot.php?id=BILL_ID`
- Automatic stock deduction when a bill is created

If you already have an old database, the SQL contains migration statements. Back up the database first.

## Admin Change Password
Admin can change their own password from **Dashboard → Change Password**. The current password is verified and the new password is stored using PHP `password_hash()`.
